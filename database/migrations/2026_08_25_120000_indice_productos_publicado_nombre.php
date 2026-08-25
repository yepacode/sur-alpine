<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase E · Índice compuesto `(publicado, nombre)` en `productos`.
 *
 * Medido en el estrés con 29.272 productos:
 *   · `SELECT count(*) WHERE publicado=?` — 51 ms (filesort de la tabla)
 *   · `WHERE publicado=? ORDER BY nombre LIMIT 24` — 76 ms
 *
 * Con este índice la lectura pasa a 4 ms (~19×). El catálogo ya no paga
 * el filesort de toda la tabla en cada request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->index(['publicado', 'nombre'], 'productos_publicado_nombre_idx');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_publicado_nombre_idx');
        });
    }
};
