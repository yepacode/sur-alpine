@extends('layouts.app')

@section('titulo', $vehiculo->pivot->alias ?: $vehiculo->nombre_completo)

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

{{--
    El perfil de un carro guardado.

    Un mecánico entra aquí por una de tres razones, y las tres están arriba sin
    tener que bajar: qué le toca, qué le hizo y dónde están sus repuestos.
--}}
@section('contenido')
    @php
        $alias = $vehiculo->pivot->alias;
        $placa = $vehiculo->pivot->placa;
        $campo = 'w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm';
        $rotulo = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500';
    @endphp

    <div class="mx-auto max-w-5xl px-4 py-10">

        <a href="{{ route('cuenta') }}" class="text-sm font-medium text-marca-700 hover:underline">
            <span aria-hidden="true">←</span> Mi cuenta
        </a>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Mi vehículo</p>
                <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">
                    {{ $alias ?: $vehiculo->nombre_completo }}
                </h1>
                <p class="mt-1 text-tinta-600">
                    @if ($placa)
                        <span class="font-semibold tabular-nums text-tinta-800">{{ $placa }}</span>
                        <span aria-hidden="true">·</span>
                    @endif
                    {{ $vehiculo->nombre_completo }}
                </p>
            </div>

            {{-- Lo que más se hace desde aquí: buscar un repuesto para este
                 carro. Es un POST porque deja el vehículo fijado en la sesión,
                 que es lo que filtra el catálogo entero. --}}
            <form method="post" action="{{ route('vehiculo.guardar') }}" class="shrink-0">
                @csrf
                <input type="hidden" name="vehiculo_id" value="{{ $vehiculo->id }}">
                <button type="submit"
                        class="con-luz rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                    Ver sus repuestos
                    @if ($piezas)
                        <span class="font-normal tabular-nums opacity-80">({{ number_format($piezas, 0, ',', '.') }})</span>
                    @endif
                </button>
            </form>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px] lg:items-start">

            {{-- ─── Lo que se le ha hecho ─────────────────────────────────── --}}
            <section>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-titulo text-xl font-bold">Mantenimientos de este carro</h2>
                    <a href="{{ route('cuenta.mantenimientos', ['placa' => $placa]) }}"
                       class="text-sm font-semibold text-marca-700 hover:underline">
                        Anotar uno nuevo →
                    </a>
                </div>

                @if ($mantenimientos->isEmpty())
                    <div class="mt-4 rounded-xl border border-dashed border-tinta-300 bg-white p-8 text-center">
                        <p class="font-semibold">A este carro todavía no le has anotado nada</p>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-tinta-600">
                            Anota qué le hiciste y cuándo. Nosotros calculamos cuándo toca el próximo.
                        </p>
                        <a href="{{ route('cuenta.mantenimientos', ['placa' => $placa]) }}"
                           class="mt-5 inline-block rounded-lg bg-alerta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-alerta-600">
                            Anotar el primero
                        </a>
                    </div>
                @else
                    <ul class="mt-4 divide-y divide-tinta-200 overflow-hidden rounded-2xl border border-tinta-200 bg-white shadow-sm">
                        @foreach ($mantenimientos as $mantenimiento)
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4">
                                <div class="min-w-48 flex-1">
                                    <p class="font-semibold">{{ $mantenimiento->tipo }}</p>
                                    <p class="text-sm tabular-nums text-tinta-600">
                                        {{ $mantenimiento->fecha->translatedFormat('d M Y') }}
                                        <span aria-hidden="true">·</span>
                                        {{ number_format($mantenimiento->kilometraje, 0, ',', '.') }} km
                                    </p>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-alerta-500/10 text-alerta-700' => $mantenimiento->vencido,
                                    'bg-marca-100 text-marca-700' => ! $mantenimiento->vencido,
                                ])>
                                    {{ $mantenimiento->aviso }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- ─── Sus datos ─────────────────────────────────────────────── --}}
            <aside class="space-y-6">
                <div class="rounded-2xl border border-tinta-200 bg-white p-6 shadow-sm">
                    <h2 class="font-titulo text-lg font-bold">Datos del carro</h2>

                    <form method="post" action="{{ route('cuenta.vehiculo.actualizar', $vehiculo) }}" class="mt-4">
                        @csrf

                        <div>
                            <label for="placa" class="{{ $rotulo }}">Placa</label>
                            <input id="placa" name="placa" maxlength="10" placeholder="ABC 123"
                                   value="{{ old('placa', $placa) }}"
                                   class="{{ $campo }} uppercase tabular-nums">
                            @error('placa')
                                <p role="alert" class="mt-1 text-xs text-alerta-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-3">
                            <label for="alias" class="{{ $rotulo }}">
                                Cómo le dices <span class="font-normal normal-case text-tinta-400">(opcional)</span>
                            </label>
                            <input id="alias" name="alias" maxlength="60" placeholder="El de la empresa"
                                   value="{{ old('alias', $alias) }}" class="{{ $campo }}">
                            @error('alias')
                                <p role="alert" class="mt-1 text-xs text-alerta-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="mt-4 w-full rounded-lg bg-marca-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-marca-800">
                            Guardar cambios
                        </button>
                    </form>

                    {{-- La ficha no se edita: es del catálogo, no del cliente.
                         Si el carro está mal elegido se quita y se agrega el
                         correcto, que es una decisión y no un descuido. --}}
                    <dl class="mt-6 space-y-2 border-t border-tinta-200 pt-5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-tinta-500">Marca</dt>
                            <dd class="font-medium">{{ $vehiculo->modelo->marca->nombre }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-tinta-500">Modelo</dt>
                            <dd class="font-medium">{{ $vehiculo->modelo->nombre }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-tinta-500">Cilindraje</dt>
                            <dd class="font-medium tabular-nums">{{ $vehiculo->cilindraje }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-tinta-500">Años</dt>
                            <dd class="font-medium tabular-nums">{{ $vehiculo->anio_inicio }}–{{ $vehiculo->anio_fin }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Quitar el carro va al final y en contorno: es lo único de
                     esta página que no se puede deshacer. --}}
                <form method="post" action="{{ route('cuenta.vehiculo.quitar', $vehiculo) }}"
                      onsubmit="return confirm('¿Quitamos este carro de tu cuenta? Sus mantenimientos anotados se quedan en tu historial.')">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-lg border border-tinta-300 px-4 py-2.5 text-sm font-medium text-tinta-600 transition hover:border-alerta-300 hover:bg-alerta-50 hover:text-alerta-700">
                        Quitar este carro de mi cuenta
                    </button>
                </form>
            </aside>
        </div>
    </div>
@endsection
