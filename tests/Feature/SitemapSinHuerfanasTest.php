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
 * Una pieza sin gemela no puede quedarse fuera del sitemap.
 *
 * Cuatro tipos de parte viven en dos categorías a la vez. De cada par, uno
 * manda y el otro canonicaliza hacia él, y por eso el sitemap publica una sola
 * cara del duplicado: enseñarle las dos a Google es pedirle que elija.
 *
 * El filtro echaba fuera el tipo de parte ENTERO, y ahí estaba el error: los
 * dos lados casi nunca traen el mismo inventario. «Retén Rueda Trasera» tiene
 * 45 piezas en Dirección y 38 en Suspensión, y dos de las de Suspensión no
 * existen en Dirección. Esas dos no son duplicados de nada: su ficha es la
 * única que hay, ya se apuntaba a sí misma con su canonical, y aun así se
 * quedaba fuera del sitemap. Eran dos repuestos reales, con página viva y
 * enlazada, que ningún buscador iba a encontrar nunca.
 *
 * En producción eran «Retén Rueda Trasera ACCENT 1300 HYUNDAI» y el de 1500.
 */
class SitemapSinHuerfanasTest extends TestCase
{
    use RefreshDatabase;

    private Producto $duplicada;

    private Producto $huerfana;

    protected function setUp(): void
    {
        parent::setUp();

        $marca = Marca::create(['nombre' => 'HYUNDAI', 'slug' => 'hyundai']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'ACCENT', 'slug' => 'accent']);

        $carro = fn (string $cc) => Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => $cc,
            'slug' => 'hyundai-accent-'.$cc, 'anio_inicio' => 1995, 'anio_fin' => 2005,
        ]);

        $direccion = Categoria::create(['nombre' => 'Dirección', 'slug' => 'direccion']);
        $suspension = Categoria::create(['nombre' => 'Suspensión', 'slug' => 'suspension']);

        // El mismo slug en las dos categorías: eso es lo que crea el par.
        $enDireccion = TipoParte::create([
            'categoria_id' => $direccion->id,
            'nombre' => 'Reten Rueda Trasera', 'slug' => 'reten-rueda-trasera',
        ]);
        $enSuspension = TipoParte::create([
            'categoria_id' => $suspension->id,
            'nombre' => 'Reten Rueda Trasera', 'slug' => 'reten-rueda-trasera',
        ]);

        $pieza = fn (TipoParte $tipo, Vehiculo $v, string $sufijo) => Producto::create([
            'vehiculo_id' => $v->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Reten Rueda Trasera '.$v->cilindraje.' HYUNDAI',
            'slug' => 'reten-rueda-trasera-'.$v->cilindraje.'-hyundai-'.$sufijo,
            'publicado' => true,
        ]);

        // Dirección manda: le damos dos piezas contra una de Suspensión.
        $mil = $carro('1000');
        $milTres = $carro('1300');

        $pieza($enDireccion, $mil, 'direccion');
        $pieza($enDireccion, $milTres, 'direccion');

        // Ésta SÍ tiene gemela en el lado que manda: es duplicado de verdad.
        $this->duplicada = $pieza($enSuspension, $mil, 'suspension');

        // Ésta NO. Sólo existe en Suspensión, que es el lado secundario.
        $this->huerfana = $pieza($enSuspension, $carro('1500'), 'suspension');
    }

    public function test_la_gemela_del_lado_secundario_no_entra(): void
    {
        $this->assertFalse(
            Producto::canonicos()->whereKey($this->duplicada->id)->exists(),
            'La cara secundaria de un duplicado no debe publicarse: para eso está su canonical.'
        );
    }

    public function test_la_pieza_sin_gemela_si_entra(): void
    {
        $this->assertTrue(
            Producto::canonicos()->whereKey($this->huerfana->id)->exists(),
            'Una pieza que sólo existe en el lado secundario no es copia de nada: su ficha es la única que hay.'
        );
    }

    /** Y de punta a punta: tiene que salir en el XML que se publica. */
    public function test_aparece_en_el_sitemap_publicado(): void
    {
        $xml = $this->get('/sitemap-productos-1.xml')->assertOk()->getContent();

        $this->assertStringContainsString($this->huerfana->slug, $xml);
        $this->assertStringNotContainsString($this->duplicada->slug, $xml);
    }

    /**
     * La ficha huérfana se apunta a sí misma.
     *
     * Si canonicalizara hacia una URL que no existe sería peor que dejarla
     * fuera del sitemap: sería decirle a Google «la buena es aquélla» y que
     * aquélla diera 404.
     */
    public function test_la_huerfana_se_apunta_a_si_misma(): void
    {
        $this->get('/repuesto/'.$this->huerfana->slug)
            ->assertOk()
            ->assertSee('rel="canonical" href="'.route('producto', $this->huerfana).'"', false);
    }
}
