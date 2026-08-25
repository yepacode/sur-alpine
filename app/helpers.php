<?php

/*
 * F · Helpers globales para el CRUD de textos y SEO editables.
 *
 * · `contenido('hero.titulo', 'La pieza exacta')` — devuelve el texto que
 *   el panel guardó para esa clave, o el fallback (texto original) si aún
 *   no ha tocado esa cadena. Con esto, la vista se pinta sobre el sitio
 *   ya funcionando y el panel entra encima sin migrar strings.
 *
 * · `seo_pagina(?string $ruta)` — devuelve la fila `SeoPagina` para el
 *   nombre de ruta pasado (o el actual si no se pasa), o null si el
 *   administrador no la ha personalizado.
 */

use App\Models\Contenido;
use App\Models\SeoPagina;

if (! function_exists('contenido')) {
    function contenido(string $clave, string $porDefecto = ''): string
    {
        $mapa = Contenido::mapa();
        return isset($mapa[$clave]) && $mapa[$clave] !== ''
            ? $mapa[$clave]
            : $porDefecto;
    }
}

if (! function_exists('seo_pagina')) {
    function seo_pagina(?string $ruta = null): ?SeoPagina
    {
        return SeoPagina::para($ruta ?? request()?->route()?->getName());
    }
}
