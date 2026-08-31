<?php

namespace App\Http\Controllers;

use App\Mail\MensajeContacto;
use App\Models\Configuracion;
use App\Models\Mensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * El formulario de «Contáctenos».
 *
 * Se guarda primero y se manda después, en ese orden y a propósito: si el
 * servidor de correo está caído, el mensaje ya está en base y el equipo lo ve
 * en el panel. En su sitio actual el correo es lo único que hay, y si rebota,
 * el mensaje se perdió sin que nadie se entere.
 */
class ContactoController extends Controller
{
    public function enviar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'mensaje' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'nombre.required' => 'Escribe tu nombre.',
            'email.required' => 'Escribe tu correo, para poder responderte.',
            'email.email' => 'Ese correo no parece válido.',
            'mensaje.required' => 'Cuéntanos en qué te podemos ayudar.',
            'mensaje.min' => 'Escribe un poco más para poder ayudarte bien.',
        ]);

        // Campo trampa, igual que en el newsletter: fuera de las reglas de
        // validación para no decirle al robot cuál es.
        if ($request->filled('sitio_web')) {
            return back(fallback: route('contacto'))->with('mensaje_enviado', true);
        }

        $mensaje = Mensaje::create($datos);

        // Los mismos buzones que reciben las cotizaciones: es la bandeja del
        // equipo comercial, y tener dos listas de correos para mantener es la
        // forma segura de que una de las dos se quede vieja.
        $destinos = Configuracion::correosDestino();

        if ($destinos === []) {
            $mensaje->update(['error_envio' => 'No hay correos de destino configurados en el panel.']);
        } else {
            try {
                Mail::to($destinos)->send(new MensajeContacto($mensaje));
                $mensaje->update(['correo_enviado_en' => now(), 'error_envio' => null]);
            } catch (\Throwable $e) {
                $mensaje->update(['error_envio' => mb_substr($e->getMessage(), 0, 500)]);

                Log::error('No se pudo enviar el mensaje de contacto', [
                    'mensaje' => $mensaje->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // A la persona se le confirma igual: su mensaje quedó guardado y el
        // equipo lo va a ver. Que el correo saliera o no es problema nuestro.
        return back(fallback: route('contacto'))->with('mensaje_enviado', true);
    }
}
