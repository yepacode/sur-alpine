{{--
    El buscador por vehículo.

    Es LA función del negocio, así que tiene que ser lo primero que se ve y lo
    más fácil de usar. Antes era un panel negro sobre un hero negro: la pieza
    más importante del sitio se desvanecía en el fondo. Ahora es al revés —una
    tarjeta clara, elevada, flotando sobre el degradado oscuro—, que es lo que
    la hace inconfundible sin necesidad de gritar.

    En el sitio actual esto está escondido tras un botón del menú y cuesta cinco
    segundos de esperas; aquí resuelve en el navegador, sin recargar.
--}}
<div x-data="selectorVehiculo('{{ route('vehiculos.arbol') }}', {{ $vehiculoActivo?->id ?? 'null' }})"
     class="overflow-hidden rounded-2xl bg-white shadow-[0_30px_60px_-20px_rgba(0,0,0,0.55)] ring-1 ring-black/5">

    {{-- Franja superior: dice qué es esto de un vistazo. --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-tinta-200 bg-tinta-50 px-5 py-3.5 sm:px-7">
        <span class="grid size-7 place-items-center rounded-lg bg-alerta-500 text-white" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
            </svg>
        </span>
        <h2 class="font-titulo text-base font-bold uppercase tracking-[0.06em] text-tinta-900 sm:text-lg">
            {{ contenido('buscador.titulo', 'Busca por tu vehículo') }}
        </h2>
        <span class="text-sm text-tinta-500">{{ contenido('buscador.subtitulo', 'y te mostramos sólo lo que le sirve') }}</span>
    </div>

    <form method="post" action="{{ route('vehiculo.guardar') }}" class="px-5 py-5 sm:px-7 sm:py-6">
        @csrf

        @php
            // D1 · La clase `.selector` (definida en app.css) pinta la flecha
            // como SVG de fondo. Antes el buscador ocultaba la nativa y no
            // ponía nada, así que no quedaba señal de que fuera un desplegable.
            $ranura = 'selector w-full rounded-xl border-2 border-tinta-200 bg-white px-3.5 py-3 text-[15px] font-medium text-tinta-900 transition hover:border-tinta-300 focus:border-marca-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600 disabled:cursor-not-allowed disabled:border-tinta-200 disabled:bg-tinta-50 disabled:text-tinta-400';
            $rotulo = 'mb-1.5 block font-titulo text-[11px] font-bold uppercase tracking-[0.14em] text-tinta-500';
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(4,1fr)_auto] lg:items-end">

            <div>
                <label for="hero-marca" class="{{ $rotulo }}">Marca</label>
                {{-- Si la lista no cargó, el campo queda inerte: ofrecerlo activo
                     y vacío es prometer algo que no se puede cumplir. --}}
                <select id="hero-marca" x-model="marca" @change="cambiarMarca()"
                        :disabled="cargando || error" aria-describedby="hero-paso" class="{{ $ranura }}">
                    <option value="">Elige la marca</option>
                    <template x-for="m in marcas" :key="m"><option :value="m" x-text="m"></option></template>
                </select>
            </div>

            <div>
                <label for="hero-modelo" class="{{ $rotulo }}">Modelo</label>
                <select id="hero-modelo" x-model="modelo" @change="cambiarModelo()" :disabled="!marca" class="{{ $ranura }}">
                    <option value="" x-text="marca ? 'Elige el modelo' : 'Elige primero la marca'">Elige el modelo</option>
                    <template x-for="m in modelos" :key="m"><option :value="m" x-text="m"></option></template>
                </select>
            </div>

            <div>
                <label for="hero-cilindraje" class="{{ $rotulo }}">Cilindraje</label>
                <select id="hero-cilindraje" x-model="cilindraje" @change="cambiarCilindraje()" :disabled="!modelo" class="{{ $ranura }}">
                    <option value="" x-text="modelo ? 'Elige el cilindraje' : 'Elige primero el modelo'">Elige el cilindraje</option>
                    <template x-for="c in cilindrajes" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
            </div>

            <div>
                <label for="hero-anio" class="{{ $rotulo }}">Año</label>
                <select id="hero-anio" x-model="anio" :disabled="!cilindraje" class="{{ $ranura }} cifra">
                    <option value="" x-text="cilindraje ? 'Elige el año' : 'Elige primero el cilindraje'">Elige el año</option>
                    <template x-for="a in anios" :key="a"><option :value="a" x-text="a"></option></template>
                </select>
            </div>

            <input type="hidden" name="vehiculo_id" :value="vehiculoId">

            {{-- El botón de arranque. Rojo de marca, y el único elemento del
                 panel que cambia de forma cuando ya se puede pulsar.

                 `aria-disabled` en vez de `disabled`: un botón deshabilitado de
                 verdad sale del recorrido de tabulación, y quien navegaba con
                 teclado pasaba del año al carrusel sin encontrarlo nunca ni
                 enterarse de por qué. Así sigue ahí y explica qué falta. --}}
            <button type="submit" :aria-disabled="!completo" @click="if (!completo) { $event.preventDefault(); enfocarPendiente() }"
                    class="group flex items-center justify-center gap-2 rounded-xl bg-alerta-500 px-8 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.08em] text-white shadow-lg shadow-alerta-500/30 transition hover:bg-alerta-600 hover:shadow-alerta-600/40 aria-disabled:bg-tinta-200 aria-disabled:text-tinta-500 aria-disabled:shadow-none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="size-4 shrink-0" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                </svg>
                {{ contenido('buscador.boton', 'Buscar') }}
            </button>
        </div>

        {{-- Qué falta para poder buscar. Los cuatro campos se van habilitando
             solos y ese cambio no lo anuncia nadie: esta línea sí. --}}
        <p id="hero-paso" class="mt-3 text-sm text-tinta-500" role="status" aria-live="polite" x-text="pendiente"></p>

        <p x-show="cargando" x-cloak role="status" aria-live="polite" class="mt-3 text-sm text-tinta-500">
            Cargando la lista de vehículos…
        </p>

        <div x-show="error" x-cloak role="status" aria-live="polite"
             class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl bg-alerta-500/5 px-4 py-3 text-sm ring-1 ring-alerta-500/20">
            <span class="font-medium text-alerta-700">No pudimos cargar la lista de vehículos.</span>
            <button type="button" @click="cargar()"
                    class="rounded-lg border border-alerta-500/30 px-3 py-1.5 font-semibold text-alerta-700 transition hover:bg-alerta-500/10">
                Reintentar
            </button>
            <a href="tel:{{ $contacto->pbxTel() }}" class="font-semibold text-marca-700 underline underline-offset-2">
                o llámanos al {{ $contacto->pbx() }}
            </a>
        </div>
    </form>
</div>
