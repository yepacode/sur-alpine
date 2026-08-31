<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * «Confirma tu correo».
 *
 * Hereda de la de Laravel para no rehacer la firma del enlace —que es lo
 * delicado— y sólo cambia lo que se lee: en español y con la plantilla del
 * sitio.
 */
class CorreoVerificar extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $minutos = config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Confirma tu correo · Importadora Sur Alpine')
            ->greeting('Hola,')
            ->line('Confirma que esta dirección es tuya para que podamos escribirte cuando respondamos tus cotizaciones.')
            ->action('Confirmar mi correo', $this->verificationUrl($notifiable))
            ->line("El enlace vence en {$minutos} minutos.")
            ->line('Si no creaste una cuenta con nosotros, puedes ignorar este correo.')
            ->salutation('Equipo Importadora Sur Alpine');
    }
}
