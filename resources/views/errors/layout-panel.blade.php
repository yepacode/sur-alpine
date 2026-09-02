{{--
    El molde de los errores DENTRO del panel.

    Existe porque un 404 en `/panel/categorias/5` servía la página pública de
    la tienda: «Buscar mi repuesto» y «¿Necesitas una pieza y no la
    encuentras? Llámanos al (601) 366 0066». El dueño leyendo «llámanos» en su
    propio panel de administración, sin una sola salida hacia donde estaba.

    Hereda el layout del panel, así que conserva su menú: la salida de verdad
    no es un botón, es la barra de arriba que ya conoce.
--}}
@extends('panel.layout')

@section('titulo', trim($__env->yieldContent('titulo', 'Error')))

@section('contenido')
    <div class="mx-auto max-w-xl py-16 text-center">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-alerta-500">Error @yield('codigo')</p>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-tinta-900">
            @yield('encabezado')
        </h1>

        <div class="mt-3 text-sm leading-relaxed text-tinta-600">
            @yield('explicacion-panel')
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('panel.tablero') }}"
               class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-semibold text-white hover:bg-marca-800">
                Volver al tablero
            </a>
            <button type="button" onclick="history.back()"
                    class="rounded-lg border border-tinta-300 bg-white px-6 py-3 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
                Volver a lo anterior
            </button>
        </div>
    </div>
@endsection
