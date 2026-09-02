<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La ficha de fábrica de un texto se pone al día sola.
 *
 * Una fila de `contenidos` nacía una vez y se quedaba para siempre con el
 * rótulo, el grupo y el `valor_ejemplo` del día en que se creó. Cuando el
 * texto por defecto de la vista cambiaba, el panel seguía enseñando el viejo
 * como «lo que hay».
 *
 * Y hacía daño de verdad en un sitio menos visible: `guardar()` decide si una
 * casilla se quedó «sin tocar» comparándola contra `valor_ejemplo`. Con un
 * `valor_ejemplo` caducado, esa comparación se hacía contra un texto que ya no
 * existía en ninguna parte del sitio.
 *
 * Lo que NO se toca es `valor`: eso es lo que escribió el dueño.
 */
class TextosDeFabricaSeActualizanTest extends TestCase
{
    use RefreshDatabase;

    private ?\App\Models\User $admin = null;

    /** Abrir la pantalla crea las filas que falten y pone al dia las viejas. */
    private function abrirElPanel(): void
    {
        // El MISMO administrador en las dos visitas. Con uno nuevo cada vez, la
        // segunda salia 302: quien entra es otra cuenta y la sesion no es suya.
        $this->admin ??= $this->usuario(Rol::Admin);

        $this->actingAs($this->admin)->get('/panel/pagina')->assertOk();
    }

    public function test_el_valor_de_fabrica_caducado_se_refresca(): void
    {
        $this->abrirElPanel();

        $fila = Contenido::where('clave', 'catalogo.filtro.parte')->firstOrFail();
        $deFabrica = $fila->valor_ejemplo;

        // Simulamos una fila vieja: se quedó con el texto de otra época.
        $fila->update(['valor_ejemplo' => 'Filtrar por parte', 'rotulo' => 'Rótulo viejo']);

        $this->abrirElPanel();

        $fila->refresh();

        $this->assertSame($deFabrica, $fila->valor_ejemplo,
            'El valor de fábrica tiene que volver a ser el que declara el panel.');
        $this->assertNotSame('Rótulo viejo', $fila->rotulo,
            'El rótulo que ve el administrador también se pone al día.');
    }

    public function test_lo_que_escribio_el_dueno_no_se_toca(): void
    {
        $this->abrirElPanel();

        $fila = Contenido::where('clave', 'catalogo.filtro.parte')->firstOrFail();
        $fila->update(['valor' => 'Busca por sistema', 'valor_ejemplo' => 'algo caducado']);

        $this->abrirElPanel();

        $this->assertSame('Busca por sistema', $fila->refresh()->valor,
            'Poner al día la ficha de fábrica no puede pisar lo que escribió el dueño.');
    }

    /**
     * Y lo que de verdad importaba: un texto nunca editado sigue a la vista.
     *
     * Es el caso que se rompió en producción. La migración de rescate escribió
     * `valor = valor_ejemplo` en 12 filas para devolverles su rótulo, y con eso
     * las congeló: el lateral del catálogo pasó a listar categorías y su título
     * siguió diciendo «Filtrar por parte» porque el texto estaba clavado en la
     * base.
     */
    public function test_un_texto_sin_editar_sigue_al_de_la_vista(): void
    {
        $this->abrirElPanel();

        $this->assertNull(
            Contenido::where('clave', 'catalogo.filtro.parte')->value('valor'),
            'Un texto que nadie ha editado tiene que quedarse en nulo: es lo que le deja seguir a la vista.'
        );

        $this->get('/repuestos')
            ->assertOk()
            ->assertSee(contenido('catalogo.filtro.parte', 'Categorías'), false);
    }
}
