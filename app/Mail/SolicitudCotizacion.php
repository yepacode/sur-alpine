<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudCotizacion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Cotizacion $cotizacion) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Solicitud {$this->cotizacion->consecutivo} · {$this->cotizacion->nombre_completo}",
            replyTo: [$this->cotizacion->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'correos.solicitud',
            with: ['porVehiculo' => $this->cotizacion->porVehiculo()],
        );
    }
}
