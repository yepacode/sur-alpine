<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modelo extends Model
{
    protected $table = 'modelos';

    protected $fillable = ['marca_id', 'nombre', 'slug'];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }
}
