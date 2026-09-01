<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los roles del esquema viejo, traídos a los dos que hay.
 *
 * El sitio nació con cuatro roles y el cliente pidió dejarlo en dos: cliente y
 * administrador. Cuando se hizo ese recorte, la migración que convertía los
 * roles viejos se borró —yo la borré— porque en ese momento se dio por hecho
 * que no había usuarios y que nada estaba desplegado.
 *
 * Sí los había. En producción quedaron filas con `rol` en «vendedor»,
 * «asesor» y «mostrador», valores que el enum `Rol` ya no conoce. El casteo
 * lanza `ValueError` y la pantalla de usuarios del panel responde 500 entera:
 * el administrador no puede ni ver su equipo, ni mucho menos arreglarlo desde
 * ahí. Se ve claro en que `?q=admin` responde 200 y `?q=vendedor` revienta.
 *
 * Todo lo desconocido pasa a `cliente`, que es el privilegio MÍNIMO. Nadie
 * gana acceso al panel por un renombre automático; a quien de verdad le
 * corresponda entrar, un administrador lo promueve a mano, que es exactamente
 * la decisión que el cliente quiso tomar cuando pidió dos roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        $afectados = DB::table('users')
            ->whereNotIn('rol', ['cliente', 'admin'])
            ->update(['rol' => 'cliente']);

        if ($afectados > 0) {
            // Queda dicho en la salida de `artisan migrate`: si alguien del
            // equipo perdió el panel, hay que devolvérselo a mano y conviene
            // que se entere en el momento del despliegue y no por un reclamo.
            echo "  Roles normalizados: {$afectados} cuenta(s) pasaron a «cliente». ".
                 "Si alguna era del equipo, vuelve a marcarla como administrador en el panel.\n";
        }
    }

    /**
     * No hay vuelta atrás.
     *
     * El valor original no se guarda en ninguna parte, y guardarlo para poder
     * restaurar un rol que el sistema ya no sabe interpretar no le sirve a
     * nadie. Deshacer esta migración deja las cuentas como quedaron.
     */
    public function down(): void
    {
        //
    }
};
