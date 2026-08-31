<?php

namespace App\Models;

use App\Models\Concerns\SeResuelvePorSlug;
use App\Services\ImportadorCatalogo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class TipoParte extends Model
{
    use SeResuelvePorSlug;

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


    /**
     * Cuatro tipos de parte existen en DOS categorias a la vez.
     *
     * En el Excel del cliente «Terminal Direccion», «Axial Direccion» y los
     * dos retenes de rueda estan clasificados en Direccion Y en Suspension. No
     * es un error de datos: es la misma pieza fisica que el importador maneja
     * bajo dos sistemas. Pero de cara afuera crea dos paginas de aterrizaje
     * con el mismo titulo y 890 fichas de producto duplicadas byte a byte,
     * todas con canonical propio y todas en el sitemap.
     *
     * Para Google eso no es «dos paginas»: es una pagina y una copia, y
     * elige cual por su cuenta. «terminal de direccion» es de las consultas de
     * mas intencion del catalogo, y la autoridad se estaba partiendo en dos.
     *
     * Se elige una principal y la otra apunta a ella. El criterio es el numero
     * de piezas —la mas completa manda—, y a igualdad, el id mas bajo, que es
     * estable entre importaciones. No se borra nada: las dos paginas siguen
     * respondiendo 200, porque las dos estan enlazadas desde la barra lateral
     * y desde enlaces que ya circulan.
     *
     * @return array<string, int> slug repetido => id del que manda
     */
    public static function principalesPorSlug(): array
    {
        return Cache::remember('tipos-parte.principales.'.ImportadorCatalogo::version(), 3600, function (): array {
            $repetidos = static::query()
                ->select('slug')
                ->groupBy('slug')
                ->havingRaw('count(*) > 1')
                ->pluck('slug');

            if ($repetidos->isEmpty()) {
                return [];
            }

            return static::query()
                ->whereIn('slug', $repetidos)
                ->withCount('productos')
                ->orderByDesc('productos_count')
                ->orderBy('id')
                ->get()
                ->groupBy('slug')
                ->map(fn ($grupo) => (int) $grupo->first()->id)
                ->all();
        });
    }

    /** ¿Esta fila es la que manda para su slug? (Si el slug no se repite, sí.) */
    public function esPrincipal(): bool
    {
        $principal = static::principalesPorSlug()[$this->slug] ?? null;

        return $principal === null || $principal === (int) $this->id;
    }

    /** La fila que manda para este slug, sea esta u otra. */
    public function principal(): self
    {
        $id = static::principalesPorSlug()[$this->slug] ?? null;

        return $id === null || $id === (int) $this->id
            ? $this
            : static::with('categoria')->find($id) ?? $this;
    }
}
