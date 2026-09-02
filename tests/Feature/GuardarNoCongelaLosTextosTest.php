<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Configuracion;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Guardar sin cambiar un texto tiene que dejarlo «sin definir».
 *
 * «Sin definir» —`valor` en nulo— no es un detalle interno: es lo que permite
 * que varios textos se CALCULEN. El horario que se lee en «Contáctenos» se
 * arma desde el rango de «Datos y correos», y el párrafo de «Quiénes somos»
 * intercala la dirección del panel.
 *
 * En cuanto la fila deja de estar en nulo, ese cálculo se congela para
 * siempre. Y como el formulario manda las 75 claves cada vez, abrir la
 * pantalla y pulsar Guardar pasaba 69 filas a «definido» de un plumazo: a
 * partir de ahí el dueño podía cambiar su dirección en el panel y la web
 * seguiría enseñando la vieja, sin que nada explicara por qué.
 */
class GuardarNoCongelaLosTextosTest extends TestCase
{
    use RefreshDatabase;

    private function abrirYGuardarTalCual(): void
    {
        $html = $this->entrarComo($this->usuario(Rol::Admin))
            ->get(route('panel.pagina'))->assertOk()->getContent();

        $textos = [];

        preg_match_all('/<input[^>]*name="textos\[(\d+)\]"[^>]*value="([^"]*)"/', $html, $entradas, PREG_SET_ORDER);
        foreach ($entradas as $e) {
            $textos[$e[1]] = html_entity_decode($e[2], ENT_QUOTES, 'UTF-8');
        }

        preg_match_all('/<textarea[^>]*name="textos\[(\d+)\]"[^>]*>(.*?)<\/textarea>/s', $html, $areas, PREG_SET_ORDER);
        foreach ($areas as $a) {
            $textos[$a[1]] = html_entity_decode($a[2], ENT_QUOTES, 'UTF-8');
        }

        $this->post(route('panel.pagina.guardar'), ['textos' => $textos])->assertRedirect();

        Cache::flush();
    }

    public function test_guardar_sin_tocar_nada_no_define_ninguna_fila(): void
    {
        $this->abrirYGuardarTalCual();

        $definidas = Contenido::whereNotNull('valor')->pluck('clave')->all();

        $this->assertSame([], $definidas, 'Guardar sin tocar nada definió: '.implode(', ', $definidas));
    }

    /**
     * La consecuencia que de verdad se nota: cambiar la dirección en «Datos y
     * correos» tiene que verse en «Quiénes somos» aunque antes se hubiera
     * pulsado Guardar en «Textos e imágenes».
     */
    public function test_la_direccion_sigue_llegando_a_quienes_somos(): void
    {
        $this->abrirYGuardarTalCual();

        Configuracion::updateOrCreate(
            ['clave' => 'direccion'],
            ['valor' => 'Calle Nueva 123', 'grupo' => 'contacto']
        );
        Cache::flush();

        $this->get(route('quienes-somos'))->assertOk()->assertSee('Calle Nueva 123');
    }

    /** Y un cambio de verdad sí se guarda. */
    public function test_un_cambio_real_si_se_guarda(): void
    {
        $this->entrarComo($this->usuario(Rol::Admin))->get(route('panel.pagina'))->assertOk();

        $fila = Contenido::where('clave', 'acceso.entrar.boton')->firstOrFail();

        $this->post(route('panel.pagina.guardar'), ['textos' => [$fila->id => 'Entrar a mi cuenta']])
            ->assertRedirect();

        Cache::flush();

        $this->assertSame('Entrar a mi cuenta', $fila->fresh()->valor);
        $this->assertSame('Entrar a mi cuenta', contenido('acceso.entrar.boton', 'X'));
    }

    /**
     * Un documento más largo de lo que cabe en la columna se rechaza con un
     * aviso, no revienta a mitad de camino dejando medio formulario escrito.
     */
    public function test_un_documento_gigante_no_guarda_a_medias(): void
    {
        $this->entrarComo($this->usuario(Rol::Admin))->get(route('panel.pagina'))->assertOk();

        $boton = Contenido::where('clave', 'acceso.entrar.boton')->firstOrFail();
        $documento = Contenido::where('clave', 'terminos.cuerpo')->firstOrFail();

        $this->post(route('panel.pagina.guardar'), ['textos' => [
            $boton->id => 'ESTO NO DEBERÍA QUEDAR',
            $documento->id => str_repeat('a', 80000),
        ]])->assertSessionHasErrors('textos.'.$documento->id);

        Cache::flush();

        $this->assertNull($boton->fresh()->valor, 'Se escribió un campo pese al error.');
        $this->assertNull($documento->fresh()->valor);
    }
}
