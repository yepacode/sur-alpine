<?php

return [

    /*
 * Interruptores de la portada.
 *
 * `modulo_clientes` enciende el área de cliente: registro, «Mi cuenta», sus
 * vehículos, su historial de mantenimientos y sus cotizaciones. Está en `true`
 * y el módulo existe completo —`CuentaController` y quince rutas—; el
 * interruptor se queda por si el cliente quiere apagarlo en algún momento.
 *
 * (El comentario que había aquí decía que «todavía no hay registro de clientes
 * ni Mi cuenta», y quien lo leyera concluía lo contrario de lo que es.)
 */

    'modulo_clientes' => env('MODULO_CLIENTES', true),

];
