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

if (! function_exists('documento_legal')) {
    /**
     * Un documento legal escrito desde el panel, convertido a HTML seguro.
     *
     * La política de datos y los términos son textos que redacta un abogado y
     * que cambian sin avisar. Estaban clavados en el blade: para tocarles una
     * coma había que llamarnos. El cliente lo pidió y tiene razón.
     *
     * NO se acepta HTML: se escapa todo y después se reconstruye una
     * estructura mínima, la que un documento legal necesita de verdad.
     *
     *   · una línea que empieza con `## ` es un subtítulo;
     *   · una línea en blanco separa párrafos;
     *   · una línea que empieza con `- ` es un punto de lista.
     *
     * Escapar primero y dar forma después es lo que impide que alguien —o algo
     * pegado desde un Word— meta un `<script>` en una página pública.
     */
    function documento_legal(string $texto): string
    {
        $lineas = preg_split('/\R/', trim($texto));
        $html = [];
        $parrafo = [];
        $lista = [];

        $volcarParrafo = function () use (&$html, &$parrafo) {
            if ($parrafo !== []) {
                // Los saltos sueltos dentro de un parrafo se respetan: en un
                // documento legal a veces separan incisos.
                // `break-words`: un documento de habeas data lleva URLs y
                // correos largos —«proteccion.datos.personales@…»— y sin esto
                // una sola línea arrastraba la página entera en horizontal.
                // Medido: 210 px de desborde en un teléfono con una URL de
                // formulario normal.
                $html[] = '<p class="mt-3 break-words leading-relaxed text-tinta-700">'
                    .implode('<br>', array_map('e', $parrafo)).'</p>';
                $parrafo = [];
            }
        };

        $volcarLista = function () use (&$html, &$lista) {
            if ($lista !== []) {
                $html[] = '<ul class="mt-3 list-disc space-y-1 pl-6 text-tinta-700">';

                foreach ($lista as $punto) {
                    $html[] = '<li class="break-words">'.e($punto).'</li>';
                }

                $html[] = '</ul>';
                $lista = [];
            }
        };

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '') {
                $volcarParrafo();
                $volcarLista();

                continue;
            }

            if (str_starts_with($linea, '## ')) {
                $volcarParrafo();
                $volcarLista();
                $html[] = '<h2 class="mt-8 font-titulo text-lg font-bold text-tinta-900">'
                    .e(mb_substr($linea, 3)).'</h2>';

                continue;
            }

            if (str_starts_with($linea, '- ')) {
                $volcarParrafo();
                $lista[] = mb_substr($linea, 2);

                continue;
            }

            $volcarLista();
            $parrafo[] = $linea;
        }

        $volcarParrafo();
        $volcarLista();

        return implode("
", $html);
    }
}

if (! function_exists('plural')) {
    /**
     * Singular o plural, en castellano, diciendo las dos formas.
     *
     * `Str::plural()` es el pluralizador de Laravel y pluraliza en INGLÉS. Con
     * palabras que acaban en vocal acierta de casualidad —«repuesto» →
     * «repuestos»— y por eso pasó inadvertido, pero en la primera línea de la
     * pantalla más usada del panel se leía «3150 solicituds». Y con dos
     * palabras sólo toca la última: «3 pieza agregadas», «5 vehículo nuevos».
     *
     * Aquí no se adivina nada: se escriben las dos formas. Es más largo de
     * teclear y es la única manera de que no vuelva a salir un «solicituds».
     */
    function plural(int $cuantos, string $singular, string $plural): string
    {
        return $cuantos === 1 ? $singular : $plural;
    }
}

if (! function_exists('es_rastreador')) {
    /**
     * ¿La peticion viene de un rastreador (Googlebot, Bingbot, WhatsApp…)?
     *
     * Se usa para relajar reglas que a una persona la fastidiarian pero a
     * Google le harian mal. El caso concreto: obligar a elegir vehiculo antes
     * de ver el catalogo. Sin esto, Googlebot ve un modal en vez del listado y
     * cuando llegue el momento del dominio real el sitio queda sin indexar.
     *
     * No es antifraude —un humano con `curl -A Googlebot` pasa igual—; es
     * sensibilidad. La lista es la de agentes que decimos «respetamos» en
     * `robots.txt`, mas los generadores de vista previa de mensajes (WhatsApp,
     * Facebook, Slack) para que las tarjetas compartidas no salgan como una
     * captura del modal.
     */
    function es_rastreador(?string $userAgent = null): bool
    {
        $ua = strtolower($userAgent ?? request()->userAgent() ?? '');

        if ($ua === '') {
            return false;
        }

        foreach ([
            'bot', 'crawler', 'crawl', 'spider', 'slurp',
            'facebookexternalhit', 'whatsapp', 'twitterbot', 'linkedinbot',
            'slackbot', 'discordbot', 'telegrambot', 'skypeuripreview',
            'yandex', 'baidu', 'duckduckgo',
        ] as $marca) {
            if (str_contains($ua, $marca)) {
                return true;
            }
        }

        return false;
    }
}
