<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\MensajeContacto;
use App\Models\Configuracion;
use App\Models\Mensaje;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

/**
 * La bandeja de «Contáctenos».
 *
 * Dos cosas que el formulario de su sitio actual no permite: ver los mensajes
 * que llegaron aunque el correo haya rebotado, y marcar cuáles ya se
 * contestaron para que la bandeja no crezca sin que nadie sepa qué falta.
 */
class MensajeController extends Controller
{
    public function index(): View
    {
        return view('panel.mensajes', [
            'mensajes' => Mensaje::query()->latest()->paginate(30),
            'pendientes' => Mensaje::query()->whereNull('atendido_en')->count(),
        ]);
    }

    public function atender(Mensaje $mensaje): RedirectResponse
    {
        // Alternar y no sólo marcar: si alguien se equivoca de fila, se
        // devuelve sin tener que tocar la base.
        $mensaje->update(['atendido_en' => $mensaje->atendido_en ? null : now()]);

        return back()->with('mensaje', $mensaje->atendido_en ? 'Marcado como atendido.' : 'Marcado como pendiente.');
    }

    public function reenviar(Mensaje $mensaje): RedirectResponse
    {
        $destinos = Configuracion::correosDestino();

        if ($destinos === []) {
            return back()->with('mensaje', 'Primero configura los correos de destino.');
        }

        try {
            Mail::to($destinos)->send(new MensajeContacto($mensaje));
            $mensaje->update(['correo_enviado_en' => now(), 'error_envio' => null]);

            return back()->with('mensaje', 'Correo reenviado.');
        } catch (\Throwable $e) {
            $mensaje->update(['error_envio' => mb_substr($e->getMessage(), 0, 500)]);

            return back()->with('mensaje', 'No se pudo enviar: '.$e->getMessage());
        }
    }
}
