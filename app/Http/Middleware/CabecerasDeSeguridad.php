<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las cabeceras de seguridad, puestas por la aplicación.
 *
 * Ya están en `public/.htaccess`, y ahí seguirán: la CSP y la caducidad de los
 * estáticos son cosa del servidor. Pero ese archivo sólo lo lee Apache. Si el
 * cliente cambia de hosting a nginx, si alguien sirve el sitio con `artisan
 * serve`, o si el hosting no trae `mod_headers`, el sitio se queda sin nada de
 * esto y nadie se entera —porque no se ve—.
 *
 * Lo que va aquí es lo que no depende del servidor y no puede faltar nunca. El
 * riesgo concreto que cubre es el clickjacking sobre el panel: sin
 * `X-Frame-Options`, cualquiera mete `/panel` en un `<iframe>` invisible, le
 * superpone un señuelo, y el administrador hace clic en «borrar» creyendo que
 * pulsa otra cosa.
 *
 * La CSP no se duplica aquí a propósito: la del `.htaccess` es larga, está
 * razonada allí, y dos políticas distintas sobre la misma respuesta se
 * intersecan de formas que nadie quiere depurar.
 */
class CabecerasDeSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        foreach ([
            // Nadie enmarca este sitio. `SAMEORIGIN` y no `DENY` porque el
            // propio panel previsualiza páginas en un marco.
            'X-Frame-Options' => 'SAMEORIGIN',
            // Un archivo subido que el navegador decida tratar como HTML es
            // justo lo que `ImagenesWeb` se molesta en impedir.
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
            // Un año de «a este dominio se entra por HTTPS y punto».
            //
            // Es la cabecera que más importa en ESTE negocio. A Sur Alpine la
            // están suplantando; sin HSTS, la primera visita de quien teclea
            // «suralpine.com» sin `https://` viaja en claro hasta la
            // redirección, y en una red hostil —el wifi de un taller, de un
            // centro comercial— ese salto se intercepta y se le sirve una
            // copia. Con HSTS el navegador ni lo intenta: va cifrado desde el
            // primer carácter.
            //
            // Sin `preload` a propósito: eso se pide una vez y sacarse de la
            // lista tarda meses. Se añade cuando el dominio definitivo lleve
            // tiempo sirviendo HTTPS sin sobresaltos, no antes.
            //
            // Sólo sobre HTTPS: por norma el navegador ignora esta cabecera si
            // llega en claro, y mandarla en el `http://localhost` del taller no
            // aporta nada. Mejor no enviarla que enviarla muerta.
            ...($request->secure()
                ? ['Strict-Transport-Security' => 'max-age=31536000; includeSubDomains']
                : []),
        ] as $cabecera => $valor) {
            // `setIfNone`: si Apache ya la puso desde el `.htaccess`, manda la
            // suya. Aquí sólo se rellena el hueco.
            if (! $respuesta->headers->has($cabecera)) {
                $respuesta->headers->set($cabecera, $valor);
            }
        }

        // La versión de PHP no le hace falta a nadie de fuera.
        //
        // `header_remove` y no `$respuesta->headers->remove`: esta cabecera no
        // la pone Laravel, la añade el propio PHP al enviar la respuesta, así
        // que quitarla de la bolsa de Symfony no hace nada. Lo definitivo es
        // `expose_php=Off` en el `php.ini` del servidor; esto es el respaldo
        // para cuando no se puede tocar esa configuración.
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $respuesta;
    }
}
