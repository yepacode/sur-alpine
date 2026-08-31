<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El tipo de parte, congelado en el ítem de la cotización.
 *
 * `cotizacion_items` ya congela `producto_nombre` y `vehiculo_nombre` por una
 * razón clara: lo que el cliente pidió ese día no puede cambiar porque después
 * se toque el catálogo. Faltaba el tipo de parte, y eso hacía que el tablero
 * reescribiera su propia historia.
 *
 * Lo que pasaba: retirar una pieza desde la matriz del panel borra la fila de
 * `productos` y deja `cotizacion_items.producto_id` en `null` —a propósito,
 * para que el histórico sobreviva—. Pero «Partes más pedidas» llegaba al
 * nombre por un `join` con `productos`, así que esos ítems desaparecían del
 * gráfico mientras la tarjeta «Repuestos solicitados» los seguía contando. Dos
 * cifras que se contradicen en la misma pantalla, y que cambian solas cada vez
 * que alguien depura el catálogo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->string('tipo_parte_nombre')->nullable()->after('producto_nombre');
        });

        // Subconsulta correlacionada y no `join`: el banco de pruebas corre
        // sobre SQLite, que no admite `UPDATE ... JOIN`. Esta forma la
        // entienden los dos motores.
        //
        // Los que aún tienen su producto vivo se rellenan desde él. Los que ya
        // lo perdieron se quedan en `null` y el tablero los agrupa aparte: no
        // hay de dónde sacar ese dato, e inventarlo sería peor.
        DB::table('cotizacion_items')
            ->whereNotNull('producto_id')
            ->update(['tipo_parte_nombre' => DB::raw(
                '(select tipos_parte.nombre from productos'
                .' inner join tipos_parte on tipos_parte.id = productos.tipo_parte_id'
                .' where productos.id = cotizacion_items.producto_id)'
            )]);
    }

    public function down(): void
    {
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropColumn('tipo_parte_nombre');
        });
    }
};
