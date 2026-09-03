<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'modelo_id', 'cilindraje', 'anio_inicio', 'anio_fin', 'slug', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'anio_inicio' => 'integer',
            'anio_fin' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /** "CHEVROLET AVEO 1600 (2006-2013)" */
    public function getNombreCompletoAttribute(): string
    {
        return sprintf(
            '%s %s %s (%d-%d)',
            $this->modelo->marca->nombre,
            $this->modelo->nombre,
            $this->cilindraje,
            $this->anio_inicio,
            $this->anio_fin
        );
    }

    /**
     * El nombre que se muestra al visitante cuando el mismo eligio el ano.
     *
     * Peticion literal del cliente, con captura por WhatsApp: eligio 1976 y el
     * sitio le decia «FIAT 128 1500 (1976-1982)». Le daba miedo pensar que
     * estaba viendo piezas de 1977 a 1982 sin haberlo pedido. En realidad el
     * rango es del vehiculo en la base —hay UN registro que cubre esos siete
     * anos— y el filtro solo comprueba que el ano elegido caiga dentro, pero
     * eso es un detalle interno. Para el visitante, si eligio 1976, el titulo
     * dice 1976.
     *
     * Con `$anio = null` cae al rango completo, que es lo que se usa en los
     * correos, el schema para Google y el detalle del panel: ahi si importa
     * saber que aquel FIAT 128 se hizo de 1976 a 1982.
     */
    public function nombreParaVisitante(?int $anio = null): string
    {
        return sprintf(
            '%s %s %s (%s)',
            $this->modelo->marca->nombre,
            $this->modelo->nombre,
            $this->cilindraje,
            $anio !== null && $anio >= $this->anio_inicio && $anio <= $this->anio_fin
                ? (string) $anio
                : $this->anio_inicio.'-'.$this->anio_fin
        );
    }

    /** Los años que cubre este vehículo, listos para el selector. */
    public function getAniosAttribute(): array
    {
        return range($this->anio_inicio, $this->anio_fin);
    }

    public function scopeDelAnio(Builder $query, int $anio): Builder
    {
        return $query->where('anio_inicio', '<=', $anio)
            ->where('anio_fin', '>=', $anio);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
