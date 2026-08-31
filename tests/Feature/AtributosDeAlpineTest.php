<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Que ningún atributo de Alpine se cierre a media expresión.
 *
 * Esta prueba existe porque el fallo pasó dos veces en la misma tarde: basta
 * escribir una comilla doble dentro de `x-data="{ … }"` —en un COMENTARIO,
 * incluso— para que el navegador cierre el atributo ahí. Lo que queda no es un
 * error visible: es JavaScript crudo impreso como texto en lo alto de la
 * página, la cabecera entera muerta y, con ella, el selector de vehículo, el
 * menú móvil, el contador del carrito y el buscador. En un sitio cuyo problema
 * es que lo suplantan, esa portada es exactamente la peor carta de
 * presentación posible.
 *
 * Se comprueba pidiendo la portada y contando comillas dentro del atributo,
 * que es como lo ve el navegador, no como se lee en el editor.
 */
class AtributosDeAlpineTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_x_data_de_la_cabecera_llega_entero(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<header[^>]*\sx-data="\{/',
            $html,
            'La cabecera perdió su `x-data`.'
        );

        // Desde `x-data="` hasta la primera comilla doble que lo cierra.
        $desde = strpos($html, 'x-data="');
        $expresion = substr($html, $desde + 8, strpos($html, '"', $desde + 8) - $desde - 8);

        $this->assertStringContainsString(
            'abrirVehiculo()',
            $expresion,
            'El `x-data` se cortó antes de tiempo: hay una comilla doble dentro de la expresión.'
        );
        $this->assertStringContainsString('atraparFoco(evento)', $expresion);
        $this->assertStringContainsString('enfocarModal(', $expresion);

        // Y cierra donde debe.
        $this->assertSame(
            substr_count($expresion, '{'),
            substr_count($expresion, '}'),
            'El objeto del `x-data` quedó a medias.'
        );
    }

    /** El diálogo del buscador tiene que tener nombre, y un solo id por página. */
    public function test_el_dialogo_del_buscador_tiene_nombre_y_sin_ids_repetidos(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-labelledby="modal-titulo"', $html);
        $this->assertSame(1, substr_count($html, 'id="modal-titulo"'));
        $this->assertSame(1, substr_count($html, 'id="hero-titulo"'));
    }
}
