<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nombre', 'slug', 'imagen', 'orden'];

    public function tiposParte(): HasMany
    {
        return $this->hasMany(TipoParte::class);
    }

    public function productos(): HasManyThrough
    {
        return $this->hasManyThrough(Producto::class, TipoParte::class);
    }

    /**
     * La misma foto en sus dos anchos. La tarjeta de la portada se pinta a
     * 223 px: sin esto el celular baja siempre la de 640.
     */
    public function getImagenSrcsetAttribute(): ?string
    {
        if (! $this->imagen || ! str_ends_with($this->imagen, '-640.webp')) {
            return null;
        }

        $base = substr($this->imagen, 0, -strlen('-640.webp'));

        return "{$base}-480.webp 480w, {$this->imagen} 640w";
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
