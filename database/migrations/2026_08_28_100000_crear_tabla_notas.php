<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Actualízate con Nosotros» — las notas del blog.
 *
 * En el sitio actual son entradas de WordPress con una URL imposible de
 * recordar («/que-es-el-kit-de-distribucion/2023/05/25/15/12/00/1666/…»), y la
 * tarjeta del kit de distribución en la portada apunta a `#`: el artículo
 * existe, pero desde la portada no se llega. Aquí la URL es `/noticias/{slug}`
 * y cada tarjeta lleva a su nota.
 *
 * El cuerpo se guarda como texto plano con líneas: cada renglón es un párrafo,
 * los que empiezan por «## » son subtítulos y los que empiezan por «- » son
 * viñetas. Sin editor enriquecido a propósito —quien escribe aquí es el asesor
 * del mostrador, no un editor— y sin HTML crudo en base, que es lo que abre la
 * puerta a que una nota inyecte scripts en la página.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            // El arranque que se ve en la tarjeta de la portada.
            $table->string('resumen', 400);
            $table->text('cuerpo');
            $table->string('imagen', 300)->nullable();
            $table->string('categoria', 60)->default('Noticias');
            $table->boolean('publicada')->default(true);
            $table->timestamp('publicada_en')->nullable();
            $table->timestamps();

            // El listado y la portada piden siempre lo mismo: publicadas, de la
            // más nueva a la más vieja.
            $table->index(['publicada', 'publicada_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
