<?php

namespace App\Mail;

use App\Models\Mensaje;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MensajeContacto extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Mensaje $mensaje) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Mensaje desde la web · {$this->mensaje->nombre}",
            // Para que «Responder» en el correo escriba a la persona y no al
            // buzón del sitio, que es lo que hace el formulario de su web.
            replyTo: [$this->mensaje->email],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'correos.mensaje');
    }
}
