@extends('panel.layout')

@section('titulo', 'Tablero')

@section('contenido')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Tablero</h1>
            <p class="mt-1 text-sm text-tinta-500">
                {{ $desde->translatedFormat('d M Y') }} — {{ $hasta->translatedFormat('d M Y') }}
            </p>
        </div>

        <form method="get" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="periodo" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Período</label>
                <select id="periodo" name="periodo" onchange="this.form.submit()"
                        class="rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                    @foreach ($periodos as $valor => $texto)
                        <option value="{{ $valor }}" @selected($periodo === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>

            @if ($periodo === 'personalizado')
                <div>
                    <label for="desde" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Desde</label>
                    <input id="desde" type="date" name="desde" value="{{ $desde->toDateString() }}"
                           class="rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="hasta" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Hasta</label>
                    <input id="hasta" type="date" name="hasta" value="{{ $hasta->toDateString() }}"
                           class="rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-lg bg-marca-700 px-4 py-2 text-sm font-semibold text-white hover:bg-marca-800">
                    Aplicar
                </button>
            @endif
        </form>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-tinta-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-tinta-500">Cotizaciones</p>
            <p class="mt-2 text-4xl font-bold tabular-nums">@numero($totalCotizaciones)</p>
            <p class="mt-1 text-sm text-tinta-500">en el período</p>
        </div>

        <div class="rounded-xl border border-tinta-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-tinta-500">Repuestos pedidos</p>
            <p class="mt-2 text-4xl font-bold tabular-nums">@numero($totalRepuestos)</p>
            <p class="mt-1 text-sm text-tinta-500">sumando cantidades</p>
        </div>

        {{-- Este indicador es el que evita que una solicitud se pierda callada. --}}
        <div @class([
            'rounded-xl border p-5',
            'border-alerta-500 bg-alerta-500/5' => $sinEnviar > 0,
            'border-tinta-200 bg-white' => $sinEnviar === 0,
        ])>
            <p @class(['text-xs font-semibold uppercase tracking-wide', 'text-alerta-600' => $sinEnviar > 0, 'text-tinta-500' => $sinEnviar === 0])>
                Correos sin salir
            </p>
            <p @class(['mt-2 text-4xl font-bold tabular-nums', 'text-alerta-600' => $sinEnviar > 0])>@numero($sinEnviar)</p>
            @if ($sinEnviar > 0)
                <a href="{{ route('panel.solicitudes', ['estado' => 'sin-enviar']) }}"
                   class="mt-1 inline-block text-sm font-semibold text-alerta-600 underline underline-offset-2">
                    Revisar y reenviar
                </a>
            @else
                <p class="mt-1 text-sm text-tinta-500">todo entregado</p>
            @endif
        </div>

        <div class="rounded-xl border border-tinta-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-tinta-500">Catálogo</p>
            <p class="mt-2 text-4xl font-bold tabular-nums">@numero($catalogo['productos'])</p>
            <p class="mt-1 text-sm text-tinta-500">@numero($catalogo['vehiculos']) vehículos</p>
        </div>
    </div>

    @php $maximo = max(1, max($porDia ?: [0])); @endphp

    <section class="mt-8 rounded-xl border border-tinta-200 bg-white p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-tinta-500">Solicitudes por día</h2>

        @if (array_sum($porDia) === 0)
            <p class="mt-6 text-sm text-tinta-500">No hubo solicitudes en este período.</p>
        @else
            <div class="mt-6 flex h-40 items-end gap-px overflow-x-auto" role="img"
                 aria-label="Solicitudes diarias entre {{ $desde->toDateString() }} y {{ $hasta->toDateString() }}">
                @foreach ($porDia as $dia => $total)
                    <div class="group relative flex min-w-2 flex-1 flex-col justify-end" style="height:100%">
                        <div @class([
                                'w-full rounded-t',
                                'bg-marca-600' => $total > 0,
                                'bg-tinta-200' => $total === 0,
                            ])
                             style="height: {{ $total > 0 ? max(4, round($total / $maximo * 100)) : 2 }}%"></div>
                        <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-tinta-900 px-2 py-1 text-xs text-white group-hover:block">
                            {{ \Illuminate\Support\Carbon::parse($dia)->translatedFormat('d M') }}: {{ $total }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs tabular-nums text-tinta-400">
                <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($porDia))->translatedFormat('d M') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse(array_key_last($porDia))->translatedFormat('d M') }}</span>
            </div>
        @endif
    </section>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        @foreach ([['Vehículos más cotizados', $vehiculosTop], ['Partes más pedidas', $partesTop]] as [$titulo, $datos])
            <section class="rounded-xl border border-tinta-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-tinta-500">{{ $titulo }}</h2>

                @if (empty($datos))
                    <p class="mt-4 text-sm text-tinta-500">Sin datos en este período.</p>
                @else
                    @php $tope = max($datos); @endphp
                    <ul class="mt-4 space-y-2">
                        @foreach ($datos as $nombre => $total)
                            <li class="grid grid-cols-[1fr_5rem_2.5rem] items-center gap-3 text-sm">
                                <span class="truncate" title="{{ $nombre }}">{{ $nombre }}</span>
                                <span class="h-2 rounded-full bg-tinta-100">
                                    <span class="block h-2 rounded-full bg-marca-500" style="width: {{ round($total / $tope * 100) }}%"></span>
                                </span>
                                <span class="text-right tabular-nums text-tinta-500">{{ $total }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
@endsection
