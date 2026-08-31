@props(['prefijo' => 'hero'])

{{--
    El buscador por vehículo.

    Es LA función del negocio, así que tiene que ser lo primero que se ve y lo
    más fácil de usar. En el sitio actual está escondido tras un botón del menú
    y cuesta cinco segundos de esperas; aquí está a la vista y resuelve en el
    navegador, sin recargar.

    Sobre el diseño, dos cosas que se cambiaron y por qué:

    · La franja del título era gris pálido sobre blanco y la pieza más
      importante de la portada se leía como un formulario administrativo.
      Ahora va en azul de marca con el texto en blanco: pesa lo que tiene que
      pesar y engancha con la barra de la cabecera.

    El `prefijo` existe porque en la portada esto se pinta dos veces —en su
    sección y dentro del modal de «Agregar vehículo»—: es lo que evita cuatro
    `id` repetidos y cuatro etiquetas apuntando al formulario equivocado.

    · Los cuatro campos llevan su número. Es una cascada —sin marca no hay
      modelo—, y sin la numeración los tres campos apagados se leen como tres
      campos rotos, no como tres pasos que todavía no llegan.
--}}
<div role="search" aria-labelledby="{{ $prefijo }}-titulo"
     x-data="selectorVehiculo('{{ route('vehiculos.arbol') }}', {{ $vehiculoActivo?->id ?? 'null' }})"
     class="overflow-hidden rounded-2xl bg-white shadow-[0_18px_45px_-18px_rgba(0,0,0,0.35)] ring-1 ring-black/5">

    {{-- Franja superior: dice qué es esto de un vistazo. --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 bg-marca-700 px-5 py-4 sm:px-7">
        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-alerta-500 text-white" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- `p` y no `h2`: este bloque aparece ANTES del contenido en todas las
         páginas, así que su encabezado salía por encima del h1 de cada una y
         el esquema empezaba en el nivel 2 para bajar luego al 1. La región
         sigue anunciada por `aria-labelledby`, que es lo que de verdad usa un
         lector de pantalla. --}}
        {{-- El id lleva el `prefijo`. Sin él, en la portada salían DOS
             `id="rotulo-buscador"` —el del hero y el del modal— y el
             `aria-labelledby="modal-titulo"` del diálogo apuntaba a un id
             inexistente: el buscador de la cabecera se anunciaba sin nombre. --}}
        {{-- `text-white` explicito: sin el heredaba `tinta-900` sobre el azul
             de marca -2,18:1 medido, contra el 3:1 que exige texto grande- y
             el rotulo mas visible de la portada era el peor de leer. --}}
        <p id="{{ $prefijo }}-titulo" class="text-lg font-bold uppercase tracking-wide text-white sm:text-xl">
            {{ contenido('buscador.titulo', 'Busca por tu vehículo') }}
        </p>
        <span class="text-sm text-marca-100">{{ contenido('buscador.subtitulo', 'y te mostramos sólo lo que le sirve') }}</span>
    </div>

    <form method="post" action="{{ route('vehiculo.guardar') }}" class="px-5 py-6 sm:px-7">
        @csrf

        @php
            // D1 · La clase `.selector` (definida en app.css) pinta la flecha
            // como SVG de fondo. Antes el buscador ocultaba la nativa y no
            // ponía nada, así que no quedaba señal de que fuera un desplegable.
            $ranura = 'selector h-12 w-full rounded-xl border border-tinta-200 bg-tinta-50 px-3.5 text-base font-medium text-tinta-900 transition hover:border-tinta-300 hover:bg-white focus:border-marca-600 focus:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600 disabled:cursor-not-allowed disabled:border-tinta-200 disabled:bg-tinta-50 disabled:text-tinta-400';
            $rotulo = 'mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-tinta-500';
            $numero = 'grid size-4 shrink-0 place-items-center rounded-full bg-tinta-200 text-xs font-bold leading-none text-tinta-600';
        @endphp

        <div class="grid gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-[repeat(4,1fr)_auto] lg:items-end">

            <div>
                <label for="{{ $prefijo }}-marca" class="{{ $rotulo }}">
                    <span class="{{ $numero }}" aria-hidden="true">1</span>Marca
                </label>
                {{-- Si la lista no cargó, el campo queda inerte: ofrecerlo activo
                     y vacío es prometer algo que no se puede cumplir. --}}
                <select id="{{ $prefijo }}-marca" x-model="marca" @change="cambiarMarca()"
                        :disabled="cargando || error" aria-describedby="{{ $prefijo }}-paso" class="{{ $ranura }}">
                    <option value="">Elige la marca</option>
                    <template x-for="m in marcas" :key="m"><option :value="m" x-text="m"></option></template>
                </select>
            </div>

            <div>
                <label for="{{ $prefijo }}-modelo" class="{{ $rotulo }}">
                    <span class="{{ $numero }}" aria-hidden="true">2</span>Modelo
                </label>
                <select id="{{ $prefijo }}-modelo" x-model="modelo" @change="cambiarModelo()" :disabled="!marca" class="{{ $ranura }}">
                    <option value="" x-text="marca ? 'Elige el modelo' : 'Primero la marca'">Elige el modelo</option>
                    <template x-for="m in modelos" :key="m"><option :value="m" x-text="m"></option></template>
                </select>
            </div>

            <div>
                <label for="{{ $prefijo }}-cilindraje" class="{{ $rotulo }}">
                    <span class="{{ $numero }}" aria-hidden="true">3</span>Cilindraje
                </label>
                <select id="{{ $prefijo }}-cilindraje" x-model="cilindraje" @change="cambiarCilindraje()" :disabled="!modelo" class="{{ $ranura }}">
                    <option value="" x-text="modelo ? 'Elige el cilindraje' : 'Primero el modelo'">Elige el cilindraje</option>
                    <template x-for="c in cilindrajes" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
            </div>

            <div>
                <label for="{{ $prefijo }}-anio" class="{{ $rotulo }}">
                    <span class="{{ $numero }}" aria-hidden="true">4</span>Año
                </label>
                <select id="{{ $prefijo }}-anio" x-model="anio" :disabled="!cilindraje" class="{{ $ranura }} cifra">
                    <option value="" x-text="cilindraje ? 'Elige el año' : 'Primero el cilindraje'">Elige el año</option>
                    <template x-for="a in anios" :key="a"><option :value="a" x-text="a"></option></template>
                </select>
            </div>

            <input type="hidden" name="vehiculo_id" :value="vehiculoId">

            {{-- El botón de arranque. Rojo de marca y a la misma altura que los
                 campos, para que la fila se lea como una sola pieza.

                 Mientras falten datos se atenúa, pero NO se vuelve gris: en
                 gris parecía un botón averiado. Baja la opacidad, que es la
                 señal de «todavía no», no la de «esto no funciona».

                 `aria-disabled` en vez de `disabled`: un botón deshabilitado de
                 verdad sale del recorrido de tabulación, y quien navegaba con
                 teclado pasaba del año al carrusel sin encontrarlo nunca ni
                 enterarse de por qué. Así sigue ahí y explica qué falta. --}}
            <button type="submit" :aria-disabled="!completo" @click="if (!completo) { $event.preventDefault(); enfocarPendiente() }"
                    class="con-luz flex h-12 items-center justify-center gap-2 rounded-xl bg-alerta-500 px-8 sm:col-span-2 lg:col-span-1 text-sm font-bold uppercase tracking-[0.08em] text-white shadow-lg shadow-alerta-500/30 transition hover:bg-alerta-600 hover:shadow-alerta-600/40 aria-disabled:bg-alerta-300 aria-disabled:shadow-none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" class="size-4 shrink-0" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                </svg>
                {{ contenido('buscador.boton', 'Buscar') }}
            </button>
        </div>

        {{-- Qué falta para poder buscar. Los cuatro campos se van habilitando
             solos y ese cambio no lo anuncia nadie: esta línea sí. --}}
        <p id="{{ $prefijo }}-paso" class="mt-4 flex items-center gap-2 text-sm text-tinta-500" role="status" aria-live="polite">
            <svg viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0 text-tinta-300" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 15h-2v-6h2v6Zm0-8h-2V7h2v2Z"/>
            </svg>
            <span x-text="pendiente"></span>
        </p>

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
