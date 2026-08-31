<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Mantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valores extremos que el propio formulario aceptaba y luego reventaban.
 *
 * Son el peor tipo de fallo para quien lo sufre: el sitio te dice que el dato
 * está bien y acto seguido te enseña una pantalla de error.
 */
class LimitesQueNoRevientanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * «Cada 999999 meses» daba el año 85.359, fuera del rango DATE de MySQL.
     */
    public function test_una_periodicidad_absurda_en_meses_se_rechaza_con_un_aviso(): void
    {
        $this->entrarComo($this->usuario(Rol::Cliente))
            ->post(route('cuenta.mantenimientos.guardar'), [
                'placa' => 'ABC123', 'tipo' => 'Cambio de aceite',
                'fecha' => today()->toDateString(), 'kilometraje' => 1000,
                'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 999999,
            ])
            ->assertSessionHasErrors('periodicidad_valor');

        $this->assertSame(0, Mantenimiento::count());
    }

    /** Pero los kilómetros no son una fecha: 60.000 es lo normal en un kit. */
    public function test_sesenta_mil_kilometros_es_un_valor_corriente(): void
    {
        $this->entrarComo($this->usuario(Rol::Cliente))
            ->post(route('cuenta.mantenimientos.guardar'), [
                'placa' => 'ABC123', 'tipo' => 'Kit de distribución',
                'fecha' => today()->toDateString(), 'kilometraje' => 48000,
                'periodicidad_tipo' => 'kilometraje', 'periodicidad_valor' => 60000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(108000, Mantenimiento::first()->proximo_kilometraje);
    }

    /**
     * El tablero con un rango de siglos.
     *
     * Construía la serie día a día desde el principio y sólo al final se
     * quedaba con los últimos 92: de 1900 a 2100 son 73.000 posiciones, y
     * `Carbon` acepta rangos mil veces mayores que se llevan por delante el
     * `memory_limit` y con él al servidor entero mientras dura.
     */
    public function test_el_tablero_aguanta_un_rango_de_dos_siglos(): void
    {
        $memoriaAntes = memory_get_usage();

        $this->entrarComo($this->usuario(Rol::Admin))
            ->get(route('panel.tablero', [
                'periodo' => 'personalizado',
                'desde' => '1900-01-01',
                'hasta' => '2100-01-01',
            ]))
            ->assertOk();

        $this->assertLessThan(
            20 * 1024 * 1024,
            memory_get_usage() - $memoriaAntes,
            'La serie del gráfico se está construyendo entera antes de recortarla.'
        );
    }
}
