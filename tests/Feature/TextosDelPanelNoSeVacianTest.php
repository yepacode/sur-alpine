<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Guardar «Textos e imágenes» sin tocar nada no puede cambiar nada.
 *
 * Es la prueba que faltaba y que habría cazado el peor fallo de todos: la
 * casilla del panel mostraba el valor GUARDADO, no el que se ve en la web. Un
 * texto nunca editado tiene `valor = null` —el sitio enseña el de fábrica— y
 * su casilla salía vacía; al pulsar Guardar, esa casilla vacía se guardaba
 * como «bórralo a propósito».
 *
 * O sea que abrir la pantalla y guardar vaciaba de golpe todos los textos que
 * nadie había tocado nunca. En producción dejó sin rótulo el botón de
 * «Iniciar sesión» y el de BUSCAR del selector de vehículo.
 */
class TextosDelPanelNoSeVacianTest extends TestCase
{
    use RefreshDatabase;

    private function abrirYGuardar(): void
    {
        $admin = $this->usuario(Rol::Admin);

        // Se abre la pantalla y se reenvía TAL CUAL lo que trae, que es lo que
        // hace una persona que entra a mirar y pulsa Guardar.
        $html = $this->entrarComo($admin)->get(route('panel.pagina'))->assertOk()->getContent();

        $textos = [];

        // Los `input` traen su contenido en `value`...
        preg_match_all('/<input[^>]*name="textos\[(\d+)\]"[^>]*value="([^"]*)"/', $html, $entradas, PREG_SET_ORDER);

        foreach ($entradas as $e) {
            $textos[$e[1]] = html_entity_decode($e[2], ENT_QUOTES, 'UTF-8');
        }

        // ...y los `textarea` entre sus etiquetas. Leer sólo `value` mandaba
        // vacíos todos los párrafos, que es justo lo que esta prueba busca
        // detectar: hay que enviar lo mismo que enviaría un navegador.
        preg_match_all('/<textarea[^>]*name="textos\[(\d+)\]"[^>]*>(.*?)<\/textarea>/s', $html, $areas, PREG_SET_ORDER);

        foreach ($areas as $a) {
            $textos[$a[1]] = html_entity_decode($a[2], ENT_QUOTES, 'UTF-8');
        }

        $this->assertNotEmpty($textos, 'La pantalla no trae ningún campo de texto.');

        $this->post(route('panel.pagina.guardar'), ['textos' => $textos])->assertRedirect();

        Cache::flush();
    }

    /**
     * La comprobación de fondo: el SITIO tiene que quedar byte a byte igual.
     *
     * Se compara el HTML antes y después, no lo que devuelve el helper con un
     * valor por defecto inventado aquí: eso mediría la prueba, no la web.
     */
    public function test_guardar_sin_tocar_nada_deja_el_sitio_igual(): void
    {
        $paginas = ['/', route('acceso'), route('quienes-somos'), route('cotizacion.ver')];

        $sinLoQueCambiaSolo = fn (string $html) => preg_replace(
            ['/name="_token" value="[^"]*"/', '/<script[^>]*>.*?<\/script>/s'],
            '',
            $html
        );

        $antes = [];
        foreach ($paginas as $url) {
            $antes[$url] = $sinLoQueCambiaSolo($this->get($url)->assertOk()->getContent());
        }

        $this->abrirYGuardar();
        \Illuminate\Support\Facades\Auth::logout();
        $this->flushSession();

        foreach ($paginas as $url) {
            $this->assertSame(
                $antes[$url],
                $sinLoQueCambiaSolo($this->get($url)->assertOk()->getContent()),
                "Guardar «Textos e imágenes» sin tocar nada cambió {$url}."
            );
        }
    }

    /** Y el sitio sigue enseñando los rótulos, que es lo que se vio roto. */
    public function test_los_botones_del_sitio_siguen_con_su_texto(): void
    {
        $this->abrirYGuardar();

        // Se sale: con sesión abierta, /acceso redirige y no habría nada que mirar.
        \Illuminate\Support\Facades\Auth::logout();
        $this->flushSession();

        $acceso = $this->get(route('acceso'))->assertOk()->getContent();

        $this->assertSame(
            0,
            preg_match_all('/<button[^>]*>\s*<\/button>/', $acceso),
            'Quedó un botón sin rótulo en la página de acceso.'
        );

        $portada = $this->get('/')->assertOk()->getContent();

        $this->assertSame(
            0,
            preg_match_all('/<button[^>]*>\s*<\/button>/', $portada),
            'Quedaron botones sin rótulo en la portada.'
        );
    }

    /** Vaciar a propósito SÍ tiene que seguir funcionando. */
    public function test_vaciar_una_casilla_a_proposito_sigue_vaciando(): void
    {
        // Abrir la pantalla es lo que crea las filas de `contenidos`.
        $this->entrarComo($this->usuario(Rol::Admin))->get(route('panel.pagina'))->assertOk();

        $fila = Contenido::where('clave', 'acceso.entrar.boton')->firstOrFail();

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.pagina.guardar'), ['textos' => [$fila->id => '']])
            ->assertRedirect();

        Cache::flush();

        $this->assertSame('', contenido('acceso.entrar.boton', 'Entrar'));
    }

    /** La migración devuelve su texto a lo que se vació sin querer. */
    public function test_la_migracion_recupera_lo_vaciado(): void
    {
        $this->entrarComo($this->usuario(Rol::Admin))->get(route('panel.pagina'))->assertOk();

        $fila = Contenido::where('clave', 'buscador.boton')->firstOrFail();
        $fabrica = $fila->valor_ejemplo;

        $fila->forceFill(['valor' => ''])->save();
        Cache::flush();
        $this->assertSame('', contenido('buscador.boton', 'X'));

        (require database_path('migrations/2026_09_02_100000_recuperar_textos_vaciados.php'))->up();
        Cache::flush();

        $this->assertSame($fabrica, contenido('buscador.boton', 'X'));
    }
}
