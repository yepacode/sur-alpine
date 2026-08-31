<?php

namespace App\Models;

use App\Models\Concerns\SeResuelvePorSlug;
use App\Services\ImportadorCatalogo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Una nota del blog («Actualízate con Nosotros»).
 *
 * El cuerpo entra como texto plano y se convierte en bloques al pintarlo; ver
 * `bloques()`. Nunca se guarda ni se imprime HTML venido del panel: es la
 * diferencia entre que un asesor escriba una nota y que pueda inyectar un
 * script en el sitio.
 */
class Nota extends Model
{
    use SeResuelvePorSlug;

    protected $table = 'notas';

    protected $fillable = [
        'titulo', 'slug', 'resumen', 'cuerpo', 'imagen', 'categoria', 'publicada', 'publicada_en',
    ];

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'publicada_en' => 'datetime',
        ];
    }

    /**
     * El sitemap de secciones incluye las notas visibles y se cachea una hora.
     *
     * Sin esto —y faltaba, aunque el `use` de arriba dejaba ver la intención—
     * despublicar una nota la sacaba del sitio pero NO del sitemap: Google
     * seguía recibiendo durante una hora una URL que ya daba 404. Ofrecerle
     * una página muerta es peor que no ofrecerla. Y al revés, una nota nueva
     * tardaba lo mismo en aparecer.
     */
    protected static function booted(): void
    {
        $olvidar = fn () => Cache::forget('sitemap.secciones.'.ImportadorCatalogo::version());

        static::saved($olvidar);
        static::deleted($olvidar);
    }


    /**
     * Lo que puede ver alguien de la calle: publicada y con la fecha cumplida.
     *
     * La segunda condición es la que permite dejar una nota escrita hoy y
     * programada para el lunes sin que se asome antes de tiempo.
     */
    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('publicada', true)
            ->where(fn (Builder $q) => $q->whereNull('publicada_en')->orWhere('publicada_en', '<=', now()));
    }

    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE(publicada_en, created_at) DESC')->orderByDesc('id');
    }

    /**
     * El cuerpo, ya partido en bloques que la vista sabe pintar.
     *
     * Tres formas, y ninguna más:
     *   «## Título»  → subtítulo
     *   «- Punto»    → viñeta (las seguidas se agrupan en una sola lista)
     *   cualquier otra línea → párrafo
     *
     * @return list<array{tipo: string, texto?: string, puntos?: list<string>}>
     */
    public function bloques(): array
    {
        $bloques = [];

        foreach (preg_split('/\R+/', trim((string) $this->cuerpo)) as $linea) {
            $linea = trim($linea);

            if ($linea === '') {
                continue;
            }

            if (str_starts_with($linea, '## ')) {
                $bloques[] = ['tipo' => 'titulo', 'texto' => trim(substr($linea, 3))];

                continue;
            }

            if (str_starts_with($linea, '- ')) {
                $punto = trim(substr($linea, 2));

                // Viñetas seguidas = una sola lista. Si cada una abriera su
                // propio `<ul>`, el lector de pantalla anunciaría «lista de un
                // elemento» seis veces seguidas.
                if ($bloques && end($bloques)['tipo'] === 'lista') {
                    $bloques[array_key_last($bloques)]['puntos'][] = $punto;

                    continue;
                }

                $bloques[] = ['tipo' => 'lista', 'puntos' => [$punto]];

                continue;
            }

            $bloques[] = ['tipo' => 'parrafo', 'texto' => $linea];
        }

        return $bloques;
    }

    /**
     * La foto en sus dos anchos, para que el celular no baje la de 1024 en una
     * tarjeta que pinta a 330 px.
     */
    public function getImagenSrcsetAttribute(): ?string
    {
        if (! $this->imagen || ! str_ends_with($this->imagen, '-1024.webp')) {
            return null;
        }

        $base = substr($this->imagen, 0, -strlen('-1024.webp'));

        return "{$base}-520.webp 520w, {$this->imagen} 1024w";
    }

    /** Cuánto se demora en leerse, redondeado hacia arriba y nunca menos de 1. */
    public function getMinutosDeLecturaAttribute(): int
    {
        // `str_word_count` no entiende UTF-8: trata cada vocal acentuada como
        // separador, así que «La cotización es rápida» contaba 6 palabras en
        // vez de 4 y el tiempo salía ~50 % inflado en un texto en español.
        preg_match_all('/\p{L}+/u', strip_tags((string) $this->cuerpo), $palabras);

        return max(1, (int) ceil(count($palabras[0]) / 200));
    }

    public static function slugUnico(string $titulo, ?int $exceptoId = null): string
    {
        $base = Str::slug($titulo) ?: 'nota';
        $slug = $base;
        $n = 2;

        while (static::query()->where('slug', $slug)->when($exceptoId, fn ($q) => $q->whereKeyNot($exceptoId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
