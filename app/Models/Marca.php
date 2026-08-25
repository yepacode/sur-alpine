<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Marca extends Model
{
    protected $table = 'marcas';

    protected $fillable = ['nombre', 'slug', 'logo', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function modelos(): HasMany
    {
        return $this->hasMany(Modelo::class);
    }

    public function vehiculos(): HasManyThrough
    {
        return $this->hasManyThrough(Vehiculo::class, Modelo::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
