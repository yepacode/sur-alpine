{{--
    Datos estructurados de una página estática: qué es y cómo se llega.

    Las de catálogo y las notas ya los tenían; `/quienes-somos`,
    `/contactenos`, `/noticias`, `/mantenimientos` y las legales emitían sólo
    el `AutoPartsStore` global, sin decir qué clase de página son ni desde
    dónde se llega.

    Importa más de lo que parece para este cliente: `/contactenos` es la página
    que sostiene la señal de negocio local, y `/quienes-somos` es donde dice
    «este es nuestro único sitio oficial» —que es el problema que vinieron a
    resolver—. Un `ContactPage` y un `AboutPage` con miga de pan son la forma
    de que Google entienda las dos cosas.

    · $tipo   — ContactPage, AboutPage, Blog, WebPage…
    · $nombre — el título de la página.
    · $miga   — [texto => url], sin incluir «Inicio», que se pone solo.
--}}
@props(['tipo' => 'WebPage', 'nombre', 'miga' => []])

@php
    $pasos = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')]];

    foreach (array_values($miga) as $i => $url) {
        $pasos[] = [
            '@type' => 'ListItem',
            'position' => $i + 2,
            'name' => array_keys($miga)[$i],
            'item' => $url,
        ];
    }

    $pagina = array_filter([
        '@context' => 'https://schema.org',
        '@type' => $tipo,
        'name' => $nombre,
        'url' => url()->current(),
        'inLanguage' => 'es-CO',
        'isPartOf' => ['@type' => 'WebSite', '@id' => url('/').'#sitio'],
        'about' => ['@type' => 'AutoPartsStore', '@id' => url('/').'#negocio'],
        'breadcrumb' => count($pasos) > 1
            ? ['@type' => 'BreadcrumbList', 'itemListElement' => $pasos]
            : null,
    ]);
@endphp

<script type="application/ld+json">{!! json_encode($pagina, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
