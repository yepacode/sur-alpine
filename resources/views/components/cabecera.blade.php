@php
    $enlaces = [
        ['texto' => 'Vehículos', 'ruta' => 'catalogo'],
        ['texto' => 'Mantenimientos', 'ruta' => 'mantenimientos'],
        ['texto' => 'Visítanos en Restrepo', 'ruta' => 'contacto'],
        ['texto' => 'Sobre nosotros', 'ruta' => 'quienes-somos'],
    ];
@endphp

<header x-data="{ menu: false }" @keydown.escape.window="menu = false"
        class="sticky top-0 z-40 border-b border-tinta-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">

        <a href="{{ route('inicio') }}" class="shrink-0" aria-label="Sur Alpine, inicio">
            <img src="/img/logo/logo-en-png-sur-alpine.webp" alt="Importadora Sur Alpine"
                 width="280" height="351" fetchpriority="high" decoding="async"
                 class="h-12 w-auto sm:h-14">
        </a>

        {{-- Buscador y cotización a la derecha, siempre a la vista. --}}
        <div class="ml-auto flex items-center gap-3 lg:order-3">
            <form action="{{ route('catalogo') }}" method="get" role="search" class="hidden w-56 sm:block xl:w-72"
                  x-data="buscadorSugerencias('{{ route('sugerencias') }}')" @click.outside="cerrar()">
                <label for="buscar" class="sr-only">Buscar repuesto o referencia</label>
                <div class="relative">
                    <input id="buscar" type="search" name="q" value="{{ request('q') }}" autocomplete="off"
                           x-model="termino" @input="escribir()" @focus="escribir()" placeholder="Buscar repuesto…"
                           class="w-full rounded-lg border border-tinta-200 bg-tinta-50 py-2 pl-3 pr-10 text-sm outline-none transition placeholder:text-tinta-400 focus:border-marca-500 focus:bg-white">
                    <button type="submit" aria-label="Buscar"
                            class="absolute inset-y-0 right-0 grid w-10 place-items-center rounded-r-lg text-tinta-500 hover:text-marca-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4.5" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                        </svg>
                    </button>

                    <ul x-show="abierto" x-cloak
                        class="absolute inset-x-0 top-full z-50 mt-1 overflow-hidden rounded-lg border border-tinta-200 bg-white shadow-lg">
                        <template x-for="s in sugerencias" :key="s.u">
                            <li><a :href="s.u" x-text="s.t" class="block px-3 py-2 text-sm hover:bg-marca-50 hover:text-marca-700"></a></li>
                        </template>
                    </ul>
                </div>
            </form>

            {{-- El equipo va al panel; el cliente, a sus vehículos y su historial. --}}
            <a href="{{ auth()->check() ? (auth()->user()->entraAlPanel() ? route('panel.tablero') : route('cuenta')) : route('acceso') }}"
               class="grid size-9 place-items-center rounded-lg text-tinta-600 transition hover:bg-tinta-100 hover:text-tinta-900"
               aria-label="{{ auth()->check() ? auth()->user()->primer_nombre : 'Mi perfil' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/>
                </svg>
            </a>

            <a href="{{ route('cotizacion.ver') }}"
               class="flex items-center gap-2 rounded-lg bg-alerta-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-alerta-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4.5 shrink-0" aria-hidden="true">
                    <path d="M3 4h2l2.2 10.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L20 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="10" cy="20" r="1.3" fill="currentColor"/><circle cx="17" cy="20" r="1.3" fill="currentColor"/>
                </svg>
                <span class="hidden sm:inline">Mi cotización</span>
                {{-- El contador se actualiza solo cuando se agrega sin recargar. --}}
                <span class="tabular-nums" x-data="{ n: {{ $itemsCotizacion ?? 0 }} }"
                      @cotizacion-actualizada.window="n = $event.detail.total">(<span x-text="n">{{ $itemsCotizacion ?? 0 }}</span>)</span>
            </a>

            <button type="button" x-ref="hamburguesa" @click="menu = ! menu"
                    :aria-expanded="menu" aria-controls="menu-movil"
                    :aria-label="menu ? 'Cerrar menú' : 'Abrir menú'"
                    class="grid size-11 place-items-center rounded-lg text-tinta-700 hover:bg-tinta-100 lg:hidden">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-6" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="order-last hidden w-full items-center gap-1 lg:order-2 lg:flex lg:w-auto" aria-label="Principal">
            <a href="{{ route('catalogo') }}"
               @class([
                   'rounded-lg px-4 py-2 text-sm font-bold uppercase tracking-wide transition',
                   'bg-marca-600 text-white hover:bg-marca-700' => ! request()->routeIs('inicio'),
                   'bg-marca-600 text-white' => request()->routeIs('inicio'),
               ])>
                Catálogo de productos
            </a>

            @foreach ($enlaces as $enlace)
                <a href="{{ route($enlace['ruta']) }}"
                   @class([
                       'rounded-lg px-3 py-2 text-sm font-semibold uppercase tracking-wide transition',
                       'text-marca-700' => request()->routeIs($enlace['ruta']),
                       'text-tinta-600 hover:bg-tinta-100 hover:text-tinta-900' => ! request()->routeIs($enlace['ruta']),
                   ])>
                    {{ $enlace['texto'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Menú móvil --}}
    <div id="menu-movil" x-show="menu" x-cloak
         @keydown.escape="menu = false; $refs.hamburguesa.focus()"
         class="border-t border-tinta-200 bg-white lg:hidden">
        <nav class="mx-auto max-w-7xl px-4 py-3" aria-label="Menú móvil">
            <form action="{{ route('catalogo') }}" method="get" role="search" class="mb-3 sm:hidden">
                <label for="buscar-movil" class="sr-only">Buscar repuesto</label>
                <input id="buscar-movil" type="search" name="q" placeholder="Buscar repuesto…"
                       class="w-full rounded-lg border border-tinta-200 bg-tinta-50 px-3 py-2.5 text-sm">
            </form>

            <a href="{{ route('catalogo') }}" class="block rounded-lg bg-marca-600 px-4 py-2.5 text-sm font-bold uppercase text-white">
                Catálogo de productos
            </a>
            @foreach ($enlaces as $enlace)
                <a href="{{ route($enlace['ruta']) }}" class="block rounded-lg px-4 py-2.5 text-sm font-semibold text-tinta-700 hover:bg-tinta-100">
                    {{ $enlace['texto'] }}
                </a>
            @endforeach

            <p class="mt-3 border-t border-tinta-200 px-4 pt-3 text-sm tabular-nums text-tinta-500">
                <a href="tel:{{ $contacto->pbxTel() }}" class="font-semibold text-marca-700">PBX {{ $contacto->pbx() }}</a><br>
                @foreach ($contacto->celulares() as $celular)
                    <a href="tel:{{ $celular['tel'] }}" class="hover:underline">{{ $celular['texto'] }}</a>{{ ! $loop->last ? ' · ' : '' }}
                @endforeach
            </p>
        </nav>
    </div>

    @if ($vehiculoActivo ?? null)
        <div class="border-t border-tinta-200 bg-marca-50">
            <div class="mx-auto flex max-w-7xl items-center gap-x-3 px-4 py-1.5 text-sm">
                <p class="min-w-0 truncate text-marca-900">
                    <span class="hidden sm:inline">Viendo repuestos de </span><strong>{{ $vehiculoActivo->nombre_completo }}</strong>
                </p>
                <form method="post" action="{{ route('vehiculo.olvidar') }}" class="ml-auto shrink-0">
                    @csrf
                    <button type="submit" class="font-semibold text-marca-700 underline-offset-2 hover:underline">Ver todo</button>
                </form>
            </div>
        </div>
    @endif
</header>
