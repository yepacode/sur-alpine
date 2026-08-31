<?php

/*
 * F · Helpers globales para el CRUD de textos y SEO editables.
 *
 * · `contenido('hero.titulo', 'La pieza exacta')` — devuelve el texto que
 *   el panel guardó para esa clave, o el fallback (texto original) si aún
 *   no ha tocado esa cadena. Con esto, la vista se pinta sobre el sitio
 *   ya funcionando y el panel entra encima sin migrar strings.
 *
 * · `imagen_contenido('imagen.local', '/img/fotos/local')` — igual que el
 *   anterior pero para fotos. Devuelve SIEMPRE el nombre base, sin el
 *   `-{ancho}.webp`: quien lo use arma el `srcset` con los anchos que
 *   necesite. Que todas las imágenes editables funcionen igual es lo que
 *   evita que la mitad se suban en un formato y la otra mitad en otro.
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

        // `null` significa «nunca lo tocaron»: se usa el original. La cadena
        // VACÍA es una decisión —el cliente borró ese texto a propósito— y hay
        // que respetarla. Antes las dos caían en el mismo saco, así que vaciar
        // un campo lo devolvía a su valor de fábrica y el panel parecía no
        // hacer caso.
        return $mapa[$clave] ?? $porDefecto;
    }
}

if (! function_exists('imagen_contenido')) {
    function imagen_contenido(string $clave, string $porDefecto): string
    {
        return contenido($clave, $porDefecto);
    }
}

if (! function_exists('celda_csv')) {
    /**
     * Blinda una celda antes de escribirla en un CSV.
     *
     * Excel evalúa como fórmula cualquier celda que empiece por `=`, `+`, `-`
     * o `@`, así que `=1+1@x.test` en un correo se convierte en código que se
     * ejecuta en la máquina de quien abre el archivo. El formulario del
     * boletín está en el pie de todas las páginas y no pide sesión: la parte
     * local de un correo válido puede empezar por cualquiera de esos signos.
     *
     * Vive aquí y no copiado en cada controlador porque ya pasó: el blindaje
     * estaba en la exportación de solicitudes y faltaba en la de suscriptores,
     * que es la puerta de al lado y la que sí es pública.
     */
    function celda_csv(mixed $valor): mixed
    {
        return is_string($valor) && $valor !== '' && str_contains("=+-@	

", $valor[0])
            ? "'".$valor
            : $valor;
    }
}

if (! function_exists('seo_pagina')) {
    function seo_pagina(?string $ruta = null): ?SeoPagina
    {
        return SeoPagina::para($ruta ?? request()?->route()?->getName());
    }
}

if (! function_exists('version_habeas')) {
    /**
     * La versión vigente de la política de datos.
     *
     * Existía en dos sitios que no se hablaban: el panel guardaba
     * `politica.version` —y eso sólo cambiaba el número impreso en la página—
     * mientras el consentimiento de cada persona se sellaba contra
     * `config('habeas.version')`, o sea contra el `.env`.
     *
     * Traducido: el dueño subía una política nueva, ponía «2» en el panel, la
     * web decía «Versión 2» y NADIE volvía a aceptar nada. Ante la SIC eso es
     * un consentimiento desactualizado con la evidencia diciendo lo contrario.
     *
     * Ahora manda lo que el panel guardó, y el `.env` queda como valor de
     * arranque para una instalación nueva.
     */
    function version_habeas(): string
    {
        $delPanel = trim(contenido('politica.version', ''));

        return $delPanel !== '' ? $delPanel : (string) config('habeas.version');
    }
}
