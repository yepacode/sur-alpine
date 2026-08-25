<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotizaciones.
 *
 * El proceso comercial es por fuera del sistema: llega el correo al equipo y
 * ellos llaman al cliente con los precios del día. Aquí no hay flujo de
 * estados. El registro existe como respaldo: si el correo falla, la solicitud
 * queda visible en el panel y se puede reenviar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('consecutivo')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('nombre');
            $table->string('apellidos')->nullable();
            $table->string('telefono');
            $table->string('email');
            $table->text('notas')->nullable();

            $table->timestamp('correo_enviado_en')->nullable();
            $table->text('error_envio')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            // El tablero cuenta cotizaciones por período.
            $table->index('created_at');
            $table->index('correo_enviado_en');
        });

        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();

            // Cada ítem sabe a qué vehículo pertenece: eso es lo que permite
            // que un mecánico cotice tres o cuatro carros en una sola solicitud.
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();

            // Nombres congelados: si mañana se edita o retira el producto, la
            // solicitud histórica sigue diciendo qué se pidió.
            $table->string('producto_nombre');
            $table->string('vehiculo_nombre');

            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();

            $table->index(['cotizacion_id', 'vehiculo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');
    }
};
