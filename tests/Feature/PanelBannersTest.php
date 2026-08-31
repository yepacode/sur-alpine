<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Subir una campaña al carrusel.
 *
 * Existe por un fallo que hacía la pantalla INUTILIZABLE: el orden de una
 * campaña nueva se calculaba como «el mínimo menos uno» sobre una columna sin
 * signo, así que con la primera campaña en 0 daba -1 y MySQL respondía «Out of
 * range value». Y como el formulario de alta no manda `orden`, fallaba
 * siempre: todas las subidas terminaban en un 500, dejando además en disco los
 * tres .webp que ya se habían escrito.
 */
class PanelBannersTest extends TestCase
{
    use RefreshDatabase;

    private function imagen(): UploadedFile
    {
        return UploadedFile::fake()->image('campana.jpg', 1600, 400);
    }

    protected function tearDown(): void
    {
        foreach (glob(public_path('img/banners/campana-*.webp')) ?: [] as $ruta) {
            @unlink($ruta);
        }

        parent::tearDown();
    }

    public function test_se_puede_subir_una_campana(): void
    {
        // La migración deja unas campañas de fábrica; se cuenta el salto.
        $antes = Banner::count();

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.banners.guardar'), [
                'imagen' => $this->imagen(),
                'alt' => 'Promoción de frenos',
            ])
            ->assertRedirect(route('panel.banners'))
            ->assertSessionHasNoErrors();

        $this->assertSame($antes + 1, Banner::count());
        $this->assertSame(0, Banner::where('alt', 'Promoción de frenos')->value('orden'));
    }

    /** Y la nueva queda ARRIBA, que es lo que espera quien acaba de subirla. */
    public function test_la_campana_nueva_queda_de_primera(): void
    {
        $primeraDeAntes = Banner::visibles()->first();

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.banners.guardar'), [
                'imagen' => $this->imagen(),
                'alt' => 'Nueva',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nueva', Banner::visibles()->first()->alt);

        if ($primeraDeAntes) {
            $this->assertGreaterThan(0, $primeraDeAntes->fresh()->orden, 'Las de antes tenían que correrse para hacer sitio.');
        }
    }

    /** Si el administrador escribe un orden, manda el suyo y nadie se mueve. */
    public function test_un_orden_escrito_a_mano_manda_y_no_mueve_a_nadie(): void
    {
        $ordenesDeAntes = Banner::query()->pluck('orden', 'id')->all();

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.banners.guardar'), [
                'imagen' => $this->imagen(),
                'alt' => 'Al final',
                'orden' => 900,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(900, Banner::where('alt', 'Al final')->value('orden'));

        $ahora = Banner::whereIn('id', array_keys($ordenesDeAntes))->pluck('orden', 'id')->all();
        ksort($ordenesDeAntes);
        ksort($ahora);

        $this->assertSame($ordenesDeAntes, $ahora, 'Nadie debía moverse: el orden lo puso el administrador.');
        $this->assertSame('Al final', Banner::visibles()->get()->last()->alt);
    }
}
