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
