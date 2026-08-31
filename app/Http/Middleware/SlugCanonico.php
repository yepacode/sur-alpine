<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un contenido, una URL. Con las mayúsculas que están en la base.
 *
 * MySQL resuelve los slugs sin distinguir mayúsculas, así que
 * `/repuesto/ACEITE-12-1300-RENAULT` respondía 200 igual que el slug bueno
 * —y el `canonical` salía de `url()->current()`, o sea del texto que escribió
 * el visitante—. Cada variante se declaraba a sí misma la original.
 *
 * Eso no es un detalle de aseo: son 2ⁿ direcciones indexables por cada uno de
 * los 29.272 repuestos, y es justo la puerta que le sirve a quien está
 * suplantando a Sur Alpine —basta con enlazar el catálogo entero con otro
 * juego de mayúsculas para inyectar copias autocanonicalizadas y obligar a
 * Google a elegir cuál es la buena—. El cliente contrató este sitio por ese
 * problema.
 *
 * Se redirige con 301, no se responde 404: los enlaces mal copiados —de un
 * WhatsApp, de un correo reenviado— tienen que seguir llevando a la pieza.
 */
class SlugCanonico
{
    public function handle(Request $request, Closure $next): Response
    {
        // Sólo GET: un POST redirigido pierde el cuerpo.
        if (! $request->isMethodCacheable()) {
            return $next($request);
        }

        $ruta = $request->route();
        $hayQueCorregir = false;

        foreach ($ruta->parameters() as $nombre => $valor) {
            if (! $valor instanceof Model || ! isset($valor->slug)) {
                continue;
            }

            $pedido = $ruta->originalParameter($nombre);

            // Comparación estricta: es la que el motor de base de datos no
            // hace, y por eso hay que hacerla aquí.
            if (is_string($pedido) && $pedido !== $valor->slug) {
                $hayQueCorregir = true;
            }
        }

        if (! $hayQueCorregir) {
            return $next($request);
        }

        // `route()` con los propios modelos ya escribe el slug de la base,
        // que es exactamente el que queremos. La cadena de consulta se
        // conserva para no perder el `?page=` ni el `?orden=`.
        $destino = route($ruta->getName(), $ruta->parameters());

        if ($consulta = $request->getQueryString()) {
            $destino .= '?'.$consulta;
        }

        return redirect()->to($destino, 301);
    }
}
