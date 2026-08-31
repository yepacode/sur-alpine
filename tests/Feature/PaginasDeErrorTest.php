<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las páginas de error, con la cara del sitio.
 *
 * Existen por una razón concreta del cliente: hay copias de su web
 * circulando, y una pantalla blanca en inglés —«Not Found», «Page Expired»—
 * es la señal más barata de «esta no es la página buena». El 404 además pega
 * en las 29.272 fichas que el sitemap publica y en cada enlace que un asesor
 * manda por WhatsApp.
 *
 * Todo se comprueba por HTTP, que es como las ve una persona: pintar la vista
 * suelta no probaría que el manejador de excepciones la esté usando.
 */
class PaginasDeErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_404_sale_en_espanol_y_con_salida(): void
    {
        $this->get('/una-pagina-que-no-existe')
            ->assertNotFound()
            ->assertSee('Esta página no existe o ya no está')
            ->assertSee('Buscar mi repuesto')
            ->assertDontSee('Not Found');
    }

    /** Y con la marca del sitio, que es justo lo que lo separa de una copia. */
    public function test_el_404_lleva_la_cabecera_el_pie_y_el_telefono(): void
    {
        $this->get('/una-pagina-que-no-existe')
            ->assertSee('ALPINE', false)
            ->assertSee(route('catalogo'), false)
            ->assertSee('366 0066')
            ->assertSee('noindex', false);
    }

    /**
     * El 419 dice lo único que de verdad importa: que la cotización no se
     * perdió. Sobrevive en la sesión, y antes nadie se lo decía a nadie.
     */
    public function test_el_419_dice_que_la_cotizacion_sigue_ahi(): void
    {
        // Se le pide al manejador de excepciones que resuelva un token
        // vencido, que es lo que pasa cuando la página estuvo dos horas
        // abierta en el bolsillo. No se puede provocar con un POST normal:
        // Laravel se salta la comprobación de token dentro del banco de
        // pruebas, así que un `post()` sin token nunca daría 419.
        $peticion = Request::create(route('cotizacion.enviar'), 'POST');
        $peticion->setLaravelSession($this->app['session']->driver());

        $respuesta = $this->app[ExceptionHandler::class]
            ->render($peticion, new TokenMismatchException('CSRF token mismatch.'));

        $html = $respuesta->getContent();

        $this->assertSame(419, $respuesta->getStatusCode());
        $this->assertStringContainsString('Tu cotización sigue completa', $html);
        $this->assertStringContainsString(route('cotizacion.ver'), $html);
        $this->assertStringNotContainsString('Page Expired', $html);
    }

    /** El 429 dice cuánto esperar y ofrece la salida que no está limitada. */
    public function test_el_429_explica_la_espera(): void
    {
        // `throttle:5,1` en el envío de contacto.
        for ($i = 0; $i < 6; $i++) {
            $respuesta = $this->post(route('contacto.enviar'), []);
        }

        $respuesta->assertStatus(429)
            ->assertSee('Espera un momento antes de volver a intentar')
            ->assertSee('366 0066')
            ->assertDontSee('Too Many Requests');
    }

    /** El 403 del panel ya no es una pantalla negra sin salida. */
    public function test_el_403_ofrece_por_donde_salir(): void
    {
        $this->entrarComo($this->usuario(Rol::Cliente))
            ->get('/panel')
            ->assertForbidden()
            ->assertSee('Tu cuenta no tiene acceso a esta sección')
            ->assertSee(route('cuenta'), false);
    }

    /**
     * Y tambien para un admin, que es la rama que reventaba.
     *
     * La pagina 403 enlazaba `route('panel')` y esa ruta no existe: se llama
     * `panel.tablero`. O sea que a un administrador el callejon sin salida que
     * esta pagina venia a arreglar se le convertia en un 500 en blanco.
     */
    public function test_el_403_no_revienta_con_un_admin(): void
    {
        $mantenimientoAjeno = \App\Models\Mantenimiento::create([
            'user_id' => $this->usuario(Rol::Cliente, ['email' => 'otro@ejemplo.test'])->id,
            'placa' => 'ABC123', 'tipo' => 'Cambio de aceite',
            'fecha' => today(), 'kilometraje' => 1000,
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('cuenta.mantenimientos.borrar', $mantenimientoAjeno))
            ->assertForbidden()
            ->assertSee('Tu cuenta no tiene acceso a esta sección')
            ->assertSee(route('panel.tablero'), false);
    }
}
