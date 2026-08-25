<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Módulo de clientes
    |--------------------------------------------------------------------------
    |
    | Las tablas de `mantenimientos` y `vehiculos_usuario` ya existen, pero
    | todavía no hay registro de clientes ni «mi cuenta». Mientras esto sea
    | `false`, la portada anuncia el historial de mantenimiento como algo que
    | viene, en vez de mandar al visitante a un formulario de acceso donde no
    | puede crear cuenta: hoy ese botón es un callejón sin salida.
    |
    | Cuando el módulo esté construido, se pone en `true` y vuelven las
    | llamadas a registrarse tal como estaban. No hay que tocar nada más.
    |
    */

    'modulo_clientes' => env('MODULO_CLIENTES', true),

];
