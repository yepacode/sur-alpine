<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use App\Notifications\ClaveOlvidada;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * «Olvidé mi contraseña».
 *
 * Antes esto no existía y quien olvidaba la clave quedaba afuera para
 * siempre. Lo que se cubre aquí no es que el correo se vea bonito, sino los
 * tres cierres: que no sirva para averiguar quién tiene cuenta, que no
 * devuelva la entrada a una cuenta desactivada, y que el enlace se gaste.
 */
class ClaveOlvidadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    }

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

    public function test_el_formulario_abre_y_esta_enlazado_desde_el_acceso(): void
    {
        $this->get(route('clave.pedir'))->assertOk()->assertSee('Olvidaste tu contraseña', false);

        $this->get(route('acceso'))->assertOk()->assertSee(route('clave.pedir'));
    }

    public function test_llega_el_enlace_al_correo_de_una_cuenta_real(): void
    {
        Notification::fake();

        $usuario = $this->cliente();

        $this->post(route('clave.enviar'), ['email' => 'julian@taller.co'])
            ->assertRedirect()
            ->assertSessionHas('mensaje');

        // El nuestro, en español, y no el que Laravel manda por defecto.
        Notification::assertSentTo($usuario, ClaveOlvidada::class);
        Notification::assertNotSentTo($usuario, ResetPassword::class);
    }

    /**
     * El cierre que importa: si un correo desconocido respondiera distinto de
     * uno registrado, este formulario sería un detector de clientes. Se prueba
     * comparando las dos respuestas, no leyendo el texto.
     */
    public function test_un_correo_desconocido_responde_igual_que_uno_real(): void
    {
        Notification::fake();

        $this->cliente();

        $conocida = $this->post(route('clave.enviar'), ['email' => 'julian@taller.co']);
        $desconocida = $this->post(route('clave.enviar'), ['email' => 'nadie@ejemplo.co']);

        $this->assertSame($conocida->status(), $desconocida->status());
        $this->assertSame(
            session()->get('mensaje'),
            $desconocida->getSession()->get('mensaje'),
            'El aviso tiene que ser idéntico, exista la cuenta o no.'
        );
        $desconocida->assertSessionHasNoErrors();
    }

    /** A quien el administrador desactivó no se le devuelve la entrada. */
    public function test_una_cuenta_desactivada_no_recibe_enlace(): void
    {
        Notification::fake();

        $inactivo = $this->cliente(['activo' => false]);

        $this->post(route('clave.enviar'), ['email' => $inactivo->email])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_con_el_enlace_se_cambia_la_contrasena_y_se_entra(): void
    {
        $usuario = $this->cliente();
        $token = Password::createToken($usuario);

        $this->get(route('clave.formulario', ['token' => $token, 'email' => $usuario->email]))
            ->assertOk()
            ->assertSee($usuario->email);

        $this->post(route('clave.restablecer'), [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'clavenueva123',
            'password_confirmation' => 'clavenueva123',
        ])->assertRedirect(route('acceso'));

        $this->assertTrue(Hash::check('clavenueva123', $usuario->fresh()->password));

        // No se inicia sesión sola: escribirla una vez es lo que la fija.
        $this->assertGuest();

        $this->post(route('entrar'), ['email' => $usuario->email, 'password' => 'clavenueva123'])
            ->assertRedirect(route('inicio'));
    }

    /** Un enlace de un solo uso: el segundo intento no entra. */
    public function test_el_enlace_no_sirve_dos_veces(): void
    {
        $usuario = $this->cliente();
        $token = Password::createToken($usuario);

        $envio = fn (string $clave) => $this->post(route('clave.restablecer'), [
            'token' => $token,
            'email' => $usuario->email,
            'password' => $clave,
            'password_confirmation' => $clave,
        ]);

        $envio('clavenueva123')->assertRedirect(route('acceso'));
        $envio('otraclave456')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('clavenueva123', $usuario->fresh()->password));
    }

    public function test_un_token_inventado_no_cambia_nada(): void
    {
        $usuario = $this->cliente();

        $this->post(route('clave.restablecer'), [
            'token' => 'me-lo-invente',
            'email' => $usuario->email,
            'password' => 'clavenueva123',
            'password_confirmation' => 'clavenueva123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('secreto123', $usuario->fresh()->password));
    }

    public function test_las_dos_contrasenas_tienen_que_coincidir(): void
    {
        $usuario = $this->cliente();

        $this->post(route('clave.restablecer'), [
            'token' => Password::createToken($usuario),
            'email' => $usuario->email,
            'password' => 'clavenueva123',
            'password_confirmation' => 'otracosa999',
        ])->assertSessionHasErrors('password');
    }

    public function test_una_contrasena_corta_no_pasa(): void
    {
        $usuario = $this->cliente();

        $this->post(route('clave.restablecer'), [
            'token' => Password::createToken($usuario),
            'email' => $usuario->email,
            'password' => '1234',
            'password_confirmation' => '1234',
        ])->assertSessionHasErrors('password');
    }

    /**
     * Quien ya entró no necesita esto, y dejarlo abierto sería una forma de
     * cambiar la contraseña sin pedir la actual.
     */
    public function test_con_sesion_abierta_no_se_llega_al_formulario(): void
    {
        $this->actingAs($this->cliente())->get(route('clave.pedir'))->assertRedirect();
    }

    /** Cinco por hora y por correo: un enlace de acceso no se pide diez veces. */
    public function test_el_sexto_intento_seguido_se_frena(): void
    {
        Notification::fake();

        $this->cliente();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('clave.enviar'), ['email' => 'julian@taller.co'])
                ->assertSessionHasNoErrors();
        }

        $this->post(route('clave.enviar'), ['email' => 'julian@taller.co'])
            ->assertSessionHasErrors('email');
    }
}
