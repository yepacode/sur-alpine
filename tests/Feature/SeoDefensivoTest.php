<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * El SEO que defiende la identidad del cliente.
 *
 * No es SEO de manual: este negocio tiene páginas que lo suplantan usando su
 * nombre y sus fotos. Lo que se cubre aquí son las señales con las que Google
 * decide cuál de dos copias es la buena, y que estaban regaladas.
 */
class SeoDefensivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Un tipo de parte se resuelve DENTRO de su categoría.
     *
     * «axial-direccion» existe en Dirección y en Suspensión. Con el binding
     * normal ganaba siempre la primera y las URLs de la segunda morían con
     * 404… mientras el sitemap las publicaba. Eran cientos de repuestos sin
     * página de aterrizaje.
     */
    public function test_dos_categorias_pueden_compartir_el_slug_de_una_parte(): void
    {
        $direccion = Categoria::create(['nombre' => 'Dirección', 'slug' => 'direccion']);
        $suspension = Categoria::create(['nombre' => 'Suspensión', 'slug' => 'suspension']);

        foreach ([$direccion, $suspension] as $categoria) {
            TipoParte::create([
                'categoria_id' => $categoria->id,
                'nombre' => 'Axial dirección',
                'slug' => 'axial-direccion',
            ]);
        }

        $this->get('/repuestos/direccion/axial-direccion')->assertOk();
        $this->get('/repuestos/suspension/axial-direccion')->assertOk();

        // Y lo que no existe en esa categoría sigue dando 404.
        $this->get('/repuestos/direccion/no-existe')->assertNotFound();
    }

    /** Y el sitemap no puede ofrecer una URL que él mismo hace morir. */
    public function test_el_sitemap_no_lista_ninguna_url_muerta(): void
    {
        $direccion = Categoria::create(['nombre' => 'Dirección', 'slug' => 'direccion']);
        $suspension = Categoria::create(['nombre' => 'Suspensión', 'slug' => 'suspension']);

        foreach ([$direccion, $suspension] as $categoria) {
            TipoParte::create([
                'categoria_id' => $categoria->id,
                'nombre' => 'Terminal dirección',
                'slug' => 'terminal-direccion',
            ]);
        }

        preg_match_all(
            '/<loc>([^<]+)<\/loc>/',
            $this->get('/sitemap-secciones.xml')->assertOk()->getContent(),
            $coincidencias
        );

        foreach ($coincidencias[1] as $url) {
            $this->get($url)->assertOk("El sitemap ofrece {$url} y no responde.");
        }
    }

    /**
     * El cierre que más importa aquí: las URLs que el sitio declara suyas
     * salen de `APP_URL`, no de la cabecera `Host` de quien pregunta.
     *
     * Sin esto, quien montara un proxy apuntando aquí recibía páginas cuyo
     * canonical decía que el original es SU dominio —le entregábamos la señal
     * con la que Google desempata entre dos copias—. Y bastaba un GET con
     * `Host:` falso al sitemap para envenenar su caché durante una hora.
     */
    public function test_el_canonical_no_lo_decide_quien_pregunta(): void
    {
        // En pruebas el forzado está apagado a propósito —el banco de pruebas
        // pone su propio host—, así que aquí se finge producción y se ejecuta
        // el proveedor de verdad. Probar `URL::forceRootUrl()` a secas sería
        // probar a Laravel; lo que hay que sostener es que NUESTRO arranque lo
        // llama, y con el valor correcto.
        config(['app.url' => 'https://www.suralpine.com']);
        $this->app['env'] = 'production';

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertSame('https://www.suralpine.com', route('inicio'));
        $this->assertStringStartsWith('https://www.suralpine.com/', route('catalogo'));

        // El esquema también: con `forceRootUrl` a secas, un sitio https
        // seguía escribiendo `http://` en su propio canonical.
        $this->assertStringStartsWith('https://', url('/'));

        \Illuminate\Support\Facades\URL::forceRootUrl(null);
        $this->app['env'] = 'testing';
    }

    /** Y en pruebas NO se fuerza, o el banco de pruebas dejaría de funcionar. */
    public function test_en_pruebas_no_se_fuerza_la_raiz(): void
    {
        config(['app.url' => 'https://www.suralpine.com']);

        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('http://localhost', route('inicio'));
    }

    /** La portada necesita un h1 que diga de quién es este sitio. */
    public function test_cada_pagina_publica_tiene_un_h1_y_solo_uno(): void
    {
        $categoria = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);
        $tipo = TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Pastillas', 'slug' => 'pastillas',
        ]);
        $producto = Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Pastillas AVEO', 'slug' => 'pastillas-aveo', 'publicado' => true,
        ]);

        $paginas = [
            route('inicio'), route('catalogo'), route('noticias'), route('quienes-somos'),
            route('contacto'), route('mantenimientos'), route('terminos'),
            route('politica-datos'), route('producto', $producto),
        ];

        foreach ($paginas as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(
                1,
                substr_count($html, '<h1'),
                "{$url} tiene que tener exactamente un h1."
            );
        }
    }

    /**
     * Lo privado y lo de trámite fuera de Google, con UNA sola etiqueta.
     *
     * Antes salían todas `index,follow`, y las dos que sí se defendían lo
     * hacían con un `@push` que dejaba dos etiquetas contradictorias.
     */
    public function test_las_paginas_privadas_no_se_indexan(): void
    {
        foreach ([route('acceso'), route('registro'), route('clave.pedir'),
                  route('cotizacion.ver'), route('cotizacion.enviada')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<meta name="robots" content="([^"]*)"/', $html, $etiquetas);

            $this->assertCount(1, $etiquetas[1], "{$url} emite más de una etiqueta robots.");
            $this->assertStringContainsString('noindex', $etiquetas[1][0], "{$url} se está indexando.");
        }
    }

    /** Y las públicas siguen abiertas: sería fácil pasarse de frenada. */
    public function test_las_publicas_siguen_indexandose(): void
    {
        foreach ([route('inicio'), route('catalogo'), route('quienes-somos'), route('contacto')] as $url) {
            $this->get($url)->assertOk()->assertSee('content="index,follow', false);
        }
    }
}
