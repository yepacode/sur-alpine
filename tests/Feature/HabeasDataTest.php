<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Cotizacion;
use App\Models\User;
use App\Notifications\CorreoVerificar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Los dos derechos del titular, y la confirmación del correo.
 *
 * La política que ya está publicada promete que el titular puede consultar lo
 * que tenemos suyo y pedir que lo borremos. Cerrar la cuenta ya estaba;
 * llevarse los datos faltaba.
 */
class HabeasDataTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(array $atributos = []): User
    {
        return User::forceCreate($atributos + [
            'name' => 'Julián Mecánico',
            'email' => 'julian@taller.co',
            'telefono' => '3134223861',
            'password' => 'secreto123',
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);
    }

    // ── Llevarse los datos ──────────────────────────────────────────────────

    public function test_se_descarga_todo_lo_suyo(): void
    {
        $usuario = $this->cliente(['acepto_en' => now(), 'politica_version' => '1']);

        $usuario->mantenimientos()->create([
            'placa' => 'ABC123', 'kilometraje' => 82000, 'tipo' => 'Cambio de aceite',
            'fecha' => today()->subMonths(2), 'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $cotizacion = Cotizacion::create([
            'consecutivo' => Cotizacion::siguienteConsecutivo(), 'user_id' => $usuario->id,
            'nombre' => 'Julián', 'apellidos' => 'Mecánico',
            'telefono' => '3134223861', 'email' => $usuario->email,
        ]);
        $cotizacion->items()->create([
            'producto_nombre' => 'Pastillas Freno Delanteras',
            'vehiculo_nombre' => 'CHEVROLET AVEO 1600 (2006-2013)',
            'cantidad' => 2,
        ]);

        $respuesta = $this->actingAs($usuario)->get(route('cuenta.descargar'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

        $datos = json_decode($respuesta->streamedContent(), true);

        $this->assertSame('julian@taller.co', $datos['cuenta']['correo']);
        $this->assertSame('Cambio de aceite', $datos['mantenimientos'][0]['tipo']);
        $this->assertSame($cotizacion->consecutivo, $datos['cotizaciones'][0]['consecutivo']);
        $this->assertSame('Pastillas Freno Delanteras', $datos['cotizaciones'][0]['repuestos'][0]['repuesto']);
        $this->assertNotNull($datos['cuenta']['autorizacion_datos']['aceptada_en']);
    }

    /** Lo de otro no viaja en el archivo de nadie. */
    public function test_la_descarga_solo_trae_lo_del_que_la_pide(): void
    {
        $otro = $this->cliente(['email' => 'otro@taller.co']);
        Cotizacion::create([
            'consecutivo' => Cotizacion::siguienteConsecutivo(), 'user_id' => $otro->id,
            'nombre' => 'Otro', 'telefono' => '300', 'email' => $otro->email,
        ]);

        $usuario = $this->cliente();

        $datos = json_decode(
            $this->actingAs($usuario)->get(route('cuenta.descargar'))->streamedContent(),
            true
        );

        $this->assertSame([], $datos['cotizaciones']);
        $this->assertSame('julian@taller.co', $datos['cuenta']['correo']);
    }

    public function test_sin_sesion_no_se_descarga_nada(): void
    {
        $this->get(route('cuenta.descargar'))->assertRedirect(route('acceso'));
    }

    /** El id que el proveedor nos dio es de ellos, no del titular. */
    public function test_el_archivo_no_lleva_el_id_del_proveedor(): void
    {
        $usuario = $this->cliente(['proveedor' => 'google', 'proveedor_id' => 'g-secreto-99']);

        $crudo = $this->actingAs($usuario)->get(route('cuenta.descargar'))->streamedContent();

        $this->assertStringNotContainsString('g-secreto-99', $crudo);
        $this->assertStringContainsString('google', $crudo);
    }

    /**
     * La descarga trae TODO lo que la política promete.
     *
     * Faltaban dos tablas: los mensajes de «Contáctenos» y el boletín. No
     * cuelgan de `user_id` —el formulario de contacto no pide sesión— así que
     * es fácil olvidarlos justo por eso.
     */
    public function test_la_descarga_incluye_mensajes_y_boletin(): void
    {
        $usuario = $this->cliente();

        \App\Models\Mensaje::create([
            'nombre' => 'Julián', 'email' => $usuario->email,
            'mensaje' => 'Necesito pastillas para un Aveo.',
        ]);
        \App\Models\Suscriptor::create(['correo' => $usuario->email, 'origen' => 'pie']);

        $datos = json_decode(
            $this->actingAs($usuario)->get(route('cuenta.descargar'))->streamedContent(),
            true
        );

        $this->assertSame('Necesito pastillas para un Aveo.', $datos['mensajes_de_contacto'][0]['mensaje']);
        $this->assertSame($usuario->email, $datos['boletin']['correo']);
    }

    /**
     * Y cerrar la cuenta los anonimiza: se quedaban enteros en el panel
     * —nombre, correo y texto— de alguien que pidió que lo borráramos.
     */
    public function test_cerrar_la_cuenta_anonimiza_los_mensajes(): void
    {
        $usuario = $this->cliente();

        $mensaje = \App\Models\Mensaje::create([
            'nombre' => 'Julián Mecánico', 'email' => $usuario->email,
            'mensaje' => 'Necesito pastillas.',
        ]);

        $this->actingAs($usuario)->post(route('cuenta.baja'), [
            'confirmo' => 1, 'password' => 'secreto123',
        ])->assertRedirect(route('inicio'));

        $mensaje->refresh();

        $this->assertSame('Dado de baja', $mensaje->nombre);
        $this->assertStringEndsWith('@suralpine.invalid', $mensaje->email);

        // El texto se conserva: es lo que el mostrador respondió, y borrarlo
        // dejaría al equipo sin el hilo de una conversación que existió.
        $this->assertSame('Necesito pastillas.', $mensaje->mensaje);

        // Y no queda ni el hash de la contraseña.
        $this->assertNull($usuario->fresh()->password);
    }


    // ── Confirmar el correo ─────────────────────────────────────────────────

    public function test_se_pide_y_llega_el_enlace(): void
    {
        Notification::fake();

        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('verificacion.reenviar'))
            ->assertRedirect()
            ->assertSessionHas('mensaje');

        Notification::assertSentTo($usuario, CorreoVerificar::class);
    }

    public function test_el_enlace_firmado_confirma_el_correo(): void
    {
        $usuario = $this->cliente();

        $this->assertFalse($usuario->hasVerifiedEmail());

        $this->actingAs($usuario)->get($this->enlaceDe($usuario))
            ->assertRedirect(route('cuenta.datos'));

        $this->assertTrue($usuario->fresh()->hasVerifiedEmail());
    }

    /** Sin firma válida no se confirma nada. */
    public function test_un_enlace_manipulado_no_confirma(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)
            ->get(route('verification.verify', ['id' => $usuario->id, 'hash' => sha1('otra-cosa')]))
            ->assertForbidden();

        $this->assertFalse($usuario->fresh()->hasVerifiedEmail());
    }

    /**
     * El cierre que importa: el enlace de uno no puede confirmar el correo de
     * otro, aunque la firma sea buena.
     */
    public function test_el_enlace_de_otro_no_sirve(): void
    {
        $ajeno = $this->cliente(['email' => 'otro@taller.co']);
        $usuario = $this->cliente();

        $this->actingAs($usuario)->get($this->enlaceDe($ajeno))->assertForbidden();

        $this->assertFalse($ajeno->fresh()->hasVerifiedEmail());
    }

    /** Cambiar el correo lo deja sin confirmar y manda el enlace al nuevo. */
    public function test_cambiar_el_correo_pide_confirmar_el_nuevo(): void
    {
        Notification::fake();

        $usuario = $this->cliente(['email_verified_at' => now()]);

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => $usuario->name,
            'telefono' => $usuario->telefono,
            'email' => 'julian.nuevo@taller.co',
            'password_actual' => 'secreto123',
        ])->assertRedirect(route('cuenta.datos'));

        $usuario->refresh();

        $this->assertNull($usuario->email_verified_at);
        Notification::assertSentTo($usuario, CorreoVerificar::class);
    }

    /**
     * Se ofrece, no se exige: quien no ha confirmado sigue pudiendo usar su
     * cuenta y pedir cotizaciones. Exigirlo dejaría fuera de un golpe a todas
     * las cuentas que ya existen.
     */
    public function test_sin_confirmar_se_sigue_usando_la_cuenta(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->get(route('cuenta'))->assertOk();
        $this->actingAs($usuario)->get(route('cuenta.datos'))
            ->assertOk()
            ->assertSee('Tu correo no está confirmado');
    }

    /** Registrarse manda el enlace: es cuando un dedazo todavía se corrige. */
    public function test_al_registrarse_llega_el_enlace_de_confirmacion(): void
    {
        Notification::fake();

        $this->post(route('registro.crear'), [
            'name' => 'Julián Mecánico',
            'telefono' => '3134223861',
            'email' => 'nuevo@taller.co',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'acepta' => 1,
        ])->assertRedirect(route('acceso'));

        $usuario = User::where('email', 'nuevo@taller.co')->firstOrFail();

        Notification::assertSentTo($usuario, CorreoVerificar::class);
    }

    private function enlaceDe(User $usuario): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $usuario->id,
            'hash' => sha1($usuario->email),
        ]);
    }
}
