<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * «A tu carro le toca».
 *
 * Un correo por persona y no uno por mantenimiento: quien lleva tres carros
 * recibiría tres correos la misma mañana, y al tercero los marca como no
 * deseados —y con ellos, el de su cotización.
 */
class AvisoMantenimiento extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Mantenimiento>  $vencidos
     * @param  Collection<int, \App\Models\Mantenimiento>  $porVencer
     * @param  Collection<int, \App\Models\Mantenimiento>  $porKilometraje
     */
    public function __construct(
        public readonly User $usuario,
        public readonly Collection $vencidos,
        public readonly Collection $porVencer,
        public readonly Collection $porKilometraje,
    ) {}

    public function envelope(): Envelope
    {
        $cuantos = $this->vencidos->count() + $this->porVencer->count();

        return new Envelope(
            subject: $this->vencidos->isNotEmpty()
                ? 'A tu carro se le pasó un mantenimiento'
                : ($cuantos === 1
                    ? 'A tu carro le toca un mantenimiento pronto'
                    : 'A tu carro le tocan '.$cuantos.' mantenimientos pronto'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'correos.mantenimiento');
    }
}
