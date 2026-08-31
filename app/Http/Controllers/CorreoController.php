<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirmar el correo.
 *
 * Se ofrece, no se exige: no hay ninguna ruta detrás del middleware
 * `verified`. Lo que resuelve es que un dedazo en la dirección se descubra
 * antes de que rebote la confirmación de una cotización, y que el mostrador
 * sepa a qué correos puede escribirle de verdad.
 */
class CorreoController extends Controller
{
    /**
     * El enlace del correo.
     *
     * `EmailVerificationRequest` valida la firma y que el hash corresponda al
     * usuario que entró: sin eso, el enlace de uno serviría para marcar como
     * verificado el correo de otro.
     */
    public function verificar(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('cuenta.datos')
                ->with('mensaje', 'Tu correo ya estaba confirmado.');
        }

        $request->fulfill();

        return redirect()->route('cuenta.datos')
            ->with('mensaje', 'Listo, confirmamos tu correo.');
    }

    /** Volver a mandar el enlace. */
    public function reenviar(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('mensaje', 'Tu correo ya está confirmado.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('mensaje', 'Te enviamos el enlace. Revisa tu correo, incluida la carpeta de no deseados.');
    }
}
