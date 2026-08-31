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

    public function test_el_administrador_ve_tablero_y_solicitudes(): void
    {
        $this->solicitud();
        $admin = $this->usuario(Rol::Admin);

        $this->actingAs($admin)->get('/panel')->assertOk()->assertSee('Tablero');
        $this->actingAs($admin)->get('/panel/solicitudes')->assertOk()->assertSee('Julián Restrepo');
    }

    /**
     * Antes el menú se recortaba por rol. Ahora el panel es de una sola pieza:
     * quien entra lo ve completo, y quien no es administrador no entra.
     */
    public function test_el_menu_llega_completo_y_el_cliente_no_lo_ve(): void
    {
        $this->actingAs($this->usuario(Rol::Admin))->get('/panel')
            ->assertSee('Solicitudes')
            ->assertSee('Catálogo')
            ->assertSee('Usuarios')
            // «Configuración de página» y «Configuración» se llamaban casi
            // igual y el cliente entraba a la que no era. Ahora dicen qué hay
            // dentro: «Textos e imágenes» y «Datos y correos».
            ->assertSee('Textos e imágenes')
            ->assertSee('Datos y correos');

        // `flushSession()` entre los dos: la sesión quedó atada a la
        // contraseña del primero (middleware `AuthenticateSession`) y
        // reusarla con otra cuenta lo saca. Un navegador no puede cambiar
        // de identidad sin cerrar sesión; esto es sólo el atajo de las
        // pruebas, y aquí se paga.
        $this->flushSession();

        $this->actingAs($this->usuario(Rol::Cliente))->get('/panel')->assertForbidden();
    }

    /**
     * «No encuentro dónde se edita».
     *
     * Lo dijo el cliente y tenía razón aunque todo estuviera hecho: el menú se
     * llama «Catálogo» y «Configuración de página», y él piensa en «quiero
     * cambiarle la foto a una categoría». El tablero abre con esas frases y
     * cada una lleva a la pantalla que ya existía.
     */
    public function test_el_tablero_dice_donde_se_cambia_cada_cosa(): void
    {
        $vista = $this->actingAs($this->usuario(Rol::Admin))->get('/panel')->assertOk();

        $vista->assertSee('¿Qué quieres cambiar?', false)
            ->assertSee('Cambiar los textos de la portada y de las páginas')
            ->assertSee('Corregir la referencia, la foto o la descripción de un repuesto')
            ->assertSee('Poner o quitar una campaña del banner')
            // El horario tiene dos sitios y el atajo llevaba al que NO cambia
            // lo que se lee en /contactenos. Ahora son dos atajos distintos.
            ->assertSee('Cambiar el horario de atención que se lee en la página')
            ->assertSee('Cambiar teléfonos, dirección o a qué correo llegan las cotizaciones');

        // Y llevan de verdad.
        //
        // Contando ocurrencias, no con `assertSee`: esas siete rutas ya están
        // en la barra de navegación de TODAS las pantallas del panel, así que
        // se podía borrar el bloque entero y las aserciones seguían verdes.
        // Aquí tienen que aparecer al menos DOS veces —menú y tarjeta—.
        //
        // «Al menos» y no «exactamente»: `panel.pagina` tiene dos atajos a
        // propósito, porque el cliente llega a esa pantalla por dos motivos
        // que no se parecen —cambiar los textos y cambiar el horario—.
        $html = $vista->getContent();

        foreach (['panel.pagina', 'panel.catalogo', 'panel.banners', 'panel.categorias',
                  'panel.notas', 'panel.configuracion', 'panel.usuarios'] as $ruta) {
            $this->assertGreaterThanOrEqual(
                2,
                substr_count($html, 'href="'.route($ruta).'"'),
                "«{$ruta}» tiene que estar en el menú Y en el bloque de atajos."
            );
        }
    }

    /**
     * Cada campo de «Configuración» dice dónde se ve lo que se escribe. Sin
     * eso, el cliente no sabe si el WhatsApp que teclea sale en el pie, en el
     * botón verde o en ninguna parte, y termina no tocándolo.
     */
    public function test_la_configuracion_explica_cada_campo(): void
    {
        $this->actingAs($this->usuario(Rol::Admin))->get(route('panel.configuracion'))
            ->assertOk()
            ->assertSee('Sale en la barra azul de arriba, en el pie y en los correos.')
            ->assertSee('adelante y sin espacios ni signos', false)
            ->assertSee('ese botón desaparece')
            ->assertSee('las solicitudes se', false);
    }

    public function test_una_cuenta_desactivada_no_puede_entrar(): void
    {
        $inactivo = $this->usuario(Rol::Admin, ['activo' => false]);

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
        $admin = $this->usuario(Rol::Admin);
        $this->post(route('entrar'), ['email' => $admin->email, 'password' => 'secreto123'])
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

        $this->actingAs($this->usuario(Rol::Admin))
            ->post(route('panel.solicitudes.reenviar', $solicitud))
            ->assertRedirect();

        Mail::assertSent(SolicitudCotizacion::class);

        $this->assertNotNull($solicitud->fresh()->correo_enviado_en);
        $this->assertNull($solicitud->fresh()->error_envio);
    }

    public function test_el_filtro_de_sin_enviar_separa_las_dos_pilas(): void
    {
        $this->solicitud();
        $this->solicitud(['correo_enviado_en' => null]);

        $vista = $this->actingAs($this->usuario(Rol::Admin))
            ->get('/panel/solicitudes?estado=sin-enviar')
            ->assertOk();

        $this->assertSame(1, $vista->viewData('solicitudes')->total());
    }

    public function test_la_busqueda_encuentra_por_consecutivo_y_por_telefono(): void
    {
        $solicitud = $this->solicitud();
        $admin = $this->usuario(Rol::Admin);

        $this->actingAs($admin)->get('/panel/solicitudes?q='.$solicitud->consecutivo)
            ->assertOk()->assertSee('Julián Restrepo');

        $this->actingAs($admin)->get('/panel/solicitudes?q=3134223861')
            ->assertOk()->assertSee('Julián Restrepo');

        $this->actingAs($admin)->get('/panel/solicitudes?q=nadie')
            ->assertOk()->assertSee('No hay solicitudes con esos filtros');
    }

    public function test_la_exportacion_trae_una_fila_por_repuesto(): void
    {
        $this->solicitud();

        $respuesta = $this->actingAs($this->usuario(Rol::Admin))
            ->get(route('panel.solicitudes.exportar'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $respuesta->streamedContent();

        $this->assertStringContainsString('Consecutivo;Fecha', $csv);
        $this->assertStringContainsString('Pastillas Freno Delanteras AVEO 1600 CHEVROLET', $csv);
        $this->assertStringContainsString('CHEVROLET AVEO 1600 (2006-2013)', $csv);
    }
}
