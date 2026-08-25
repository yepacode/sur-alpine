<?php

namespace App\Mail;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmacionCotizacion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Cotizacion $cotizacion) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recibimos tu solicitud {$this->cotizacion->consecutivo} · Sur Alpine",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'correos.confirmacion',
            with: ['porVehiculo' => $this->cotizacion->porVehiculo()],
        );
    }
}
