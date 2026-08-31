<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Todo lo que entra tiene que ser UTF-8 bien formado.
 *
 * La regla `string` de Laravel NO comprueba la codificación, así que un byte
 * suelto de latin1 —el `\xE9` de un «café» pegado desde un sistema viejo, o
 * copiado de un correo de Outlook— atravesaba la validación intacto y moría en
 * el `INSERT`: «SQLSTATE[22007]: Incorrect string value». O sea, un 500 en la
 * cara de alguien que sólo escribió su nombre.
 *
 * Tres sitios reales, todos públicos:
 *   · el envío de la cotización, donde además se pierde la solicitud;
 *   · el formulario de «Contáctenos»;
 *   · el boletín del pie, que ni siquiera necesitaba un formulario mal
 *     escrito: bastaba con llegar desde un enlace cuya URL trajera el byte
 *     malo, porque se guarda de dónde vino.
 *
 * Y lo que hacía todavía más difícil de ver el problema: la placa de un
 * mantenimiento con el MISMO byte sí se guardaba, como `AB?123`. No por
 * diseño: pasa por un `mb_strtoupper()` que lo sustituye de pasada. Los
 * campos que se salvaban lo hacían por accidente.
 *
 * Se limpia en vez de rechazar. Quien escribió «José» quiere mandar su
 * mensaje, no leer un aviso sobre codificaciones de texto; y quien manda bytes
 * raros a propósito no merece una pantalla que le diga qué comprobamos.
 */
class TextoValido
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->limpiar($request->input()));

        return $next($request);
    }

    /** @param  array<mixed>  $valores */
    private function limpiar(array $valores): array
    {
        foreach ($valores as $clave => $valor) {
            if (is_array($valor)) {
                $valores[$clave] = $this->limpiar($valor);

                continue;
            }

            if (is_string($valor) && ! mb_check_encoding($valor, 'UTF-8')) {
                // `mb_convert_encoding` desde UTF-8 a UTF-8 sustituye las
                // secuencias inválidas y deja intacto todo lo demás: las
                // tildes, la ñ y los emoji sobreviven.
                $valores[$clave] = mb_convert_encoding($valor, 'UTF-8', 'UTF-8');
            }
        }

        return $valores;
    }
}
