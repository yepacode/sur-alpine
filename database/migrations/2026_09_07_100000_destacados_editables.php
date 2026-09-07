<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los productos destacados de la portada, elegidos a mano.
 *
 * Antes se ordenaban solos por «veces cotizado» y no había forma de que el
 * equipo empujara una promo o retirara una pieza vieja. Peticion del cliente:
 * poder elegir cuáles salen y darles su propio orden.
 *
 * El campo `destacado_orden` es NULLABLE a propósito. `null` = no es
 * destacado; cualquier número = orden ascendente en el carrusel. Con nadie
 * marcado (todos en null), el sitio cae al automático de siempre — así el
 * carrusel nunca sale vacío y no obliga a rellenar diez fichas antes de
 * publicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $t) {
            // `smallInteger` (no `integer`) porque nunca van a ser más que
            // decenas; nadie va a poner un carrusel de 3.000 destacados.
            $t->smallInteger('destacado_orden')->nullable()->after('publicado');
            $t->index('destacado_orden', 'productos_destacado_orden_idx');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $t) {
            $t->dropIndex('productos_destacado_orden_idx');
            $t->dropColumn('destacado_orden');
        });
    }
};
