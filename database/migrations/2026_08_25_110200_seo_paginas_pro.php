<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F · SEO profesional — todos los campos que un SEO senior espera tocar.
 *
 * A la migración base y a la de «completo» se suma esto:
 *
 * · H1 y focus-keyword para diagnóstico interno (Yoast-style).
 * · Robots avanzado: max-snippet, max-image-preview, max-video-preview,
 *   noarchive, nosnippet, noimageindex, notranslate.
 * · Open Graph con locale, alternates y dimensiones de imagen.
 * · Twitter con handles @sitio y @creador.
 * · Grupo article:* para posts (published_time, section, tags…).
 * · hreflang como JSON: cada entrada { lang, href }.
 * · Control de sitemap por página: prioridad, frecuencia, incluir/excluir.
 * · rel="prev" / rel="next" para paginación seriada.
 * · Tipo de Schema.org sugerido y bloque libre de HTML extra en el <head>
 *   (con aviso de riesgo en el panel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_paginas', function (Blueprint $table) {
            $table->string('titulo_h1', 200)->nullable()->after('titulo');
            $table->string('focus_keyword', 120)->nullable()->after('palabras_clave');

            // Robots avanzado
            $table->integer('max_snippet')->nullable()->after('seguir_enlaces');
            $table->string('max_image_preview', 20)->default('large')->after('max_snippet');
            $table->integer('max_video_preview')->nullable()->after('max_image_preview');
            $table->boolean('noarchive')->default(false)->after('max_video_preview');
            $table->boolean('nosnippet')->default(false)->after('noarchive');
            $table->boolean('noimageindex')->default(false)->after('nosnippet');
            $table->boolean('notranslate')->default(false)->after('noimageindex');

            // Open Graph completo
            $table->string('og_locale', 20)->default('es_CO')->after('og_tipo');
            $table->string('og_locale_alternate', 200)->nullable()->after('og_locale');
            $table->unsignedInteger('og_imagen_ancho')->nullable()->after('og_locale_alternate');
            $table->unsignedInteger('og_imagen_alto')->nullable()->after('og_imagen_ancho');

            // Twitter completo
            $table->string('twitter_sitio', 40)->nullable()->after('twitter_imagen');
            $table->string('twitter_creador', 40)->nullable()->after('twitter_sitio');

            // Article (para posts / blog)
            $table->timestamp('article_publicado_en')->nullable()->after('twitter_creador');
            $table->timestamp('article_modificado_en')->nullable()->after('article_publicado_en');
            $table->string('article_seccion', 80)->nullable()->after('article_modificado_en');
            $table->string('article_etiquetas', 200)->nullable()->after('article_seccion');
            $table->string('article_autor', 120)->nullable()->after('article_etiquetas');

            // Hreflang (JSON: [{lang, href}])
            $table->json('hreflang')->nullable()->after('article_autor');

            // Paginación seriada
            $table->string('rel_prev', 300)->nullable()->after('hreflang');
            $table->string('rel_next', 300)->nullable()->after('rel_prev');

            // Control de sitemap por página
            $table->boolean('sitemap_incluir')->default(true)->after('rel_next');
            $table->string('sitemap_frecuencia', 20)->default('weekly')->after('sitemap_incluir');
            $table->decimal('sitemap_prioridad', 2, 1)->default(0.5)->after('sitemap_frecuencia');

            // Schema.org sugerido
            $table->string('schema_tipo', 40)->nullable()->after('json_ld_extra');

            // Bloque libre de HTML dentro del <head>. Aviso en el panel.
            $table->text('head_extra')->nullable()->after('schema_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('seo_paginas', function (Blueprint $table) {
            $table->dropColumn([
                'titulo_h1', 'focus_keyword',
                'max_snippet', 'max_image_preview', 'max_video_preview',
                'noarchive', 'nosnippet', 'noimageindex', 'notranslate',
                'og_locale', 'og_locale_alternate', 'og_imagen_ancho', 'og_imagen_alto',
                'twitter_sitio', 'twitter_creador',
                'article_publicado_en', 'article_modificado_en',
                'article_seccion', 'article_etiquetas', 'article_autor',
                'hreflang', 'rel_prev', 'rel_next',
                'sitemap_incluir', 'sitemap_frecuencia', 'sitemap_prioridad',
                'schema_tipo', 'head_extra',
            ]);
        });
    }
};
