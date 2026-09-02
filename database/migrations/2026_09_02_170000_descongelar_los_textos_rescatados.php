<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Devuelve a «sin tocar» los textos que el rescate escribió a mano.
 *
 * La migración que recuperó los textos borrados hizo `valor = valor_ejemplo`
 * en las 12 filas que se habían quedado en blanco. Eso arregló lo urgente —el
 * botón de «Iniciar sesión» volvió a tener rótulo— pero dejó un efecto
 * secundario: esas 12 pasaron de «nadie ha tocado esto, manda la vista» a
 * «alguien escribió esto». Quedaron congeladas.
 *
 * Se vio enseguida. El lateral del catálogo dejó de listar tipos de parte y
 * pasó a listar categorías, así que su rótulo cambió de «Filtrar por parte» a
 * «Categorías»… y en producción seguía diciendo lo viejo, encima de una lista
 * de categorías. El texto estaba clavado en la base.
 *
 * Poner en nulo lo que es idéntico al valor de fábrica no cambia una sola
 * letra de lo que se ve hoy —son el mismo texto— y devuelve el comportamiento
 * que debe tener: si mañana la vista cambia su texto por defecto, la web se
 * entera. Lo que alguien haya escrito de verdad, distinto del de fábrica, no
 * se toca.
 */
return new class extends Migration
{
    public function up(): void
    {
        $descongelados = DB::table('contenidos')
            ->whereNotNull('valor')
            ->whereNotNull('valor_ejemplo')
            ->whereColumn('valor', 'valor_ejemplo')
            ->update(['valor' => null]);

        Cache::forget('contenidos.mapa.v1');

        if ($descongelados > 0) {
            echo "  Textos descongelados: {$descongelados} vuelven a seguir su valor de fábrica ".
                 "(lo que se ve en la web no cambia).\n";
        }
    }

    /**
     * No se deshace.
     *
     * Volver a escribir el valor de fábrica dentro de `valor` es justo lo que
     * esta migración viene a deshacer, y no se ve distinto de todas formas.
     */
    public function down(): void
    {
        //
    }
};
