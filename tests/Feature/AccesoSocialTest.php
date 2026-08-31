<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Http\Controllers\AccesoSocialController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioSocial;
use Mockery;
use Tests\TestCase;

/**
 * Entrar con Facebook o con Google.
 *
 * Lo importante aquí no es que funcione —eso lo resuelve Socialite— sino que no
 * se convierta en una puerta trasera: que una cuenta desactivada siga sin poder
 * entrar, que nadie gane permisos por venir de Google, y que el botón avise
 * mientras el proveedor no tenga sus llaves puestas.
 */
class AccesoSocialTest extends TestCase
{
    use RefreshDatabase;

    private function configurar(string ...$proveedores): void
    {
        foreach ($proveedores as $p) {
            config([
                "services.{$p}.client_id" => 'id-de-prueba',
                "services.{$p}.client_secret" => 'secreto-de-prueba',
                "services.{$p}.redirect" => "/acceso/{$p}/volver",
            ]);
        }
    }

    /**
     * @param  bool  $verificado  si el proveedor jura que ese correo está
     *                            comprobado. Google lo informa; Facebook no,
     *                            y por eso allí enlazar por correo no procede.
     */
    private function fingirPerfil(
        string $proveedor,
        string $correo,
        string $id = '1234',
        ?string $nombre = 'Ana Gómez',
        bool $verificado = true,
    ): void {
        $perfil = Mockery::mock(UsuarioSocial::class);
        $perfil->shouldReceive('getEmail')->andReturn($correo);
        $perfil->shouldReceive('getId')->andReturn($id);
        $perfil->shouldReceive('getName')->andReturn($nombre);
        $perfil->user = ['email_verified' => $verificado];

        $driver = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $driver->shouldReceive('user')->andReturn($perfil);

        Socialite::shouldReceive('driver')->with($proveedor)->andReturn($driver);
    }

    public function test_sin_llaves_el_boton_se_ve_pero_avisa(): void
    {
        config(['services.facebook.client_id' => null, 'services.google.client_id' => null]);

        $this->assertSame([], AccesoSocialController::disponibles());

        // El botón se pinta igual: su página los tiene y esconderlos deja la
        // pantalla a medias. Lo que no puede pasar es que no diga nada.
        $this->get(route('acceso'))
            ->assertOk()
            ->assertSee('Ingresar con Facebook')
            ->assertSee('Ingresar con Google');

        $this->get('/acceso/facebook')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_aviso_dice_de_que_proveedor_se_trata(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->followingRedirects()
            ->get('/acceso/google')
            ->assertOk()
            ->assertSee('Estamos activando el ingreso con Google');
    }

    public function test_con_llaves_aparecen_los_dos_botones(): void
    {
        $this->configurar('facebook', 'google');

        $this->get(route('acceso'))
            ->assertOk()
            ->assertSee('Ingresar con Facebook')
            ->assertSee('Ingresar con Google')
            ->assertSee(route('acceso.social', 'facebook'));
    }

    public function test_un_proveedor_inventado_da_404(): void
    {
        // Un nombre que no existe no es «pendiente de configurar»: es una URL
        // inventada, y eso sí es 404.
        $this->configurar('facebook', 'google');

        $this->get('/acceso/tiktok')->assertNotFound();
    }

    public function test_una_cuenta_que_ya_existe_se_enlaza_y_entra(): void
    {
        $this->configurar('google');
        $this->fingirPerfil('google', 'Cliente@Ejemplo.CO', 'g-99');

        $usuario = User::forceCreate([
            'name' => 'Cliente', 'email' => 'cliente@ejemplo.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $this->get('/acceso/google/volver')->assertRedirect(route('cuenta'));

        $this->assertAuthenticatedAs($usuario);
        $this->assertSame('google', $usuario->fresh()->proveedor);
        $this->assertSame('g-99', $usuario->fresh()->proveedor_id);
    }

    /**
     * El enlace por CORREO no alcanza a una cuenta del equipo.
     *
     * Buscar por `proveedor_id` es prueba dura: ese identificador sólo lo
     * conoce el proveedor. Buscar por correo es una conjetura, y Facebook
     * permite cuentas con el correo sin verificar. Quien registrara allá el
     * correo de un administrador entraba al panel completo —y con cookie de
     * «recordarme»—. Ahora se le manda a entrar con su contraseña.
     */
    public function test_el_acceso_social_no_alcanza_a_una_cuenta_del_equipo(): void
    {
        $this->configurar('google');
        $this->fingirPerfil('google', 'admin@ejemplo.co', 'g-1');

        $admin = User::forceCreate([
            'name' => 'Administradora', 'email' => 'admin@ejemplo.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);

        $this->get('/acceso/google/volver')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        // Y no queda enlazada: el proveedor no puede reclamar esa cuenta.
        $this->assertNull($admin->fresh()->proveedor);
        $this->assertSame(Rol::Admin, $admin->fresh()->rol);
    }

    /**
     * Sin correo verificado, el proveedor NO puede reclamar una cuenta que ya
     * existe.
     *
     * Era la puerta grande: el correo no es prueba de nada —Facebook permite
     * abrir cuenta con uno sin comprobar— y bastaba registrar allá la
     * dirección de un cliente para entrar a su «Mi cuenta»: sus placas, su
     * historial, sus cotizaciones con teléfono y dirección, y la descarga de
     * todos sus datos en un JSON. De paso le reescribía el identificador del
     * proveedor, así que la próxima vez ya entraba directo.
     */
    public function test_sin_correo_verificado_no_se_enlaza_una_cuenta_ajena(): void
    {
        $this->configurar('facebook');
        $this->fingirPerfil('facebook', 'victima@ejemplo.co', 'f-atacante', verificado: false);

        $victima = User::forceCreate([
            'name' => 'Víctima', 'email' => 'victima@ejemplo.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Cliente, 'activo' => true,
            'proveedor' => 'google', 'proveedor_id' => 'g-legitimo',
        ]);

        $this->get('/acceso/facebook/volver')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        // Y su identificador sigue siendo el suyo.
        $this->assertSame('g-legitimo', $victima->fresh()->proveedor_id);
    }

    /**
     * La cuenta nueva nace CON el identificador del proveedor.
     *
     * `proveedor` y `proveedor_id` están fuera de `$fillable` —no pueden
     * llegar desde una petición— y `User::create()` los descartaba en
     * silencio: la cuenta quedaba sin el único dato duro que permite
     * reconocerla después sin fiarse del correo.
     */
    public function test_la_cuenta_nueva_guarda_el_identificador_del_proveedor(): void
    {
        $this->configurar('google');
        $this->fingirPerfil('google', 'nueva@ejemplo.co', 'g-777');

        $this->get('/acceso/google/volver')->assertRedirect(route('cuenta'));

        $usuario = User::firstWhere('email', 'nueva@ejemplo.co');

        $this->assertNotNull($usuario);
        $this->assertSame('google', $usuario->proveedor);
        $this->assertSame('g-777', $usuario->proveedor_id);
        $this->assertSame(Rol::Cliente, $usuario->rol);
        $this->assertTrue($usuario->activo);
    }


    /** A un cliente sí se le enlaza por correo, y sigue siendo cliente. */
    public function test_entrar_con_google_no_da_permisos_nuevos(): void
    {
        $this->configurar('google');
        $this->fingirPerfil('google', 'cliente@ejemplo.co', 'g-2');

        $cliente = User::forceCreate([
            'name' => 'Clienta', 'email' => 'cliente@ejemplo.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $this->get('/acceso/google/volver')->assertRedirect(route('cuenta'));

        // Ni sube ni baja por haber entrado con Google. El riesgo real es el
        // contrario —que alguien salga de aquí con permisos— y por eso el rol
        // no se toca nunca.
        $this->assertSame(Rol::Cliente, $cliente->fresh()->rol);
        $this->assertSame('google', $cliente->fresh()->proveedor);
    }

    public function test_una_cuenta_desactivada_sigue_sin_poder_entrar(): void
    {
        $this->configurar('facebook');
        $this->fingirPerfil('facebook', 'exempleado@ejemplo.co', 'f-7');

        User::forceCreate([
            'name' => 'Ex empleado', 'email' => 'exempleado@ejemplo.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => false,
        ]);

        $this->get('/acceso/facebook/volver')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sin_correo_del_proveedor_no_se_entra(): void
    {
        // Facebook deja tener cuenta sin correo verificado. Sin correo no hay
        // forma de saber si esta persona ya es cliente nuestro.
        $this->configurar('facebook');
        $this->fingirPerfil('facebook', '', 'f-8');

        $this->get('/acceso/facebook/volver')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_si_el_proveedor_falla_se_vuelve_al_formulario(): void
    {
        $this->configurar('google');

        $driver = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $driver->shouldReceive('user')->andThrow(new \RuntimeException('El usuario canceló'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get('/acceso/google/volver')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_una_cuenta_nueva_nace_como_cliente(): void
    {
        $this->configurar('google');
        $this->fingirPerfil('google', 'nueva@ejemplo.co', 'g-nuevo', 'Nueva Persona');

        config(['portada.modulo_clientes' => true]);

        $this->get('/acceso/google/volver')->assertRedirect(route('cuenta'));

        $usuario = User::firstWhere('email', 'nueva@ejemplo.co');

        $this->assertNotNull($usuario);
        $this->assertSame(Rol::Cliente, $usuario->rol);
        $this->assertTrue($usuario->activo);
        $this->assertNull($usuario->password);
    }

    public function test_sin_modulo_de_clientes_no_se_crean_cuentas(): void
    {
        $this->configurar('google');
        $this->fingirPerfil('google', 'otra@ejemplo.co', 'g-otro');

        config(['portada.modulo_clientes' => false]);

        $this->get('/acceso/google/volver')
            ->assertRedirect(route('acceso'))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, User::count());
    }
}
