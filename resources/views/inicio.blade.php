@extends('layouts.app')

@section('titulo', 'Autopartes en Bogotá')
@section('descripcion', 'Importadora Sur Alpine: repuestos y autopartes para 12 marcas de vehículo. Busca por tu carro, arma tu solicitud y un asesor te contacta.')

@section('contenido')

    {{-- 1 · Hero. El banner del cliente NO va aquí: es un volante promocional,
         no una portada, y aplanaba toda la página. Va en su propia franja más
         abajo, donde sigue siendo suyo pero no secuestra la puerta de entrada. --}}
    <section class="relative overflow-hidden bg-tinta-900">

        {{-- Capas de profundidad: azul de marca, resplandor y filigrana. --}}
        <div class="absolute inset-0 bg-gradient-to-br from-marca-900 via-tinta-900 to-black" aria-hidden="true"></div>
        <div class="absolute -left-40 top-1/2 size-[38rem] -translate-y-1/2 rounded-full bg-marca-600/25 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -right-20 bottom-0 size-[30rem] rounded-full bg-alerta-500/10 blur-3xl" aria-hidden="true"></div>

        {{-- El asesor: es su propia foto, y da la escala humana que faltaba.

             Sólo se ve de lg en adelante, pero un `<img>` oculto con CSS se
             descarga igual. Con el `<source>` vacío por debajo de 1024 px el
             navegador no pide nada en el celular, que es donde menos sobra.

             La foto original mide 418 px: los archivos «-520» y «-900» son el
             mismo archivo. Cuando el cliente mande una en alta, aquí sólo hay
             que añadir el ancho al `srcset`. --}}
        <picture>
            <source media="(min-width: 1024px)" srcset="/img/promo/senor-900.webp">
            <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
                 alt="" width="418" height="425" decoding="async"
                 class="pointer-events-none absolute -bottom-4 right-0 hidden h-[86%] w-auto object-contain opacity-90 drop-shadow-2xl lg:block xl:right-12"
                 aria-hidden="true">
        </picture>

        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-8 sm:pb-14 sm:pt-20">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] sm:px-4 sm:py-1.5 sm:text-xs font-semibold uppercase tracking-[0.18em] text-marca-100 ring-1 ring-white/15">
                    <span class="size-1.5 rounded-full bg-alerta-500"></span>
                    {{ now()->year - 1982 }} años · único sitio oficial
                </p>

                <h1 class="mt-4 text-[1.75rem] font-extrabold sm:mt-6 leading-[1.08] tracking-tight text-white text-balance sm:text-5xl">
                    Tu socio de confianza en
                    <span class="text-marca-300">autopartes</span> en Bogotá
                </h1>

                {{-- Con carro elegido la cifra ya no es del catálogo entero, así
                     que decirlo "para 12 marcas" sería mentir por partida doble. --}}
                <p class="mt-3 max-w-xl text-marca-100 sm:mt-5 sm:text-lg">
                    @if ($vehiculoActivo)
                        <span class="font-semibold text-white tabular-nums">@numero($totalProductos)</span>
                        repuestos para tu <span class="font-semibold text-white">{{ $vehiculoActivo->nombre_completo }}</span>.
                        Todo lo que ves abajo le sirve.
                    @else
                        <span class="font-semibold text-white tabular-nums">@numero($totalProductos)</span>
                        repuestos para {{ $marcas->count() }} marcas de vehículo.
                        Dinos qué carro tienes y te mostramos sólo lo que le sirve.
                    @endif
                </p>
            </div>

            {{-- El tablero, encima de todo. --}}
            <div class="relative z-10 mt-6 max-w-5xl sm:mt-10">
                <x-buscador-vehiculo />
            </div>
        </div>
    </section>

    {{-- 1b · Las campañas del cliente, en su propia franja. --}}
    @if ($banners)
        <div class="mx-auto max-w-7xl px-4 pt-10">
            <x-banner-carrusel :banners="$banners" />
        </div>
    @endif

    {{-- 2 · Confianza. Lo que los diferencia y hoy no se ve por ningún lado. --}}
    <section class="mx-auto max-w-7xl px-4 pt-12">
        <ul class="grid gap-6 sm:grid-cols-3">
            @foreach ([
                [now()->year - 1982 .' años', 'importando autopartes', 'M12 2 4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6l-8-4Z'],
                ['Un solo punto', $contacto->direccion().', Restrepo', 'M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z'],
                ['Envíos', 'a ciudades y municipios del país', 'M3 7h11v8H3zM14 10h4l3 3v2h-7z'],
            ] as [$titulo, $texto, $trazo])
                <li class="flex items-center gap-4 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-black/5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-alerta-500/10 text-alerta-500">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-6" aria-hidden="true">
                            <path d="{{ $trazo }}" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <p class="text-sm leading-snug">
                        <strong class="block text-base text-tinta-900">{{ $titulo }}</strong>
                        <span class="text-tinta-500">{{ $texto }}</span>
                    </p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 3 · Categorías: las diez que el cliente exhibe, no cuatro. Un mecánico
         escanea buscando su sistema; un clic de más le cuesta. --}}
    <section id="categorias" class="mx-auto max-w-7xl px-4 py-14">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Categorías de autopartes</h2>
                <p class="mt-1 text-tinta-500">
                    <span class="tabular-nums">@numero($totalProductos)</span> repuestos
                    @if ($vehiculoActivo)
                        para tu {{ $vehiculoActivo->nombre_completo }}.
                    @else
                        para {{ $marcas->count() }} marcas de vehículo.
                    @endif
                </p>
            </div>
            <a href="{{ route('catalogo') }}" class="text-sm font-semibold text-marca-700 underline-offset-4 hover:underline">
                Ver todo el catálogo →
            </a>
        </div>

        <ul class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ($categorias as $categoria)
                <li>
                    <a href="{{ route('categoria', $categoria) }}"
                       class="group flex h-full flex-col rounded-xl bg-white p-3 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="grid flex-1 place-items-center overflow-hidden rounded-lg">
                            <img src="{{ $categoria->imagen }}" alt=""
                                 @if ($categoria->imagen_srcset)
                                     srcset="{{ $categoria->imagen_srcset }}"
                                     sizes="(min-width: 1024px) 240px, 45vw"
                                 @endif
                                 width="640" height="640" loading="lazy" decoding="async"
                                 class="w-full scale-105 object-contain transition duration-300 group-hover:scale-110">
                        </div>
                        <div class="mt-3 flex items-baseline justify-between gap-2 px-1">
                            <h3 class="font-semibold text-tinta-900 group-hover:text-marca-700">{{ $categoria->nombre }}</h3>
                            <span class="shrink-0 text-xs tabular-nums text-tinta-400">@numero($categoria->productos_count)</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 4 · Mantenimientos. En la maqueta esto era un tablero con datos falsos;
         aquí es lo que de verdad es: la invitación a registrarse. El tablero
         real vive dentro de la cuenta. --}}
    <section class="mx-auto max-w-7xl px-4 py-4">
        <div class="grid items-center gap-8 overflow-hidden rounded-2xl bg-marca-800 p-8 text-white lg:grid-cols-2 lg:p-12">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-marca-300">Nuestros servicios</p>
                <h2 class="mt-3 text-2xl font-bold leading-tight sm:text-3xl">
                    Lleva el historial de mantenimiento de tu carro
                </h2>
                <p class="mt-4 max-w-md text-marca-100">
                    Registra kilometraje, fechas y qué le hiciste. Te avisamos cuándo toca
                    el próximo cambio, para cada placa que manejes.
                </p>
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    @if (config('portada.modulo_clientes'))
                        <a href="{{ auth()->check() ? route('cuenta') : route('registro') }}"
                           class="rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                            {{ auth()->check() ? 'Ver mi historial' : 'Registrar ahora' }}
                        </a>
                    @else
                        {{-- Sin módulo de clientes, «Registrar ahora» lleva a un
                             acceso donde no se puede crear cuenta. Mejor decir la
                             verdad que dejar el botón muerto. --}}
                        <span class="rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-white ring-1 ring-white/20">
                            Muy pronto
                        </span>
                    @endif

                    <a href="{{ route('mantenimientos') }}"
                       class="rounded-lg border border-marca-500 px-6 py-3 font-semibold text-white transition hover:bg-marca-700">
                        Cómo funciona
                    </a>
                </div>
            </div>

            {{-- Ilustración, no datos: nadie tiene un tablero antes de registrarse. --}}
            <div class="rounded-xl bg-white/10 p-6 ring-1 ring-white/15">
                <p class="text-sm font-semibold text-marca-100">Así se ve tu historial</p>
                <ul class="mt-4 space-y-3">
                    @foreach ([
                        ['Cambio de aceite', 'ABC 123 · 48.000 km', 'Al día', true],
                        ['Pastillas de freno', 'ABC 123 · 45.200 km', 'En 2 meses', true],
                        ['Kit de distribución', 'ABC 123 · 40.000 km', 'Vencido', false],
                    ] as [$servicio, $detalle, $estado, $bien])
                        <li class="flex items-center gap-3 rounded-lg bg-white/10 px-4 py-3">
                            <span @class(['size-2.5 shrink-0 rounded-full', 'bg-emerald-400' => $bien, 'bg-alerta-500' => ! $bien])></span>
                            <span class="flex-1 text-sm">
                                <strong class="block font-semibold">{{ $servicio }}</strong>
                                <span class="tabular-nums text-marca-200">{{ $detalle }}</span>
                            </span>
                            <span class="shrink-0 text-xs font-semibold text-marca-100">{{ $estado }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-marca-300">Ejemplo ilustrativo.</p>
            </div>
        </div>
    </section>

    {{-- 5 · Productos destacados. Son piezas reales, así que el botón sí puede
         decir "Cotizar": una categoría no se cotiza. --}}
    @if ($destacados->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14"
                 x-data="{ desplazar(dir) { const p = this.$refs.pista; p.scrollBy({ left: dir * p.clientWidth * 0.8, behavior: 'smooth' }) } }">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Productos destacados
                    @if ($vehiculoActivo ?? null)
                        <span class="block text-base font-normal text-tinta-500">para tu {{ $vehiculoActivo->nombre_completo }}</span>
                    @endif
                </h2>
                <div class="flex gap-2">
                    <button type="button" @click="desplazar(-1)" aria-label="Productos destacados anteriores"
                            class="grid size-9 place-items-center rounded-lg border border-tinta-200 bg-white text-tinta-600 transition hover:border-marca-400 hover:text-marca-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
                            <path d="M15 18 9 12l6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button type="button" @click="desplazar(1)" aria-label="Productos destacados siguientes"
                            class="grid size-9 place-items-center rounded-lg border border-tinta-200 bg-white text-tinta-600 transition hover:border-marca-400 hover:text-marca-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <ul x-ref="pista"
                class="mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($destacados as $producto)
                    <li class="w-60 shrink-0 snap-start">
                        <div class="flex h-full flex-col rounded-xl bg-white p-4 shadow-sm ring-1 ring-black/5 transition hover:shadow-lg">
                            {{-- Mientras ningún producto tenga foto propia, la tarjeta
                                 no lleva imagen: todas caerían en la de su categoría y
                                 el carrusel se vería como diez repeticiones de seis
                                 fotos. Se sostiene con tipografía y el nombre del
                                 sistema, que es lo que el mecánico lee de verdad. --}}
                            <a href="{{ route('producto', $producto) }}" class="group block">
                                @if ($producto->imagen)
                                    <div class="grid aspect-square place-items-center overflow-hidden rounded-lg bg-tinta-50">
                                        <img src="{{ $producto->imagen }}" alt=""
                                             width="240" height="240" loading="lazy" decoding="async"
                                             class="size-full object-contain transition duration-300 group-hover:scale-105">
                                    </div>
                                @endif

                                <p class="text-xs font-semibold uppercase tracking-wide text-marca-600">
                                    {{ $producto->tipoParte->categoria->nombre }}
                                </p>
                                <h3 class="mt-1 line-clamp-3 text-base font-semibold leading-snug group-hover:text-marca-700">
                                    {{-- Con carro elegido, el sufijo «OPTRA 1800 CHEVROLET»
                                         se repite en las diez tarjetas y la cabecera de la
                                         sección ya lo dijo: sobra en cada línea. --}}
                                    {{ $vehiculoActivo ? $producto->tipoParte->nombre : $producto->nombre }}
                                </h3>
                                @unless ($vehiculoActivo)
                                    <p class="mt-1 text-xs text-tinta-500">{{ $producto->vehiculo->nombre_completo }}</p>
                                @endunless
                            </a>

                            {{-- El sitio nunca habla de dinero: eso lo trata el asesor. --}}
                            <form method="post" action="{{ route('cotizacion.agregar', $producto) }}" class="mt-auto pt-4"
                                  x-data="agregarACotizacion" @submit.prevent="enviar($event)">
                                @csrf
                                <button type="submit" :disabled="enviando"
                                        class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition"
                                        :class="listo ? 'bg-marca-700' : 'bg-alerta-500 hover:bg-alerta-600'">
                                    <span x-show="!listo">Añadir a cotización</span>
                                    <span x-show="listo" x-cloak>Agregado ✓</span>
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 6 · Marcas --}}
    @if ($proveedores)
        <section class="mx-auto max-w-7xl px-4 py-10">
            <h2 class="text-sm font-bold uppercase tracking-wider text-tinta-500">Nuestras marcas</h2>
            <ul class="mt-6 grid grid-cols-3 items-center gap-6 sm:grid-cols-4 lg:grid-cols-8">
                @foreach ($proveedores as $proveedor)
                    <li>
                        <img src="{{ $proveedor['src'] }}" alt="{{ $proveedor['nombre'] }}"
                             width="140" height="70" loading="lazy" decoding="async"
                             class="mx-auto h-12 w-auto object-contain opacity-60 transition hover:opacity-100">
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 7 · Dónde estamos --}}
    <section class="mx-auto max-w-7xl px-4 py-14">
        <div class="grid items-stretch gap-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 lg:grid-cols-[1fr_1.2fr]">
            <div class="p-8 lg:p-10">
                <h2 class="text-2xl font-bold tracking-tight">Visítanos en Restrepo</h2>
                <p class="mt-4 text-tinta-600">
                    Un solo punto de atención en Bogotá, con asesores que te ayudan a encontrar
                    la pieza exacta que necesita tu vehículo.
                </p>

                <p class="mt-6 flex items-start gap-2 font-semibold">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         class="mt-0.5 size-5 shrink-0 text-alerta-500" aria-hidden="true">
                        <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                    </svg>
                    {{ $contacto->direccion() }}, Barrio Restrepo<br>{{ $contacto->ciudad() }}, Colombia
                </p>

                <ul class="mt-4 space-y-1 tabular-nums text-tinta-600">
                    <li><a href="tel:{{ $contacto->pbxTel() }}" class="hover:underline">PBX {{ $contacto->pbx() }}</a></li>
                    @foreach ($contacto->celulares() as $celular)
                        <li><a href="tel:{{ $celular['tel'] }}" class="hover:underline">{{ $celular['texto'] }}</a></li>
                    @endforeach
                </ul>

                <a href="{{ $contacto->mapaUrl() }}" target="_blank" rel="noopener"
                   class="mt-7 inline-block rounded-lg bg-marca-600 px-6 py-3 font-semibold text-white transition hover:bg-marca-700">
                    Cómo llegar
                </a>
            </div>

            {{-- El mapa carga sólo si alguien lo pide: el iframe de Google es
                 de lo más pesado de una página, y casi nadie lo usa. --}}
            <div x-data="{ cargado: false }" class="relative min-h-64 bg-tinta-100">
                <template x-if="cargado">
                    <iframe title="Ubicación de Importadora Sur Alpine"
                            src="https://www.google.com/maps?q={{ urlencode($contacto->direccionCompleta()) }}&output=embed"
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            class="size-full border-0"></iframe>
                </template>
                <button type="button" x-show="!cargado" @click="cargado = true"
                        class="absolute inset-0 grid place-items-center text-marca-700 transition hover:bg-tinta-200/50">
                    <span class="rounded-lg bg-white px-5 py-3 font-semibold shadow-sm">Ver el mapa</span>
                </button>
            </div>
        </div>
    </section>
@endsection
