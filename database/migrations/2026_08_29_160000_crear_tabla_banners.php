<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las campañas de la portada, administrables.
 *
 * Hasta ahora se leían del disco: el carrusel hacía `glob()` sobre
 * `public/img/banners` y los textos alternativos estaban escritos en el
 * controlador. Funcionaba mientras las subiéramos nosotros, pero el cliente
 * pidió poder poner y quitar campañas él, y con `glob()` no hay forma de
 * ordenarlas, apagarlas por temporada ni ponerles un texto.
 *
 * La migración NO empieza en blanco: recoge lo que ya está en el disco y lo
 * inserta con los mismos textos y el mismo orden que tenía el controlador. El
 * día del despliegue la portada tiene que verse igual antes y después.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            // El nombre base, sin `-1600.webp`. Las tres versiones se derivan.
            $table->string('archivo')->unique();
            $table->string('alt');
            $table->unsignedSmallInteger('orden')->default(10);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'orden']);
        });

        $this->recogerLosQueYaEstan();
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }

    /** Lo que el controlador tenía escrito a mano, ahora en la tabla. */
    private function recogerLosQueYaEstan(): void
    {
        $rotulos = [
            'sitio-oficial' => 'Importadora Sur Alpine: único sitio web oficial',
            'espirales' => 'Espirales Imal: mayor resistencia y duración',
            'gabriel' => 'Amortiguadores Gabriel: las mejores piezas de suspensión',
            'mac' => 'Baterías MAC',
            'bwb' => 'Frenos BWB',
            'incolbest' => 'Frenos Incolbest',
            'aceite' => 'Aceites y lubricantes',
        ];

        $filas = [];

        foreach (glob(public_path('img/banners/*-1600.webp')) as $ruta) {
            $base = str_replace('-1600.webp', '', basename($ruta));

            // El mismo criterio de antes: «sitio oficial» va primero. Los
            // demás quedan en el orden en que estaban los rótulos, que es el
            // que el cliente ve hoy.
            $posicion = 0;
            $alt = 'Novedades Sur Alpine';

            foreach (array_keys($rotulos) as $i => $clave) {
                if (str_contains($base, $clave)) {
                    $posicion = $i;
                    $alt = $rotulos[$clave];
                    break;
                }
            }

            $filas[] = [
                'archivo' => $base,
                'alt' => $alt,
                'orden' => $posicion,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($filas !== []) {
            DB::table('banners')->insert($filas);
        }
    }
};
