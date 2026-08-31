@extends('layouts.app')

@section('titulo', 'Historial de mantenimientos')

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-10">

        <a href="{{ route('cuenta') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
            ← Mi cuenta
        </a>

        <p class="mt-4 font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Mi cuenta</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Historial de mantenimientos</h1>

        @if ($errors->any())
            {{-- El aviso se enfoca al cargar: un `role="alert"` que ya viene en el
     HTML no lo anuncia ningún lector de pantalla —la región viva sirve
     para lo que aparece DESPUÉS—, y aquí el foco se quedaba en el `body`
     o se lo llevaba el `autofocus` del primer campo. --}}
                <div role="alert" tabindex="-1" x-data x-init="$el.focus()" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Anotar uno nuevo. Va arriba porque es a lo que se entra. --}}
        {{-- `old('_editando')`: si el error vino de una correccion, este bloque
             NO se abre. Antes se abria con cualquier error y el cliente leia el
             aviso encima de un formulario vacio que no tenia nada que ver. --}}
        <section x-data="{ abierto: {{ ($errors->any() && old('_editando') === null) || $mantenimientos->isEmpty() ? 'true' : 'false' }} }"
                 class="mt-6 rounded-2xl border border-tinta-200 bg-white shadow-sm">
            <button type="button" @click="abierto = !abierto" :aria-expanded="abierto" aria-controls="form-mantenimiento"
                    class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="font-bold">Anotar un mantenimiento</span>
                <span class="text-sm font-semibold text-marca-700" x-text="abierto ? 'Cerrar' : 'Abrir'"></span>
            </button>

            <form id="form-mantenimiento" x-show="abierto" x-cloak method="post"
                  action="{{ route('cuenta.mantenimientos.guardar') }}"
                  class="grid gap-4 border-t border-tinta-200 p-5 sm:grid-cols-2">
                @csrf

                @include('cuenta.campos-mantenimiento', ['m' => null, 'prefijo' => 'nuevo'])

                <div class="sm:col-span-2">
                    <button type="submit"
                            class="rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                        Guardar
                    </button>
                </div>
            </form>
        </section>

        {{-- Las sugerencias viven aquí, fuera de los formularios: un `datalist`
             se referencia por id desde cualquier parte de la página, y así no
             se repiten una vez por cada mantenimiento del historial. --}}
        <datalist id="mis-placas">
            @foreach ($placas as $placa)
                <option value="{{ $placa }}"></option>
            @endforeach
        </datalist>
        <datalist id="tipos-comunes">
            @foreach (['Cambio de aceite', 'Filtro de aceite', 'Pastillas de freno', 'Kit de distribución',
                       'Bujías', 'Amortiguadores', 'Batería', 'Alineación y balanceo', 'Refrigerante'] as $comun)
                <option value="{{ $comun }}"></option>
            @endforeach
        </datalist>

        {{-- Filtro por placa: un mecánico maneja varios carros. --}}
        @if ($placas->count() > 1)
            <div class="mt-8 flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-tinta-600">Placa:</span>
                <a href="{{ route('cuenta.mantenimientos') }}"
                   @class(['rounded-full px-3 py-1 text-sm font-medium',
                           'bg-marca-700 text-white' => ! $placaFiltrada,
                           'bg-white text-tinta-700 ring-1 ring-tinta-200' => $placaFiltrada])>Todas</a>
                @foreach ($placas as $placa)
                    <a href="{{ route('cuenta.mantenimientos', ['placa' => $placa]) }}"
                       @class(['rounded-full px-3 py-1 text-sm font-medium tabular-nums',
                               'bg-marca-700 text-white' => $placaFiltrada === $placa,
                               'bg-white text-tinta-700 ring-1 ring-tinta-200' => $placaFiltrada !== $placa])>{{ $placa }}</a>
                @endforeach
            </div>
        @endif

        {{-- El historial --}}
        @if ($mantenimientos->isEmpty())
            <p class="mt-8 rounded-xl border border-dashed border-tinta-300 bg-white p-8 text-center text-tinta-600">
                Todavía no hay nada anotado.
            </p>
        @else
            <ul class="mt-6 space-y-3">
                @foreach ($mantenimientos as $mantenimiento)
                    {{-- Se reabre solo el que fallo: si no, la correccion volvia
                         a quedar cerrada y el error de arriba parecia de otro. --}}
                    <li data-revelar x-data="{ editando: {{ (string) old('_editando') === (string) $mantenimiento->id ? 'true' : 'false' }} }"
                        class="con-luz rounded-2xl border border-tinta-200 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md">
                        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                            <div class="min-w-48 flex-1">
                                <p class="font-semibold">{{ $mantenimiento->tipo }}</p>
                                <p class="mt-0.5 text-sm tabular-nums text-tinta-600">
                                    <span class="font-medium">{{ $mantenimiento->placa }}</span> ·
                                    {{ $mantenimiento->fecha->translatedFormat('d M Y') }} ·
                                    {{ number_format($mantenimiento->kilometraje, 0, ',', '.') }} km
                                </p>
                                @if ($mantenimiento->notas)
                                    <p class="mt-2 text-sm text-tinta-600">{{ $mantenimiento->notas }}</p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-alerta-500/10 text-alerta-700' => $mantenimiento->vencido,
                                    'bg-marca-100 text-marca-700' => ! $mantenimiento->vencido,
                                ])>{{ $mantenimiento->aviso }}</span>

                                {{-- Corregir, y no sólo borrar. Quien se equivocaba
                                     en el kilometraje tenía que borrar el registro y
                                     escribirlo todo de nuevo. --}}
                                <button type="button" @click="editando = ! editando"
                                        :aria-expanded="editando"
                                        aria-label="Corregir {{ $mantenimiento->tipo }}"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-marca-700 hover:bg-marca-50">
                                    <span x-text="editando ? 'Cancelar' : 'Corregir'">Corregir</span>
                                </button>

                                <form method="post" action="{{ route('cuenta.mantenimientos.borrar', $mantenimiento) }}"
                                      onsubmit="return confirm('¿Borrar este registro?')">
                                    @csrf
                                    <button type="submit" aria-label="Borrar {{ $mantenimiento->tipo }}"
                                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-tinta-500 hover:bg-tinta-100 hover:text-alerta-600">
                                        Borrar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form x-show="editando" x-cloak x-transition.opacity.duration.200ms method="post"
                              action="{{ route('cuenta.mantenimientos.actualizar', $mantenimiento) }}"
                              class="mt-5 grid gap-4 border-t border-tinta-200 pt-5 sm:grid-cols-2">
                            @csrf
                            {{-- Marca cuál de los formularios de la página se envió,
                                 para que `old()` reponga los datos en este y no en
                                 los otros diez. --}}
                            <input type="hidden" name="_editando" value="{{ $mantenimiento->id }}">
                            @include('cuenta.campos-mantenimiento', [
                                'm' => $mantenimiento,
                                'prefijo' => 'm'.$mantenimiento->id,
                            ])

                            <div class="sm:col-span-2 flex flex-wrap gap-3">
                                <button type="submit"
                                        class="rounded-lg bg-marca-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-marca-800">
                                    Guardar cambios
                                </button>
                                <button type="button" @click="editando = false"
                                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-tinta-600 hover:bg-tinta-100">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $mantenimientos->links() }}</div>
        @endif
    </div>
@endsection
