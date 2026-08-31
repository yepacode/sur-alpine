<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Contenido;
use App\Models\SeoPagina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F · «Configuración de página» — el panel unificado, y su rebote sobre
 * el sitio público. Los textos y el SEO viven en tablas clave-valor; si
 * el asesor los toca, se ven en el HTML sin tocar código.
 */
class ConfiguracionPaginaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::forceCreate([
            'name' => 'Admin', 'email' => 'admin@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);
    }

    public function test_el_panel_muestra_las_secciones_del_sitio(): void
    {
        $this->actingAs($this->admin())
            ->get(route('panel.pagina'))
            ->assertOk()
            ->assertSee('Buscador de vehículo')
            ->assertSee('Buscador de vehículo')
            ->assertSee('Cabecera y menú')
            ->assertSee('SEO de la página');
    }

    public function test_las_claves_conocidas_se_registran_al_primer_acceso(): void
    {
        // Antes del acceso al panel: la tabla está vacía. Después: hay filas
        // para todas las claves conocidas. Así el asesor entra y ve el
        // catálogo listo para editar sin pasar una tarea previa.
        $this->assertSame(0, Contenido::count());

        $this->actingAs($this->admin())->get(route('panel.pagina'))->assertOk();

        $this->assertGreaterThan(0, Contenido::count());
        $this->assertTrue(Contenido::where('clave', 'buscador.titulo')->exists());
    }

    public function test_al_guardar_un_texto_ese_texto_reemplaza_al_original_en_la_portada(): void
    {
        $admin = $this->admin();
        // Primer refresco: sincroniza las claves.
        $this->actingAs($admin)->get(route('panel.pagina'));

        $bajada = Contenido::firstWhere('clave', 'buscador.subtitulo');
        $this->assertNotNull($bajada);

        // El asesor cambia la bajada.
        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'textos' => [$bajada->id => 'y te decimos qué le sirve a tu carro'],
        ])->assertRedirect();

        $this->assertSame(
            'y te decimos qué le sirve a tu carro',
            $bajada->fresh()->valor,
        );

        // Y aparece en la portada.
        $this->get('/')->assertSee('y te decimos qué le sirve a tu carro', false);
    }

    /**
     * Lo que el cliente pidió revisar: que se pueda editar «a detalle».
     *
     * Estos bloques estaban clavados en la plantilla: los tres respaldos, la
     * tarjeta roja de servicios, «¿Dónde estamos ubicados?» y «Marcas
     * destacadas». Se veían bien, pero para cambiarles una coma había que
     * llamarnos.
     */
    public function test_los_bloques_de_la_portada_son_editables(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'))->assertOk();

        foreach ([
            'respaldo.1.titulo', 'respaldo.1.texto',
            'respaldo.2.titulo', 'respaldo.2.texto',
            'respaldo.3.titulo', 'respaldo.3.texto',
            'servicios.historial.titulo', 'servicios.historial.texto',
            'servicios.historial.boton', 'servicios.historial.boton_dentro',
            'ubicacion.titulo', 'ubicacion.texto', 'marcas.titulo',
        ] as $clave) {
            $this->assertTrue(
                Contenido::where('clave', $clave)->exists(),
                "Falta la clave editable «{$clave}»."
            );
        }
    }

    /** Y editarlos cambia la portada de verdad, no sólo la fila. */
    public function test_cambiar_esos_bloques_se_ve_en_la_portada(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $cambios = [
            'respaldo.2.texto' => 'Trabajamos con más de doce marcas de vehículo liviano.',
            'ubicacion.titulo' => 'Ven a visitarnos al Restrepo',
            'marcas.titulo' => 'Con quién trabajamos',
        ];

        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'textos' => collect($cambios)
                ->mapWithKeys(fn ($valor, $clave) => [Contenido::firstWhere('clave', $clave)->id => $valor])
                ->all(),
        ])->assertRedirect();

        $portada = $this->get('/')->assertOk();

        foreach ($cambios as $valor) {
            $portada->assertSee($valor, false);
        }
    }

    /**
     * Los tres respaldos tenían UN solo párrafo compartido: cambiar el segundo
     * cambiaba los tres. Ahora cada uno lleva su clave.
     */
    public function test_cada_respaldo_tiene_su_propio_texto(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $segundo = Contenido::firstWhere('clave', 'respaldo.2.texto');

        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'textos' => [$segundo->id => 'Sólo este cambia.'],
        ])->assertRedirect();

        $html = $this->get('/')->getContent();

        $this->assertSame(1, mb_substr_count($html, 'Sólo este cambia.'));
        $this->assertGreaterThanOrEqual(
            2,
            mb_substr_count($html, 'Nuestro equipo cuenta con amplia experiencia'),
            'Los otros dos siguen con el texto que tenían.'
        );
    }

    /** Las dos páginas legales también se editan desde el panel. */
    public function test_terminos_y_noticias_tienen_seo_editable(): void
    {
        $this->actingAs($this->admin())->get(route('panel.pagina'))->assertOk();

        foreach (['terminos', 'noticias'] as $ruta) {
            $this->assertTrue(
                SeoPagina::where('ruta', $ruta)->exists(),
                "«{$ruta}» tiene que poder editarse desde el panel."
            );
        }
    }

    /**
     * Ninguna sección puede quedar sin nada que editar.
     *
     * Es lo que el cliente vio: abría «Quiénes somos» o «Mantenimientos» en el
     * panel y no había un solo campo, sólo el bloque de SEO. Una sección que
     * se abre y está vacía se lee como «esto no se puede cambiar».
     */
    public function test_ninguna_seccion_queda_sin_campos(): void
    {
        $secciones = app(\App\Http\Controllers\Panel\ConfiguracionPaginaController::class)->secciones();

        foreach ($secciones as $slug => $seccion) {
            $this->assertNotEmpty(
                $seccion['textos'],
                "La sección «{$slug}» se abre sin un solo campo que editar."
            );
        }
    }

    /** Las fotos fijas del sitio también se cambian desde el panel. */
    public function test_las_imagenes_del_sitio_son_editables(): void
    {
        $this->actingAs($this->admin())->get(route('panel.pagina'))->assertOk();

        foreach ([
            'quienes.imagen', 'contacto.imagen', 'contacto.local',
            'servicios.historial.imagen', 'ubicacion.mapa', 'acceso.imagen',
        ] as $clave) {
            $fila = Contenido::firstWhere('clave', $clave);

            $this->assertNotNull($fila, "Falta la imagen editable «{$clave}».");
            $this->assertSame('imagen', $fila->tipo);
        }
    }

    /**
     * Subir una foto la deja en WebP y en los anchos que esa pieza usa, y la
     * página empieza a mostrarla.
     */
    public function test_subir_una_foto_la_cambia_en_la_pagina(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $fila = Contenido::firstWhere('clave', 'quienes.imagen');
        $anterior = $fila->valor;

        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'imagenes' => [$fila->id => \Illuminate\Http\UploadedFile::fake()->image('nueva.jpg', 2000, 700)],
        ])->assertRedirect();

        $nuevo = $fila->fresh()->valor;

        $this->assertNotSame($anterior, $nuevo);
        $this->assertStringStartsWith('/img/editables/', $nuevo);

        // 1024 y 1600 son los que pide `components/cabecera-pagina.blade.php`.
        // Declarar otros anchos dejaba el `srcset` apuntando a un 404 en cuanto
        // el cliente cambiara la foto.
        foreach ([1024, 1600] as $ancho) {
            $ruta = public_path(ltrim($nuevo, '/')."-{$ancho}.webp");

            $this->assertFileExists($ruta);
            $this->assertSame('image/webp', mime_content_type($ruta));

            unlink($ruta);
        }

        $this->get(route('quienes-somos'))->assertOk()->assertSee($nuevo, false);
    }

    /**
     * El formulario manda los diez campos de imagen a la vez. Guardar un texto
     * no puede dejar sin foto a las nueve que nadie tocó.
     */
    public function test_guardar_sin_elegir_foto_no_borra_la_que_habia(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $fila = Contenido::firstWhere('clave', 'contacto.local');
        $antes = $fila->valor;

        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'imagenes' => [$fila->id => null],
        ])->assertRedirect();

        $this->assertSame($antes, $fila->fresh()->valor);
    }

    public function test_al_guardar_seo_los_meta_de_la_portada_los_llevan(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $seo = SeoPagina::firstWhere('ruta', 'inicio');
        $this->assertNotNull($seo);

        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'seo' => [$seo->id => [
                'titulo' => 'Repuestos AVEO Bogotá',
                'descripcion' => 'Autopartes para carros Chevrolet en Bogotá.',
                'palabras_clave' => 'repuestos aveo, autopartes bogota',
                'canonical' => 'https://suralpine.com/',
                'og_titulo' => 'OG Título',
                'og_descripcion' => 'OG Descripción',
                'og_imagen' => 'https://suralpine.com/img/og.webp',
                'og_imagen_alt' => 'Sur Alpine',
                'og_tipo' => 'website',
                'twitter_card' => 'summary_large_image',
                'twitter_titulo' => 'TW Título',
                'twitter_descripcion' => 'TW Descripción',
                'twitter_imagen' => 'https://suralpine.com/img/tw.webp',
                'indexable' => '1',
                'seguir_enlaces' => '1',
                'json_ld_extra' => '{"@context":"https://schema.org","@type":"Thing"}',
            ]],
        ])->assertRedirect();

        // Cierro la sesión (para no ver la barra del panel en la portada).
        auth()->logout();
        $html = $this->get('/')->assertOk()->getContent();

        // Todo esto viene del panel, no del blade original.
        $this->assertStringContainsString('<title>Repuestos AVEO Bogotá</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Autopartes para carros Chevrolet en Bogotá.', $html);
        $this->assertStringContainsString('<meta name="keywords" content="repuestos aveo, autopartes bogota"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://suralpine.com/"', $html);
        $this->assertStringContainsString('<meta property="og:title" content="OG Título"', $html);
        $this->assertStringContainsString('<meta property="og:image" content="https://suralpine.com/img/og.webp"', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="https://suralpine.com/img/tw.webp"', $html);
        $this->assertStringContainsString('<meta name="robots" content="index,follow', $html);
        $this->assertStringContainsString('"@context":"https://schema.org","@type":"Thing"', $html);
    }

    public function test_desactivar_indexable_marca_noindex_en_los_meta(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $seo = SeoPagina::firstWhere('ruta', 'inicio');

        // Sin el hidden input, `checkbox` no marcado no llega — pero el
        // controlador ya lo maneja con `(bool)` sobre el valor puesto.
        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'seo' => [$seo->id => ['indexable' => '0', 'seguir_enlaces' => '0']],
        ])->assertRedirect();

        auth()->logout();
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow', $html);
    }
}
