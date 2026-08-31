<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · Sur Alpine</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-tinta-100">

@php
    $usuario = auth()->user();
    // Sin `rol` por enlace: al panel sólo entran administradores, y todos
    // ven lo mismo. Cuando había cuatro roles esta lista se filtraba.
    $enlaces = [
        ['ruta' => 'panel.tablero', 'texto' => 'Tablero'],
        ['ruta' => 'panel.solicitudes', 'texto' => 'Solicitudes'],
        ['ruta' => 'panel.mensajes', 'texto' => 'Mensajes'],
        ['ruta' => 'panel.catalogo', 'texto' => 'Catálogo'],
        ['ruta' => 'panel.pagina', 'texto' => 'Textos e imágenes'],
        ['ruta' => 'panel.banners', 'texto' => 'Campañas'],
        ['ruta' => 'panel.categorias', 'texto' => 'Categorías'],
        ['ruta' => 'panel.notas', 'texto' => 'Noticias'],
        ['ruta' => 'panel.suscriptores', 'texto' => 'Suscriptores'],
        ['ruta' => 'panel.usuarios', 'texto' => 'Usuarios'],
        ['ruta' => 'panel.configuracion', 'texto' => 'Datos y correos'],
    ];
@endphp

<header class="border-b border-tinta-300 bg-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
        <a href="{{ route('panel.tablero') }}" class="text-base font-black tracking-tight text-marca-700">
            SUR<span class="text-alerta-500">ALPINE</span>
            <span class="ml-1 align-middle text-xs font-semibold uppercase tracking-widest text-tinta-400">Panel</span>
        </a>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Panel">
            @foreach ($enlaces as $enlace)
                @php $activo = request()->routeIs($enlace['ruta'].'*'); @endphp
                <a href="{{ Route::has($enlace['ruta']) ? route($enlace['ruta']) : '#' }}"
                   @class([
                       'rounded-lg px-3 py-2 text-sm font-medium',
                       'bg-marca-50 text-marca-700' => $activo,
                       'text-tinta-600 hover:bg-tinta-100' => ! $activo,
                   ])>{{ $enlace['texto'] }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-3 text-sm">
            <span class="text-tinta-500">
                {{ $usuario->primer_nombre }}
                <span class="rounded bg-tinta-100 px-1.5 py-0.5 text-xs font-medium text-tinta-600">{{ $usuario->rol->etiqueta() }}</span>
            </span>
            <a href="{{ route('inicio') }}" class="text-marca-700 underline-offset-2 hover:underline">Ver el sitio</a>
            <form method="post" action="{{ route('salir') }}">
                @csrf
                <button type="submit" class="text-tinta-500 underline-offset-2 hover:text-alerta-600 hover:underline">Salir</button>
            </form>
        </div>
    </div>
</header>

@if (session('mensaje'))
    <p role="status" class="bg-marca-700 px-4 py-2 text-center text-sm text-white">{{ session('mensaje') }}</p>
@endif

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8">
    @yield('contenido')
</main>

</body>
</html>
