<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Nota;
use App\Models\Producto;
use App\Models\SeoPagina;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * El sitemap.
 *
 * No tenía ninguna prueba, y ahí apareció el fallo que motivó esta: la fila
 * de SEO de la portada tenía la casilla «incluir en sitemap» apagada, así que
 * la raíz del sitio llevaba tiempo fuera del índice. Nada falla cuando eso
 * pasa; simplemente Google deja de ver la página principal.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function producto(): Producto
    {
        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);
        $categoria = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $tipo = TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Pastillas Freno', 'slug' => 'pastillas-freno',
        ]);

        return Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Pastillas Freno AVEO', 'slug' => 'pastillas-freno-aveo', 'publicado' => true,
        ]);
    }

    private function secciones(): string
    {
        return $this->get('/sitemap-secciones.xml')->assertOk()->getContent();
    }

    public function test_el_indice_apunta_a_los_demas(): void
    {
        $this->producto();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('sitemap-secciones.xml')
            ->assertSee('sitemap-productos-1.xml');
    }

    /**
     * La portada. Es la URL más importante del sitio y la que faltaba.
     */
    public function test_la_portada_esta_en_el_sitemap(): void
    {
        $this->assertStringContainsString('<loc>'.route('inicio').'</loc>', $this->secciones());
    }

    /**
     * Y no se puede sacar. La casilla del panel sirve para excluir una página
     * secundaria; apagar la raíz del sitio no es algo que nadie quiera hacer,
     * y no se nota hasta perder el tráfico.
     */
    public function test_la_portada_no_se_puede_sacar_ni_apagando_la_casilla(): void
    {
        SeoPagina::updateOrCreate(['ruta' => 'inicio'], ['etiqueta' => 'Portada', 'sitemap_incluir' => false]);
        Cache::flush();

        $this->assertStringContainsString('<loc>'.route('inicio').'</loc>', $this->secciones());
    }

    /** Una página secundaria sí se puede excluir: para eso está la casilla. */
    public function test_una_pagina_secundaria_si_se_excluye(): void
    {
        $this->assertStringContainsString(route('quienes-somos'), $this->secciones());

        SeoPagina::updateOrCreate(
            ['ruta' => 'quienes-somos'],
            ['etiqueta' => 'Quiénes somos', 'sitemap_incluir' => false]
        );
        Cache::flush();

        $this->assertStringNotContainsString(route('quienes-somos'), $this->secciones());
    }

    /**
     * Las legales pesan poco pero se indexan: son las que miran para creer
     * que detrás hay una empresa y no una tienda fantasma —que es justo el
     * problema que este cliente tiene con las copias de su sitio.
     */
    public function test_las_paginas_legales_estan(): void
    {
        $xml = $this->secciones();

        $this->assertStringContainsString(route('terminos'), $xml);
        $this->assertStringContainsString(route('politica-datos'), $xml);
    }

    public function test_las_notas_publicadas_estan_y_los_borradores_no(): void
    {
        Nota::create([
            'titulo' => 'Kit de distribución', 'slug' => 'kit-de-distribucion',
            'categoria' => 'Mantenimiento', 'resumen' => 'Qué es', 'cuerpo' => 'Texto',
            'publicada_en' => now()->subDay(),
        ]);
        // Un borrador de verdad es `publicada => false`. Dejar
        // `publicada_en` en null NO es un borrador: es «publicada sin fecha»,
        // y esa sí sale. (Me equivoqué al escribir esta prueba la primera vez
        // y por poco lo reporto como un fallo del sitemap.)
        Nota::create([
            'titulo' => 'Borrador', 'slug' => 'borrador',
            'categoria' => 'Mantenimiento', 'resumen' => 'Aún no', 'cuerpo' => 'Texto',
            'publicada' => false,
        ]);

        $enElFuturo = Nota::create([
            'titulo' => 'Programada', 'slug' => 'programada',
            'categoria' => 'Mantenimiento', 'resumen' => 'Sale el mes que viene', 'cuerpo' => 'Texto',
            'publicada_en' => now()->addMonth(),
        ]);

        $xml = $this->secciones();

        $this->assertStringContainsString('kit-de-distribucion', $xml);
        $this->assertStringNotContainsString('/noticias/borrador', $xml);
        $this->assertStringNotContainsString('/noticias/programada', $xml);

        // Y lo que no está en el sitemap tampoco abre: las dos puertas cierran
        // igual, que es lo que evita ofrecerle a Google un 404.
        $this->get(route('nota', $enElFuturo))->assertNotFound();
    }

    /** Una pieza despublicada no puede seguir ofreciéndose a Google. */
    public function test_un_producto_despublicado_sale_del_sitemap(): void
    {
        $producto = $this->producto();

        $this->get('/sitemap-productos-1.xml')->assertOk()->assertSee($producto->slug);

        $producto->update(['publicado' => false]);
        Cache::flush();

        $this->get('/sitemap-productos-1.xml')->assertOk()->assertDontSee($producto->slug);
    }

    /** Lo que el sitemap ofrece tiene que abrir de verdad. */
    public function test_lo_que_lista_responde_200(): void
    {
        $this->producto();

        preg_match_all('/<loc>([^<]+)<\/loc>/', $this->secciones(), $coincidencias);

        $rutas = array_slice($coincidencias[1], 0, 12);
        $this->assertNotEmpty($rutas);

        foreach ($rutas as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_robots_apunta_al_sitemap_y_cierra_lo_privado(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertSee('Disallow: /panel')
            ->assertSee('Disallow: /mi-cuenta');
    }
}
