<?php

namespace App\Models;

use App\Models\Concerns\SeResuelvePorSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use SeResuelvePorSlug;

    /** Lo que se ve mientras nadie ha subido la foto de la pieza. */
    public const IMAGEN_GENERICA = '/img/generico/generico.webp';

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

    /** Cuántas veces ha entrado esta pieza en una solicitud. */
    public function itemsCotizados(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
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
        // Una sola imagen general mientras llega la foto real, nunca la de la
        // categoría: el cliente pidió expresamente que no se use, porque una
        // foto de «Frenos» junto al nombre de una pieza concreta se lee como si
        // ese fuera el repuesto que se está vendiendo.
        return $this->imagen
            ?? $this->tipoParte?->imagen_defecto
            ?? self::IMAGEN_GENERICA;
    }

    /**
     * La ficha que manda cuando esta pieza esta duplicada.
     *
     * 890 fichas del catalogo son pares identicos: la misma pieza del mismo
     * carro, importada bajo dos categorias porque su tipo de parte existe en
     * las dos (ver `TipoParte::principalesPorSlug`). Titulo, descripcion y
     * contenido coinciden byte a byte; solo cambia el sufijo del slug.
     *
     * Las dos siguen respondiendo 200 —hay enlaces circulando hacia ambas—
     * pero solo una entra al sitemap y la otra la senala con su canonical.
     */
    public function fichaPrincipal(): self
    {
        $this->loadMissing('tipoParte');

        $principal = $this->tipoParte?->principal();

        if (! $principal || (int) $principal->id === (int) $this->tipo_parte_id) {
            return $this;
        }

        return static::query()
            ->where('vehiculo_id', $this->vehiculo_id)
            ->where('nombre', $this->nombre)
            ->where('tipo_parte_id', $principal->id)
            ->first() ?? $this;
    }

    public function esFichaPrincipal(): bool
    {
        return (int) $this->fichaPrincipal()->id === (int) $this->id;
    }

    /**
     * Sólo las fichas que mandan. Es el filtro del sitemap: publicar las dos
     * caras del duplicado es pedirle a Google que elija, y elige él.
     */
    public function scopeCanonicos(Builder $query): Builder
    {
        $secundarios = collect(TipoParte::principalesPorSlug());

        if ($secundarios->isEmpty()) {
            return $query;
        }

        return $query->whereNotIn('tipo_parte_id', TipoParte::query()
            ->whereIn('slug', $secundarios->keys())
            ->whereNotIn('id', $secundarios->values())
            ->pluck('id'));
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
        // Se PARTE por lo que no es letra ni número, no se borra.
        //
        // Antes se eliminaba, y eso pegaba dos palabras que el índice tiene
        // separadas: la referencia `MB-092` se convertía en `MB092`, un token
        // que no existe en ninguna parte, y la pieza no aparecía. Las
        // referencias de autopartes llevan guion casi siempre —`90915-YZZD4`,
        // `MB-092`— y es EL dato con el que llama un mecánico. Partiendo,
        // `MB-092` busca `+MB* +092*` y encuentra la fila.
        $expresion = collect(preg_split('/[^\p{L}\p{N}_]+/u', $termino, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($palabra) => mb_strlen($palabra) > 1)
            ->map(fn ($palabra) => '+'.$palabra.'*')
            ->implode(' ');

        if ($expresion === '') {
            return $query->where('nombre', 'like', $like);
        }

        return $query->where(fn (Builder $q) => $q
            ->whereFullText(['nombre', 'referencia'], $expresion, ['mode' => 'boolean'])
            // Y la referencia, además, tal cual.
            //
            // El índice de MySQL no guarda las palabras de menos de tres
            // letras (`ft_min_word_len`), así que una referencia como `MB-092`
            // pierde su `MB` y el fulltext solo ya no basta. Este `like`
            // rescata la búsqueda exacta, y sólo se paga cuando el término
            // trae algún número —o sea, cuando de verdad parece una
            // referencia—, no en un «pastillas freno» cualquiera.
            ->when(preg_match('/\d/u', $termino), fn (Builder $sub) => $sub
                ->orWhere('referencia', 'like', $like)));
    }

}
