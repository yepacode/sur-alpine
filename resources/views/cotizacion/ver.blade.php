@extends('layouts.app')

@section('titulo', 'Mi cotización')

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-8">

        <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Tu solicitud</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">
            {{ contenido('cotizacion.titulo', 'Mi cotización') }}
        </h1>

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
                {{ plural($totalItems, 'repuesto', 'repuestos') }} para
                <span class="tabular-nums">{{ $porVehiculo->count() }}</span>
                {{ plural($porVehiculo->count(), 'vehículo', 'vehículos') }}.
                Un asesor te contacta en horario de oficina para confirmarte disponibilidad.
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
                            {{-- Confirma, como en «Mis mantenimientos». El criterio
                                 ya existía en el sitio y estaba aplicado justo al
                                 revés: los tres botones que borran el trabajo que
                                 el cliente acaba de armar eran los únicos que no
                                 preguntaban nada. Y en un teléfono este va con
                                 `ml-auto`, o sea en el borde derecho, en plena
                                 zona del pulgar al hacer scroll. --}}
                            <form method="post" action="{{ route('cotizacion.quitar-vehiculo', $vehiculo) }}" class="ml-auto"
                                  onsubmit="return confirm('¿Quitar los {{ $items->sum('cantidad') }} repuestos de {{ addslashes($nombreVehiculo) }}? El resto de tu cotización se queda.')">
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

                                    {{-- Menos y más de 44 px, y la casilla sigue ahí
                                         para quien prefiera teclear.
                                         Antes cada cambio de cantidad recargaba la
                                         página entera y devolvía al principio de una
                                         lista larga, y la única forma de subir de 1 a
                                         3 era sacar el teclado numérico. --}}
                                    <form method="post" action="{{ route('cotizacion.actualizar', $item->producto) }}"
                                          x-data class="flex items-center gap-1">
                                        @csrf
                                        <label for="cant-{{ $item->producto->id }}" class="mr-1 text-sm text-tinta-500">Cant.</label>

                                        <button type="button" aria-label="Una unidad menos de {{ $item->producto->nombre }}"
                                                x-on:click="$refs.cant{{ $item->producto->id }}.stepDown(); $refs.cant{{ $item->producto->id }}.form.requestSubmit()"
                                                class="grid size-11 shrink-0 place-items-center rounded-lg border border-tinta-300 text-lg font-bold text-tinta-600 hover:bg-tinta-100">−</button>

                                        <input id="cant-{{ $item->producto->id }}" x-ref="cant{{ $item->producto->id }}"
                                               type="number" name="cantidad" inputmode="numeric"
                                               value="{{ $item->cantidad }}" min="1" max="99"
                                               onchange="this.form.requestSubmit()"
                                               class="w-14 rounded-lg border border-tinta-300 px-2 py-2.5 text-center text-sm tabular-nums">

                                        <button type="button" aria-label="Una unidad más de {{ $item->producto->nombre }}"
                                                x-on:click="$refs.cant{{ $item->producto->id }}.stepUp(); $refs.cant{{ $item->producto->id }}.form.requestSubmit()"
                                                class="grid size-11 shrink-0 place-items-center rounded-lg border border-tinta-300 text-lg font-bold text-tinta-600 hover:bg-tinta-100">+</button>
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
                <form method="post" action="{{ route('cotizacion.vaciar') }}" class="ml-auto"
                      onsubmit="return confirm('¿Vaciar la cotización entera? Se van los {{ $totalItems }} repuestos y no se puede deshacer.')">
                    @csrf
                    <button type="submit" class="text-sm text-tinta-500 underline-offset-2 hover:text-alerta-600 hover:underline">
                        Vaciar todo
                    </button>
                </form>
            </div>

            <section class="mt-12 rounded-xl border border-tinta-200 bg-white p-6">

                @guest
                    {{-- Sin sesión no se envía. Lo importante de esta tarjeta es
                         la última línea: quien teme perder lo que armó, se va. --}}
                    <h2 class="text-lg font-bold tracking-tight">Para enviarla, entra a tu cuenta</h2>
                    <p class="mt-2 max-w-prose text-sm text-tinta-600">
                        Así tu solicitud queda guardada a tu nombre, no tienes que volver a
                        escribir tus datos, y puedes seguirle la pista desde «Mi cuenta».
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('acceso') }}"
                           class="con-luz rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                            Iniciar sesión
                        </a>
                        <a href="{{ route('registro') }}"
                           class="rounded-lg border-2 border-marca-700 px-6 py-3 font-semibold text-marca-700 transition hover:bg-marca-50">
                            Crear una cuenta
                        </a>
                    </div>

                    {{-- Lo dice y ADEMÁS es cierto: `CotizacionController::ver`
                         deja anotada la vuelta en la sesión, así que al entrar
                         se cae otra vez aquí y no en la portada. Antes el
                         registro soltaba al visitante en el inicio, siete
                         pantallas lejos de la lista que acababa de armar, y
                         tenía que volver a encontrar un ícono de 18 px. --}}
                    <p class="mt-4 text-sm text-tinta-500">
                        Los repuestos que ya agregaste te esperan aquí: al entrar vuelves a esta página.
                    </p>
                @else
                <h2 class="text-lg font-bold tracking-tight">¿A quién llamamos?</h2>
                <p class="mt-1 text-sm text-tinta-500">
                    Un asesor te contacta en horario de oficina para atender tu solicitud.
                </p>

                {{-- El aviso se enfoca solo al cargar.
                     Es el único formulario que convierte y era el peor tratado:
                     tras un envío fallido la página volvía arriba del todo, con
                     este recuadro a 774 px de scroll —fuera de pantalla— y el
                     foco en el `body`. El mecánico veía la misma página, sin
                     nada rojo, y se iba creyendo que la había enviado. --}}
                @if ($errors->any())
                    <div role="alert" tabindex="-1" x-data x-init="$el.focus(); $el.scrollIntoView({ block: 'center' })"
                         class="mt-4 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
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

                    @php $claseCampo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600'; @endphp

                    @php
                        // Prellenado con lo de su cuenta. `old()` manda siempre:
                        // si la validación devolvió el formulario, lo que la
                        // persona escribió no se puede pisar con lo guardado.
                        //
                        // El nombre se parte en la primera palabra. No es una
                        // regla del idioma —«María Fernanda» son dos nombres— y
                        // por eso los dos campos siguen siendo editables: es una
                        // conjetura útil, no un dato que demos por cierto.
                        [$suNombre, $susApellidos] = array_pad(explode(' ', trim($usuario->name), 2), 2, '');
                    @endphp


                    <div>
                        <label for="nombre" class="text-sm font-medium">Nombre <span class="text-alerta-500">*</span></label>
                        <input id="nombre" name="nombre" value="{{ old('nombre', $suNombre) }}" required autocomplete="given-name"
                               @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror
                               class="{{ $claseCampo }} @error('nombre') border-alerta-500 @enderror">
                        @error('nombre') <p id="nombre-error" class="mt-1 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="apellidos" class="text-sm font-medium">Apellidos</label>
                        <input id="apellidos" name="apellidos" value="{{ old('apellidos', $susApellidos) }}" autocomplete="family-name" class="{{ $claseCampo }}">
                    </div>
                    <div>
                        <label for="telefono" class="text-sm font-medium">Teléfono <span class="text-alerta-500">*</span></label>
                        <input id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" required inputmode="tel" autocomplete="tel"
                               @error('telefono') aria-invalid="true" aria-describedby="telefono-error" @enderror
                               class="{{ $claseCampo }} @error('telefono') border-alerta-500 @enderror">
                        @error('telefono') <p id="telefono-error" class="mt-1 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="text-sm font-medium">Correo electrónico <span class="text-alerta-500">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email', $usuario->email) }}" required autocomplete="email"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                               class="{{ $claseCampo }} @error('email') border-alerta-500 @enderror">
                        @error('email') <p id="email-error" class="mt-1 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notas" class="text-sm font-medium">Comentarios</label>
                        <textarea id="notas" name="notas" rows="3" class="{{ $claseCampo }}"
                                  placeholder="Referencias, marca preferida, urgencia…">{{ old('notas') }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="acepta" value="1" @checked(old('acepta'))
                                   @error('acepta') aria-invalid="true" aria-describedby="acepta-error" @enderror
                                   class="mt-0.5 size-4 rounded border-tinta-300 text-marca-700">
                            <span>
                                Autorizo a Importadora Sur Alpine a tratar mis datos para responder esta
                                solicitud, en los términos de la
                                <a href="{{ route('politica-datos') }}" target="_blank" rel="noopener"
                                   class="font-semibold text-marca-700 hover:underline">política de datos</a>
                                (Ley 1581 de 2012). <span class="text-alerta-500">*</span>
                            </span>
                        </label>
                        @error('acepta') <p id="acepta-error" class="mt-1 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit"
                                class="w-full rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white hover:bg-alerta-600 sm:w-auto">
                            {{ contenido('cotizacion.boton', 'Enviar mi solicitud') }}
                        </button>
                    </div>
                </form>
                @endguest
            </section>
        @endif
    </div>
@endsection
