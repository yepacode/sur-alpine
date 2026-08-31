<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * El correo para volver a entrar.
 *
 * Laravel trae uno, pero llega en inglés y firmado con el nombre de la app.
 * Éste usa la plantilla del sitio —la del logo y el pie con la dirección— y
 * dice en dos líneas lo único que importa: el botón, cuánto dura y qué hacer
 * si no fuiste tú.
 */
class ClaveOlvidada extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutos = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Restablece tu contraseña · Importadora Sur Alpine')
            ->greeting('Hola,')
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta en Importadora Sur Alpine.')
            ->action('Crear una contraseña nueva', $this->enlace($notifiable))
            ->line("El enlace vence en {$minutos} minutos y sirve una sola vez.")
            // Sin esta línea, quien no pidió nada se asusta. Con ella sabe que
            // no tiene que hacer nada: sin abrir el enlace, no pasa nada.
            ->line('Si no fuiste tú, puedes ignorar este correo: tu contraseña sigue siendo la misma.')
            ->salutation('Equipo Importadora Sur Alpine');
    }

    /**
     * El correo viaja en la URL a propósito.
     *
     * El token solo no basta: la tabla guarda uno por correo, así que sin
     * saber de quién es no se puede verificar. Va firmado por el propio token,
     * que es aleatorio y de un solo uso.
     */
    private function enlace(object $notifiable): string
    {
        return route('clave.formulario', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
