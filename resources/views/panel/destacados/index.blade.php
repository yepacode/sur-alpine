@extends('panel.layout')

@section('titulo', 'Productos destacados')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-tinta-900">Productos destacados</h1>
            <p class="mt-2 max-w-2xl text-sm text-tinta-600">
                Estas son las piezas que salen en el carrusel «Productos Destacados» de la portada.
                Cambia el número de la columna «Orden» para reordenarlos y dale a «Guardar orden». Para cambiar la foto, el nombre o la descripción de una pieza, tócale «Editar» y vuelves a esta pantalla al guardar.
                Sin ninguna elegida, el sitio elige solo las piezas más cotizadas.
            </p>
        </div>

        @if (session('mensaje'))
            <div class="mb-4 rounded-lg border border-marca-200 bg-marca-50 px-4 py-3 text-sm text-marca-800">
                {{ session('mensaje') }}
            </div>
        @endif

        {{-- ─── Tabla de destacados ─────────────────────────────────────── --}}
        <section class="rounded-2xl border border-tinta-200 bg-white p-5">
            <div class="mb-4 flex items-baseline justify-between gap-3">
                <h2 class="font-semibold text-tinta-900">
                    Lo que sale ahora
                    <span class="ml-2 text-sm font-normal text-tinta-500">
                        @if ($actuales->isEmpty())
                            (ninguno · el sitio elige las más cotizadas)
                        @else
                            ({{ $actuales->count() }} de máximo 10)
                        @endif
                    </span>
                </h2>
            </div>

            @if ($actuales->isEmpty())
                <p class="rounded-lg bg-tinta-50 px-4 py-6 text-center text-sm text-tinta-500">
                    Todavía no hay destacados elegidos a mano. Busca abajo y agrega los que quieras que salgan.
                </p>
            @else
                <form method="post" action="{{ route('panel.destacados.orden') }}">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-tinta-200 text-left text-xs uppercase tracking-wide text-tinta-500">
                                <tr>
                                    <th class="w-20 py-2 pr-3">Orden</th>
                                    <th class="w-14 py-2 pr-3">Foto</th>
                                    <th class="py-2 pr-3">Repuesto</th>
                                    <th class="hidden py-2 pr-3 md:table-cell">Categoría</th>
                                    <th class="hidden py-2 pr-3 lg:table-cell">Vehículo</th>
                                    <th class="w-40 py-2 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-tinta-100">
                                @foreach ($actuales as $p)
                                    <tr class="hover:bg-tinta-50">
                                        <td class="py-3 pr-3">
                                            <input type="number" name="orden[{{ $p->id }}]" value="{{ $p->destacado_orden }}"
                                                   min="1" max="999" step="1"
                                                   class="w-16 rounded border border-tinta-300 px-2 py-1.5 text-center text-sm tabular-nums focus:border-marca-500 focus:outline-none focus:ring-1 focus:ring-marca-500">
                                        </td>
                                        <td class="py-3 pr-3">
                                            <img src="{{ $p->imagen_mostrable }}" alt="" class="size-11 rounded object-cover">
                                        </td>
                                        <td class="min-w-0 py-3 pr-3">
                                            <p class="truncate font-semibold text-tinta-900">{{ $p->nombre }}</p>
                                            <p class="mt-0.5 text-xs text-tinta-500 md:hidden">
                                                {{ $p->tipoParte->categoria->nombre }}
                                            </p>
                                        </td>
                                        <td class="hidden py-3 pr-3 text-tinta-600 md:table-cell">
                                            {{ $p->tipoParte->categoria->nombre }}
                                        </td>
                                        <td class="hidden py-3 pr-3 text-tinta-600 lg:table-cell">
                                            {{ $p->vehiculo->nombre_completo }}
                                        </td>
                                        <td class="whitespace-nowrap py-3 text-right">
                                            <a href="{{ route('panel.catalogo.producto', $p) }}"
                                               class="rounded px-3 py-1 text-sm text-marca-700 hover:bg-marca-50"
                                               title="Cambiar foto, nombre, referencia y descripción">
                                                Editar
                                            </a>
                                            <button type="button" x-data
                                                    @click="if (confirm('¿Quitar «{{ addslashes($p->nombre) }}» del carrusel?')) { document.getElementById('quitar-{{ $p->id }}').submit() }"
                                                    class="rounded px-3 py-1 text-sm text-alerta-600 hover:bg-alerta-50">
                                                Quitar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3 border-t border-tinta-100 pt-4">
                        <p class="text-xs text-tinta-500">
                            El número más bajo sale primero. Si repites un número, se ordena por el orden que le diste antes.
                        </p>
                        <button type="submit" class="rounded-lg bg-marca-600 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-700">
                            Guardar orden
                        </button>
                    </div>
                </form>

                {{-- Formularios ocultos de quitar: uno por producto. Se envían
                     por el botón «Quitar» de la tabla, previa confirmación. --}}
                @foreach ($actuales as $p)
                    <form id="quitar-{{ $p->id }}" method="post" action="{{ route('panel.destacados.quitar', $p) }}" hidden>
                        @csrf
                    </form>
                @endforeach
            @endif
        </section>

        {{-- ─── Buscar y agregar ────────────────────────────────────────── --}}
        <section class="mt-8 rounded-2xl border border-tinta-200 bg-white p-5">
            <h2 class="mb-4 font-semibold text-tinta-900">Agregar una pieza</h2>

            <form method="get" action="{{ route('panel.destacados') }}" class="mb-4 flex gap-2">
                <input type="search" name="q" value="{{ $termino }}"
                       placeholder="Nombre de la pieza o referencia (mínimo 2 letras)"
                       class="min-w-0 flex-1 rounded-lg border border-tinta-300 px-3 py-2 text-sm focus:border-marca-500 focus:outline-none focus:ring-1 focus:ring-marca-500"
                       autofocus>
                <button type="submit" class="rounded-lg bg-marca-600 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-700">
                    Buscar
                </button>
            </form>

            @if ($termino !== '' && $resultados->isEmpty())
                <p class="rounded-lg bg-tinta-50 px-4 py-6 text-center text-sm text-tinta-500">
                    No encontramos nada con «{{ $termino }}».
                </p>
            @elseif ($resultados->isNotEmpty())
                <ul class="space-y-2">
                    @foreach ($resultados as $p)
                        <li class="flex items-center gap-3 rounded-lg border border-tinta-200 bg-tinta-50 p-3">
                            <img src="{{ $p->imagen_mostrable }}" alt="" class="size-12 shrink-0 rounded object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-tinta-900">{{ $p->nombre }}</p>
                                <p class="truncate text-xs text-tinta-500">
                                    {{ $p->tipoParte->categoria->nombre }}
                                    ·
                                    {{ $p->vehiculo->nombre_completo }}
                                </p>
                            </div>
                            <form method="post" action="{{ route('panel.destacados.agregar') }}">
                                @csrf
                                <input type="hidden" name="producto_id" value="{{ $p->id }}">
                                <button type="submit" class="rounded-lg bg-marca-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-marca-700">
                                    + Destacar
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
