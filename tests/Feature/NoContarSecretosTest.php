<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El sitio no puede decirle a un desconocido quién es cliente ni qué existe.
 *
 * Los dos defectos de aquí son del mismo tipo: no filtran contenido, filtran
 * la LISTA. Y para este cliente eso es lo caro: lo están suplantando, y una
 * lista confirmada de sus clientes es lo que hace creíble un correo falso
 * mandado desde uno de esos sitios copia.
 */
class NoContarSecretosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * «Olvidé mi contraseña» responde IGUAL exista o no el correo, incluso al
     * segundo intento.
     *
     * Había una excepción «por utilidad» para el caso «ya te lo mandamos». El
     * problema es que ese estado SÓLO puede ocurrir con un correo que existe
     * —Laravel devuelve «no existe» antes de mirar el límite—, así que dos
     * peticiones seguidas revelaban si esa persona tiene cuenta.
     */
    public function test_la_recuperacion_responde_igual_exista_o_no_el_correo(): void
    {
        $this->usuario(Rol::Cliente, ['email' => 'existe@ejemplo.test']);

        $respuestas = [];

        foreach (['existe@ejemplo.test', 'no-existe@ejemplo.test'] as $correo) {
            // Dos veces cada uno: la segunda es la que disparaba el aviso.
            foreach ([1, 2] as $_) {
                $this->post(route('clave.enviar'), ['email' => $correo]);
                $respuestas[$correo][] = session('mensaje');
            }
        }

        $unicos = collect($respuestas)->flatten()->unique()->values();

        $this->assertCount(1, $unicos, 'La respuesta cambia según si el correo existe: '.$unicos->implode(' | '));
    }

    /**
     * El panel responde 403 tanto si el recurso existe como si no.
     *
     * `SubstituteBindings` corría antes que la comprobación de rol, así que un
     * cliente cualquiera recibía 403 en un id existente y 404 en uno
     * inventado: barriendo, cuenta usuarios y cotizaciones sin ser nadie.
     */
    public function test_el_panel_no_dice_que_ids_existen(): void
    {
        $otro = $this->usuario(Rol::Admin, ['email' => 'jefe@ejemplo.test']);

        $sesion = $this->entrarComo($this->usuario(Rol::Cliente));

        $existe = $sesion->get('/panel/usuarios?editar='.$otro->id);
        $noExiste = $sesion->get('/panel/usuarios?editar=99999');

        $existe->assertForbidden();
        $noExiste->assertForbidden();

        $this->assertSame(
            $existe->getStatusCode(),
            $noExiste->getStatusCode(),
            'El código cambia según si el recurso existe.'
        );
    }

    /** Y con un parámetro de ruta, que es donde de verdad pasaba. */
    public function test_lo_mismo_con_un_id_en_la_ruta(): void
    {
        $solicitud = \App\Models\Cotizacion::create([
            'consecutivo' => 'SA-2026-00001', 'nombre' => 'Ana',
            'telefono' => '3000000000', 'email' => 'ana@ejemplo.test',
        ]);

        $sesion = $this->entrarComo($this->usuario(Rol::Cliente));

        $sesion->get(route('panel.solicitudes.ver', $solicitud))->assertForbidden();
        $sesion->get('/panel/solicitudes/999999')->assertForbidden();
    }
}
