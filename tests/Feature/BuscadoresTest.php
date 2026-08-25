<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que ven los buscadores, que es distinto de lo que ve una persona.
 *
 * Importa más de lo normal en este negocio: el cliente pelea contra sitios que
 * lo suplantan, y la forma en que Google distingue al legítimo es exactamente
 * este marcado.
 */
class BuscadoresTest extends TestCase
{
    use RefreshDatabase;

    private Producto $pastillas;

    protected function setUp(): void
    {
        parent::setUp();

        $frenos = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $tipo = TipoParte::create(['categoria_id' => $frenos->id, 'nombre' => 'Pastillas Freno', 'slug' => 'pastillas-freno']);

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);

        $this->pastillas = Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Pastillas Freno AVEO 1600 CHEVROLET',
            'slug' => 'pastillas-freno-aveo-1600-chevrolet',
        ]);

        Configuracion::poner('direccion', 'Av. Caracas 19-21 sur', 'contacto');
        Configuracion::poner('ciudad', 'Bogotá D.C.', 'contacto');
        Configuracion::poner('telefono_pbx', '(601) 366 0066', 'contacto');
    }

    public function test_la_portada_declara_el_negocio_con_su_direccion(): void
    {
        $respuesta = $this->get(route('inicio'));

        $datos = $this->schemaDe($respuesta->getContent());
        $negocio = collect($datos)->firstWhere('@type', 'AutoPartsStore');

        $this->assertNotNull($negocio, 'Falta el dato estructurado del negocio.');
        $this->assertSame('Importadora Sur Alpine', $negocio['name']);
        $this->assertSame('1982', $negocio['foundingDate']);
        $this->assertSame('Av. Caracas 19-21 sur', $negocio['address']['streetAddress']);
        $this->assertSame('Bogotá D.C.', $negocio['address']['addressLocality']);
        $this->assertSame('+576013660066', $negocio['telephone']);
    }

    /** Si no hay redes cargadas, mejor no declarar `sameAs` que declararlo vacío. */
    public function test_las_redes_solo_se_declaran_si_existen(): void
    {
        $sinRedes = collect($this->schemaDe($this->get(route('inicio'))->getContent()))
            ->firstWhere('@type', 'AutoPartsStore');

        $this->assertArrayNotHasKey('sameAs', $sinRedes);

        Configuracion::poner('facebook', 'https://facebook.com/suralpine', 'redes');

        $conRedes = collect($this->schemaDe($this->get(route('inicio'))->getContent()))
            ->firstWhere('@type', 'AutoPartsStore');

        $this->assertSame(['https://facebook.com/suralpine'], $conRedes['sameAs']);
    }

    public function test_la_ficha_se_comparte_con_titulo_e_imagen(): void
    {
        $this->get(route('producto', $this->pastillas))
            ->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('Pastillas Freno AVEO 1600 CHEVROLET · Importadora Sur Alpine', false)
            ->assertSee('rel="canonical" href="'.route('producto', $this->pastillas).'"', false);
    }

    public function test_la_ficha_declara_para_que_vehiculo_es_la_pieza(): void
    {
        $contenido = $this->get(route('producto', $this->pastillas))->getContent();

        $pieza = collect($this->schemaDe($contenido))->firstWhere('@type', 'Product');

        $this->assertSame('Pastillas Freno AVEO 1600 CHEVROLET', $pieza['name']);
        $this->assertSame('CHEVROLET AVEO 1600 (2006-2013)', $pieza['isAccessoryOrSparePartFor']['name'] ?? null);
    }

    public function test_el_sitemap_lista_las_secciones_y_las_fichas(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('sitemap.mapa', 'secciones'))
            ->assertSee(route('sitemap.mapa', 'productos-1'));

        $this->get('/sitemap-secciones.xml')
            ->assertOk()
            ->assertSee(route('categoria', 'frenos'))
            ->assertSee(route('tipo-parte', ['frenos', 'pastillas-freno']));

        $this->get('/sitemap-productos-1.xml')
            ->assertOk()
            ->assertSee(route('producto', $this->pastillas));
    }

    public function test_un_sitemap_inventado_da_404(): void
    {
        $this->get('/sitemap-loquesea.xml')->assertNotFound();
    }

    public function test_robots_declara_el_sitemap_y_esconde_el_panel(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'))
            ->assertSee('Disallow: /panel');
    }

    /** @return array<int, array<string, mixed>> */
    private function schemaDe(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $coincidencias);

        return collect($coincidencias[1])
            ->flatMap(function (string $json) {
                $datos = json_decode(trim($json), true);

                $this->assertIsArray($datos, 'El JSON-LD no es válido: '.mb_substr($json, 0, 120));

                return array_is_list($datos) ? $datos : [$datos];
            })
            ->all();
    }
}
