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
 * Una dirección por página, con o sin parámetros de más.
 *
 * Dos defectos distintos con la misma consecuencia, y es la que le importa a
 * este cliente: cada URL de más que se declara «la original» es una posición
 * que le regala a quien lo está suplantando.
 */
class CanonicalDelCatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $marca = Marca::create(['nombre' => 'RENAULT', 'slug' => 'renault']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => '12', 'slug' => '12']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1300',
            'slug' => 'renault-12-1300', 'anio_inicio' => 1980, 'anio_fin' => 1990,
        ]);
        $categoria = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);

        // Un tipo de parte por pieza: `productos` tiene índice único sobre
        // (vehiculo_id, tipo_parte_id), que es justo lo que impide que un
        // mismo carro lleve dos veces la misma pieza.
        foreach (range(1, 30) as $i) {
            $tipo = TipoParte::create([
                'categoria_id' => $categoria->id, 'nombre' => "Pieza {$i}", 'slug' => "pieza-{$i}",
            ]);

            Producto::create([
                'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
                'nombre' => "Pieza {$i} 12 1300 RENAULT", 'slug' => "pieza-{$i}-12-1300-renault",
                'publicado' => true,
            ]);
        }
    }

    /**
     * Un parámetro cualquiera no puede fabricar una copia que se declare la
     * original. Basta con enlazar `?x=1`, `?x=2`, `?x=3`… para inventar
     * catálogos enteros: es la misma puerta que cierra el 301 de mayúsculas.
     */
    public function test_un_parametro_desconocido_no_crea_una_copia(): void
    {
        $vista = $this->get('/repuestos?utm_source=facebook')->assertOk();

        $vista->assertSee('rel="canonical" href="'.route('catalogo').'"', false)
            ->assertDontSee('utm_source', false)
            ->assertSee('content="noindex,follow"', false);
    }

    /** Y con paginación, conserva la página pero no la basura. */
    public function test_conserva_la_pagina_y_descarta_lo_demas(): void
    {
        $this->get('/repuestos?utm_source=fb&page=2')
            ->assertOk()
            ->assertSee('rel="canonical" href="'.route('catalogo').'?page=2"', false)
            ->assertDontSee('utm_source', false);
    }

    /**
     * El `&` sale escapado UNA vez, no dos.
     *
     * `@section` ya escapa y `{{ }}` volvía a escapar: la etiqueta publicaba
     * `?orden=z-a&amp;amp;page=2`, que decodifica a `&amp;page=2` — una
     * dirección distinta, con un parámetro inventado llamado `amp;page`. Y se
     * realimentaba: el `rel=next` de esa página le añadía otro encima.
     */
    public function test_el_ampersand_no_se_escapa_dos_veces(): void
    {
        $html = $this->get('/repuestos?orden=z-a&page=2')->assertOk()->getContent();

        $this->assertStringContainsString(
            'rel="canonical" href="'.route('catalogo').'?orden=z-a&amp;page=2"',
            $html
        );
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    /** Lo mismo en el título, que es donde llevaba años sin verse. */
    public function test_un_titulo_con_ampersand_sale_una_sola_vez_escapado(): void
    {
        $producto = Producto::first();
        $producto->forceFill(['nombre' => 'Piñón & Corona 12 1300 RENAULT'])->save();

        $this->get(route('producto', $producto))
            ->assertOk()
            ->assertSee('<title>Piñón &amp; Corona 12 1300 RENAULT · Importadora Sur Alpine</title>', false)
            ->assertDontSee('&amp;amp;', false);
    }

    /** La página 1 es `/repuestos`, no `/repuestos?page=1`. */
    public function test_la_primera_pagina_no_lleva_sufijo(): void
    {
        $this->get(route('catalogo'))
            ->assertSee('rel="canonical" href="'.route('catalogo').'"', false)
            ->assertDontSee('?page=1"', false);
    }
}
