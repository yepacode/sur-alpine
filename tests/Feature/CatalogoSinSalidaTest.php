<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use App\Services\VehiculoActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El catálogo con un carro puesto no puede dejar a nadie sin salida.
 *
 * Con un vehículo activo, la barra lateral pintaba «Transmisión 0» como enlace
 * vivo. Al entrar, el visitante encontraba «0 repuestos en el catálogo»
 * —falso, hay 29.272—, «no encontramos repuestos con esa búsqueda» aunque no
 * hubiera buscado nada, y un botón «Ver todo el catálogo» que volvía al mismo
 * listado SEGUÍA filtrado por su carro. La salida no sacaba.
 */
class CatalogoSinSalidaTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $aveo;

    private Categoria $frenos;

    private Categoria $transmision;

    protected function setUp(): void
    {
        parent::setUp();

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $this->aveo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1400',
            'slug' => 'chevrolet-aveo-1400', 'anio_inicio' => 2006, 'anio_fin' => 2009,
        ]);

        $this->frenos = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $this->transmision = Categoria::create(['nombre' => 'Transmisión', 'slug' => 'transmision']);

        $tipo = TipoParte::create([
            'categoria_id' => $this->frenos->id, 'nombre' => 'Bandas Freno', 'slug' => 'bandas-freno',
        ]);

        Producto::create([
            'vehiculo_id' => $this->aveo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Bandas Freno AVEO 1400 CHEVROLET', 'slug' => 'bandas-freno-aveo-1400-chevrolet',
            'publicado' => true,
        ]);

        // Transmisión existe en el catálogo pero no tiene nada para este carro.
        TipoParte::create([
            'categoria_id' => $this->transmision->id, 'nombre' => 'Cardán', 'slug' => 'cardan',
        ]);
    }

    private function conElAveo(): self
    {
        $this->withSession([]);
        app(VehiculoActivo::class);

        $this->app['session']->put('vehiculo_activo', $this->aveo->id);

        return $this;
    }

    /** Una categoría sin nada para este carro deja de ser un enlace. */
    public function test_una_categoria_en_cero_no_es_un_enlace(): void
    {
        $vista = $this->conElAveo()->get(route('catalogo'))->assertOk();

        $html = $vista->getContent();

        // El menú «Productos» de la cabecera SÍ enlaza todas las categorías, y
        // debe: ese menú es del catálogo entero, no de este carro. Lo que no
        // puede enlazarla es la barra lateral, que está contando cero.
        $this->assertSame(
            1,
            substr_count($html, 'href="'.route('categoria', $this->transmision).'"'),
            'La barra lateral sigue enlazando una categoría vacía.'
        );

        // La lateral se pinta dos veces —plegada en móvil y abierta en
        // escritorio—, así que la entrada muerta aparece dos veces.
        $this->assertSame(2, substr_count($html, 'No manejamos piezas de este sistema para el vehículo seleccionado'));

        $vista->assertSee(route('categoria', $this->frenos), false);
    }

    /**
     * Y si aun así se llega a una sección vacía, el mensaje nombra el carro y
     * ofrece quitar el filtro: la acción que de verdad resuelve.
     */
    public function test_el_vacio_nombra_el_carro_y_ofrece_quitar_el_filtro(): void
    {
        $vista = $this->conElAveo()->get(route('categoria', $this->transmision))->assertOk();

        $vista->assertSee('Para tu CHEVROLET AVEO 1400', false)
            ->assertSee('Quitar el filtro de mi carro')
            ->assertSee(route('vehiculo.olvidar'), false)
            ->assertDontSee('No encontramos repuestos con esa búsqueda');
    }

    /** Sin carro puesto, el vacío de siempre: es el que corresponde. */
    public function test_sin_carro_el_vacio_habla_de_la_busqueda(): void
    {
        $this->get(route('catalogo', ['q' => 'zzzqqq']))
            ->assertOk()
            ->assertSee('No encontramos repuestos con esa búsqueda')
            ->assertDontSee('Quitar el filtro de mi carro');
    }

    /** El h1 dice lo que se está viendo, no «Todos los repuestos». */
    public function test_el_titulo_nombra_el_carro_activo(): void
    {
        $this->conElAveo()->get(route('catalogo'))
            ->assertOk()
            ->assertSee('para tu CHEVROLET AVEO 1400', false)
            ->assertSee('que le sirven');
    }
}
