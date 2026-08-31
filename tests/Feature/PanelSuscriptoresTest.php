<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Suscriptor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La baja de un suscriptor, desde el panel.
 *
 * La supresión es una obligación del responsable del tratamiento —Ley 1581, y
 * el plazo corre desde que la persona la pide—. La pantalla decía «escríbenos
 * y lo marcamos», o sea que el dueño dependía de una llamada a la agencia para
 * cumplir algo que le exigen a él.
 */
class PanelSuscriptoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_da_de_baja_y_queda_la_fecha(): void
    {
        $suscriptor = Suscriptor::create(['correo' => 'ana@ejemplo.test', 'origen' => 'pie']);

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.suscriptores.baja', $suscriptor))
            ->assertRedirect();

        $this->assertNotNull($suscriptor->fresh()->baja_en);
    }

    /** No se borra la fila: si mañana se vuelve a suscribir, tiene que constar todo. */
    public function test_la_fila_no_se_borra(): void
    {
        $suscriptor = Suscriptor::create(['correo' => 'beto@ejemplo.test', 'origen' => 'pie']);

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.suscriptores.baja', $suscriptor));

        $this->assertDatabaseHas('suscriptores', ['correo' => 'beto@ejemplo.test']);
    }

    public function test_un_cliente_no_puede_dar_de_baja_a_nadie(): void
    {
        $suscriptor = Suscriptor::create(['correo' => 'ana@ejemplo.test', 'origen' => 'pie']);

        $this->entrarComo($this->usuario(Rol::Cliente))
            ->post(route('panel.suscriptores.baja', $suscriptor))
            ->assertForbidden();

        $this->assertNull($suscriptor->fresh()->baja_en);
    }
}
