<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entrar con Facebook o con Google.
 *
 * Sólo dos columnas: de qué proveedor viene la cuenta y su identificador allá.
 * El identificador se guarda —y no sólo el correo— porque una persona puede
 * cambiar el correo de su Facebook, y si el enlace fuera por correo, al día
 * siguiente entraría como si fuera otra.
 *
 * `password` pasa a ser opcional: quien entra con Google nunca eligió una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('proveedor', 20)->nullable()->after('password');
            $table->string('proveedor_id', 100)->nullable()->after('proveedor');

            $table->unique(['proveedor', 'proveedor_id']);
        });

        // SQLite (las pruebas) no permite cambiar una columna a nullable sin
        // doctrine/dbal; con `change()` de Laravel 11+ sí funciona en ambos.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['proveedor', 'proveedor_id']);
            $table->dropColumn(['proveedor', 'proveedor_id']);
        });
    }
};
