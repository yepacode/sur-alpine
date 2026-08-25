<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Mail\SolicitudCotizacion;
use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(Rol $rol, bool $activo = true): User
    {
        return User::create([
            'name' => 'Prueba '.$rol->value,
            'email' => $rol->value.'@suralpine.com',
            'password' => 'secreto123',
            'rol' => $rol,
            'activo' => $activo,
        ]);
    }

    private function solicitud(array $atributos = []): Cotizacion
    {
        $cotizacion = Cotizacion::create(array_merge([
            'consecutivo' => Cotizacion::siguienteConsecutivo(),
            'nombre' => 'Julián',
            'apellidos' => 'Restrepo',
            'telefono' => '3134223861',
            'email' => 'julian@taller.co',
            'correo_enviado_en' => now(),
        ], $atributos));

        $cotizacion->items()->create([
            'producto_nombre' => 'Pastillas Freno Delanteras AVEO 1600 CHEVROLET',
            'vehiculo_nombre' => 'CHEVROLET AVEO 1600 (2006-2013)',
            'cantidad' => 2,
        ]);

        return $cotizacion;
    }

    public function test_el_panel_pide_sesion(): void
    {
        $this->get('/panel')->assertRedirect(route('acceso'));
    }

    public function test_un_cliente_no_entra_al_panel(): void
    {
        $this->actingAs($this->usuario(Rol::Cliente))->get('/panel')->assertForbidden();
    }

    public function test_el_vendedor_ve_tablero_y_solicitudes(): void
    {
        $this->solicitud();
        $vendedor = $this->usuario(Rol::Vendedor);

        $this->actingAs($vendedor)->get('/panel')->assertOk()->assertSee('Tablero');
        $this->actingAs($vendedor)->get('/panel/solicitudes')->assertOk()->assertSee('Julián Restrepo');
    }

    /**
     * La escalera de roles: el vendedor no toca el catálogo, el asesor sí,
     * y sólo el administrador administra usuarios y configuración.
     */
    public function test_el_menu_muestra_solo_lo_que_cada_rol_puede(): void
    {
        $this->actingAs($this->usuario(Rol::Vendedor))->get('/panel')
            ->assertSee('Solicitudes')
            ->assertDontSee('Configuración');

        $this->actingAs($this->usuario(Rol::Admin))->get('/panel')
            ->assertSee('Solicitudes')
            ->assertSee('Configuración');
    }

    public function test_una_cuenta_desactivada_no_puede_entrar(): void
    {
        $inactivo = $this->usuario(Rol::Admin, activo: false);

        $this->post(route('entrar'), ['email' => $inactivo->email, 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_las_credenciales_malas_no_entran(): void
    {
        $usuario = $this->usuario(Rol::Admin);

        $this->post(route('entrar'), ['email' => $usuario->email, 'password' => 'equivocada'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_equipo_aterriza_en_el_panel_y_el_cliente_en_el_sitio(): void
    {
        $asesor = $this->usuario(Rol::Asesor);
        $this->post(route('entrar'), ['email' => $asesor->email, 'password' => 'secreto123'])
            ->assertRedirect(route('panel.tablero'));

        $this->post(route('salir'));

        $cliente = $this->usuario(Rol::Cliente);
        $this->post(route('entrar'), ['email' => $cliente->email, 'password' => 'secreto123'])
            ->assertRedirect(route('inicio'));
    }

    public function test_el_tablero_cuenta_solo_lo_del_periodo(): void
    {
        $this->solicitud();
        $vieja = $this->solicitud();
        $vieja->forceFill(['created_at' => now()->subDays(45)])->save();

        $admin = $this->usuario(Rol::Admin);

        $this->assertSame(1, $this->actingAs($admin)->get('/panel?periodo=hoy')->viewData('totalCotizaciones'));
        $this->assertSame(1, $this->actingAs($admin)->get('/panel?periodo=30')->viewData('totalCotizaciones'));
        $this->assertSame(2, $this->actingAs($admin)->get('/panel?periodo=90')->viewData('totalCotizaciones'));
    }

    public function test_el_rango_personalizado_se_respeta(): void
    {
        $vieja = $this->solicitud();
        $vieja->forceFill(['created_at' => now()->subDays(10)])->save();

        $vista = $this->actingAs($this->usuario(Rol::Admin))->get('/panel?'.http_build_query([
            'periodo' => 'personalizado',
            'desde' => now()->subDays(12)->toDateString(),
            'hasta' => now()->subDays(8)->toDateString(),
        ]));

        $this->assertSame(1, $vista->viewData('totalCotizaciones'));
    }

    public function test_el_tablero_avisa_de_los_correos_que_no_salieron(): void
    {
        $this->solicitud(['correo_enviado_en' => null, 'error_envio' => 'Buzón lleno']);

        $this->actingAs($this->usuario(Rol::Admin))->get('/panel')
            ->assertOk()
            ->assertSee('Correos sin salir')
            ->assertSee('Revisar y reenviar');
    }

    public function test_se_puede_reenviar_una_solicitud_que_fallo(): void
    {
        Mail::fake();
        \App\Models\Configuracion::poner('correos_cotizacion', 'cotizaciones@suralpine.com');

        $solicitud = $this->solicitud(['correo_enviado_en' => null, 'error_envio' => 'Buzón lleno']);

        $this->actingAs($this->usuario(Rol::Vendedor))
            ->post(route('panel.solicitudes.reenviar', $solicitud))
            ->assertRedirect();

        Mail::assertQueued(SolicitudCotizacion::class);

        $this->assertNotNull($solicitud->fresh()->correo_enviado_en);
        $this->assertNull($solicitud->fresh()->error_envio);
    }

    public function test_el_filtro_de_sin_enviar_separa_las_dos_pilas(): void
    {
        $this->solicitud();
        $this->solicitud(['correo_enviado_en' => null]);

        $vista = $this->actingAs($this->usuario(Rol::Vendedor))
            ->get('/panel/solicitudes?estado=sin-enviar')
            ->assertOk();

        $this->assertSame(1, $vista->viewData('solicitudes')->total());
    }

    public function test_la_busqueda_encuentra_por_consecutivo_y_por_telefono(): void
    {
        $solicitud = $this->solicitud();
        $vendedor = $this->usuario(Rol::Vendedor);

        $this->actingAs($vendedor)->get('/panel/solicitudes?q='.$solicitud->consecutivo)
            ->assertOk()->assertSee('Julián Restrepo');

        $this->actingAs($vendedor)->get('/panel/solicitudes?q=3134223861')
            ->assertOk()->assertSee('Julián Restrepo');

        $this->actingAs($vendedor)->get('/panel/solicitudes?q=nadie')
            ->assertOk()->assertSee('No hay solicitudes con esos filtros');
    }

    public function test_la_exportacion_trae_una_fila_por_repuesto(): void
    {
        $this->solicitud();

        $respuesta = $this->actingAs($this->usuario(Rol::Vendedor))
            ->get(route('panel.solicitudes.exportar'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $respuesta->streamedContent();

        $this->assertStringContainsString('Consecutivo;Fecha', $csv);
        $this->assertStringContainsString('Pastillas Freno Delanteras AVEO 1600 CHEVROLET', $csv);
        $this->assertStringContainsString('CHEVROLET AVEO 1600 (2006-2013)', $csv);
    }
}
