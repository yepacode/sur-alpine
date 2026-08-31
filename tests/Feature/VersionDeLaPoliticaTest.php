<?php

namespace Tests\Feature;

use App\Models\Contenido;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Subir la versión de la política tiene que volver a pedir el consentimiento.
 *
 * Antes no: el panel guardaba `politica.version` y eso sólo cambiaba el número
 * impreso en la página, mientras el consentimiento de cada persona se sellaba
 * contra `config('habeas.version')`, o sea contra el `.env`. El dueño subía una
 * política nueva, ponía «2», la web decía «Versión 2» y nadie volvía a aceptar
 * nada. Ante la SIC eso es un consentimiento desactualizado con la evidencia
 * diciendo lo contrario.
 */
class VersionDeLaPoliticaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_version_que_manda_es_la_del_panel(): void
    {
        config(['habeas.version' => '1']);

        $this->assertSame('1', version_habeas());

        Contenido::updateOrCreate(['clave' => 'politica.version'], ['valor' => '3', 'grupo' => 'politica', 'rotulo' => 'Versión del documento', 'tipo' => 'texto']);
        Cache::flush();

        $this->assertSame('3', version_habeas());
    }

    /** Y un campo vacío no deja el sitio sin versión: cae en la del despliegue. */
    public function test_vaciarla_en_el_panel_no_la_deja_en_blanco(): void
    {
        config(['habeas.version' => '1']);

        Contenido::updateOrCreate(['clave' => 'politica.version'], ['valor' => '', 'grupo' => 'politica', 'rotulo' => 'Versión del documento', 'tipo' => 'texto']);
        Cache::flush();

        $this->assertSame('1', version_habeas());
    }

    /** Quien aceptó la versión vieja vuelve a aceptar al cotizar. */
    public function test_al_subirla_se_vuelve_a_sellar_el_consentimiento(): void
    {
        config(['habeas.version' => '1']);

        $usuario = $this->usuario(\App\Enums\Rol::Cliente);
        $usuario->forceFill(['acepto_en' => now()->subYear(), 'politica_version' => '1'])->save();

        Contenido::updateOrCreate(['clave' => 'politica.version'], ['valor' => '2', 'grupo' => 'politica', 'rotulo' => 'Versión del documento', 'tipo' => 'texto']);
        Cache::flush();

        $this->assertNotSame($usuario->politica_version, version_habeas());
    }
}
