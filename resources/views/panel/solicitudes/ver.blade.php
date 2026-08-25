@extends('panel.layout')

@section('titulo', $solicitud->consecutivo)

@section('contenido')
    <a href="{{ route('panel.solicitudes') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
        ← Todas las solicitudes
    </a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tabular-nums tracking-tight">{{ $solicitud->consecutivo }}</h1>
            <p class="mt-1 text-sm text-tinta-500">
                Recibida el {{ $solicitud->created_at->translatedFormat('d \d\e F \d\e Y \a \l\a\s H:i') }}
            </p>
        </div>

        @if ($solicitud->seEnvio())
            <p class="rounded-lg bg-marca-50 px-4 py-2 text-sm text-marca-800">
                Correo entregado el {{ $solicitud->correo_enviado_en->format('d/m/Y H:i') }}
            </p>
        @else
            <div class="rounded-lg border border-alerta-500 bg-alerta-500/5 p-4">
                <p class="text-sm font-semibold text-alerta-600">El correo no salió</p>
                @if ($solicitud->error_envio)
                    <p class="mt-1 max-w-md text-xs text-alerta-700">{{ $solicitud->error_envio }}</p>
                @endif
                <form method="post" action="{{ route('panel.solicitudes.reenviar', $solicitud) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="rounded-lg bg-alerta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-alerta-600">
                        Reenviar ahora
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[20rem_1fr]">

        <aside class="rounded-xl border border-tinta-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-tinta-500">A quién llamar</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-tinta-500">Nombre</dt>
                    <dd class="font-medium">{{ $solicitud->nombre_completo }}</dd>
                </div>
                <div>
                    <dt class="text-tinta-500">Teléfono</dt>
                    <dd><a href="tel:{{ $solicitud->telefono }}" class="font-medium tabular-nums text-marca-700 hover:underline">{{ $solicitud->telefono }}</a></dd>
                </div>
                <div>
                    <dt class="text-tinta-500">Correo</dt>
                    <dd><a href="mailto:{{ $solicitud->email }}" class="font-medium text-marca-700 hover:underline">{{ $solicitud->email }}</a></dd>
                </div>
                @if ($solicitud->notas)
                    <div>
                        <dt class="text-tinta-500">Comentarios</dt>
                        <dd class="mt-1 rounded-lg bg-tinta-50 p-3">{{ $solicitud->notas }}</dd>
                    </div>
                @endif
            </dl>

            <a href="tel:{{ $solicitud->telefono }}"
               class="mt-6 block rounded-lg bg-marca-700 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-marca-800">
                Llamar a {{ $solicitud->nombre }}
            </a>
        </aside>

        <div class="space-y-4">
            @foreach ($porVehiculo as $vehiculo => $items)
                <section class="overflow-hidden rounded-xl border border-tinta-200 bg-white">
                    <header class="flex items-center gap-3 border-b border-tinta-200 bg-tinta-50 px-5 py-3">
                        <h2 class="font-semibold">{{ $vehiculo }}</h2>
                        <span class="rounded-full bg-marca-100 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-marca-700">
                            {{ $items->sum('cantidad') }}
                        </span>
                    </header>
                    <ul class="divide-y divide-tinta-200">
                        @foreach ($items as $item)
                            <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                                <span>{{ $item->producto_nombre }}</span>
                                <span class="shrink-0 tabular-nums text-tinta-500">× {{ $item->cantidad }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
@endsection
