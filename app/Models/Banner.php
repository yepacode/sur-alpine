<?php

namespace App\Models;

use App\Services\ImagenesWeb;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Banner extends Model
{
    protected $table = 'banners';

    protected $fillable = ['archivo', 'alt', 'orden', 'activo'];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * La portada guarda el carrusel en caché por una hora. Sin esto, el
     * cliente sube una campaña y sigue sin verla hasta que expire sola —que
     * es exactamente lo que lo hace pensar que el panel no sirve.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('inicio.banners'));
        static::deleted(fn () => Cache::forget('inicio.banners'));
    }

    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden')->orderBy('id');
    }

    /** Lo que el carrusel necesita, con sus tres anchos. */
    /** Lo que el carrusel necesita, con todos sus anchos. */
    public function paraElCarrusel(): array
    {
        return [
            'src' => "/img/banners/{$this->archivo}-1600.webp",
            'alt' => $this->alt,
            // El `src` de arranque: el rastreador de recursos lo pide antes de
            // resolver el `srcset`, y en un teléfono esa carrera la tiene que
            // ganar el archivo pequeño.
            'chico' => "/img/banners/{$this->archivo}-400.webp",
            // Cinco escalones, no tres.
            //
            // Faltaban los dos de abajo, y son los que importan: el hueco en un
            // teléfono mide 358 px CSS, así que el navegador escogía el de 900
            // tanto a densidad sencilla como a doble. Y esta imagen es el
            // elemento LCP de la portada en móvil.
            'srcset' => collect(ImagenesWeb::ANCHOS_BANNER)
                ->map(fn (int $ancho) => "/img/banners/{$this->archivo}-{$ancho}.webp {$ancho}w")
                ->implode(', '),
        ];
    }
}
