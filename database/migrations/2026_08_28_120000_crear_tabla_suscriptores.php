<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El newsletter del pie.
 *
 * Existe porque el botón existe. En el sitio actual ese formulario lo maneja un
 * plugin y los correos no se ven en ninguna parte del panel; aquí quedan en una
 * tabla que el administrador puede mirar y exportar. Un formulario que traga
 * direcciones y no las guarda en ningún sitio es peor que no tener formulario.
 *
 * `origen` guarda desde qué página se suscribió: sirve para saber si el que
 * trae suscriptores es el pie de la portada o el de una nota del blog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscriptores', function (Blueprint $table) {
            $table->id();
            $table->string('correo', 190)->unique();
            $table->string('origen', 190)->nullable();
            $table->timestamp('baja_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscriptores');
    }
};
