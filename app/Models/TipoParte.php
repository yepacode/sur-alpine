<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoParte extends Model
{
    protected $table = 'tipos_parte';

    protected $fillable = ['categoria_id', 'nombre', 'slug', 'imagen_defecto', 'orden'];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
