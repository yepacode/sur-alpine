@extends('layouts.app')

@section('titulo', 'Mi cuenta')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-10">

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Hola, {{ auth()->user()->primer_nombre }}
                </h1>
                <p class="mt-1 text-tinta-600">
                    Tus carros y sus mantenimientos, en un solo lugar.
                </p>
            </div>
            <form method="post" action="{{ route('salir') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-tinta-600 underline-offset-2 hover:underline">
                    Cerrar sesión
                </button>
            </form>
        </div>

        {{-- Lo que toca pronto va arriba: es a lo que entra un mecánico. --}}
        <section class="mt-8">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold">Próximos mantenimientos</h2>
                <a href="{{ route('cuenta.mantenimientos') }}" class="text-sm font-semibold text-marca-700 hover:underline">
                    Ver todo el historial ({{ $totalMantenimientos }}) →
                </a>
            </div>

            @if ($proximos->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-tinta-300 bg-white p-8 text-center">
                    <p class="font-semibold">Todavía no has anotado ningún mantenimiento</p>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-tinta-600">
                        Anota qué le hiciste al carro y cuándo. Nosotros calculamos cuándo toca el próximo.
                    </p>
                    <a href="{{ route('cuenta.mantenimientos') }}"
                       class="mt-5 inline-block rounded-lg bg-alerta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-alerta-600">
                        Anotar el primero
                    </a>
                </div>
            @else
                <ul class="mt-4 divide-y divide-tinta-200 overflow-hidden rounded-xl border border-tinta-200 bg-white">
                    @foreach ($proximos as $mantenimiento)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4">
                            <div class="min-w-48 flex-1">
                                <p class="font-semibold">{{ $mantenimiento->tipo }}</p>
                                <p class="text-sm tabular-nums text-tinta-600">
                                    {{ $mantenimiento->placa }} ·
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

        {{-- Mis vehículos --}}
        <section class="mt-10">
            <h2 class="text-lg font-bold">Mis vehículos</h2>

            @if ($vehiculos->isEmpty())
                <p class="mt-2 text-sm text-tinta-600">
                    Guarda los carros que manejas para no tener que buscarlos cada vez.
                </p>
            @else
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($vehiculos as $vehiculo)
                        <li class="flex items-center gap-3 rounded-xl border border-tinta-200 bg-white px-5 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">
                                    {{ $vehiculo->pivot->alias ?: $vehiculo->nombre_completo }}
                                </p>
                                <p class="truncate text-sm text-tinta-600">
                                    @if ($vehiculo->pivot->placa)
                                        <span class="font-medium tabular-nums">{{ $vehiculo->pivot->placa }}</span> ·
                                    @endif
                                    {{ $vehiculo->nombre_completo }}
                                </p>
                            </div>
                            <form method="post" action="{{ route('cuenta.vehiculo.quitar', $vehiculo) }}">
                                @csrf
                                <button type="submit" aria-label="Quitar {{ $vehiculo->nombre_completo }}"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-tinta-500 hover:bg-tinta-100 hover:text-alerta-600">
                                    Quitar
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-5 rounded-xl border border-tinta-200 bg-white p-5">
                <h3 class="text-sm font-bold uppercase tracking-wide text-tinta-700">Agregar un vehículo</h3>

                <form method="post" action="{{ route('cuenta.vehiculo.guardar') }}"
                      x-data="selectorVehiculo('{{ route('vehiculos.arbol') }}')" class="mt-4">
                    @csrf
                    @php $campo = 'w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm disabled:bg-tinta-100 disabled:text-tinta-400'; @endphp

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="cta-marca" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Marca</label>
                            <select id="cta-marca" x-model="marca" @change="cambiarMarca()" :disabled="cargando || error" class="{{ $campo }}">
                                <option value="">Elige la marca</option>
                                <template x-for="m in marcas" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                        </div>
                        <div>
                            <label for="cta-modelo" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Modelo</label>
                            <select id="cta-modelo" x-model="modelo" @change="cambiarModelo()" :disabled="!marca" class="{{ $campo }}">
                                <option value="">Elige el modelo</option>
                                <template x-for="m in modelos" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                        </div>
                        <div>
                            <label for="cta-cc" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Cilindraje</label>
                            <select id="cta-cc" x-model="cilindraje" @change="cambiarCilindraje()" :disabled="!modelo" class="{{ $campo }}">
                                <option value="">Elige el cilindraje</option>
                                <template x-for="c in cilindrajes" :key="c"><option :value="c" x-text="c"></option></template>
                            </select>
                        </div>
                        <div>
                            <label for="cta-anio" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Año</label>
                            <select id="cta-anio" x-model="anio" :disabled="!cilindraje" class="{{ $campo }} tabular-nums">
                                <option value="">Elige el año</option>
                                <template x-for="a in anios" :key="a"><option :value="a" x-text="a"></option></template>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label for="cta-placa" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Placa</label>
                            <input id="cta-placa" name="placa" maxlength="10" placeholder="ABC 123"
                                   class="{{ $campo }} uppercase tabular-nums">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="cta-alias" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">
                                Cómo le dices <span class="font-normal normal-case text-tinta-400">(opcional)</span>
                            </label>
                            <input id="cta-alias" name="alias" maxlength="60" placeholder="El de la empresa" class="{{ $campo }}">
                        </div>
                    </div>

                    <input type="hidden" name="vehiculo_id" :value="vehiculoId">

                    <button type="submit" :disabled="!completo"
                            class="mt-4 rounded-lg bg-marca-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-marca-800 disabled:cursor-not-allowed disabled:bg-tinta-300">
                        Guardar vehículo
                    </button>

                    <p x-show="error" x-cloak role="status" class="mt-3 text-xs text-alerta-600">
                        No pudimos cargar la lista.
                        <button type="button" @click="cargar()" class="font-semibold underline">Reintentar</button>
                    </p>
                </form>
            </div>
        </section>
    </div>
@endsection
