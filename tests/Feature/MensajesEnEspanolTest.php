<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ni un mensaje de validación en inglés.
 *
 * Faltaba `lang/es/validation.php` entero, así que `APP_LOCALE=es` caía al
 * inglés del framework y sólo salían en español los mensajes escritos a mano.
 * El resultado eran listas bilingües —«The placa field is required.» encima de
 * «Escribe qué se le hizo al carro.»— y, peor, un inglés seco justo en el
 * momento de la conversión: al enviar la cotización.
 *
 * Para un cliente cuyo problema es que lo suplantan, un error en inglés es la
 * señal más barata de «esta no es la página buena».
 */
class MensajesEnEspanolTest extends TestCase
{
    use RefreshDatabase;

    /** El peor sitio donde podía pasar: el envío de la solicitud. */
    public function test_el_envio_de_la_cotizacion_avisa_en_espanol(): void
    {
        $usuario = $this->usuario(\App\Enums\Rol::Cliente);

        $marca = \App\Models\Marca::create(['nombre' => 'RENAULT', 'slug' => 'renault']);
        $modelo = \App\Models\Modelo::create(['marca_id' => $marca->id, 'nombre' => '12', 'slug' => '12']);
        $vehiculo = \App\Models\Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1300',
            'slug' => 'renault-12-1300', 'anio_inicio' => 1980, 'anio_fin' => 1990,
        ]);
        $categoria = \App\Models\Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $tipo = \App\Models\TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Bujía', 'slug' => 'bujia',
        ]);
        $producto = Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Bujía 12 1300 RENAULT', 'slug' => 'bujia-12-1300-renault',
            'publicado' => true,
        ]);

        $this->entrarComo($usuario);
        $this->post(route('cotizacion.agregar', $producto));

        $this->post(route('cotizacion.enviar'), ['nombre' => 'Ana'])
            ->assertSessionHasErrors(['telefono', 'email']);

        $errores = session('errors');

        $this->assertSame('Falta el teléfono.', $errores->first('telefono'));
        $this->assertSame('Falta el correo.', $errores->first('email'));
    }

    /** Y el nombre del campo es el rótulo humano, no la clave interna. */
    public function test_los_campos_se_nombran_como_en_pantalla(): void
    {
        $usuario = $this->usuario(\App\Enums\Rol::Cliente);

        $this->entrarComo($usuario)
            ->post(route('cuenta.mantenimientos.guardar'), [
                'tipo' => 'Cambio de aceite',
                'fecha' => today()->toDateString(),
                'kilometraje' => 'no es un número',
                'periodicidad_tipo' => 'meses',
                'periodicidad_valor' => 6,
            ])
            ->assertSessionHasErrors(['placa', 'kilometraje']);

        $errores = session('errors');

        $this->assertSame('Falta la placa.', $errores->first('placa'));
        $this->assertStringContainsString('kilometraje', $errores->first('kilometraje'));
        $this->assertStringNotContainsString('field', $errores->first('kilometraje'));
    }

    /** Nada de `lang/en`: si vuelve, el respaldo al inglés vuelve con él. */
    public function test_no_hay_traducciones_en_ingles_de_respaldo(): void
    {
        $this->assertDirectoryDoesNotExist(base_path('lang/en'));
        $this->assertFileExists(base_path('lang/es/validation.php'));
    }
}
