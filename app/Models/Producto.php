<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'vehiculo_id', 'tipo_parte_id', 'nombre', 'slug',
        'referencia', 'imagen', 'descripcion', 'publicado',
    ];

    protected function casts(): array
    {
        return ['publicado' => 'boolean'];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function tipoParte(): BelongsTo
    {
        return $this->belongsTo(TipoParte::class);
    }

    /**
     * Imagen del producto, o la de su tipo de parte, o la de su categoría.
     * Mientras no lleguen las fotos reales el cliente ve la de la categoría,
     * que ya es mucho mejor que una ilustración genérica para todo el catálogo.
     */
    public function getImagenMostrableAttribute(): ?string
    {
        return $this->imagen
            ?? $this->tipoParte?->imagen_defecto
            ?? $this->tipoParte?->categoria?->imagen;
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('publicado', true);
    }

    /** Filtra por el vehículo que el visitante tiene seleccionado. */
    public function scopeParaVehiculo(Builder $query, ?int $vehiculoId): Builder
    {
        return $vehiculoId ? $query->where('vehiculo_id', $vehiculoId) : $query;
    }

    /**
     * Busca por nombre y por referencia de parte, que es como busca un mecánico.
     * Usa el índice FULLTEXT en MySQL y cae a LIKE en SQLite (pruebas).
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return $query;
        }

        if ($query->getConnection()->getDriverName() !== 'mysql') {
            return $query->where(fn (Builder $q) => $q
                ->where('nombre', 'like', "%{$termino}%")
                ->orWhere('referencia', 'like', "%{$termino}%"));
        }

        // Cada palabra suma y admite prefijo: "filtro ace" encuentra
        // "Filtro Aceite" sin obligar al usuario a escribirlo completo.
        //
        // Se limpia ANTES de medir: "freno ()" tiene dos "palabras" de dos
        // caracteres, y si se mide primero, el paréntesis pasa el filtro, se
        // queda en nada al limpiarlo y produce un "+*" suelto — que no es una
        // búsqueda vacía sino un error de sintaxis de MySQL.
        $expresion = collect(preg_split('/\s+/u', $termino))
            ->map(fn ($palabra) => preg_replace('/[+\-><()~*"@]+/', '', $palabra))
            ->filter(fn ($palabra) => mb_strlen($palabra) > 1)
            ->map(fn ($palabra) => '+'.$palabra.'*')
            ->implode(' ');

        return $expresion === ''
            ? $query->where('nombre', 'like', "%{$termino}%")
            : $query->whereFullText(['nombre', 'referencia'], $expresion, ['mode' => 'boolean']);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
