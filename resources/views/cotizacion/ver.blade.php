@extends('layouts.app')

@section('titulo', 'Mi cotización')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-8">

        <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Tu solicitud</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Mi cotización</h1>

        @if ($porVehiculo->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-tinta-300 bg-white p-12 text-center">
                <p class="text-lg font-semibold">Todavía no has agregado repuestos</p>
                <p class="mx-auto mt-2 max-w-md text-sm text-tinta-500">
                    Busca las piezas que necesitas y agrégalas. Puedes pedir para varios carros
                    en una misma solicitud.
                </p>
                <a href="{{ route('catalogo') }}"
                   class="mt-6 inline-block rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
                    Ver el catálogo
                </a>
            </div>
        @else
            <p class="mt-1 text-sm text-tinta-500">
                <span class="tabular-nums">{{ $totalItems }}</span>
                {{ Str::plural('repuesto', $totalItems) }} para
                <span class="tabular-nums">{{ $porVehiculo->count() }}</span>
                {{ Str::plural('vehículo', $porVehiculo->count()) }}.
                Un asesor te contacta para confirmarte disponibilidad.
            </p>

            <div class="mt-8 space-y-6">
                @foreach ($porVehiculo as $nombreVehiculo => $items)
                    @php $vehiculo = $items->first()->producto->vehiculo; @endphp

                    <section data-revelar class="overflow-hidden rounded-2xl border border-tinta-200 bg-white shadow-sm">
                        <header class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-tinta-200 bg-tinta-50 px-5 py-3">
                            <h2 class="font-semibold">{{ $nombreVehiculo }}</h2>
                            <span class="rounded-full bg-marca-100 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-marca-700">
                                {{ $items->sum('cantidad') }}
                            </span>
                            <form method="post" action="{{ route('cotizacion.quitar-vehiculo', $vehiculo) }}" class="ml-auto">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-tinta-500 underline-offset-2 hover:text-alerta-600 hover:underline">
                                    Quitar este vehículo
                                </button>
                            </form>
                        </header>

                        <ul class="divide-y divide-tinta-200">
                            @foreach ($items as $item)
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-3 px-5 py-4">
                                    <div class="min-w-48 flex-1">
                                        <a href="{{ route('producto', $item->producto) }}"
                                           class="font-medium hover:text-marca-700 hover:underline">
                                            {{ $item->producto->nombre }}
                                        </a>
                                        <p class="text-sm text-tinta-500">{{ $item->producto->tipoParte->categoria->nombre }}</p>
                                    </div>

                                    <form method="post" action="{{ route('cotizacion.actualizar', $item->producto) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        <label for="cant-{{ $item->producto->id }}" class="text-sm text-tinta-500">Cant.</label>
                                        <input id="cant-{{ $item->producto->id }}" type="number" name="cantidad"
                                               value="{{ $item->cantidad }}" min="1" max="99"
                                               onchange="this.form.submit()"
                                               class="w-20 rounded-lg border border-tinta-300 px-2 py-1.5 text-center text-sm tabular-nums">
                                    </form>

                                    <form method="post" action="{{ route('cotizacion.quitar', $item->producto) }}">
                                        @csrf
                                        <button type="submit" aria-label="Quitar {{ $item->producto->nombre }}"
                                                class="rounded-lg px-3 py-1.5 text-sm font-medium text-tinta-500 hover:bg-tinta-100 hover:text-alerta-600">
                                            Quitar
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <a href="{{ route('catalogo') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
                    ← Seguir agregando repuestos
                </a>
                <form method="post" action="{{ route('cotizacion.vaciar') }}" class="ml-auto">
                    @csrf
                    <button type="submit" class="text-sm text-tinta-500 underline-offset-2 hover:text-alerta-600 hover:underline">
                        Vaciar todo
                    </button>
                </form>
            </div>

            <section class="mt-12 rounded-xl border border-tinta-200 bg-white p-6">
                <h2 class="text-lg font-bold tracking-tight">¿A quién llamamos?</h2>
                <p class="mt-1 text-sm text-tinta-500">
                    Un asesor te contacta para atender tu solicitud.
                </p>

                @if ($errors->any())
                    <div role="alert" class="mt-4 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
                        <p class="font-semibold">Revisa estos datos:</p>
                        <ul class="mt-1 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('cotizacion.enviar') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf

                    {{-- Campo trampa: invisible para las personas, irresistible para los robots. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="sitio_web">No llenes este campo</label>
                        <input id="sitio_web" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
                    </div>

                    @php $claseCampo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600 focus:outline-none'; @endphp

                    <div>
                        <label for="nombre" class="text-sm font-medium">Nombre <span class="text-alerta-500">*</span></label>
                        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="given-name" class="{{ $claseCampo }}">
                    </div>
                    <div>
                        <label for="apellidos" class="text-sm font-medium">Apellidos</label>
                        <input id="apellidos" name="apellidos" value="{{ old('apellidos') }}" autocomplete="family-name" class="{{ $claseCampo }}">
                    </div>
                    <div>
                        <label for="telefono" class="text-sm font-medium">Teléfono <span class="text-alerta-500">*</span></label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" required inputmode="tel" autocomplete="tel" class="{{ $claseCampo }}">
                    </div>
                    <div>
                        <label for="email" class="text-sm font-medium">Correo electrónico <span class="text-alerta-500">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="{{ $claseCampo }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notas" class="text-sm font-medium">Comentarios</label>
                        <textarea id="notas" name="notas" rows="3" class="{{ $claseCampo }}"
                                  placeholder="Referencias, marca preferida, urgencia…">{{ old('notas') }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="acepta" value="1" @checked(old('acepta'))
                                   class="mt-0.5 size-4 rounded border-tinta-300 text-marca-700">
                            <span>
                                Autorizo a Importadora Sur Alpine a tratar mis datos para responder esta
                                solicitud. <span class="text-alerta-500">*</span>
                            </span>
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit"
                                class="w-full rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white hover:bg-alerta-600 sm:w-auto">
                            Enviar mi solicitud
                        </button>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection
