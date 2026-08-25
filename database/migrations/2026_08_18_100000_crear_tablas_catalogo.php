<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo Sur Alpine.
 *
 * El catálogo real no es una lista de productos sueltos: es una matriz de
 * compatibilidad de 224 vehículos por 290 tipos de parte. Cada celda marcada
 * en el Excel del cliente se convierte en una fila de `productos`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->constrained('marcas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['marca_id', 'slug']);
        });

        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_id')->constrained('modelos')->cascadeOnDelete();

            // Texto, no entero: el Excel trae "1700 DIESEL", "1600 M.N", "1300 CARB".
            $table->string('cilindraje', 40);

            $table->unsignedSmallInteger('anio_inicio');
            $table->unsignedSmallInteger('anio_fin');
            $table->string('slug')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // El rango de años hace parte de la identidad: el Optra 1800 existe
            // como 2004-2006 y como 2007-2013, y son dos vehículos distintos.
            $table->unique(['modelo_id', 'cilindraje', 'anio_inicio'], 'vehiculos_identidad_unica');
            $table->index(['modelo_id', 'cilindraje']);
            $table->index(['anio_inicio', 'anio_fin']);
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('imagen')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('tipos_parte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->string('imagen_defecto')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['categoria_id', 'slug']);
            $table->index('slug');
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('tipo_parte_id')->constrained('tipos_parte')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('referencia')->nullable();
            $table->string('imagen')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('publicado')->default(true);
            $table->unsignedInteger('vistas')->default(0);
            $table->timestamps();

            // Una celda de la matriz = un producto. Impide duplicados desde la base.
            $table->unique(['vehiculo_id', 'tipo_parte_id'], 'productos_matriz_unica');
            $table->index(['tipo_parte_id', 'vehiculo_id']);
            $table->index('referencia');
        });

        // El buscador consulta por nombre y por referencia de parte.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('productos', function (Blueprint $table) {
                $table->fullText(['nombre', 'referencia'], 'productos_busqueda');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('tipos_parte');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('modelos');
        Schema::dropIfExists('marcas');
    }
};
