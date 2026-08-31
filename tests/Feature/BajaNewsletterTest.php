<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Http\Controllers\SuscripcionController;
use App\Models\Suscriptor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Darse de baja del boletín.
 *
 * Esto faltaba entero y era un incumplimiento en toda regla: la columna
 * `baja_en` existía, el panel la leía, la insignia «De baja» estaba pintada…
 * y NADIE la escribía nunca. O sea, no había forma de salirse de una lista a
 * la que cualquiera entra desde el pie de cualquier página. La política
 * publicada promete lo contrario.
 */
class BajaNewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function suscrito(string $correo = 'julian@taller.co'): Suscriptor
    {
        return Suscriptor::create(['correo' => $correo, 'origen' => 'pie']);
    }

    public function test_el_enlace_firmado_saca_de_la_lista(): void
    {
        $suscriptor = $this->suscrito();

        $this->get(SuscripcionController::enlaceDeBaja($suscriptor->correo))
            ->assertOk()
            ->assertSee('te sacamos de la lista', false);

        $this->assertNotNull($suscriptor->fresh()->baja_en);
    }

    /**
     * El cierre que hace falta: sin firma, cualquiera daría de baja el correo
     * de otro escribiéndolo en la URL.
     */
    public function test_sin_firma_no_se_da_de_baja_a_nadie(): void
    {
        $suscriptor = $this->suscrito();

        $this->get('/baja-newsletter/'.$suscriptor->correo)->assertForbidden();

        $this->assertNull($suscriptor->fresh()->baja_en);
    }

    public function test_una_firma_de_otro_correo_no_sirve(): void
    {
        $victima = $this->suscrito('victima@taller.co');
        $mio = $this->suscrito('mio@taller.co');

        // Firmo lo mío y cambio el correo en la URL: la firma deja de cuadrar.
        $manipulado = str_replace(
            $mio->correo,
            $victima->correo,
            SuscripcionController::enlaceDeBaja($mio->correo)
        );

        $this->get($manipulado)->assertForbidden();

        $this->assertNull($victima->fresh()->baja_en);
    }

    /**
     * La misma pantalla exista o no el correo: decir «ése no está en la lista»
     * convertiría el enlace en una forma de averiguar quién se suscribió.
     */
    public function test_un_correo_desconocido_ve_lo_mismo(): void
    {
        $conocido = $this->get(SuscripcionController::enlaceDeBaja($this->suscrito()->correo));
        $desconocido = $this->get(SuscripcionController::enlaceDeBaja('nadie@ejemplo.co'));

        $this->assertSame($conocido->status(), $desconocido->status());

        // No se compara el HTML entero: el pie lleva el formulario del
        // boletín con su token CSRF, que cambia en cada petición. Lo que
        // importa es que el mensaje sea el mismo y que ninguna de las dos
        // páginas diga si ese correo estaba o no en la lista.
        foreach ([$conocido, $desconocido] as $respuesta) {
            $respuesta->assertSee('te sacamos de la lista', false);
            $respuesta->assertDontSee('nadie@ejemplo.co');
            $respuesta->assertDontSee('julian@taller.co');
        }
    }

    /** Darse de baja dos veces no reescribe la fecha original. */
    public function test_la_fecha_de_baja_no_se_pisa(): void
    {
        $suscriptor = $this->suscrito();
        $enlace = SuscripcionController::enlaceDeBaja($suscriptor->correo);

        $this->get($enlace)->assertOk();
        $primera = $suscriptor->fresh()->baja_en;

        $this->travel(2)->days();
        $this->get($enlace)->assertOk();

        $this->assertEquals($primera, $suscriptor->fresh()->baja_en);
    }

    /** Y el formulario del pie no revive a quien ya se dio de baja. */
    public function test_volver_a_escribir_el_correo_no_revive_la_baja(): void
    {
        $suscriptor = $this->suscrito();
        $this->get(SuscripcionController::enlaceDeBaja($suscriptor->correo))->assertOk();

        $this->post(route('suscripcion'), ['correo' => $suscriptor->correo]);

        $this->assertNotNull($suscriptor->fresh()->baja_en, 'La baja la decidió esa persona.');
        $this->assertSame(1, Suscriptor::where('correo', $suscriptor->correo)->count());
    }

    /**
     * Habeas Data: quien pide que borremos sus datos no puede seguir en la
     * lista de correos que el panel exporta en CSV.
     */
    public function test_cerrar_la_cuenta_saca_tambien_del_boletin(): void
    {
        $usuario = User::forceCreate([
            'name' => 'Julián Mecánico', 'email' => 'julian@taller.co',
            'telefono' => '3134223861', 'password' => 'secreto123',
            'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $suscriptor = $this->suscrito($usuario->email);

        $this->actingAs($usuario)->post(route('cuenta.baja'), [
            'confirmo' => 1,
            'password' => 'secreto123',
        ])->assertRedirect(route('inicio'));

        $this->assertNotNull($suscriptor->fresh()->baja_en);
    }

    /**
     * Y quien entró con Google puede cerrar su cuenta: no tiene contraseña que
     * confirmar, y pedírsela la dejaba encerrada sin poder ejercer su derecho.
     */
    public function test_una_cuenta_sin_contrasena_puede_cerrarse(): void
    {
        $usuario = User::forceCreate([
            'name' => 'Entró Con Google', 'email' => 'google@taller.co',
            'telefono' => '300', 'password' => null,
            'proveedor' => 'google', 'proveedor_id' => 'g-1',
            'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $this->actingAs($usuario)->post(route('cuenta.baja'), ['confirmo' => 1])
            ->assertRedirect(route('inicio'));

        $this->assertFalse($usuario->fresh()->activo);
    }

    /** A quien sí tiene contraseña se le sigue pidiendo. */
    public function test_con_contrasena_se_sigue_exigiendo(): void
    {
        $usuario = User::forceCreate([
            'name' => 'Julián', 'email' => 'julian2@taller.co',
            'telefono' => '300', 'password' => 'secreto123',
            'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $this->actingAs($usuario)->post(route('cuenta.baja'), ['confirmo' => 1])
            ->assertSessionHasErrors('password');

        $this->assertTrue($usuario->fresh()->activo);
    }
}
