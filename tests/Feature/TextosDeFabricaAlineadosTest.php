<?php

namespace Tests\Feature;

use App\Http\Controllers\Panel\ConfiguracionPaginaController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El texto que declara el panel tiene que ser el que pinta la vista.
 *
 * Cada texto editable vive escrito DOS veces: como respaldo en la vista
 * —`contenido('clave', 'este texto')`— y como valor de fábrica en
 * `secciones()`. Dos copias de lo mismo se desincronizan solas, y aquí
 * se desincronizaron cinco.
 *
 * Cuando eso pasa, la casilla del panel le enseña al dueño un texto y la web
 * muestra otro: edita creyendo que corrige una cosa y cambia otra. El caso
 * peor fue `quienes.texto`, declarado como cadena vacía, que borró la
 * historia de la empresa de «Quiénes somos» en cuanto alguien abrió la
 * pantalla y guardó.
 *
 * Esta prueba compara las dos copias y falla si se separan.
 */
class TextosDeFabricaAlineadosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Claves cuyo respaldo en la vista NO es un literal, sino algo que se
     * calcula: el horario se arma desde «Datos y correos», el párrafo de la
     * empresa intercala la dirección, los respaldos usan clave dinámica y las
     * fechas salen de `config/habeas.php`.
     *
     * Están aquí a propósito y con nombre y apellido: si alguien añade una
     * clave nueva que tampoco se puede comparar, tiene que venir a escribirla
     * en esta lista y explicarse. Es la parte que hace que la excepción no se
     * convierta en la regla.
     *
     * @var list<string>
     */
    private const CALCULADAS = [
        'contacto.horario.semana',
        'contacto.horario.sabado',
        'contacto.horario.festivo',
        'quienes.texto',
        'politica.version',
        'politica.vigencia',
        'politica.cuerpo',
        'terminos.vigencia',
        'terminos.cuerpo',
        'respaldo.1.titulo', 'respaldo.1.texto',
        'respaldo.2.titulo', 'respaldo.2.texto',
        'respaldo.3.titulo', 'respaldo.3.texto',
    ];

    /** @return array<string, string> clave => texto de respaldo escrito en la vista */
    private function respaldosDeLasVistas(): array
    {
        $barra = chr(92);
        $patron = "/contenido\(\s*'([^']+)'\s*,\s*'((?:[^'{$barra}{$barra}]|{$barra}{$barra}.)*)'\s*\)/";

        $respaldos = [];

        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(resource_path('views'))
            ) as $archivo
        ) {
            if (! str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all($patron, file_get_contents($archivo->getPathname()), $hallados, PREG_SET_ORDER);

            foreach ($hallados as $h) {
                $respaldos[$h[1]] = str_replace($barra."'", "'", $h[2]);
            }
        }

        return $respaldos;
    }

    public function test_el_panel_declara_lo_mismo_que_pinta_la_vista(): void
    {
        $declarados = [];

        foreach (app(ConfiguracionPaginaController::class)->secciones() as $seccion) {
            foreach ($seccion['textos'] as $texto) {
                $declarados[$texto['clave']] = $texto['valor'];
            }
        }

        $respaldos = $this->respaldosDeLasVistas();
        $desalineados = [];

        foreach ($declarados as $clave => $valor) {
            if (in_array($clave, self::CALCULADAS, true) || ! isset($respaldos[$clave])) {
                continue;
            }

            if ($respaldos[$clave] !== $valor) {
                $desalineados[] = sprintf(
                    "  %s\n     la vista pinta : %s\n     el panel dice  : %s",
                    $clave,
                    var_export($respaldos[$clave], true),
                    var_export($valor, true)
                );
            }
        }

        $this->assertSame([], $desalineados, "El panel y la vista dicen cosas distintas:\n".implode("\n", $desalineados));
    }

    /**
     * Y ningún texto de fábrica puede declararse vacío.
     *
     * Un vacío ahí no es «no hay texto»: es lo que la casilla del panel le
     * enseña al dueño, y desde que se declaró vacío `quienes.texto` la
     * historia de la empresa desapareció del sitio.
     */
    public function test_ningun_texto_de_fabrica_esta_vacio(): void
    {
        $vacios = [];

        foreach (app(ConfiguracionPaginaController::class)->secciones() as $seccion) {
            foreach ($seccion['textos'] as $texto) {
                // Las imágenes y los documentos legales sí nacen vacíos: la foto
                // de fábrica vive en la carpeta y el documento en su blade.
                if (in_array($texto['tipo'], ['imagen', 'documento'], true)) {
                    continue;
                }

                if (trim((string) $texto['valor']) === '') {
                    $vacios[] = $texto['clave'];
                }
            }
        }

        $this->assertSame([], $vacios, 'Estos textos de fábrica están vacíos: '.implode(', ', $vacios));
    }

    /**
     * Y las filas nacen en NULO, no con el texto escrito.
     *
     * Escribirlo convertía la declaración en el contenido real del sitio en
     * cuanto alguien abría la pantalla, y la web cambiaba sola.
     */
    public function test_las_filas_nacen_sin_valor_propio(): void
    {
        $this->entrarComo($this->usuario(\App\Enums\Rol::Admin))
            ->get(route('panel.pagina'))
            ->assertOk();

        $this->assertGreaterThan(50, \App\Models\Contenido::count());
        $this->assertSame(
            0,
            \App\Models\Contenido::whereNotNull('valor')->count(),
            'Alguna fila nació con valor propio: el panel está escribiendo el contenido del sitio.'
        );
    }
}
