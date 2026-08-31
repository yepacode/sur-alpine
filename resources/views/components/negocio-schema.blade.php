{{--
    Los datos estructurados del negocio.

    Es la pieza que le dice a Google cuál es la Importadora Sur Alpine de
    verdad. El cliente pelea contra sitios que lo suplantan, y hasta ahora
    «único sitio oficial» era sólo una frase en la portada: nada en el marcado
    lo respaldaba. `sameAs` con las redes oficiales es lo que cierra el
    círculo Google ↔ redes ↔ web.

    Todo sale de la tabla de configuración, así que el administrador lo corrige
    desde el panel sin llamar a nadie.
--}}
@php
    $negocio = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'AutoPartsStore',
        '@id' => url('/').'#negocio',
        'name' => 'Importadora Sur Alpine',
        'legalName' => 'Importadora Sur Alpine S.A.',
        'description' => 'Importadora de repuestos y autopartes para vehículos livianos en Bogotá desde 1982.',
        'url' => url('/'),
        'logo' => url('/img/logo/logo-en-png-sur-alpine.webp'),
        'image' => url('/img/logo/logo-en-png-sur-alpine.webp'),
        'foundingDate' => '1982',
        'telephone' => $contacto->pbxTel(),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $contacto->direccion(),
            'addressLocality' => $contacto->ciudad(),
            'addressCountry' => 'CO',
        ],

        // Los cuatro campos que Google cruza contra el perfil de Google
        // Business para confirmar que esta web y ese local son el mismo
        // negocio. Es el trabajo directo contra los sitios que lo suplantan:
        // ellos pueden copiar el texto y las fotos, no la ficha del negocio.
        'email' => $contacto->correo(),
        'hasMap' => $contacto->mapaUrl(),
        'openingHoursSpecification' => $contacto->horarioSchema(),
        'areaServed' => 'Colombia',
        'sameAs' => $contacto->redes(),
    ]);

    // El buscador del sitio, para que Google lo ofrezca en sus resultados.
    // Con 29.272 piezas, es la diferencia entre entrar por la portada y entrar
    // directo a la que se busca.
    $sitio = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'url' => url('/'),
        'name' => 'Importadora Sur Alpine',
        'inLanguage' => 'es-CO',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('catalogo').'?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
@endphp

{{-- `JSON_HEX_TAG` como en los otros seis bloques: sin él, un `</script>`
     escrito en «Dirección» o en «Ciudad» del panel se sale del `<script>` y
     lo que venga detrás se ejecuta —en TODAS las páginas, porque este nodo va
     en todas—. Que sólo lo pueda escribir un administrador no lo hace menos
     raro: nadie trata el campo «Ciudad» como un sitio donde cabe HTML. --}}
<script type="application/ld+json">@json([$negocio, $sitio], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>
