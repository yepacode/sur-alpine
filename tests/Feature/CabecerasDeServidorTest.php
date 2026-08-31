<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Las cabeceras del `.htaccess`.
 *
 * Existe por un fallo que no se ve en desarrollo y rompe el sitio entero en
 * producción: `artisan serve` NO lee `public/.htaccess`, así que una política
 * de seguridad mal escrita pasa todas las pruebas del mundo en local y mata la
 * página el día del despliegue.
 *
 * Pasó de verdad: la primera CSP no incluía `'unsafe-eval'`, y Alpine evalúa
 * las expresiones de `x-data`, `@click` y `x-show` con `new Function()`. En el
 * servidor habría muerto de golpe el selector de vehículo, el menú móvil, las
 * sugerencias del buscador, el contador del carrito y el mapa de la portada
 * —que además se queda invisible para siempre por su `x-cloak`—.
 *
 * Estas pruebas leen el archivo como texto. Es tosco, pero es lo único que
 * puede cazar un error en un archivo que el banco de pruebas nunca ejecuta.
 */
class CabecerasDeServidorTest extends TestCase
{
    private function htaccess(): string
    {
        $ruta = public_path('.htaccess');

        $this->assertFileExists($ruta, 'Sin `.htaccess` el sitio va sin cabeceras de seguridad.');

        return file_get_contents($ruta);
    }

    private function csp(): string
    {
        preg_match('/Content-Security-Policy\s+"([^"]+)"/', $this->htaccess(), $partes);

        $this->assertNotEmpty($partes, 'No hay Content-Security-Policy en el .htaccess.');

        return $partes[1];
    }

    /**
     * Mientras el sitio use Alpine, `script-src` necesita `'unsafe-eval'`.
     *
     * Si algún día se migra a `@alpinejs/csp` —que evita `new Function()`—
     * esta prueba tiene que cambiar A LA VEZ que la política. Que falle es la
     * señal de que alguien tocó una sin la otra.
     */
    public function test_la_csp_deja_funcionar_a_alpine(): void
    {
        $usaAlpine = str_contains(file_get_contents(base_path('package.json')), 'alpinejs');

        if (! $usaAlpine) {
            $this->markTestSkipped('El sitio ya no usa Alpine: revisa si `unsafe-eval` sigue haciendo falta.');
        }

        $this->assertStringContainsString(
            "'unsafe-eval'",
            $this->csp(),
            'Alpine evalúa con `new Function()`: sin `unsafe-eval` el sitio queda sin nada interactivo en el servidor.'
        );
    }

    /** El telón decide antes del primer pintado con un script en línea. */
    public function test_la_csp_deja_funcionar_el_telon(): void
    {
        $this->assertStringContainsString("'unsafe-inline'", $this->csp());
    }

    /** Y lo que sí cierra, cerrado: nada de plugins ni de enmarcar el sitio. */
    public function test_la_csp_cierra_lo_que_puede_cerrar(): void
    {
        $csp = $this->csp();

        foreach ([
            "object-src 'none'" => 'un plugin embebido no tiene nada que hacer aquí',
            "frame-ancestors 'self'" => 'otro sitio no puede enmarcar el nuestro',
            "base-uri 'self'" => 'nadie puede reescribir la base de las URLs relativas',
            "form-action 'self'" => 'un formulario no puede enviarse a otro dominio',
        ] as $directiva => $porque) {
            $this->assertStringContainsString($directiva, $csp, "Falta `{$directiva}`: {$porque}.");
        }
    }

    /**
     * Y salen de verdad en la respuesta, no sólo en un archivo que Apache
     * puede no estar leyendo.
     *
     * Las del `.htaccess` desaparecen si el cliente cambia a nginx, si el
     * hosting no trae `mod_headers`, o si alguien sirve el sitio con `artisan
     * serve` — y nadie se entera, porque una cabecera que falta no se ve. Lo
     * que cubre esto es el clickjacking sobre el panel: sin
     * `X-Frame-Options`, cualquiera lo mete en un marco invisible, le
     * superpone un señuelo y el administrador pulsa «borrar» creyendo que
     * pulsa otra cosa.
     */
    public function test_las_cabeceras_salen_aunque_el_servidor_no_lea_el_htaccess(): void
    {
        $respuesta = $this->get('/');

        foreach ([
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ] as $cabecera => $valor) {
            $respuesta->assertHeader($cabecera, $valor);
        }
    }

    /** También en las rutas de máquina, que van fuera del grupo `web`. */
    public function test_tambien_en_el_sitemap_y_en_robots(): void
    {
        foreach (['/robots.txt', '/sitemap.xml'] as $ruta) {
            $this->get($ruta)->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    /** Las otras cabeceras que ya estaban, para que nadie las quite sin querer. */
    public function test_siguen_las_demas_cabeceras(): void
    {
        $htaccess = $this->htaccess();

        foreach (['X-Content-Type-Options', 'Referrer-Policy', 'X-Frame-Options'] as $cabecera) {
            $this->assertStringContainsString($cabecera, $htaccess);
        }
    }
}
