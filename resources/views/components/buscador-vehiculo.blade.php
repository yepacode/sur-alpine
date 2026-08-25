{{--
    El buscador por vehículo, con forma de tablero de carro.

    Es la función central del negocio, así que va sobre el hero y con una forma
    que se reconoce: panel oscuro, campos como instrumentos y el botón de
    arranque en rojo. En el sitio actual está escondido tras un botón del menú
    y cuesta cinco segundos de esperas; aquí resuelve en el navegador.
--}}
<div x-data="selectorVehiculo('{{ route('vehiculos.arbol') }}', {{ $vehiculoActivo?->id ?? 'null' }})"
     class="relative rounded-t-[2.5rem] rounded-b-2xl bg-gradient-to-b from-tinta-700 via-tinta-900 to-black p-1 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7)] ring-1 ring-white/10">

    {{-- Reflejo del vidrio del tablero --}}
    <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent" aria-hidden="true"></div>

    <div class="rounded-t-[2.25rem] rounded-b-xl bg-gradient-to-b from-white/[0.07] to-transparent px-5 pb-5 pt-5 sm:px-8 sm:pb-6">

        <div class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="size-2 rounded-full bg-alerta-500 shadow-[0_0_10px_2px] shadow-alerta-500/60" aria-hidden="true"></span>
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-white sm:text-base">
                Busca por tu vehículo
            </h2>
            <span class="text-xs text-tinta-400 sm:text-sm">y te mostramos sólo lo que le sirve</span>
        </div>

        <form method="post" action="{{ route('vehiculo.guardar') }}">
            @csrf

            @php
                $ranura = 'w-full appearance-none rounded-lg border border-white/10 bg-black/50 px-3 py-3 text-sm font-semibold text-white transition focus:border-alerta-500 focus:bg-black/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-alerta-500 disabled:cursor-not-allowed disabled:text-tinta-500 [background-image:none]';
                $rotulo = 'mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-tinta-400';
            @endphp

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(4,1fr)_auto] lg:items-end">

                <div>
                    <label for="hero-marca" class="{{ $rotulo }}">Marca</label>
                    {{-- Si la lista no cargó, el campo queda inerte: ofrecerlo activo
                         y vacío es prometer algo que no se puede cumplir. --}}
                    <select id="hero-marca" x-model="marca" @change="cambiarMarca()"
                            :disabled="cargando || error" aria-describedby="hero-paso" class="{{ $ranura }}">
                        <option value="" class="text-tinta-900">Elige la marca</option>
                        <template x-for="m in marcas" :key="m"><option :value="m" x-text="m" class="text-tinta-900"></option></template>
                    </select>
                </div>

                <div>
                    <label for="hero-modelo" class="{{ $rotulo }}">Modelo</label>
                    <select id="hero-modelo" x-model="modelo" @change="cambiarModelo()" :disabled="!marca" class="{{ $ranura }}">
                        <option value="" class="text-tinta-900" x-text="marca ? 'Elige el modelo' : 'Elige primero la marca'">Elige el modelo</option>
                        <template x-for="m in modelos" :key="m"><option :value="m" x-text="m" class="text-tinta-900"></option></template>
                    </select>
                </div>

                <div>
                    <label for="hero-cilindraje" class="{{ $rotulo }}">Cilindraje</label>
                    <select id="hero-cilindraje" x-model="cilindraje" @change="cambiarCilindraje()" :disabled="!modelo" class="{{ $ranura }}">
                        <option value="" class="text-tinta-900" x-text="modelo ? 'Elige el cilindraje' : 'Elige primero el modelo'">Elige el cilindraje</option>
                        <template x-for="c in cilindrajes" :key="c"><option :value="c" x-text="c" class="text-tinta-900"></option></template>
                    </select>
                </div>

                <div>
                    <label for="hero-anio" class="{{ $rotulo }}">Año</label>
                    <select id="hero-anio" x-model="anio" :disabled="!cilindraje" class="{{ $ranura }} tabular-nums">
                        <option value="" class="text-tinta-900" x-text="cilindraje ? 'Elige el año' : 'Elige primero el cilindraje'">Elige el año</option>
                        <template x-for="a in anios" :key="a"><option :value="a" x-text="a" class="text-tinta-900"></option></template>
                    </select>
                </div>

                <input type="hidden" name="vehiculo_id" :value="vehiculoId">

                {{-- Botón de arranque.
                     `aria-disabled` en vez de `disabled`: un botón deshabilitado
                     de verdad sale del recorrido de tabulación, y quien navegaba
                     con teclado pasaba del año al carrusel sin encontrarlo nunca
                     ni enterarse de por qué. Así sigue ahí y explica qué falta. --}}
                <button type="submit" :aria-disabled="!completo" @click="if (!completo) { $event.preventDefault(); enfocarPendiente() }"
                        class="group relative flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-alerta-500 to-alerta-700 px-7 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-alerta-700/40 ring-1 ring-white/20 transition hover:from-alerta-600 hover:to-alerta-700 aria-disabled:from-tinta-600 aria-disabled:to-tinta-700 aria-disabled:text-tinta-300 aria-disabled:shadow-none">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                    </svg>
                    Buscar
                </button>
            </div>

            {{-- Qué falta para poder buscar. Los cuatro campos se van habilitando
                 solos y ese cambio no lo anuncia nadie: esta línea sí. --}}
            <p id="hero-paso" class="mt-3 text-xs text-tinta-300" role="status" aria-live="polite" x-text="pendiente"></p>

            {{-- Los dos mensajes son región viva: si la lista no carga, el visitante
                 tiene que enterarse aunque no esté mirando este rincón del tablero. --}}
            <p x-show="cargando" x-cloak role="status" aria-live="polite" class="mt-4 text-xs text-tinta-300">
                Encendiendo…
            </p>

            <div x-show="error" x-cloak role="status" aria-live="polite" class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                {{-- Rojo claro, no `alerta-500`: sobre el panel negro del tablero
                     el rojo de marca no alcanza el contraste mínimo. --}}
                <span class="text-red-300">No pudimos cargar la lista de vehículos.</span>
                <button type="button" @click="cargar()"
                        class="rounded-full border border-white/25 px-4 py-1.5 font-semibold uppercase tracking-wider text-white transition hover:bg-white/10">
                    Reintentar
                </button>
                <a href="tel:{{ $contacto->pbxTel() }}" class="font-semibold text-white underline underline-offset-2">
                    o llámanos al {{ $contacto->pbx() }}
                </a>
            </div>
        </form>
    </div>
</div>
