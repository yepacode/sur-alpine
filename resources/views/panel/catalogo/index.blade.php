@extends('panel.layout')

@section('titulo', 'Catálogo')

@section('contenido')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Catálogo</h1>
            <p class="mt-1 text-sm text-tinta-500">
                <span class="tabular-nums">@numero($totales['vehiculos'])</span> vehículos ·
                <span class="tabular-nums">@numero($totales['tiposParte'])</span> tipos de parte ·
                <span class="tabular-nums">@numero($totales['productos'])</span> piezas
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('panel.catalogo.importar') }}"
               class="rounded-lg bg-marca-700 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-800">
                Cargar Excel
            </a>
            <a href="{{ route('panel.catalogo.crear') }}"
               class="rounded-lg border border-tinta-300 bg-white px-4 py-2 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
                Vehículo nuevo
            </a>
        </div>
    </div>

    <form method="get" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-tinta-200 bg-white p-4">
        @php $campo = 'rounded-lg border border-tinta-300 px-3 py-2 text-sm'; @endphp
        <div class="min-w-56 flex-1">
            <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Buscar</label>
            <input id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Marca o modelo" class="w-full {{ $campo }}">
        </div>
        <div>
            <label for="marca" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Marca</label>
            <select id="marca" name="marca" class="{{ $campo }}">
                <option value="">Todas</option>
                @foreach ($marcas as $marca)
                    <option value="{{ $marca->slug }}" @selected(($filtros['marca'] ?? '') === $marca->slug)>{{ $marca->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-marca-700 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-800">
            Filtrar
        </button>
        @if (array_filter($filtros))
            <a href="{{ route('panel.catalogo') }}" class="px-2 py-2 text-sm text-tinta-500 underline-offset-2 hover:underline">Limpiar</a>
        @endif
    </form>

    @if ($vehiculos->isEmpty())
        <div class="mt-6 rounded-xl border border-dashed border-tinta-300 bg-white p-12 text-center">
            <p class="font-semibold">No hay vehículos con esos filtros</p>
        </div>
    @else
        <div class="mt-6 overflow-x-auto rounded-xl border border-tinta-200 bg-white">
            <table class="w-full min-w-2xl text-sm">
                <thead class="border-b border-tinta-200 text-left text-xs uppercase tracking-wide text-tinta-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Marca</th>
                        <th class="px-4 py-3 font-medium">Modelo</th>
                        <th class="px-4 py-3 font-medium">Cilindraje</th>
                        <th class="px-4 py-3 font-medium">Años</th>
                        <th class="px-4 py-3 text-right font-medium">Piezas</th>
                        <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-tinta-200">
                    @foreach ($vehiculos as $vehiculo)
                        <tr class="hover:bg-tinta-50">
                            <td class="px-4 py-3 font-medium">{{ $vehiculo->modelo->marca->nombre }}</td>
                            <td class="px-4 py-3">{{ $vehiculo->modelo->nombre }}</td>
                            <td class="px-4 py-3">{{ $vehiculo->cilindraje }}</td>
                            <td class="px-4 py-3 tabular-nums text-tinta-600">{{ $vehiculo->anio_inicio }}–{{ $vehiculo->anio_fin }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $vehiculo->productos_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('panel.catalogo.editar', $vehiculo) }}"
                                   class="font-medium text-marca-700 hover:underline">Editar piezas</a>
                                <span class="mx-2 text-tinta-300" aria-hidden="true">·</span>
                                <a href="{{ route('panel.catalogo.datos', $vehiculo) }}"
                                   class="font-medium text-tinta-600 hover:underline">Datos</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $vehiculos->links() }}</div>
    @endif
@endsection
