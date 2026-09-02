<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Devuelve su texto a todo lo que se quedó en blanco sin querer.
 *
 * Lo que pasó: la casilla del panel mostraba el valor GUARDADO, no el que se
 * ve en la web. Un texto que nadie había tocado tiene `valor = null` —la web
 * enseña el de fábrica— y esa casilla salía vacía. Al pulsar «Guardar»,
 * esas casillas vacías se guardaron como cadena vacía, que el sitio
 * interpreta como «bórralo a propósito».
 *
 * Resultado: abrir «Textos e imágenes» y guardar, sin tocar nada, vaciaba de
 * golpe todos los textos nunca editados. En producción se quedaron sin rótulo
 * el botón de «Iniciar sesión» y el de BUSCAR del selector de vehículo, entre
 * otros siete de la portada. El cliente no hizo nada mal: hizo exactamente lo
 * que la pantalla invitaba a hacer.
 *
 * `valor_ejemplo` guarda el texto de fábrica de cada clave, así que se puede
 * recuperar. La causa está corregida en la vista del panel, que ahora muestra
 * el valor efectivo: lo que se lee ahí es lo que hay en la web.
 *
 * Nota honesta: si alguien había vaciado un texto A PROPÓSITO, esta migración
 * se lo devuelve. Es el precio de recuperar los ocho que se perdieron sin
 * querer, y volver a vaciarlo cuesta un clic —ahora sin ambigüedad, porque la
 * casilla enseña lo que va a borrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $recuperados = DB::table('contenidos')
            ->where('valor', '')
            ->whereNotNull('valor_ejemplo')
            ->where('valor_ejemplo', '<>', '')
            ->update(['valor' => DB::raw('valor_ejemplo')]);

        Cache::forget('contenidos.mapa.v1');

        if ($recuperados > 0) {
            echo "  Textos recuperados: {$recuperados} volvieron a su valor de fábrica.\n";
        }
    }

    /**
     * No se deshace.
     *
     * Volver a poner en blanco esos textos es justo el defecto que esta
     * migración viene a reparar.
     */
    public function down(): void
    {
        //
    }
};
