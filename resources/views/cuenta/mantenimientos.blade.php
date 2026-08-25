@extends('layouts.app')

@section('titulo', 'Historial de mantenimientos')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-10">

        <a href="{{ route('cuenta') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
            ← Mi cuenta
        </a>

        <p class="mt-4 font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Mi cuenta</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Historial de mantenimientos</h1>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Anotar uno nuevo. Va arriba porque es a lo que se entra. --}}
        <section x-data="{ abierto: {{ $errors->any() || $mantenimientos->isEmpty() ? 'true' : 'false' }} }"
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
                @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600 focus:outline-none'; @endphp

                <div>
                    <label for="placa" class="text-sm font-medium">Placa</label>
                    <input id="placa" name="placa" value="{{ old('placa') }}" required maxlength="10"
                           list="mis-placas" placeholder="ABC 123" class="{{ $campo }} uppercase tabular-nums">
                    <datalist id="mis-placas">
                        @foreach ($placas as $placa)
                            <option value="{{ $placa }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <label for="vehiculo_id" class="text-sm font-medium">
                        Vehículo <span class="font-normal text-tinta-500">(opcional)</span>
                    </label>
                    <select id="vehiculo_id" name="vehiculo_id" class="{{ $campo }}">
                        <option value="">Sin asociar</option>
                        @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id') == $vehiculo->id)>
                                {{ $vehiculo->pivot->alias ?: $vehiculo->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tipo" class="text-sm font-medium">Qué se le hizo</label>
                    <input id="tipo" name="tipo" value="{{ old('tipo') }}" required maxlength="80"
                           list="tipos-comunes" placeholder="Cambio de aceite" class="{{ $campo }}">
                    <datalist id="tipos-comunes">
                        @foreach (['Cambio de aceite', 'Filtro de aceite', 'Pastillas de freno', 'Kit de distribución',
                                   'Bujías', 'Amortiguadores', 'Batería', 'Alineación y balanceo', 'Refrigerante'] as $comun)
                            <option value="{{ $comun }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="fecha" class="text-sm font-medium">Fecha</label>
                        <input id="fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}"
                               required max="{{ today()->toDateString() }}" class="{{ $campo }} tabular-nums">
                    </div>
                    <div>
                        <label for="kilometraje" class="text-sm font-medium">Kilometraje</label>
                        <input id="kilometraje" type="number" name="kilometraje" value="{{ old('kilometraje') }}"
                               required min="0" inputmode="numeric" class="{{ $campo }} tabular-nums">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-sm font-medium">Avísame del próximo</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span class="text-sm text-tinta-600">cada</span>
                        <input type="number" name="periodicidad_valor" value="{{ old('periodicidad_valor', 6) }}"
                               required min="1" aria-label="Cada cuánto"
                               class="w-24 rounded-lg border border-tinta-300 px-3 py-2.5 text-sm tabular-nums">
                        <select name="periodicidad_tipo" aria-label="Unidad"
                                class="rounded-lg border border-tinta-300 px-3 py-2.5 text-sm">
                            @foreach (\App\Models\Mantenimiento::PERIODICIDADES as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('periodicidad_tipo', 'meses') === $valor)>
                                    {{ $texto }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="mt-1 text-xs text-tinta-500">
                        Por kilómetros se suman a los de hoy; por días o meses se cuentan desde la fecha.
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label for="notas" class="text-sm font-medium">
                        Notas <span class="font-normal text-tinta-500">(opcional)</span>
                    </label>
                    <textarea id="notas" name="notas" rows="2" maxlength="1000"
                              placeholder="Marca del aceite, taller, lo que quieras recordar"
                              class="{{ $campo }}">{{ old('notas') }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <button type="submit"
                            class="rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                        Guardar
                    </button>
                </div>
            </form>
        </section>

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
                    <li data-revelar class="con-luz rounded-2xl border border-tinta-200 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md">
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
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $mantenimientos->links() }}</div>
        @endif
    </div>
@endsection
