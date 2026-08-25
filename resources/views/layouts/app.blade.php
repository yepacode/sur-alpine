<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Repuestos y autopartes') · Importadora Sur Alpine</title>
    <meta name="description" content="@yield('descripcion', 'Importadora Sur Alpine: repuestos y autopartes para vehículos livianos. Encuentra la pieza exacta de tu carro y pide tu cotización.')">

    {{-- Canónica: el catálogo genera variantes con `?q=` y `?page=`, y sin esto
         Google las indexa como páginas distintas de la misma cosa. --}}
    <link rel="canonical" href="@yield('canonica', url()->current())">
    <meta name="robots" content="index, follow, max-image-preview:large">

    {{-- Este negocio se mueve por WhatsApp: cuando un asesor pasa el enlace de
         una pieza, esto es lo que decide si llega con foto y título o pelado. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Importadora Sur Alpine">
    <meta property="og:locale" content="es_CO">
    <meta property="og:title" content="@yield('titulo', 'Repuestos y autopartes') · Importadora Sur Alpine">
    <meta property="og:description" content="@yield('descripcion', 'Importadora Sur Alpine: repuestos y autopartes para vehículos livianos.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og-imagen', url('/img/logo/logo-en-png-sur-alpine.webp'))">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/img/logo/logo-en-png-sur-alpine.webp" type="image/webp">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#0a2f6b">

    {{-- Tipografía: Archivo para titulares, Barlow para texto y controles.
         Con `preconnect` el navegador abre la conexión mientras aún parsea el
         head, y con `display=swap` el texto se lee desde el primer instante
         aunque la fuente llegue tarde. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Barlow:wght@400;500;600;700&display=swap">

    <x-negocio-schema />

    @stack('cabeza')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">

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
     class="fixed inset-x-0 bottom-4 z-50 mx-auto w-fit max-w-[92vw] rounded-full bg-marca-800 px-5 py-2.5 text-center text-sm text-white shadow-lg">
    <span x-text="texto"></span>
</div>

<main id="contenido" tabindex="-1" class="flex-1 focus:outline-none">
    @yield('contenido')
</main>

<footer class="mt-16 bg-marca-800 text-marca-100">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <img src="/img/logo/logo-en-png-sur-alpine.webp" alt="Importadora Sur Alpine"
                 width="280" height="351" loading="lazy" decoding="async"
                 class="h-14 w-auto rounded bg-white p-1">
            <p class="mt-3 text-sm leading-relaxed">
                Importadora de repuestos y autopartes desde 1982.<br>
                {{ $contacto->direccionCompleta() }}
            </p>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Catálogo</h2>
            {{-- Todas, no siete: el pie es la única puerta de entrada de las
                 categorías que todavía no tienen foto y por eso no salen en la
                 rejilla de la portada. --}}
            <ul class="mt-3 space-y-2 text-sm sm:columns-2 sm:space-y-0 sm:[&>li]:mb-2">
                @foreach (($categoriasMenu ?? collect()) as $categoria)
                    <li>
                        <a href="{{ route('categoria', $categoria) }}" class="hover:text-white hover:underline">
                            {{ $categoria->nombre }}
                        </a>
                    </li>
                @endforeach
                <li><a href="{{ route('catalogo') }}" class="font-semibold text-white hover:underline">Ver todo</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Contacto</h2>
            <ul class="mt-3 space-y-2 text-sm tabular-nums">
                <li><a href="tel:{{ $contacto->pbxTel() }}" class="hover:underline">PBX {{ $contacto->pbx() }}</a></li>
                @foreach ($contacto->celulares() as $celular)
                    <li><a href="tel:{{ $celular['tel'] }}" class="hover:underline">{{ $celular['texto'] }}</a></li>
                @endforeach
                <li class="pt-2"><a href="{{ route('contacto') }}" class="font-semibold text-white hover:underline">Escríbenos</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Cómo funciona</h2>
            <p class="mt-3 text-sm leading-relaxed">
                Elige tu vehículo, arma tu lista de repuestos y envíanosla.
                Un asesor te contacta para atenderte.
            </p>
            <a href="{{ route('quienes-somos') }}" class="mt-3 inline-block text-sm font-semibold text-white hover:underline">
                Quiénes somos
            </a>
        </div>
    </div>
    <div class="border-t border-marca-700">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-4 text-xs text-marca-200 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} Importadora Sur Alpine S.A. Todos los derechos reservados.</p>
            <p>
                <a href="{{ route('politica-datos') }}" class="hover:underline">Política de tratamiento de datos</a>
            </p>
        </div>
    </div>
</footer>

</body>
</html>
