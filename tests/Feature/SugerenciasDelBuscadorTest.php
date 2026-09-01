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
 * El autocompletado no puede quedarse callado.
 *
 * Las sugerencias respetan el vehículo activo, igual que el catálogo, y eso
 * está bien. Lo que estaba mal era el silencio: con un carro de catálogo corto
 * puesto, escribir «freno» no producía absolutamente nada —la lista ni
 * siquiera se abría— y la persona se iba pensando que el buscador está roto.
 *
 * Salió probando el sitio de producción con un CHINOS DFM, que tiene tres
 * piezas: es justo lo que iba a reportar el cliente al probarlo.
 */
class SugerenciasDelBuscadorTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $carroCorto;

    protected function setUp(): void
    {
        parent::setUp();

        $marca = Marca::create(['nombre' => 'RENAULT', 'slug' => 'renault']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => '19', 'slug' => '19']);

        $conFrenos = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1400',
            'slug' => 'renault-19-1400', 'anio_inicio' => 1990, 'anio_fin' => 1999,
        ]);

        $this->carroCorto = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1700',
            'slug' => 'renault-19-1700', 'anio_inicio' => 1990, 'anio_fin' => 1999,
        ]);

        $categoria = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $bandas = TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Bandas Freno', 'slug' => 'bandas-freno',
        ]);
        $aceite = TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Aceite', 'slug' => 'aceite',
        ]);

        Producto::create([
            'vehiculo_id' => $conFrenos->id, 'tipo_parte_id' => $bandas->id,
            'nombre' => 'Bandas Freno 19 1400 RENAULT', 'slug' => 'bandas-freno-19-1400-renault',
            'publicado' => true,
        ]);

        // El carro corto no tiene frenos: sólo aceite.
        Producto::create([
            'vehiculo_id' => $this->carroCorto->id, 'tipo_parte_id' => $aceite->id,
            'nombre' => 'Aceite 19 1700 RENAULT', 'slug' => 'aceite-19-1700-renault',
            'publicado' => true,
        ]);
    }

    public function test_sin_carro_sugiere_lo_que_hay(): void
    {
        $this->getJson(route('sugerencias', ['q' => 'freno']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['t' => 'Bandas Freno 19 1400 RENAULT']);
    }

    /** Con el carro puesto y sin resultados, se dice por qué y a dónde ir. */
    public function test_con_un_carro_sin_esa_pieza_explica_la_causa(): void
    {
        $this->withSession(['vehiculo_activo' => $this->carroCorto->id]);

        $respuesta = $this->getJson(route('sugerencias', ['q' => 'freno']))
            ->assertOk()
            ->assertJsonCount(1);

        $fila = $respuesta->json()[0];

        $this->assertStringContainsString('Nada para tu', $fila['t']);
        $this->assertStringContainsString('RENAULT 19 1700', $fila['t']);
        $this->assertStringContainsString('q=freno', $fila['u']);
    }

    /** Y si el carro SÍ tiene la pieza, sugiere la pieza y no el aviso. */
    public function test_con_un_carro_que_si_la_tiene_sugiere_la_pieza(): void
    {
        $this->withSession(['vehiculo_activo' => $this->carroCorto->id]);

        $this->getJson(route('sugerencias', ['q' => 'aceite']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['t' => 'Aceite 19 1700 RENAULT']);
    }

    /** Con menos de tres letras no se consulta nada. */
    public function test_con_dos_letras_no_dice_nada(): void
    {
        $this->getJson(route('sugerencias', ['q' => 'fr']))->assertOk()->assertJsonCount(0);
    }
}
