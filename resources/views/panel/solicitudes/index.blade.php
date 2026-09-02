@extends('panel.layout')

@section('titulo', 'Solicitudes')

@section('contenido')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Solicitudes de cotización</h1>
            <p class="mt-1 text-sm text-tinta-500">
                <span class="tabular-nums">{{ $solicitudes->total() }}</span>
                {{ plural($solicitudes->total(), 'solicitud', 'solicitudes') }}
                @if ($sinEnviar > 0)
                    · <span class="font-semibold text-alerta-600">{{ $sinEnviar }} sin enviar</span>
                @endif
            </p>
        </div>

        <a href="{{ route('panel.solicitudes.exportar', request()->query()) }}"
           class="rounded-lg border border-tinta-300 bg-white px-4 py-2 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
            Exportar a Excel
        </a>
    </div>

    <form method="get" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-tinta-200 bg-white p-4">
        @php $campo = 'rounded-lg border border-tinta-300 px-3 py-2 text-sm'; @endphp
        <div class="min-w-56 flex-1">
            <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Buscar</label>
            <input id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Consecutivo, nombre, teléfono o correo"
                   class="w-full {{ $campo }}">
        </div>
        <div>
            <label for="estado" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Estado</label>
            <select id="estado" name="estado" class="{{ $campo }}">
                <option value="">Todas</option>
                <option value="enviadas" @selected(($filtros['estado'] ?? '') === 'enviadas')>Correo entregado</option>
                <option value="sin-enviar" @selected(($filtros['estado'] ?? '') === 'sin-enviar')>Correo sin salir</option>
            </select>
        </div>
        <div>
            <label for="desde" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Desde</label>
            <input id="desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="{{ $campo }}">
        </div>
        <div>
            <label for="hasta" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Hasta</label>
            <input id="hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="{{ $campo }}">
        </div>
        <button type="submit" class="rounded-lg bg-marca-700 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-800">
            Filtrar
        </button>
        @if (array_filter($filtros))
            <a href="{{ route('panel.solicitudes') }}" class="px-2 py-2 text-sm text-tinta-500 underline-offset-2 hover:underline">
                Limpiar
            </a>
        @endif
    </form>

    {{-- Los atajos que el tablero ya tenía y aquí no.
         Para «quién pidió cotización esta semana» había que teclear dos fechas
         a mano, que es justo la pregunta que se hace todos los lunes. --}}
    @php
        $atajos = [
            'Hoy' => [today(), today()],
            'Esta semana' => [today()->startOfWeek(), today()],
            'Últimos 30 días' => [today()->subDays(29), today()],
            'Este mes' => [today()->startOfMonth(), today()],
        ];
    @endphp
    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
        <span class="text-xs font-semibold uppercase tracking-wide text-tinta-500">Ver:</span>
        @foreach ($atajos as $texto => [$d, $h])
            @php $activo = ($filtros['desde'] ?? '') === $d->toDateString() && ($filtros['hasta'] ?? '') === $h->toDateString(); @endphp
            <a href="{{ route('panel.solicitudes', array_merge(request()->only(['q', 'estado']), ['desde' => $d->toDateString(), 'hasta' => $h->toDateString()])) }}"
               @class([
                   'rounded-full px-3 py-1 font-medium',
                   'bg-marca-700 text-white' => $activo,
                   'bg-tinta-100 text-tinta-700 hover:bg-tinta-200' => ! $activo,
               ])>{{ $texto }}</a>
        @endforeach
    </div>

    @if ($solicitudes->isEmpty())
        <div class="mt-6 rounded-xl border border-dashed border-tinta-300 bg-white p-12 text-center">
            <p class="font-semibold">No hay solicitudes con esos filtros</p>
        </div>
    @else
        <div class="mt-6 overflow-x-auto rounded-xl border border-tinta-200 bg-white">
            {{-- `min-w-xl` y no `min-w-3xl`: 806 px de tabla dentro de los 768
                 de una tablet no sólo cortaban la columna «Correo», sino que
                 el desborde se escapaba del contenedor y desplazaba la página
                 entera de lado. Lo que quedaba fuera de pantalla era la
                 columna «Acciones», o sea el botón «Reenviar» —la acción
                 principal justo cuando un correo no salió, en la tablet del
                 mostrador—. --}}
            <table class="w-full min-w-xl text-sm">
                <thead class="border-b border-tinta-200 text-left text-xs uppercase tracking-wide text-tinta-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Consecutivo</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Contacto</th>
                        <th class="px-4 py-3 text-right font-medium">Ítems</th>
                        <th class="px-4 py-3 font-medium">Correo</th>
                        <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-tinta-200">
                    @foreach ($solicitudes as $solicitud)
                        <tr class="hover:bg-tinta-50">
                            <td class="px-4 py-3 font-medium tabular-nums">
                                <a href="{{ route('panel.solicitudes.ver', $solicitud) }}" class="text-marca-700 hover:underline">
                                    {{ $solicitud->consecutivo }}
                                </a>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-tinta-600">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $solicitud->nombre_completo }}</td>
                            <td class="px-4 py-3 text-tinta-600">
                                <a href="tel:{{ $solicitud->telefono }}" class="tabular-nums hover:underline">{{ $solicitud->telefono }}</a><br>
                                <a href="mailto:{{ $solicitud->email }}" class="text-xs hover:underline">{{ $solicitud->email }}</a>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $solicitud->items_count }}</td>
                            <td class="px-4 py-3">
                                @if ($solicitud->seEnvio())
                                    <span class="rounded-full bg-pass/10 px-2 py-0.5 text-xs font-medium text-marca-700">Entregado</span>
                                @else
                                    <span class="rounded-full bg-alerta-500/10 px-2 py-0.5 text-xs font-medium text-alerta-600">Sin salir</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @unless ($solicitud->seEnvio())
                                    <form method="post" action="{{ route('panel.solicitudes.reenviar', $solicitud) }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-marca-700 hover:underline">Reenviar</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $solicitudes->links() }}</div>
    @endif
@endsection
