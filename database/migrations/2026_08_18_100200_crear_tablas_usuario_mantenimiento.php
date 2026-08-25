<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles, vehículos guardados y mantenimiento.
 *
 * Los roles son una escalera: cada uno puede todo lo del anterior más algo.
 *   cliente  → navega, cotiza, guarda vehículos y lleva mantenimientos
 *   vendedor → además recibe las solicitudes, ve la bandeja y el tablero
 *   asesor   → además edita el catálogo y da de alta vehículos
 *   admin    → además configura correos y administra usuarios
 *
 * Los mecánicos entran como "cliente".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('cliente')->after('email');
            $table->string('telefono', 30)->nullable()->after('rol');
            $table->boolean('activo')->default(true)->after('telefono');

            $table->index('rol');
        });

        Schema::create('vehiculos_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('alias')->nullable();
            $table->string('placa', 10)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'vehiculo_id']);
        });

        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();

            $table->string('placa', 10);
            $table->unsignedInteger('kilometraje');
            $table->string('tipo');
            $table->date('fecha');

            // Periodicidad configurable: por días, por meses o por kilometraje.
            $table->string('periodicidad_tipo', 20)->default('meses');
            $table->unsignedInteger('periodicidad_valor')->default(6);

            // Se calculan al guardar, para que el listado no tenga que hacerlo.
            $table->date('proximo_fecha')->nullable();
            $table->unsignedInteger('proximo_kilometraje')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'placa']);
            $table->index('proximo_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
        Schema::dropIfExists('vehiculos_usuario');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rol']);
            $table->dropColumn(['rol', 'telefono', 'activo']);
        });
    }
};
