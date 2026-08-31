<?php

namespace App\Http\Controllers;

use App\Models\Suscriptor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * El «Suscríbete al newsletter» del pie.
 *
 * Es el formulario más expuesto del sitio —está en todas las páginas y no pide
 * sesión—, así que lleva tres cierres: validación de correo real, un campo
 * trampa que sólo rellenan los robots, y el límite de peticiones de la ruta.
 */
class SuscripcionController extends Controller
{
    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            // `rfc` y no `rfc,dns`: la comprobación de DNS sale a la red en
            // cada envío, deja las pruebas dependiendo de que haya internet y
            // se cae con un dominio corporativo que sólo resuelve por dentro.
            // Lo que frena a los robots es la trampa y el límite de la ruta.
            'correo' => ['required', 'email:rfc', 'max:190'],
        ], [
            'correo.required' => 'Escribe tu correo.',
            'correo.email' => 'Ese correo no parece válido.',
        ]);

        // Campo trampa: invisible para una persona, irresistible para un robot
        // que rellena todo lo que encuentra. Va fuera de las reglas de
        // validación a propósito —si saliera como error, el robot sabría qué
        // campo dejar vacío en el siguiente intento.
        if ($request->filled('sitio_web')) {
            // Se le responde lo mismo que a una persona: si el robot ve un
            // error, prueba otra cosa; si ve un «listo», se va.
            return back(fallback: route('inicio'))->with('suscrito', true);
        }

        $correo = mb_strtolower(trim($datos['correo']));

        // Volver a escribir el mismo correo no crea una fila nueva ni revive
        // una baja: quien se dio de baja decidió, y un formulario público no
        // puede deshacer esa decisión por él.
        $suscriptor = Suscriptor::firstOrNew(['correo' => $correo]);

        if (! $suscriptor->exists) {
            // La URL de la que vino, saneada.
            //
            // Esto NO pasa por la validación —no es un campo del formulario,
            // sale de la cabecera `Referer`— así que un enlace entrante con un
            // byte de latin1 en su cadena de consulta tumbaba el formulario del
            // boletín, que está en el pie de TODAS las páginas y no pide
            // sesión. Bastaba con un enlace mal formado desde cualquier sitio.
            $origen = (string) url()->previous();

            if (! mb_check_encoding($origen, 'UTF-8')) {
                $origen = mb_convert_encoding($origen, 'UTF-8', 'UTF-8');
            }

            $suscriptor->origen = mb_substr($origen, 0, 190);
            $suscriptor->save();
        }

        return back(fallback: route('inicio'))->with('suscrito', true);
    }

    /**
     * Darse de baja del boletín.
     *
     * Faltaba entero: `baja_en` existía en la tabla, el panel la leía y la
     * insignia «De baja» era inalcanzable porque NADIE la escribía nunca. Es
     * decir, no había forma de salirse de una lista a la que cualquiera puede
     * entrar desde el pie de cualquier página. La política publicada promete
     * lo contrario.
     *
     * El enlace va firmado y sin caducidad: es el que se pega al pie de cada
     * correo, y tiene que seguir sirviendo dentro de dos años. Sin firma,
     * cualquiera daría de baja el correo de otro escribiéndolo en la URL.
     *
     * Un solo clic, sin pedir confirmación: quien llega aquí ya decidió, y
     * poner una pantalla intermedia es lo que hace que la gente marque el
     * correo como no deseado en vez de darse de baja.
     */
    public function baja(string $correo): View
    {
        $correo = mb_strtolower(trim($correo));

        Suscriptor::where('correo', $correo)
            ->whereNull('baja_en')
            ->update(['baja_en' => now()]);

        // La misma pantalla exista o no el correo: decir «ése no está en la
        // lista» convertiría el enlace en una forma de averiguar quién se
        // suscribió.
        return view('paginas.baja-newsletter', ['correo' => $correo]);
    }

    /** El enlace firmado para pegar al pie de un correo. */
    public static function enlaceDeBaja(string $correo): string
    {
        return URL::signedRoute('suscripcion.baja', ['correo' => mb_strtolower(trim($correo))]);
    }
}
