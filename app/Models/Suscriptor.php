<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un correo suscrito al newsletter desde el pie.
 *
 * `baja_en` en vez de borrar la fila: si alguien se da de baja y vuelve a
 * escribir su correo en el formulario, hay que saber que ya estuvo y respetar
 * su decisión anterior en vez de tratarlo como alta nueva.
 */
class Suscriptor extends Model
{
    protected $table = 'suscriptores';

    protected $fillable = ['correo', 'origen', 'baja_en'];

    protected function casts(): array
    {
        return ['baja_en' => 'datetime'];
    }
}
