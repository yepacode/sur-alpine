@extends('layouts.app')

@section('titulo', 'Autopartes en Bogotá')
@section('descripcion', 'Importadora Sur Alpine: repuestos y autopartes para 12 marcas de vehículo. Busca por tu carro, arma tu solicitud y un asesor te contacta.')

@section('contenido')

    {{-- 1 · Hero. El banner del cliente NO va aquí: es un volante promocional,
         no una portada, y aplanaba toda la página. Va en su propia franja más
         abajo, donde sigue siendo suyo pero no secuestra la puerta de entrada. --}}
    <section class="relative overflow-hidden bg-tinta-900">

        {{-- Capas de profundidad. Las dos manchas de color respiran muy
             despacio: es lo que hace que el fondo no se lea como un rectángulo
             muerto, sin robarle un gramo de atención al buscador. --}}
        <div class="absolute inset-0 bg-gradient-to-br from-marca-900 via-tinta-900 to-noche" aria-hidden="true"></div>
        <div class="aurora absolute -left-40 top-1/2 size-[42rem] -translate-y-1/2 rounded-full bg-marca-500/30 blur-[100px]" aria-hidden="true"></div>
        <div class="aurora aurora-lenta absolute -right-24 bottom-0 size-[34rem] rounded-full bg-alerta-500/20 blur-[90px]" aria-hidden="true"></div>

        {{-- Rejilla técnica, como el papel milimetrado de un plano de taller.
             Al 4% no se ve: se siente. --}}
        <div class="absolute inset-0 opacity-[0.06] [background-image:linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] [background-size:56px_56px]" aria-hidden="true"></div>

        {{-- Un filo de luz en el borde inferior: separa el hero de lo que sigue
             sin necesidad de una línea dura. --}}
        <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-marca-400/50 to-transparent" aria-hidden="true"></div>

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

        <div class="relative mx-auto max-w-7xl px-4 pb-10 pt-8 sm:pb-20 sm:pt-20">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3.5 py-1.5 font-titulo text-[11px] font-bold uppercase tracking-[0.16em] text-white ring-1 ring-white/25 sm:px-4 sm:text-xs">
                    <span class="size-1.5 rounded-full bg-alerta-500"></span>
                    {{ contenido('inicio.hero.chip', (now()->year - 1982).' años · único sitio oficial') }}
                </p>

                {{-- El titular es la promesa del negocio, no el nombre de la
                     empresa: lo que el mecánico quiere saber es si aquí está su
                     pieza. La escala sube porque antes competía de tú a tú con
                     el párrafo de abajo. F · Editable desde el panel: si el
                     asesor lo cambia, el `<span>` de la segunda línea se
                     conserva sólo cuando el titular contiene un `\n` — la
                     forma tradicional de partirlo en dos —; si no, se pinta
                     entero. --}}
                <h1 class="mt-4 text-[2rem] font-extrabold leading-[0.98] text-white text-balance sm:mt-6 sm:text-[3.5rem] lg:text-[4rem]">
                    @php
                        $titular = contenido('inicio.hero.titulo', "La pieza exacta\nde tu carro");
                        $partes = preg_split('/\r?\n/', $titular, 2);
                    @endphp
                    {{ $partes[0] }}
                    @if (isset($partes[1]))
                        <br><span class="text-marca-300">{{ $partes[1] }}</span>
                    @endif
                </h1>

                {{-- Con carro elegido la cifra ya no es del catálogo entero, así
                     que decirlo "para 12 marcas" sería mentir por partida doble. --}}
                {{-- La cifra es el argumento de venta, no un dato al paso: sube
                     desde cero la primera vez que se ve. Si el JavaScript no
                     corre, el número ya está escrito en el HTML. --}}
                <p class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1 sm:mt-6">
                    <span class="cifra font-titulo text-4xl font-extrabold text-white sm:text-5xl"
                          data-contar="{{ $totalProductos }}">@numero($totalProductos)</span>
                    <span class="text-lg text-marca-100 sm:text-xl">
                        @if ($vehiculoActivo)
                            repuestos para tu {{ $vehiculoActivo->nombre_completo }}
                        @else
                            repuestos para {{ $marcas->count() }} marcas
                        @endif
                    </span>
                </p>

                <p class="mt-2 max-w-lg text-marca-200">
                    @if ($vehiculoActivo)
                        Todo lo que ves abajo le sirve.
                    @else
                        {{ contenido('inicio.hero.bajada', 'Dinos qué carro tienes y te mostramos sólo lo que le sirve.') }}
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

    {{-- 2 · Confianza. Lo que los diferencia y hoy no se ve por ningún lado.

         La tercera tarjeta lleva el video de envíos de fondo: son 5 segundos y
         382 KB —lo que pesa una foto—, así que puede correr solo sin castigar a
         nadie. Rompe la fila de tarjetas blancas a propósito: los envíos son lo
         que menos se cree de un negocio con un solo local. --}}
    <section class="mx-auto max-w-7xl px-4 pt-12">
        <ul class="grid gap-6 sm:grid-cols-3">
            @foreach ([
                [now()->year - 1982 .' años', 'importando autopartes', 'M12 2 4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6l-8-4Z', null],
                ['Un solo punto', $contacto->direccion().', Restrepo', 'M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z', null],
                ['Envíos', 'a ciudades y municipios del país', 'M3 7h11v8H3zM14 10h4l3 3v2h-7z', '/video/envios.mp4'],
            ] as $i => [$titulo, $texto, $trazo, $video])
                <li data-revelar data-retraso="{{ $i + 1 }}"
                    @class([
                        'con-luz relative flex items-center gap-4 overflow-hidden rounded-2xl px-6 py-5 shadow-sm ring-1 transition duration-300 hover:-translate-y-1 hover:shadow-lg',
                        'bg-white ring-black/5' => ! $video,
                        'bg-noche ring-white/10' => $video,
                    ])>

                    @if ($video)
                        {{-- Sin controles y sin sonido: es ambiente, no un video
                             que alguien vino a ver. Quien pidió menos movimiento
                             lo recibe quieto, en su primer fotograma. --}}
                        {{-- E2/E3 · Sin `#t=0.5`: Chrome descargaba el primer
                             tramo del archivo para pintar ese fotograma como
                             poster, y eran cientos de KB antes de arrancar.
                             Ahora `preload="none"` no baja nada, y `autoplay
                             muted` empieza la carga sólo cuando la tarjeta
                             está pintada. Quien pidió menos movimiento no lo
                             ve arrancar. --}}
                        <video class="absolute inset-0 size-full object-cover opacity-45"
                               autoplay muted loop playsinline preload="none"
                               aria-hidden="true" tabindex="-1"
                               x-data
                               x-init="if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { $el.removeAttribute('autoplay'); $el.pause() }">
                            <source src="{{ $video }}" type="video/mp4">
                        </video>
                        <span class="absolute inset-0 bg-gradient-to-r from-noche via-noche/70 to-noche/20" aria-hidden="true"></span>
                    @endif

                    <span @class([
                        'relative grid size-12 shrink-0 place-items-center rounded-xl',
                        'bg-alerta-500/10 text-alerta-500' => ! $video,
                        'bg-white/15 text-white ring-1 ring-white/20' => $video,
                    ])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-6" aria-hidden="true">
                            <path d="{{ $trazo }}" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <p class="relative leading-snug">
                        <strong @class([
                            'block font-titulo text-lg font-bold',
                            'text-tinta-900' => ! $video,
                            'text-white' => $video,
                        ])>{{ $titulo }}</strong>
                        <span @class([
                            'text-[15px]',
                            'text-tinta-500' => ! $video,
                            'text-marca-100' => $video,
                        ])>{{ $texto }}</span>
                    </p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 3 · Categorías: las diez que el cliente exhibe, no cuatro. Un mecánico
         escanea buscando su sistema; un clic de más le cuesta. --}}
    <section id="categorias" class="mx-auto max-w-7xl px-4 py-16" data-revelar>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">El catálogo</p>
                <h2 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Categorías de autopartes</h2>
                <p class="mt-1.5 text-[15px] text-tinta-500">
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
                    {{-- Sin foto no se esconde la categoría —eso escondía 2.234
                         piezas de Carrocería y Transmisión sin puerta de entrada—:
                         cae en un tratamiento tipográfico con la inicial grande y
                         un tinte de marca, coherente con el resto de la rejilla. --}}
                    <a href="{{ route('categoria', $categoria) }}"
                       class="con-luz group flex h-full flex-col rounded-2xl bg-white p-3 shadow-sm ring-1 ring-black/5 transition duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:ring-marca-200">
                        @if ($categoria->imagen)
                            <div class="grid flex-1 place-items-center overflow-hidden rounded-lg">
                                <img src="{{ $categoria->imagen }}" alt=""
                                     @if ($categoria->imagen_srcset)
                                         srcset="{{ $categoria->imagen_srcset }}"
                                         sizes="(min-width: 1024px) 240px, 45vw"
                                     @endif
                                     width="640" height="640" loading="lazy" decoding="async"
                                     class="w-full scale-105 object-contain transition duration-500 ease-out group-hover:scale-[1.18]">
                            </div>
                        @else
                            <div class="grid aspect-square flex-1 place-items-center overflow-hidden rounded-lg bg-gradient-to-br from-marca-50 to-marca-100">
                                <span aria-hidden="true"
                                      class="font-titulo text-[4rem] font-extrabold leading-none text-marca-800/25 transition duration-500 group-hover:scale-110 group-hover:text-marca-800/40">
                                    {{ mb_substr($categoria->nombre, 0, 1) }}
                                </span>
                            </div>
                        @endif
                        <div class="mt-3 flex items-baseline justify-between gap-2 px-1">
                            <h3 class="font-titulo text-[15px] font-bold leading-tight text-tinta-900 group-hover:text-marca-700">{{ $categoria->nombre }}</h3>
                            <span class="cifra shrink-0 text-xs font-semibold text-tinta-400">@numero($categoria->productos_count)</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 4 · Mantenimientos. En la maqueta esto era un tablero con datos falsos;
         aquí es lo que de verdad es: la invitación a registrarse. El tablero
         real vive dentro de la cuenta. --}}
    <section class="mx-auto max-w-7xl px-4 py-4" data-revelar>
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
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Lo que más sale</p>
                <h2 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">
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

    {{-- 6 · Marcas.

         En rejilla estática parecían una hoja de cálculo. En cinta se leen como
         lo que son: el respaldo de dieciséis fabricantes. Rueda sola y se
         detiene al pasar el cursor o al llegar con el teclado, para poder mirar
         un logo con calma. --}}
    @if ($proveedores)
        <section class="mx-auto max-w-7xl px-4 py-12" data-revelar>
            <div class="flex items-center gap-3">
                <h2 class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-tinta-500">
                    Trabajamos con
                </h2>
                <span class="h-px flex-1 bg-tinta-200" aria-hidden="true"></span>
            </div>

            <div class="cinta-marco mt-7 overflow-hidden">
                {{-- La lista va duplicada: es lo que hace que el giro no tenga
                     costura. La copia se esconde del lector de pantalla. --}}
                <ul class="cinta flex w-max items-center gap-12">
                    @foreach ($proveedores as $proveedor)
                        <li class="shrink-0">
                            <img src="{{ $proveedor['src'] }}" alt="{{ $proveedor['nombre'] }}"
                                 width="140" height="70" loading="lazy" decoding="async"
                                 class="h-11 w-auto object-contain opacity-55 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0">
                        </li>
                    @endforeach
                    @foreach ($proveedores as $proveedor)
                        <li class="shrink-0" aria-hidden="true">
                            <img src="{{ $proveedor['src'] }}" alt="" width="140" height="70"
                                 loading="lazy" decoding="async"
                                 class="h-11 w-auto object-contain opacity-55 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0">
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- 7 · Dónde estamos --}}
    <section class="mx-auto max-w-7xl px-4 py-14" data-revelar
             x-data="{ mapa: false, abrirMapa() { this.mapa = true; $nextTick(() => $refs.marco?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })) } }">
        <div class="grid items-stretch gap-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 lg:grid-cols-[1fr_1.2fr]">
            <div class="p-8 lg:p-10">
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Dónde estamos</p>
                <h2 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-3xl">Visítanos en Restrepo</h2>
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

                {{-- «Cómo llegar» abre el mapa aquí mismo en vez de mandar a
                     Google: sacar a alguien del sitio para enseñarle dónde
                     queda la tienda es perder la visita. El enlace externo
                     queda como salida secundaria, para quien va manejando. --}}
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <button type="button" @click="abrirMapa()"
                            class="con-luz inline-flex items-center gap-2 rounded-xl bg-marca-700 px-6 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-white shadow-lg shadow-marca-700/25 transition hover:bg-marca-800">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="size-4.5" aria-hidden="true">
                            <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                        </svg>
                    @php $comoLlegar = contenido('contacto.mapa.boton', 'Cómo llegar'); @endphp
                        <span x-text="mapa ? 'Mapa abierto' : @js($comoLlegar)">{{ $comoLlegar }}</span>
                    </button>

                    <a href="{{ $contacto->mapaUrl() }}" target="_blank" rel="noopener"
                       class="text-sm font-semibold text-marca-700 underline-offset-4 hover:underline">
                        {{ contenido('contacto.mapa.enlace', 'Abrir en Google Maps') }} ↗
                    </a>
                </div>
            </div>

            {{-- El local, en video. El mapa NO está: aparece al lado cuando
                 alguien pulsa «Cómo llegar».

                 Un recuadro gris esperando a que lo pulsen es un hueco en la
                 página, no una función. Así el espacio siempre muestra algo
                 real —el local— y el mapa llega cuando se pide.

                 El video pesa 9 MB. E3 · Antes usábamos `#t=1.2` para que
                 Chrome pintara el fotograma como poster, pero eso hacía que
                 el navegador se descargara todo el rango 0-1,2 s del archivo
                 antes de que nadie pulsara play —cientos de KB de red por
                 nada. Ahora `preload="none"` no descarga nada y el `poster`
                 es un SVG diminuto pintado en línea. Los MB del video sólo
                 se piden si alguien le da al play. --}}
            <div x-ref="marco" class="flex flex-col gap-px bg-noche sm:flex-row">

                <template x-if="mapa">
                    <div class="min-h-72 flex-1 sm:min-h-0">
                        <iframe title="Ubicación de Importadora Sur Alpine"
                                src="https://www.google.com/maps?q={{ urlencode($contacto->direccionCompleta()) }}&output=embed"
                                loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                                class="size-full border-0"></iframe>
                    </div>
                </template>

                {{-- Vertical porque así se grabó: recortarlo a apaisado sería
                     quedarse con la mitad del local. --}}
                <div x-data="{ andando: false }"
                     class="relative flex flex-1 items-center justify-center overflow-hidden p-5 sm:flex-none"
                     :class="mapa ? 'sm:w-[240px] lg:w-[260px]' : 'sm:w-full'">

                    {{-- Un resplandor detrás del video, para que el panel negro
                         no se lea como un agujero. --}}
                    <span class="aurora pointer-events-none absolute size-72 rounded-full bg-marca-500/25 blur-[70px]" aria-hidden="true"></span>

                    @php
                        // Poster SVG en línea: gradiente de marca + rótulo. Pesa
                        // menos de un kB y evita cualquier descarga de red hasta
                        // que la persona pulse el play.
                        $poster = 'data:image/svg+xml;utf8,'.rawurlencode(
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 640">'
                            .'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
                            .'<stop offset="0" stop-color="#0a2f6b"/><stop offset="1" stop-color="#080d1a"/>'
                            .'</linearGradient></defs>'
                            .'<rect width="360" height="640" fill="url(#g)"/>'
                            .'<text x="180" y="315" fill="#82adf4" font-family="Archivo, sans-serif" font-size="18" font-weight="700" letter-spacing="3" text-anchor="middle">EL LOCAL</text>'
                            .'<text x="180" y="345" fill="#eef4fe" font-family="Archivo, sans-serif" font-size="22" font-weight="800" text-anchor="middle">Barrio Restrepo</text>'
                            .'</svg>'
                        );
                    @endphp
                    <div class="relative aspect-[9/16] w-full max-w-[240px] overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/10">
                        <video x-ref="video" class="size-full object-cover"
                               preload="none" muted loop playsinline
                               poster="{{ $poster }}"
                               @play="andando = true" @pause="andando = false"
                               aria-label="Recorrido por el local de Sur Alpine en el Barrio Restrepo">
                            <source src="/video/local-restrepo.mp4" type="video/mp4">
                        </video>

                        <button type="button" x-show="!andando" @click="$refs.video.play()"
                                class="group absolute inset-0 grid place-items-center bg-noche/30 transition hover:bg-noche/10">
                            <span class="grid size-16 place-items-center rounded-full bg-white/95 text-alerta-600 shadow-xl transition group-hover:scale-110">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="size-7 translate-x-0.5" aria-hidden="true">
                                    <path d="M7 4.5v15l12-7.5z"/>
                                </svg>
                            </span>
                            <span class="sr-only">Reproducir el recorrido por el local</span>
                        </button>

                        <button type="button" x-show="andando" x-cloak @click="$refs.video.pause()"
                                class="absolute bottom-3 right-3 grid size-9 place-items-center rounded-full bg-noche/60 text-white backdrop-blur transition hover:bg-noche/80">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5" aria-hidden="true">
                                <rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>
                            </svg>
                            <span class="sr-only">Pausar el video</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
