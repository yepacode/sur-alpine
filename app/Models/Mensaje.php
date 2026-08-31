<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un mensaje enviado desde «Contáctenos».
 *
 * `atendido_en` lo marca el equipo desde el panel: sin eso, la bandeja crece y
 * nadie sabe cuáles ya se contestaron.
 */
class Mensaje extends Model
{
    protected $table = 'mensajes';

    protected $fillable = ['nombre', 'email', 'mensaje', 'correo_enviado_en', 'error_envio', 'atendido_en'];

    protected function casts(): array
    {
        return [
            'correo_enviado_en' => 'datetime',
            'error_envio' => 'string',
            'atendido_en' => 'datetime',
        ];
    }
}
