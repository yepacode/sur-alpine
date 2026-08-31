<?php

namespace App\Models;

use App\Models\Concerns\SeResuelvePorSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Categoria extends Model
{
    use SeResuelvePorSlug;

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

    /** Los anchos en que se guarda la foto de una categoría. */
    public const ANCHOS = [320, 480, 640];

    /**
     * La misma foto en sus tres anchos.
     *
     * El más chico era 480 y la tarjeta se pinta a 250 px en escritorio: se
     * bajaban diez imágenes de 480 para diez huecos de 250. El escalón de 320
     * pesa menos de la mitad y en un teléfono a triple densidad sigue mandando
     * el de 640, que es lo correcto ahí.
     */
    public function getImagenSrcsetAttribute(): ?string
    {
        if (! $this->imagen || ! str_ends_with($this->imagen, '-640.webp')) {
            return null;
        }

        $base = substr($this->imagen, 0, -strlen('-640.webp'));

        return collect(self::ANCHOS)
            ->map(fn (int $ancho) => "{$base}-{$ancho}.webp {$ancho}w")
            ->implode(', ');
    }

}
