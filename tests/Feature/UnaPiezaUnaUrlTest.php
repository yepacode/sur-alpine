<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un contenido, una direccion.
 *
 * Todo lo de aqui existe por el mismo motivo de negocio: hay copias del sitio
 * de Sur Alpine circulando, y cada URL de mas que se declara «la original» es
 * una posicion que la empresa le regala a quien la suplanta. Google, ante dos
 * paginas que dicen ser la buena, elige por su cuenta.
 */
class UnaPiezaUnaUrlTest extends TestCase
{
    use RefreshDatabase;

    private Producto $producto;

    private Categoria $direccion;

    private Categoria $suspension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->direccion = Categoria::create(['nombre' => 'Dirección', 'slug' => 'direccion']);
        $this->suspension = Categoria::create(['nombre' => 'Suspensión', 'slug' => 'suspension']);

        $marca = Marca::create(['nombre' => 'RENAULT', 'slug' => 'renault']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => '12', 'slug' => '12']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1300',
            'slug' => 'renault-12-1300', 'anio_inicio' => 1980, 'anio_fin' => 1990,
        ]);

        $tipo = TipoParte::create([
            'categoria_id' => $this->direccion->id, 'nombre' => 'Bujía', 'slug' => 'bujia',
        ]);

        $this->producto = Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Bujía 12 1300 RENAULT', 'slug' => 'bujia-12-1300-renault',
            'publicado' => true,
        ]);
    }

    /**
     * MySQL resuelve el slug sin distinguir mayusculas, asi que
     * `/repuesto/BUJIA-...` respondia 200 con un canonical que apuntaba a si
     * mismo. Eran variantes indexables ilimitadas de las 29.272 fichas.
     */
    public function test_el_slug_en_mayusculas_redirige_al_bueno(): void
    {
        $this->get('/repuesto/BUJIA-12-1300-RENAULT')
            ->assertRedirect(route('producto', $this->producto))
            ->assertStatus(301);

        $this->get(route('producto', $this->producto))->assertOk();
    }

    /** Redirige, no responde 404: los enlaces mal copiados tienen que llevar a la pieza. */
    public function test_lo_mismo_en_categorias_y_conserva_la_pagina(): void
    {
        $this->get('/repuestos/DIRECCION?page=2')
            ->assertStatus(301)
            ->assertRedirect(route('categoria', $this->direccion).'?page=2');
    }

    /**
     * Y la pagina de tipo de parte corrige los DOS segmentos de un salto.
     *
     * De un salto y no de dos: ahi el slug se resuelve a mano dentro de su
     * categoria, asi que si lo arreglara el middleware saldria un 301 hacia
     * otro 301. Y la URL buena tiene que responder 200 —al comparar contra el
     * modelo ya resuelto en vez de contra el texto pedido, se redirigia a si
     * misma: bucle infinito en el navegador—.
     */
    public function test_el_tipo_de_parte_corrige_los_dos_segmentos_de_un_salto(): void
    {
        $buena = route('tipo-parte', [$this->direccion, 'bujia']);

        $this->get('/repuestos/DIRECCION/BUJIA')
            ->assertStatus(301)
            ->assertRedirect($buena);

        $this->get($buena)->assertOk();
    }

    /**
     * Cuatro tipos de parte viven en dos categorias a la vez, y eso duplicaba
     * 890 fichas byte a byte. La secundaria apunta a la principal.
     */
    public function test_la_ficha_gemela_apunta_a_la_que_manda(): void
    {
        $gemelo = $this->crearGemelaEnSuspension();

        // Manda la que tiene mas piezas; a igualdad, el id mas bajo. Aqui
        // empatan a una, asi que manda la de Direccion.
        $this->get(route('producto', $gemelo))
            ->assertOk()
            ->assertSee('rel="canonical" href="'.route('producto', $this->producto).'"', false);

        $this->get(route('producto', $this->producto))
            ->assertSee('rel="canonical" href="'.route('producto', $this->producto).'"', false);
    }

    /** Y sólo una de las dos entra al sitemap. */
    public function test_el_sitemap_publica_una_sola_cara_del_duplicado(): void
    {
        $gemelo = $this->crearGemelaEnSuspension();

        $xml = $this->get('/sitemap-productos-1.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('producto', $this->producto), $xml);
        $this->assertStringNotContainsString(route('producto', $gemelo), $xml);

        $secciones = $this->get('/sitemap-secciones.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('tipo-parte', [$this->direccion, 'bujia']), $secciones);
        $this->assertStringNotContainsString(route('tipo-parte', [$this->suspension, 'bujia']), $secciones);
    }

    /** Un trozo de sitemap que no existe no puede responder con un mapa vacío. */
    public function test_los_trozos_inventados_dan_404(): void
    {
        $this->get('/sitemap-productos-1.xml')->assertOk();
        $this->get('/sitemap-productos-0.xml')->assertNotFound();
        $this->get('/sitemap-productos-99.xml')->assertNotFound();
    }

    /**
     * Una busqueda no se indexa: `?q=` es un espacio infinito de paginas casi
     * identicas, y el `SearchAction` del schema invita a entrar en el.
     */
    public function test_los_resultados_de_busqueda_no_se_indexan(): void
    {
        $this->get(route('catalogo'))->assertSee('index,follow', false);
        $this->get(route('catalogo', ['q' => 'bujia']))->assertSee('noindex,follow', false);
    }

    private function crearGemelaEnSuspension(): Producto
    {
        $tipoGemelo = TipoParte::create([
            'categoria_id' => $this->suspension->id, 'nombre' => 'Bujía', 'slug' => 'bujia',
        ]);

        return Producto::create([
            'vehiculo_id' => $this->producto->vehiculo_id,
            'tipo_parte_id' => $tipoGemelo->id,
            'nombre' => $this->producto->nombre,
            'slug' => 'bujia-12-1300-renault-suspension',
            'publicado' => true,
        ]);
    }
}
