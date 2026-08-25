<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * F · SEO editable por página.
 *
 * Cada landing estática (portada, quiénes-somos, contactenos,
 * mantenimientos, política de datos, cada categoría) puede tener su
 * `title`, `description` y `og_imagen` propios. Si la fila no existe, la
 * vista usa el `@section('titulo')` original.
 */
class SeoPagina extends Model
{
    protected $table = 'seo_paginas';

    protected $fillable = [
        'ruta', 'etiqueta', 'slug',
        // Básico
        'titulo', 'titulo_h1', 'descripcion', 'palabras_clave', 'focus_keyword', 'canonical',
        // OG
        'og_imagen', 'og_titulo', 'og_descripcion', 'og_imagen_alt', 'og_tipo',
        'og_locale', 'og_locale_alternate', 'og_imagen_ancho', 'og_imagen_alto',
        // Twitter
        'twitter_card', 'twitter_titulo', 'twitter_descripcion', 'twitter_imagen',
        'twitter_sitio', 'twitter_creador',
        // Robots
        'indexable', 'seguir_enlaces',
        'max_snippet', 'max_image_preview', 'max_video_preview',
        'noarchive', 'nosnippet', 'noimageindex', 'notranslate',
        // Article
        'article_publicado_en', 'article_modificado_en',
        'article_seccion', 'article_etiquetas', 'article_autor',
        // hreflang y paginación
        'hreflang', 'rel_prev', 'rel_next',
        // Sitemap por página
        'sitemap_incluir', 'sitemap_frecuencia', 'sitemap_prioridad',
        // Avanzado
        'json_ld_extra', 'schema_tipo', 'head_extra',
    ];

    protected $casts = [
        'indexable' => 'boolean',
        'seguir_enlaces' => 'boolean',
        'noarchive' => 'boolean',
        'nosnippet' => 'boolean',
        'noimageindex' => 'boolean',
        'notranslate' => 'boolean',
        'sitemap_incluir' => 'boolean',
        'sitemap_prioridad' => 'float',
        'hreflang' => 'array',
        'article_publicado_en' => 'datetime',
        'article_modificado_en' => 'datetime',
    ];

    /**
     * Cadena para `<meta name="robots">`. Combina las casillas del panel:
     * index/noindex, follow/nofollow, max-snippet:N, max-image-preview:X,
     * max-video-preview:N, noarchive, nosnippet, noimageindex, notranslate.
     */
    public function metaRobots(): string
    {
        $partes = [
            $this->indexable ? 'index' : 'noindex',
            $this->seguir_enlaces ? 'follow' : 'nofollow',
        ];
        if ($this->max_snippet !== null) $partes[] = 'max-snippet:'.$this->max_snippet;
        if ($this->max_image_preview) $partes[] = 'max-image-preview:'.$this->max_image_preview;
        if ($this->max_video_preview !== null) $partes[] = 'max-video-preview:'.$this->max_video_preview;
        if ($this->noarchive) $partes[] = 'noarchive';
        if ($this->nosnippet) $partes[] = 'nosnippet';
        if ($this->noimageindex) $partes[] = 'noimageindex';
        if ($this->notranslate) $partes[] = 'notranslate';

        return implode(',', $partes);
    }

    public const LLAVE_CACHE = 'seo.mapa.v1';

    /** Diccionario ruta → fila cacheado. */
    public static function mapa(): array
    {
        return Cache::remember(self::LLAVE_CACHE, 3600,
            fn () => self::query()->get()->keyBy('ruta')->all());
    }

    /** Devuelve la fila para el nombre de ruta actual, o null. */
    public static function para(?string $ruta): ?self
    {
        if (! $ruta) return null;
        return self::mapa()[$ruta] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::LLAVE_CACHE));
        static::deleted(fn () => Cache::forget(self::LLAVE_CACHE));
    }
}
