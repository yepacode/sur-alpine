<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo se avisó de cada mantenimiento.
 *
 * Sin esta columna el recordatorio no se puede mandar: el comando corre todos
 * los días y no habría forma de saber a quién ya se le escribió, así que el
 * mismo cambio de aceite llegaría por correo cada mañana hasta que la persona
 * lo anotara o se diera de baja del sitio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->timestamp('aviso_enviado_en')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn('aviso_enviado_en');
        });
    }
};
