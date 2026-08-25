<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F · Editables desde el panel.
 *
 * Dos tablas hermanas, las dos con estructura clave-valor.
 *
 *   `contenidos` guarda los textos y las etiquetas de botón que la vista
 *   pinta con el helper `contenido('clave', 'valor por defecto')`. Si la
 *   clave no está en la tabla, se devuelve el fallback (que es el texto
 *   original del blade). Esto permite pintar el panel sobre el sitio ya
 *   funcionando, sin migrar strings a mano.
 *
 *   `seo_paginas` guarda el título, la descripción y la OG image que
 *   sobreescriben los `@section('titulo')` y `@section('descripcion')` de
 *   cada blade estática y de cada categoría/tipo. Igual: si la fila no
 *   existe, el sitio usa el valor original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenidos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 120)->unique();
            $table->string('grupo', 60)->index();
            $table->string('rotulo', 160);
            $table->text('valor')->nullable();
            // `texto`, `boton`, `parrafo` — para pintar el input adecuado.
            $table->string('tipo', 20)->default('texto');
            $table->text('valor_ejemplo')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_paginas', function (Blueprint $table) {
            $table->id();
            $table->string('ruta', 120)->unique();
            $table->string('etiqueta', 160);
            $table->string('titulo', 200)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('og_imagen', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_paginas');
        Schema::dropIfExists('contenidos');
    }
};
