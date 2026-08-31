<?php

namespace Tests\Feature;

use App\Http\Controllers\Panel\ConfiguracionPaginaController;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * «Textos e imágenes» no puede costar una consulta por campo.
 *
 * La pantalla crea las filas que falten para los campos que declara el
 * controlador, y lo hacía con un `firstOrCreate` por cada uno: 73 sondeos a
 * `contenidos` más 9 a `seo_paginas` en CADA carga, todos para descubrir lo
 * mismo —que ya estaban todas—. Era, y por mucho, la única página del panel
 * que pasaba de once consultas.
 *
 * Se prueba llamando al método directamente y contando: por HTTP habría que
 * pintar dieciséis tarjetas con sus cuarenta campos de SEO cada vez, y lo que
 * se quiere medir no es eso.
 */
class ConsultasDelPanelTest extends TestCase
{
    use RefreshDatabase;

    private function sincronizarContando(): int
    {
        $controlador = app(ConfiguracionPaginaController::class);
        $metodo = new \ReflectionMethod($controlador, 'sincronizar');
        $metodo->setAccessible(true);

        $consultas = 0;
        DB::listen(function () use (&$consultas) {
            $consultas++;
        });

        $metodo->invoke($controlador);

        DB::flushQueryLog();

        return $consultas;
    }

    public function test_no_sondea_campo_por_campo(): void
    {
        // Primera vez: crea lo que falte.
        $primera = $this->sincronizarContando();

        $this->assertGreaterThan(50, Contenido::count(), 'No creó los campos.');

        // Segunda: no falta nada, así que sólo debería mirar qué hay.
        $segunda = $this->sincronizarContando();

        $this->assertLessThanOrEqual(
            4,
            $segunda,
            "Hizo {$segunda} consultas sin nada que crear: está sondeando campo por campo."
        );

        $this->assertLessThanOrEqual(
            8,
            $primera,
            "Hizo {$primera} consultas para crearlos: tendría que bastar con leer y escribir en bloque."
        );
    }

    /** Y si falta uno solo, lo crea sin tocar los demás. */
    public function test_crea_el_campo_que_falte(): void
    {
        $this->sincronizarContando();

        $borrado = Contenido::where('clave', 'buscador.titulo')->firstOrFail();
        $total = Contenido::count();
        $borrado->delete();

        $this->sincronizarContando();

        $this->assertSame($total, Contenido::count());
        $this->assertNotNull(Contenido::where('clave', 'buscador.titulo')->first());
    }
}
