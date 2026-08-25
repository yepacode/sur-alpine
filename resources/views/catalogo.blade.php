@extends('layouts.app')

@section('titulo', $titulo)

@section('contenido')
    <div class="mx-auto max-w-7xl px-4 py-8">

        <nav aria-label="Migas de pan" class="mb-6 text-sm text-tinta-500">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('inicio') }}" class="hover:text-marca-700 hover:underline">Inicio</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('catalogo') }}" class="hover:text-marca-700 hover:underline">Repuestos</a></li>
                @if ($categoria)
                    <li aria-hidden="true">/</li>
                    <li>
                        @if ($tipoParte)
                            <a href="{{ route('categoria', $categoria) }}" class="hover:text-marca-700 hover:underline">{{ $categoria->nombre }}</a>
                        @else
                            <span class="text-tinta-900">{{ $categoria->nombre }}</span>
                        @endif
                    </li>
                @endif
                @if ($tipoParte)
                    <li aria-hidden="true">/</li>
                    <li class="text-tinta-900">{{ $tipoParte->nombre }}</li>
                @endif
            </ol>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-tinta-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $titulo }}</h1>
                <p class="mt-1 text-sm text-tinta-500">
                    @if (request('q'))
                        <span class="tabular-nums">@numero($productos->total())</span>
                        {{ Str::plural('resultado', $productos->total()) }} para
                        <span class="font-medium text-tinta-700">«{{ request('q') }}»</span>
                    @else
                        <span class="tabular-nums">@numero($productos->total())</span>
                        {{ Str::plural('repuesto', $productos->total()) }} en el catálogo
                    @endif
                </p>
            </div>

            <form method="get" class="flex items-center gap-2">
                @foreach (request()->except(['orden', 'page']) as $campo => $valor)
                    <input type="hidden" name="{{ $campo }}" value="{{ $valor }}">
                @endforeach
                <label for="orden" class="text-sm text-tinta-500">Ordenar</label>
                <select id="orden" name="orden" onchange="this.form.submit()"
                        class="rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                    <option value="a-z" @selected(request('orden', 'a-z') === 'a-z')>Nombre A-Z</option>
                    <option value="z-a" @selected(request('orden') === 'z-a')>Nombre Z-A</option>
                    <option value="recientes" @selected(request('orden') === 'recientes')>Más recientes</option>
                </select>
            </form>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[16rem_1fr]">

            <aside class="lg:self-start">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tinta-500">Tu vehículo</h2>
                <div class="mt-3 rounded-xl border border-tinta-200 bg-white p-4">
                    @if ($vehiculoActivo ?? null)
                        <p class="text-sm font-semibold">{{ $vehiculoActivo->nombre_completo }}</p>
                        <form method="post" action="{{ route('vehiculo.olvidar') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
                                Quitar filtro
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-tinta-600">
                            Elige tu carro y te mostramos sólo las piezas que le sirven.
                        </p>
                        <a href="{{ route('inicio') }}#contenido"
                           class="mt-2 inline-block text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
                            Elegir vehículo
                        </a>
                    @endif
                </div>

                <h2 class="mt-8 text-xs font-bold uppercase tracking-wider text-tinta-500">Filtrar por parte</h2>

                @if ($tiposParte->isNotEmpty())
                    <p class="mt-3 text-sm">
                        <a href="{{ route('categoria', $categoria) }}"
                           class="font-medium text-marca-700 hover:underline">← Todo en {{ $categoria->nombre }}</a>
                    </p>
                    <ul class="mt-2 max-h-[28rem] space-y-1 overflow-y-auto pr-2 text-sm">
                        @foreach ($tiposParte as $tipo)
                            <li>
                                {{-- Sin resultados no es un enlace: decir "0" y dejarlo clicable
                                     manda al usuario a una página vacía. --}}
                                @if ($tipo->productos_count === 0)
                                    <span class="flex items-center justify-between gap-2 rounded px-2 py-1.5 text-tinta-400"
                                          title="No manejamos esta pieza para el vehículo seleccionado">
                                        <span>{{ $tipo->nombre }}</span>
                                        <span class="shrink-0 text-xs">—</span>
                                    </span>
                                @else
                                    <a href="{{ route('tipo-parte', [$categoria, $tipo]) }}"
                                       @class([
                                           'flex items-center justify-between gap-2 rounded px-2 py-1.5 hover:bg-tinta-100',
                                           'bg-marca-50 font-semibold text-marca-700' => $tipoParte?->id === $tipo->id,
                                       ])>
                                        <span>{{ $tipo->nombre }}</span>
                                        <span class="shrink-0 tabular-nums text-xs text-tinta-400">@numero($tipo->productos_count)</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <ul class="mt-3 space-y-1 text-sm">
                        @foreach ($categorias as $cat)
                            <li>
                                <a href="{{ route('categoria', $cat) }}"
                                   class="flex items-center justify-between gap-2 rounded px-2 py-1.5 hover:bg-tinta-100">
                                    <span>{{ $cat->nombre }}</span>
                                    <span class="shrink-0 tabular-nums text-xs text-tinta-400">@numero($cat->productos_count)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            <div>
                @if ($productos->isEmpty())
                    <div class="rounded-xl border border-dashed border-tinta-300 bg-white p-12 text-center">
                        <p class="text-lg font-semibold">No encontramos repuestos con esa búsqueda</p>
                        <p class="mt-2 text-sm text-tinta-500">
                            Prueba con el nombre de la pieza, por ejemplo «pastillas freno» o «filtro aceite».
                        </p>
                        <a href="{{ route('catalogo') }}"
                           class="mt-6 inline-block rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
                            Ver todo el catálogo
                        </a>
                    </div>
                @else
                    <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($productos as $producto)
                            <li class="flex flex-col rounded-xl border border-tinta-200 bg-white p-4 transition hover:border-marca-300 hover:shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-marca-600">
                                    {{ $producto->tipoParte->categoria->nombre }}
                                </p>
                                <h3 class="mt-1 font-semibold leading-snug">
                                    <a href="{{ route('producto', $producto) }}" class="hover:underline">
                                        {{ $producto->nombre }}
                                    </a>
                                </h3>
                                <p class="mt-2 text-sm text-tinta-500">
                                    {{ $producto->vehiculo->modelo->marca->nombre }}
                                    {{ $producto->vehiculo->modelo->nombre }}
                                    {{ $producto->vehiculo->cilindraje }}
                                    <span class="tabular-nums">{{ $producto->vehiculo->anio_inicio }}-{{ $producto->vehiculo->anio_fin }}</span>
                                </p>
                                <a href="{{ route('producto', $producto) }}"
                                   class="mt-4 inline-block self-start rounded-lg bg-alerta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-alerta-600">
                                    Ver y cotizar
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8">
                        {{ $productos->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
