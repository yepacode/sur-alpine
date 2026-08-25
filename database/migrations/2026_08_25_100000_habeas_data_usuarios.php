<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Habeas Data (Ley 1581 de 2012 · Colombia).
 *
 * Guarda cuándo cada usuario aceptó los términos y qué versión aceptó,
 * porque «autorizó» sin fecha ni texto no le sirve al oficial de datos si
 * mañana lo piden. La `politica_version` sube cada vez que cambia el
 * documento; el sitio lee la versión vigente de `config('habeas.version')`.
 *
 * `baja_solicitada_en` recuerda si el usuario pidió cerrar su cuenta; hasta
 * que ese día llegue el sitio la trata como desactivada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('acepto_en')->nullable()->after('activo');
            $table->string('politica_version', 20)->nullable()->after('acepto_en');
            $table->timestamp('baja_solicitada_en')->nullable()->after('politica_version');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['acepto_en', 'politica_version', 'baja_solicitada_en']);
        });
    }
};
