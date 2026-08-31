<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los mensajes del formulario de «Contáctenos».
 *
 * En el sitio actual ese formulario lo maneja un plugin: el mensaje sale por
 * correo y no queda registrado en ninguna parte. Si el correo rebota o alguien
 * lo borra sin querer, el mensaje se perdió y nadie se entera.
 *
 * Aquí se guarda primero y se manda después, con la misma disciplina que las
 * cotizaciones: `correo_enviado_en` y `error_envio` permiten ver en el panel
 * cuáles salieron y reintentar los que no.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('email', 190);
            $table->text('mensaje');
            $table->timestamp('correo_enviado_en')->nullable();
            $table->string('error_envio', 500)->nullable();
            $table->timestamp('atendido_en')->nullable();
            $table->timestamps();

            // El panel lista siempre lo mismo: los más nuevos primero, y aparte
            // los que están sin atender.
            $table->index(['atendido_en', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
