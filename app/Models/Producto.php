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

        // `%` y `_` son comodines de LIKE: si no se escapan, `?q=%` devuelve
        // el catálogo entero como si no hubiera filtro.
        $like = '%'.addcslashes($termino, "%_\\").'%';

        if ($query->getConnection()->getDriverName() !== 'mysql') {
            return $query->where(fn (Builder $q) => $q
                ->where('nombre', 'like', $like)
                ->orWhere('referencia', 'like', $like));
        }

        // Cada palabra suma y admite prefijo: "filtro ace" encuentra
        // "Filtro Aceite" sin obligar al usuario a escribirlo completo.
        //
        // Lista blanca —sólo letras, números y guion bajo— en vez de lista
        // negra de operadores. Con la lista negra se colaban caracteres
        // legales como `%`, que MySQL rechaza dentro de un término fulltext:
        // `%%%` producía `+%%%*` y devolvía HTTP 500 en el catálogo.
        //
        // Se limpia ANTES de medir la longitud: "freno ()" tiene dos "palabras"
        // de dos caracteres, y si se mide primero, el paréntesis pasa el filtro,
        // se queda en nada al limpiarlo y produce un "+*" suelto —tampoco es
        // una búsqueda vacía, es error de sintaxis.
        $expresion = collect(preg_split('/\s+/u', $termino))
            ->map(fn ($palabra) => preg_replace('/[^\p{L}\p{N}_]+/u', '', $palabra))
            ->filter(fn ($palabra) => mb_strlen($palabra) > 1)
            ->map(fn ($palabra) => '+'.$palabra.'*')
            ->implode(' ');

        return $expresion === ''
            ? $query->where('nombre', 'like', $like)
            : $query->whereFullText(['nombre', 'referencia'], $expresion, ['mode' => 'boolean']);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
