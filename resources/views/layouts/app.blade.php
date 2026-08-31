<!DOCTYPE html>
<html lang="es" class="h-full">
@php
    // F · Todo lo que el panel «Configuración de página» pueda haber tocado.
    // Si no hay fila para la ruta actual, `seo_pagina()` devuelve null y se
    // usan los `@yield` originales del blade.
    $seo = seo_pagina();

    /*
     * Lo que una página declara con `@section`, en crudo.
     *
     * `@section('titulo', $algo)` YA escapa el valor —lo hace Laravel al
     * guardarlo—, y aquí abajo `{{ }}` lo escapa otra vez. Doble escapado: un
     * repuesto llamado «Piñón & Corona» salía como «Piñón &amp;amp; Corona» en
     * la pestaña, en el título de Google y en la tarjeta de WhatsApp.
     *
     * Llevaba ahí desde siempre y no se notaba porque ningún nombre del
     * catálogo tiene un `&`. Saltó al poner el canonical de las paginadas:
     * las URLs SÍ llevan `&`, y `?orden=z-a&page=2` se publicaba como
     * `?orden=z-a&amp;page=2` —una dirección distinta, con un parámetro
     * inventado llamado `amp;page`— que además se realimentaba, porque el
     * paginador conserva la query y el `rel=next` de esa página le añadía otro
     * `amp;` encima.
     *
     * Se decodifica una vez aquí para que el `{{ }}` de abajo escape una sola.
     */
    $seccion = fn (string $nombre, string $porDefecto = '') => html_entity_decode(
        trim($__env->yieldContent($nombre, $porDefecto)),
        ENT_QUOTES,
        'UTF-8'
    );

    $tituloBase = $seccion('titulo', 'Repuestos y autopartes');
    $descBase = $seccion('descripcion', 'Importadora Sur Alpine: repuestos y autopartes para vehículos livianos. Encuentra la pieza exacta de tu carro y pide tu cotización.');
    // La tarjeta de 1200x630, no el logo suelto.
    //
    // El logo mide 280x351: por debajo del minimo de 300 px de X y muy por
    // debajo de los 600 que piden Facebook y WhatsApp para la tarjeta grande.
    // Con `twitter:card = summary_large_image` declarado en todas las paginas,
    // el resultado era que TODOS los enlaces que un asesor manda por WhatsApp
    // llegaban pelados. La tarjeta ademas dice «sitio oficial», que es lo que
    // el cliente necesita frente a las copias que lo suplantan.
    $imgBase = $seccion('og-imagen', url('/img/logo/og-sur-alpine.webp'));
    $imgBaseEsLaNuestra = $imgBase === url('/img/logo/og-sur-alpine.webp');

    $tituloFinal = $seo?->titulo ?: ($tituloBase.' · Importadora Sur Alpine');
    $descFinal = $seo?->descripcion ?: $descBase;
    // Una página puede imponer su canonical con `@section('canonical', ...)`.
    // Hace falta para las que llevan un dato personal en la URL —la baja del
    // boletín trae el correo firmado—: sin esto, esa dirección terminaba
    // escrita en el `canonical` y en `og:url`, que es justo donde no debe
    // estar. Estar en `noindex` no basta; esas etiquetas viajan igual.
    $canonicalFinal = $seccion('canonical') ?: ($seo?->canonical ?: url()->current());
    // `metaRobots()` ya incluye max-image-preview desde el panel; concatenar
    // aquí `max-image-preview:large` duplicaba el token y contradecía a un
    // admin que hubiera pedido `none`.
    // Una página puede imponer su `robots` con `@section('robots', ...)`. Las
    // privadas y las de trámite lo necesitan: salían todas `index,follow`,
    // incluidas `/mi-cuenta`, `/mi-cotizacion` y `/cotizacion-enviada`. Va por
    // `@section` y no por `@push` para que NO queden dos etiquetas
    // contradictorias en el mismo `<head>`, que es lo que pasaba en
    // `/clave-olvidada`.
    $robotsFinal = trim($__env->yieldContent('robots'))
        ?: ($seo ? $seo->metaRobots() : 'index,follow,max-image-preview:large');

    $ogTitulo = $seo?->og_titulo ?: $tituloFinal;
    $ogDesc = $seo?->og_descripcion ?: $descFinal;
    $ogImg = $seo?->og_imagen ?: $imgBase;
    $ogAlt = $seo?->og_imagen_alt
        ?: ($seccion('og-imagen-alt') ?: 'Importadora Sur Alpine · Sitio oficial');
    $ogTipo = $seo?->og_tipo ?: 'website';

    $twCard = $seo?->twitter_card ?: 'summary_large_image';
    $twTitulo = $seo?->twitter_titulo ?: $ogTitulo;
    $twDesc = $seo?->twitter_descripcion ?: $ogDesc;
    $twImg = $seo?->twitter_imagen ?: $ogImg;
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tituloFinal }}</title>
    <meta name="description" content="{{ $descFinal }}">
    @if ($seo?->palabras_clave)
        <meta name="keywords" content="{{ $seo->palabras_clave }}">
    @endif

    <link rel="canonical" href="{{ $canonicalFinal }}">
    <meta name="robots" content="{{ $robotsFinal }}">

    {{-- Este negocio se mueve por WhatsApp: cuando un asesor pasa el enlace de
         una pieza, esto es lo que decide si llega con foto y título o pelado. --}}
    <meta property="og:type" content="{{ $ogTipo }}">
    <meta property="og:site_name" content="Importadora Sur Alpine">
    {{-- og:locale se emite abajo con el valor del panel (fallback es_CO). Antes
         salía dos veces —una hardcoded aquí y otra desde el panel— y
         Facebook tomaba la primera, ignorando el cambio del admin. --}}
    <meta property="og:title" content="{{ $ogTitulo }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ $canonicalFinal }}">
    <meta property="og:image" content="{{ $ogImg }}">
    <meta property="og:image:alt" content="{{ $ogAlt }}">

    <meta name="twitter:card" content="{{ $twCard }}">
    <meta name="twitter:title" content="{{ $twTitulo }}">
    <meta name="twitter:description" content="{{ $twDesc }}">
    <meta name="twitter:image" content="{{ $twImg }}">
    @if ($seo?->twitter_sitio)
        <meta name="twitter:site" content="{{ $seo->twitter_sitio }}">
    @endif
    @if ($seo?->twitter_creador)
        <meta name="twitter:creator" content="{{ $seo->twitter_creador }}">
    @endif

    {{-- OG locale + alternates + dimensiones de imagen (Facebook las usa
         para decidir qué card mostrar sin descargar la imagen dos veces). --}}
    <meta property="og:locale" content="{{ $seo?->og_locale ?: 'es_CO' }}">
    @if ($seo?->og_locale_alternate)
        @foreach (preg_split('/[\s,]+/', $seo->og_locale_alternate, -1, PREG_SPLIT_NO_EMPTY) as $loc)
            <meta property="og:locale:alternate" content="{{ $loc }}">
        @endforeach
    @endif
    {{-- Las medidas salen SIEMPRE que se conozcan. Sin ellas, el primer
         rastreo tiene que bajar la imagen entera para medirla, y hasta que
         termina muestra la tarjeta pequena. --}}
    @if ($seo?->og_imagen_ancho)
        <meta property="og:image:width" content="{{ $seo->og_imagen_ancho }}">
        @if ($seo?->og_imagen_alto)
            <meta property="og:image:height" content="{{ $seo->og_imagen_alto }}">
        @endif
    @elseif (! $seo?->og_imagen && $imgBaseEsLaNuestra)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif

    {{-- article:* — sólo salen si el schema tipo es Article y hay datos. --}}
    @if ($seo?->article_publicado_en)
        <meta property="article:published_time" content="{{ $seo->article_publicado_en->toIso8601String() }}">
    @endif
    @if ($seo?->article_modificado_en)
        <meta property="article:modified_time" content="{{ $seo->article_modificado_en->toIso8601String() }}">
    @endif
    @if ($seo?->article_seccion)
        <meta property="article:section" content="{{ $seo->article_seccion }}">
    @endif
    @if ($seo?->article_autor)
        <meta property="article:author" content="{{ $seo->article_autor }}">
    @endif
    @if ($seo?->article_etiquetas)
        @foreach (preg_split('/\s*,\s*/', $seo->article_etiquetas, -1, PREG_SPLIT_NO_EMPTY) as $tag)
            <meta property="article:tag" content="{{ $tag }}">
        @endforeach
    @endif

    {{-- Idiomas alternativos (hreflang). --}}
    @if ($seo?->hreflang)
        @foreach ($seo->hreflang as $alt)
            <link rel="alternate" hreflang="{{ $alt['lang'] }}" href="{{ $alt['href'] }}">
        @endforeach
    @endif

    {{-- Paginación seriada. Google todavía respeta rel="next" cuando lo ve.
         La pagina manda con `@section('rel-prev'/'rel-next')`; el panel sigue
         pudiendo imponerlos a mano para las estaticas. --}}
    @php
        $relPrev = $seccion('rel-prev') ?: $seo?->rel_prev;
        $relNext = $seccion('rel-next') ?: $seo?->rel_next;
    @endphp
    @if ($relPrev)
        <link rel="prev" href="{{ $relPrev }}">
    @endif
    @if ($relNext)
        <link rel="next" href="{{ $relNext }}">
    @endif

    {{-- Schema.org por página: si el asesor eligió un `schema_tipo` en el
         panel, se emite un JSON-LD ligero con los básicos. No reemplaza al
         `<x-negocio-schema />` global; añade otro nodo con el tipo específico
         de la página (AboutPage, ContactPage, Article, WebPage…). --}}
    @if ($seo?->schema_tipo)
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => $seo->schema_tipo,
            'name' => $tituloFinal,
            'url' => $canonicalFinal,
            'description' => $descFinal,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif

    @if ($seo?->json_ld_extra)
        {{-- H1 · Seguridad: aunque el campo es JSON-LD, el asesor podría
             pegar `</script><script>alert(1)</script>` y ejecutar JS en el
             visitante anónimo. Reemplazar `</` por `<\/` mantiene el JSON
             válido para Google y cierra la fuga. `<` haría lo mismo. --}}
        <script type="application/ld+json">{!! str_replace('</', '<\/', $seo->json_ld_extra) !!}</script>
    @endif

    {{-- Bloque libre del asesor. AVISO en el panel: se pinta tal cual, así
         que puede meter un pixel de Meta o un GTM sin tocar código. --}}
    @if ($seo?->head_extra)
        {!! $seo->head_extra !!}
    @endif

    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/img/logo/logo-en-png-sur-alpine.webp" type="image/webp">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#1866e0">

    {{-- E1 · Tipografía alojada en el propio dominio. Los siete `.woff2`
         (Archivo 600/700/800, Barlow 400/500/600/700, subconjunto latin)
         viven en `public/fonts/`. Antes venía de `fonts.googleapis.com`,
         que forzaba una conexión TLS aparte, un `preconnect` sólo para
         eso, y arrastraba cookies de Google en cada visita. `preload` sobre
         los dos pesos que aparecen primero acelera el LCP. --}}
    <link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/fonts/fonts.css">

    <x-negocio-schema />

    @stack('cabeza')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- El telón de entrada. Este script va en línea y ANTES del cuerpo a
         propósito: decide si hay telón antes del primer pintado, para que
         quien ya lo vio en esta sesión no lo alcance a ver parpadear.

         Todo lo que puede fallar falla hacia «no hay telón»: sin JavaScript
         la clase no se pone, y en modo privado `sessionStorage` lanza y
         volvemos sin hacer nada. Un telón que no se puede recordar aparecería
         en cada página, que es justo lo que no queremos. --}}
    <script>
        (function () {
            try {
                if (sessionStorage.getItem('telon') === 'visto') return;
                sessionStorage.setItem('telon', 'visto');
            } catch (e) { return; }

            var raiz = document.documentElement;
            raiz.classList.add('cargando');

            var desde = Date.now(), fuera = false;

            function quitar() {
                if (fuera) return;
                fuera = true;
                raiz.classList.add('cargador-fuera');
                setTimeout(function () {
                    raiz.classList.remove('cargando', 'cargador-fuera');
                }, 500);
            }

            // Se va con `DOMContentLoaded` y no con `load`.
            //
            // `load` espera TODOS los subrecursos: en un celular con 3G en un
            // taller, la portada tarda, y quien llega desde Google se queda
            // mirando un logo con el scroll bloqueado hasta cinco segundos. Su
            // primera impresión. Con `DOMContentLoaded` la animación se ve
            // completa igual —dura 1,5 s— y el contenido queda usable en
            // cuanto existe.
            document.addEventListener('DOMContentLoaded', function () {
                // 800 ms y no 1500: la animacion se lee entera igual, y
                // este telon tapa con el scroll bloqueado la PRIMERA pagina
                // de cada sesion -que es la que se abre desde un resultado de
                // Google, muchas veces la ficha de un repuesto y no la
                // portada-. Segundo y medio encima del tiempo de servidor es
                // un peaje sobre el primer contacto.
                setTimeout(quitar, Math.max(0, 800 - (Date.now() - desde)));
            });

            // Red de seguridad: nadie se queda mirando un logo a medio
            // llenar porque algo no termino de bajar.
            setTimeout(quitar, 1600);
        })();
    </script>
</head>
<body class="flex min-h-full flex-col">

<x-cargador />

<a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-3 focus:rounded focus:bg-marca-700 focus:px-4 focus:py-2 focus:text-white">
    Saltar al contenido
</a>

<x-cabecera />

@if (session('mensaje'))
    <p role="status" class="bg-marca-700 px-4 py-2 text-center text-sm text-white">{{ session('mensaje') }}</p>
@endif

{{-- El mismo aviso, pero para lo que se agrega sin recargar. Es región viva:
     quien no ve la pantalla también tiene que enterarse de que quedó agregado. --}}
<div x-data="{ texto: '', mostrar: false, temporizador: null }"
     @cotizacion-actualizada.window="
        texto = $event.detail.mensaje; mostrar = true;
        clearTimeout(temporizador); temporizador = setTimeout(() => mostrar = false, 4000)"
     x-show="mostrar" x-cloak role="status" aria-live="polite"
     class="fixed inset-x-0 bottom-24 z-50 mx-auto w-fit max-w-[75vw] rounded-full bg-marca-800 px-5 py-2.5 text-center text-sm text-white shadow-lg sm:bottom-6 sm:max-w-[60vw]">
    <span x-text="texto"></span>
</div>

{{-- `pb-24` por debajo de `lg`: los dos botones flotantes miden 56 px y se
     pegan a las esquinas de abajo, así que en un teléfono caían encima de
     controles de verdad —el «VER Y COTIZAR» de una tarjeta del catálogo, el
     «Seguir agregando repuestos» y el «Vaciar todo» de la cotización, el
     «Ingresar con Google» del acceso—. El toque iba al botón flotante y no a
     lo que la persona quería pulsar. Este colchón deja que el final de la
     página suba por encima de ellos. --}}
<main id="contenido" tabindex="-1" class="flex flex-1 flex-col pb-24 focus:outline-none lg:pb-0">
    @yield('contenido')
</main>

{{--
    El pie, calcado del suyo: fondo #1866E0, cinco columnas —Menú, Enlaces de
    interés, Legales, Redes y el newsletter—, títulos de 18/500 en blanco,
    enlaces de 14/300 al 79 % de opacidad, y la línea de derechos centrada
    abajo. Las proporciones de las columnas son las medidas sobre su sitio
    (12,6 / 16 / 21,6 / 20,7 / 29,1 %), no cinco quintos iguales.

    Dos cosas que aquí sí funcionan y allá no: el botón del newsletter guarda
    de verdad el correo —en su sitio lo maneja un plugin y esas direcciones no
    aparecen en ningún panel— y el rojo es el del manual de marca (#E02929) y
    no el #FA0000 que tiene puesto su CSS.
--}}
{{-- Sin margen propio: las páginas ponen su aire abajo. El `mt-16` fijo
     dejaba una franja blanca de más en las páginas que ya llegan al pie,
     como la de acceso. --}}
<footer class="bg-marca-600 text-white">
    <div class="contenedor grid gap-x-8 gap-y-10 px-[3vw] pb-5 pt-[50px] lg:grid-cols-[12.6fr_16fr_21.6fr_20.7fr_29.1fr]">

        <div>
            <h2 class="text-lg font-medium text-white">{{ contenido('pie.menu', 'Menú') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm font-light">
                <li><a href="{{ route('quienes-somos') }}" class="text-white/90 hover:text-white hover:underline">Quienes somos</a></li>
                <li><a href="{{ route('contacto') }}" class="text-white/90 hover:text-white hover:underline">Contáctenos</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-medium text-white">{{ contenido('pie.enlaces', 'Enlaces de interés') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm font-light">
                <li><a href="{{ route('noticias') }}" class="text-white/90 hover:text-white hover:underline">Noticias y novedades</a></li>
                <li><a href="{{ route('catalogo') }}" class="text-white/90 hover:text-white hover:underline">Catálogo de repuestos</a></li>
                <li><a href="{{ route('mantenimientos') }}" class="text-white/90 hover:text-white hover:underline">Historial de mantenimientos</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-medium text-white">{{ contenido('pie.legales', 'Legales') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm font-light">
                <li><a href="{{ route('politica-datos') }}" class="text-white/90 hover:text-white hover:underline">Política de tratamiento de datos</a></li>
                <li><a href="{{ route('terminos') }}" class="text-white/90 hover:text-white hover:underline">Términos y condiciones</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-medium text-white">{{ contenido('pie.redes', 'Nuestras redes sociales') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm font-light">
                @if ($contacto->facebook())
                    <li>
                        <a href="{{ $contacto->facebook() }}" target="_blank" rel="noopener"
                           class="flex items-center gap-2.5 text-white/90 hover:text-white hover:underline">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0" aria-hidden="true">
                                <path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12Z"/>
                            </svg>
                            Facebook
                        </a>
                    </li>
                @endif
                @if ($contacto->instagram())
                    <li>
                        <a href="{{ $contacto->instagram() }}" target="_blank" rel="noopener"
                           class="flex items-center gap-2.5 text-white/90 hover:text-white hover:underline">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0" aria-hidden="true">
                                <path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2 0 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c0 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2 0-1.8-.3-2.2-.4a3.9 3.9 0 0 1-1.4-.9 3.9 3.9 0 0 1-.9-1.4c-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c0-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 6.8a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm0-1.8a4.8 4.8 0 1 1 0 9.6 4.8 4.8 0 0 1 0-9.6Zm6.1-.3a1.1 1.1 0 1 1-2.3 0 1.1 1.1 0 0 1 2.3 0Z"/>
                            </svg>
                            Instagram
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        {{-- El newsletter. `honeypot` invisible y ruta con límite por minuto:
             es el único formulario del sitio sin sesión detrás. --}}
        <div x-data="{ listo: {{ session('suscrito') ? 'true' : 'false' }} }">
            {{-- `#newsletter`: tras un error el navegador vuelve al PIE, no al
                 principio de una página que puede medir siete pantallas. Antes
                 el aviso se pintaba aquí abajo y había que bajar de nuevo a
                 buscarlo sin saber que existía. --}}
            <form method="post" action="{{ route('suscripcion') }}#newsletter"
                  id="newsletter" class="flex flex-col gap-3" x-show="! listo">
                @csrf
                <label for="pie-correo" class="sr-only">Tu correo</label>
                <input id="pie-correo" type="email" name="correo" placeholder="Email" required maxlength="190"
                       autocomplete="email" value="{{ old('correo') }}"
                       class="h-[47px] w-full border-0 bg-white px-4 text-base text-tinta-900 placeholder:text-tinta-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">

                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label for="pie-sitio">No llenes este campo</label>
                    <input id="pie-sitio" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit"
                        class="w-full bg-alerta-500 px-[13px] py-[13px] text-base font-semibold uppercase text-white transition hover:bg-alerta-600">
                    {{ contenido('pie.newsletter.boton', 'Suscríbete al newsletter') }}
                </button>
            </form>

            <p x-show="listo" x-cloak role="status"
               class="border-l-4 border-white bg-white/10 px-4 py-3 text-sm">
                {{ contenido('pie.newsletter.gracias', 'Listo, quedaste suscrito. Te escribiremos cuando haya algo que valga la pena.') }}
            </p>

            @error('correo')
                <p role="alert" tabindex="-1" x-init="$el.focus()"
                   class="mt-2 border-l-4 border-white bg-white/10 px-4 py-2 text-sm font-medium text-white">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="contenedor px-[3vw] pb-6">
        <p class="text-center text-base text-white">
            {{ contenido('pie.derechos', 'Todos los derechos reservados') }}
            <span aria-hidden="true">–</span> Importadora Sur Alpine {{ date('Y') }}
        </p>
    </div>
</footer>

<x-botones-flotantes />

</body>
</html>
