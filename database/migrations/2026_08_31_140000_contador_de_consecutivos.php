<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una fila por año para numerar las solicitudes.
 *
 * El consecutivo se calculaba buscando el último `SA-2026-…` con
 * `lockForUpdate()`. Eso serializa bien mientras haya filas del año, pero la
 * PRIMERA de cada año no bloquea ninguna fila: InnoDB toma bloqueos de hueco,
 * que no se excluyen entre sí, y varios envíos simultáneos acaban en
 * interbloqueo. Medido: con cinco envíos a la vez sobre un año vacío, cuatro
 * fallaban; con diez, seis. Con una sola fila ya existente, doce simultáneos
 * daban doce números correlativos sin un solo fallo.
 *
 * O sea que rompía exactamente dos días: el del estreno y cada 1 de enero. Los
 * dos días en que el cliente está mirando.
 *
 * Bloquear una fila que SIEMPRE existe elimina el hueco y con él el
 * interbloqueo. El contador no reemplaza al índice único de `consecutivo`: ese
 * sigue siendo la garantía de que no hay dos iguales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contadores', function (Blueprint $table) {
            // La clave es el prefijo del año («SA-2026-»), así que la tabla
            // sirve para cualquier otra serie que haga falta después.
            $table->string('clave', 40)->primary();
            $table->unsignedInteger('valor')->default(0);
            $table->timestamps();
        });

        // Se arranca desde el número más alto que ya exista, para no repetir
        // ninguno de los que están en la base.
        foreach (DB::table('cotizaciones')->pluck('consecutivo') as $consecutivo) {
            if (! preg_match('/^(SA-\d{4}-)(\d+)$/', (string) $consecutivo, $partes)) {
                continue;
            }

            $prefijos[$partes[1]] = max($prefijos[$partes[1]] ?? 0, (int) $partes[2]);
        }

        foreach ($prefijos ?? [] as $prefijo => $ultimo) {
            DB::table('contadores')->insert([
                'clave' => $prefijo,
                'valor' => $ultimo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores');
    }
};
