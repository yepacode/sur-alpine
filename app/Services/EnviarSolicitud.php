<?php

namespace App\Services;

use App\Mail\ConfirmacionCotizacion;
use App\Mail\SolicitudCotizacion;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Manda la solicitud al equipo y deja constancia de si salió.
 *
 * El proceso comercial es por teléfono: el correo es el disparador. Por eso el
 * envío nunca puede fallar en silencio — si el buzón está lleno o el servidor
 * de correo está caído, la solicitud queda marcada en el panel para reenviarla.
 */
class EnviarSolicitud
{
    public function despachar(Cotizacion $cotizacion): bool
    {
        $destinos = Configuracion::correosDestino();

        if ($destinos === []) {
            $this->marcarError($cotizacion, 'No hay correos de destino configurados en el panel.');

            return false;
        }

        try {
            // Encolado: con un SMTP lento el cliente se quedaba mirando el
            // botón "Enviar" hasta que el servidor de correo contestara. Si no
            // hay trabajador de cola corriendo, `QUEUE_CONNECTION=sync` lo
            // manda en el acto igual que antes.
            Mail::to($destinos)->queue(new SolicitudCotizacion($cotizacion));

            $cotizacion->update([
                'correo_enviado_en' => now(),
                'error_envio' => null,
            ]);
        } catch (\Throwable $e) {
            $this->marcarError($cotizacion, $e->getMessage());

            return false;
        }

        // La confirmación al cliente es cortesía: si falla, la solicitud ya
        // llegó al equipo y no hay que marcarla como perdida.
        try {
            Mail::to($cotizacion->email)->queue(new ConfirmacionCotizacion($cotizacion));
        } catch (\Throwable $e) {
            Log::warning('No se pudo confirmar la cotización al solicitante', [
                'consecutivo' => $cotizacion->consecutivo,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    private function marcarError(Cotizacion $cotizacion, string $mensaje): void
    {
        $cotizacion->update([
            'correo_enviado_en' => null,
            'error_envio' => mb_substr($mensaje, 0, 500),
        ]);

        Log::error('No se pudo enviar la solicitud de cotización', [
            'consecutivo' => $cotizacion->consecutivo,
            'error' => $mensaje,
        ]);
    }
}
