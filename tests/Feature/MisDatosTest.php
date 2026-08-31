<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * «Mis datos».
 *
 * El teléfono es el dato que más se mueve y el que más importa —es por donde
 * el mostrador devuelve la llamada— y hasta ahora quedaba congelado en lo que
 * la persona escribió al registrarse.
 *
 * Lo que se cubre: que se pueda corregir, que cambiar el CORREO pida la
 * contraseña (porque cambiar el correo es cambiar por dónde se recupera la
 * cuenta), y que quien entró con Google pueda ponerse una sin tener una vieja
 * que confirmar.
 */
class MisDatosTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(array $atributos = []): User
    {
        return User::forceCreate($atributos + [
            'name' => 'Julián Mecánico',
            'email' => 'julian@taller.co',
            'telefono' => '313 422 3861',
            'password' => 'secreto123',
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);
    }

    public function test_la_pantalla_llega_con_sus_datos(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->get(route('cuenta.datos'))
            ->assertOk()
            ->assertSee('value="Julián Mecánico"', false)
            ->assertSee('value="313 422 3861"', false)
            ->assertSee('value="julian@taller.co"', false);
    }

    public function test_sin_sesion_no_se_entra(): void
    {
        $this->get(route('cuenta.datos'))->assertRedirect(route('acceso'));
    }

    /** Lo que más se corrige, y sin pedir contraseña: no hay daño que evitar. */
    public function test_se_corrige_el_telefono_sin_pedir_la_contrasena(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => 'Julián Mecánico',
            'telefono' => '300 999 8877',
            'email' => $usuario->email,
        ])->assertRedirect(route('cuenta.datos'));

        $this->assertSame('300 999 8877', $usuario->fresh()->telefono);
    }

    /**
     * El cierre: quien alcance una sesión abierta no puede quedarse con la
     * cuenta cambiando el correo y pidiendo después «olvidé mi contraseña».
     */
    public function test_cambiar_el_correo_exige_la_contrasena_actual(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => $usuario->name,
            'telefono' => $usuario->telefono,
            'email' => 'ladron@ejemplo.co',
        ])->assertSessionHasErrors('password_actual');

        $this->assertSame('julian@taller.co', $usuario->fresh()->email);

        // Con la contraseña equivocada tampoco.
        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => $usuario->name,
            'telefono' => $usuario->telefono,
            'email' => 'ladron@ejemplo.co',
            'password_actual' => 'no-es',
        ])->assertSessionHasErrors('password_actual');

        $this->assertSame('julian@taller.co', $usuario->fresh()->email);
    }

    public function test_con_la_contrasena_correcta_el_correo_si_cambia(): void
    {
        $usuario = $this->cliente(['email_verified_at' => now()]);

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => $usuario->name,
            'telefono' => $usuario->telefono,
            'email' => 'julian.nuevo@taller.co',
            'password_actual' => 'secreto123',
        ])->assertRedirect(route('cuenta.datos'));

        $usuario->refresh();

        $this->assertSame('julian.nuevo@taller.co', $usuario->email);

        // Todavía no verificamos correos, pero el día que se active, una
        // dirección nueva no puede heredar el visto bueno de la anterior.
        $this->assertNull($usuario->email_verified_at);
    }

    public function test_no_se_puede_tomar_el_correo_de_otra_cuenta(): void
    {
        $this->cliente(['email' => 'ocupado@taller.co']);
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => $usuario->name,
            'telefono' => $usuario->telefono,
            'email' => 'ocupado@taller.co',
            'password_actual' => 'secreto123',
        ])->assertSessionHasErrors('email');
    }

    /** Guardar sin tocar el correo no debe reclamar que falta la contraseña. */
    public function test_dejar_el_mismo_correo_no_pide_nada(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => 'Julián R. Mecánico',
            'telefono' => $usuario->telefono,
            'email' => 'JULIAN@TALLER.CO',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Julián R. Mecánico', $usuario->fresh()->name);
    }

    // ── La contraseña ───────────────────────────────────────────────────────

    public function test_se_cambia_la_contrasena(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.clave'), [
            'password_actual' => 'secreto123',
            'password' => 'clavenueva123',
            'password_confirmation' => 'clavenueva123',
        ])->assertRedirect(route('cuenta.datos'));

        $this->assertTrue(Hash::check('clavenueva123', $usuario->fresh()->password));
    }

    public function test_sin_la_actual_no_se_cambia(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.clave'), [
            'password_actual' => 'equivocada',
            'password' => 'clavenueva123',
            'password_confirmation' => 'clavenueva123',
        ])->assertSessionHasErrors('password_actual');

        $this->assertTrue(Hash::check('secreto123', $usuario->fresh()->password));
    }

    public function test_las_dos_nuevas_tienen_que_coincidir(): void
    {
        $usuario = $this->cliente();

        $this->actingAs($usuario)->post(route('cuenta.clave'), [
            'password_actual' => 'secreto123',
            'password' => 'clavenueva123',
            'password_confirmation' => 'otracosa456',
        ])->assertSessionHasErrors('password');
    }

    /**
     * Quien entró con Facebook o Google no tiene contraseña anterior. Para esa
     * cuenta esto no es cambiarla: es ponerle la primera, y no se le puede
     * pedir que confirme una que nunca existió.
     */
    public function test_quien_entro_con_google_puede_ponerse_la_primera(): void
    {
        $usuario = $this->cliente([
            'password' => null,
            'proveedor' => 'google',
            'proveedor_id' => 'g-1',
        ]);

        $this->actingAs($usuario)->post(route('cuenta.clave'), [
            'password' => 'clavenueva123',
            'password_confirmation' => 'clavenueva123',
        ])->assertRedirect(route('cuenta.datos'));

        $this->assertTrue(Hash::check('clavenueva123', $usuario->fresh()->password));
    }

    /** Y tampoco se le pide para cambiar su correo: no tiene ninguna. */
    public function test_quien_entro_con_google_cambia_su_correo_sin_contrasena(): void
    {
        $usuario = $this->cliente([
            'password' => null,
            'proveedor' => 'google',
            'proveedor_id' => 'g-1',
        ]);

        $this->actingAs($usuario)->post(route('cuenta.datos.guardar'), [
            'name' => $usuario->name,
            'telefono' => $usuario->telefono,
            'email' => 'otro@taller.co',
        ])->assertSessionHasNoErrors();

        $this->assertSame('otro@taller.co', $usuario->fresh()->email);
    }

    /** Desde «Mi cuenta» se tiene que poder llegar. */
    public function test_mi_cuenta_enlaza_mis_datos(): void
    {
        $this->actingAs($this->cliente())->get(route('cuenta'))
            ->assertOk()
            ->assertSee(route('cuenta.datos'));
    }
}
