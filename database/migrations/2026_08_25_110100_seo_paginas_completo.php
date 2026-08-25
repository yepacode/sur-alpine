<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F · SEO por página, completo.
 *
 * La primera migración de `seo_paginas` sólo trajo `titulo`, `descripcion`
 * y `og_imagen`. El asesor pidió que no faltara nada: se amplía a
 *
 *   · palabras clave (para IAs y buscadores viejos),
 *   · canonical (evita duplicados en Google),
 *   · Open Graph: título, descripción, texto alterno de la imagen, tipo,
 *   · Twitter: card, título, descripción, imagen,
 *   · robots: `index/noindex` y `follow/nofollow`,
 *   · slug editable (con advertencia en el panel: cambiarlo rompe URLs),
 *   · JSON-LD extra pegado a mano para casos especiales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_paginas', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->after('ruta');
            $table->string('palabras_clave', 300)->nullable()->after('descripcion');
            $table->string('canonical', 300)->nullable()->after('palabras_clave');

            $table->string('og_titulo', 200)->nullable()->after('og_imagen');
            $table->text('og_descripcion')->nullable()->after('og_titulo');
            $table->string('og_imagen_alt', 200)->nullable()->after('og_descripcion');
            $table->string('og_tipo', 40)->default('website')->after('og_imagen_alt');

            $table->string('twitter_card', 30)->default('summary_large_image')->after('og_tipo');
            $table->string('twitter_titulo', 200)->nullable()->after('twitter_card');
            $table->text('twitter_descripcion')->nullable()->after('twitter_titulo');
            $table->string('twitter_imagen', 300)->nullable()->after('twitter_descripcion');

            $table->boolean('indexable')->default(true)->after('twitter_imagen');
            $table->boolean('seguir_enlaces')->default(true)->after('indexable');

            $table->text('json_ld_extra')->nullable()->after('seguir_enlaces');
        });
    }

    public function down(): void
    {
        Schema::table('seo_paginas', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'palabras_clave', 'canonical',
                'og_titulo', 'og_descripcion', 'og_imagen_alt', 'og_tipo',
                'twitter_card', 'twitter_titulo', 'twitter_descripcion', 'twitter_imagen',
                'indexable', 'seguir_enlaces', 'json_ld_extra',
            ]);
        });
    }
};
