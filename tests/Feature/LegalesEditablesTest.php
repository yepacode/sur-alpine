<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * La política de datos y los términos se editan desde el panel.
 *
 * Lo pidió el cliente: son textos que redacta un abogado y que cambian sin
 * avisar, y estaban clavados en el código —para cambiarles una coma había que
 * llamarnos—. Sólo eran editables la versión y la fecha.
 */
class LegalesEditablesTest extends TestCase
{
    use RefreshDatabase;

    private function escribir(string $clave, string $texto): void
    {
        $this->entrarComo($this->usuario(Rol::Admin))->get(route('panel.pagina'))->assertOk();

        $fila = Contenido::where('clave', $clave)->firstOrFail();

        $this->post(route('panel.pagina.guardar'), ['textos' => [$fila->id => $texto]])
            ->assertRedirect();

        Cache::flush();
    }

    public function test_el_cuerpo_de_la_politica_se_puede_reemplazar(): void
    {
        $this->escribir('politica.cuerpo', "## Nuestro compromiso\nTratamos tus datos con cuidado.\n\n- No los vendemos\n- No los compartimos");

        $this->get(route('politica-datos'))
            ->assertOk()
            ->assertSee('Nuestro compromiso')
            ->assertSee('Tratamos tus datos con cuidado.')
            ->assertSee('No los vendemos')
            // Y el de fábrica ya no sale: lo reemplaza, no lo acompaña.
            ->assertDontSee('Ley Estatutaria 1581 de 2012', false);
    }

    public function test_el_cuerpo_de_los_terminos_tambien(): void
    {
        $this->escribir('terminos.cuerpo', "## Uso del sitio\nEste sitio es un catálogo de consulta.");

        $this->get(route('terminos'))
            ->assertOk()
            ->assertSee('Uso del sitio')
            ->assertSee('Este sitio es un catálogo de consulta.')
            ->assertDontSee('INDUSTRIA COLOMBIANA DE AUTOPARTES');
    }

    /** Vacío, se sigue mostrando el documento que trae el sitio. */
    public function test_sin_texto_propio_se_muestra_el_de_fabrica(): void
    {
        $this->get(route('politica-datos'))->assertOk()->assertSee('Ley Estatutaria 1581 de 2012', false);
        $this->get(route('terminos'))->assertOk()->assertSee('INDUSTRIA COLOMBIANA DE AUTOPARTES');
    }

    /**
     * Un documento pegado desde un Word no puede meter HTML en una página
     * pública. Se escapa todo y sólo después se le da forma.
     */
    public function test_no_se_puede_colar_html(): void
    {
        $this->escribir('politica.cuerpo', '## Aviso'."\n".'Texto con <script>alert(1)</script> y <img src=x onerror=alert(1)> dentro.');

        $html = $this->get(route('politica-datos'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
