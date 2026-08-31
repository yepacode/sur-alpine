<?php

/*
 * Habeas Data · Ley 1581 de 2012 (Colombia).
 *
 * La `version` sube cada vez que se toca el texto de la política de datos.
 * El sitio la guarda junto a la fecha de aceptación de cada usuario, para
 * que quede claro qué documento aceptó, no sólo cuándo. Empezar en 1 con
 * la fecha del despliegue original.
 */
return [
    'version' => env('HABEAS_VERSION', '1'),
    'vigente_desde' => env('HABEAS_VIGENTE_DESDE', '2026-08-25'),

    // Los correos de contacto del responsable del tratamiento salen de aquí
    // porque los pinta la política y el pie del sitio. Van sueltos, no como
    // parte del texto, para poder cambiarlos sin tocar el HTML.
    'responsable' => [
        'razon_social' => env('HABEAS_RAZON_SOCIAL', 'Importadora Sur Alpine S.A.S.'),
        'nit' => env('HABEAS_NIT', '900.000.000-0'),
        'correo' => env('HABEAS_CORREO', 'datos@suralpine.com'),
        'telefono' => env('HABEAS_TELEFONO', '(601) 000 0000'),
        'direccion' => env('HABEAS_DIRECCION', 'Av. Caracas #19-15 sur, Bogotá D.C.'),
    ],
];
