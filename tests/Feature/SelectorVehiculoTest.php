<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SelectorVehiculoTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $aveo;

    private Vehiculo $logan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aveo = $this->crearVehiculo('CHEVROLET', 'AVEO', '1600', 2006, 2013);
        $this->logan = $this->crearVehiculo('RENAULT', 'LOGAN', '1600', 2010, 2015);

        $frenos = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $this->crearProducto($frenos, 'Pastillas Freno Delanteras', $this->aveo);
        $this->crearProducto($frenos, 'Bandas Freno', $this->logan);
    }

    private function crearVehiculo(string $marca, string $modelo, string $cc, int $desde, int $hasta): Vehiculo
    {
        $m = Marca::firstOrCreate(['slug' => Str::slug($marca)], ['nombre' => $marca]);
        $mo = Modelo::firstOrCreate(
            ['marca_id' => $m->id, 'slug' => Str::slug($modelo)],
            ['nombre' => $modelo]
        );

        return Vehiculo::create([
            'modelo_id' => $mo->id, 'cilindraje' => $cc,
            'anio_inicio' => $desde, 'anio_fin' => $hasta,
            'slug' => Str::slug("{$marca}-{$modelo}-{$cc}-{$desde}-{$hasta}"),
        ]);
    }

    private function crearProducto(Categoria $categoria, string $parte, Vehiculo $vehiculo): Producto
    {
        $tipo = TipoParte::firstOrCreate(
            ['categoria_id' => $categoria->id, 'slug' => Str::slug($parte)],
            ['nombre' => $parte]
        );

        $nombre = $parte.' '.$vehiculo->slug;

        return Producto::create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_parte_id' => $tipo->id,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
        ]);
    }

    public function test_el_arbol_trae_todos_los_vehiculos_en_una_sola_respuesta(): void
    {
        $this->get('/vehiculos.json')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['ma' => 'CHEVROLET', 'mo' => 'AVEO', 'c' => '1600', 'd' => 2006, 'h' => 2013]);
    }

    public function test_guardar_el_vehiculo_lo_deja_en_la_sesion(): void
    {
        $this->from('/')
            ->post('/mi-vehiculo', ['vehiculo_id' => $this->aveo->id])
            ->assertRedirect('/')
            ->assertSessionHas('vehiculo_activo', $this->aveo->id);
    }

    public function test_con_vehiculo_el_catalogo_muestra_solo_sus_repuestos(): void
    {
        $this->post('/mi-vehiculo', ['vehiculo_id' => $this->aveo->id]);

        $this->get('/repuestos')
            ->assertOk()
            ->assertSee('CHEVROLET AVEO 1600 (2006-2013)')
            ->assertSee('Pastillas Freno Delanteras')
            ->assertDontSee('Bandas Freno');
    }

    /**
     * El sitio anterior anunciaba "182 productos encontrados" y pintaba tres.
     * El contador del filtro lateral tiene que respetar el mismo filtro.
     */
    public function test_los_contadores_respetan_el_vehiculo_elegido(): void
    {
        $this->post('/mi-vehiculo', ['vehiculo_id' => $this->aveo->id]);

        $categorias = $this->get('/repuestos')->viewData('categorias');

        $this->assertSame(1, $categorias->firstWhere('slug', 'frenos')->productos_count);
    }

    public function test_quitar_el_vehiculo_devuelve_el_catalogo_completo(): void
    {
        $this->post('/mi-vehiculo', ['vehiculo_id' => $this->aveo->id]);

        $this->post('/mi-vehiculo/quitar')->assertSessionMissing('vehiculo_activo');

        $this->get('/repuestos')
            ->assertOk()
            ->assertSee('Pastillas Freno Delanteras')
            ->assertSee('Bandas Freno');
    }

    public function test_un_vehiculo_inexistente_no_se_guarda(): void
    {
        $this->from('/')
            ->post('/mi-vehiculo', ['vehiculo_id' => 99999])
            ->assertSessionHasErrors('vehiculo_id');

        $this->assertNull(session('vehiculo_activo'));
    }

    /**
     * Si una reimportación borra el vehículo, la sesión no puede dejar al
     * visitante filtrando contra algo que ya no existe.
     */
    public function test_si_el_vehiculo_desaparece_la_sesion_se_limpia_sola(): void
    {
        $this->post('/mi-vehiculo', ['vehiculo_id' => $this->aveo->id]);

        $this->aveo->delete();

        $this->get('/repuestos')
            ->assertOk()
            ->assertSee('Bandas Freno');
    }

    public function test_las_sugerencias_necesitan_al_menos_tres_letras(): void
    {
        $this->getJson('/sugerencias?q=pa')->assertOk()->assertJsonCount(0);
        $this->getJson('/sugerencias?q=pastillas')->assertOk()->assertJsonCount(1);
    }
}
