@extends('layouts.app')

@section('titulo', 'Autopartes en Bogotá')
@section('descripcion', 'Importadora Sur Alpine: repuestos y autopartes para 12 marcas de vehículo. Busca por tu carro, arma tu solicitud y un asesor te contacta.')

@section('contenido')

    {{-- El h1 de la portada.

         Faltaba: la página arrancaba en un h2 y no había ni un encabezado que
         dijera de quién es este sitio. Es la señal más directa que tiene
         Google para decidir que esta página trata de ESTA empresa, y con
         copias suplantándolos es justo la que hay que dar.

         `sr-only` porque el cliente pidió que la portada se vea idéntica a la
         suya: el titular está para quien lee el código y para quien usa lector
         de pantalla, no para cambiar el diseño. --}}
    <h1 class="sr-only">
        {{ contenido('inicio.h1', 'Importadora Sur Alpine · Repuestos y autopartes en Bogotá') }}
    </h1>

    {{-- 1 · Las campañas del cliente, arriba del todo y a todo el ancho, como
         en su sitio. El hero oscuro que había aquí se retiró a pedido suyo: sus
         clientes reconocen la página actual y un cambio fuerte les hace dudar de
         si están en la oficial. --}}
    {{-- A todo el ancho de la ventana, no dentro del contenedor.
         El cliente lo comparó con su web actual y tenía razón: allá el banner
         mide 630 px de alto y aquí medía 442. La causa no es el arte —sus
         archivos son 2560x853, la misma proporción que los nuestros— sino que
         el contenedor lo encajonaba en 1.354 px. A todo ancho, una pantalla de
         1920 lo pinta a 625 px de alto, que es lo que él está acostumbrado a
         ver, y sin recortar ni deformar la imagen que el proveedor paga por
         publicar. --}}
    {{-- A todo el ancho, pero sin pasar de lo que mide la imagen.
         El cliente lo comparó con su web actual y tenía razón: allá el banner
         mide 630 px de alto y aquí medía 442, porque el contenedor lo
         encajonaba en 1.354 px. A todo ancho gana esa altura.

         El tope de 1600 px es el tamaño real del archivo más grande que hay.
         Sin él, en un monitor de 1920 —o en cualquier portátil retina— el
         navegador estiraba esa imagen un 19 % y el banner salía blando: justo
         lo primero que se ve, y lo que el proveedor paga por publicar. El día
         que suban arte en más resolución, se añade el escalón a
         `ImagenesWeb::ANCHOS_BANNER` y este tope sube con él. --}}
    @if ($banners)
        <div class="mx-auto max-w-[1600px] pt-5">
            <x-banner-carrusel :banners="$banners" />
        </div>
    @endif

    {{-- 2 · El buscador por vehículo.

         Ya no monta sobre el banner: es su propia sección, con aire arriba y
         abajo. Al superponerlo tapaba el arte que el proveedor paga por
         publicar, y encaramado se leía como un cartel pegado encima en vez de
         como el primer paso de la página. --}}
    <div id="buscador" class="contenedor relative z-10 scroll-mt-6 pb-4 pt-10 sm:pt-12">
        <x-buscador-vehiculo />
    </div>

    {{-- 3 · Categorías Autopartes.

         Título y rejilla copiados del código del sitio actual, no aproximados:
         título en negrita con la línea roja de 4 px pegada debajo; rejilla de
         cinco columnas con 40 px de aire; tarjeta blanca de radio 16 y sombra
         suave que sube 5 px al pasar el cursor; foto de 220 px de alto
         («Dirección» va a 250, así la tienen ellos) y el nombre debajo, en
         azul y seminegrita. Sin contador de piezas, como pidió el cliente.

         El azul y el rojo salen del manual de marca (#1866E0 / #E02929) y no
         del CSS del sitio (#007BFF / #E12F2E): son los que el jefe pidió usar,
         y la diferencia entre unos y otros no se distingue a simple vista. --}}
    <section id="categorias" class="px-[3vw] py-[60px]" data-revelar>
        <x-titulo-seccion :texto="contenido('inicio.categorias.titulo', 'Categorías Autopartes')" />

        <ul class="contenedor mt-10 grid grid-cols-2 gap-5 sm:gap-[25px] md:grid-cols-3 md:gap-[30px] lg:grid-cols-4 lg:gap-[35px] xl:grid-cols-5 xl:gap-10">
            @foreach ($categorias as $categoria)
                @php
                    // «Dirección» lleva la foto 30 px más alta que las demás. Es
                    // un capricho de su rejilla, pero es el que reconocen.
                    $masAlta = str_starts_with($categoria->slug, 'direccion');
                @endphp
                <li>
                    <a href="{{ route('categoria', $categoria) }}"
                       class="group flex h-full flex-col rounded-2xl bg-white p-1.5 shadow-[0_6px_20px_rgba(0,0,0,0.08)] transition duration-300 hover:-translate-y-[5px] hover:shadow-[0_10px_30px_rgba(0,0,0,0.15)]">
                        <div @class([
                            'flex items-center justify-center overflow-hidden rounded-xl bg-white',
                            'h-[170px] sm:h-[190px] md:h-[210px] lg:h-[230px] xl:h-[250px]' => $masAlta,
                            'h-[140px] sm:h-[160px] md:h-[180px] lg:h-[200px] xl:h-[220px]' => ! $masAlta,
                        ])>
                            @if ($categoria->imagen)
                                <img src="{{ $categoria->imagen }}" alt="{{ $categoria->nombre }}"
                                     @if ($categoria->imagen_srcset)
                                         srcset="{{ $categoria->imagen_srcset }}"
                                         sizes="(min-width: 1280px) 250px, (min-width: 768px) 30vw, 45vw"
                                     @endif
                                     width="640" height="640" loading="lazy" decoding="async"
                                     class="size-full object-contain object-center">
                            @else
                                {{-- Sin foto no se esconde la categoría —eso dejaba a
                                     Carrocería y Transmisión sin puerta de entrada—:
                                     cae en la inicial grande, en tinte de marca. --}}
                                <span aria-hidden="true" class="font-titulo text-[4rem] font-extrabold leading-none text-marca-800/20">
                                    {{ mb_substr($categoria->nombre, 0, 1) }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1.5 px-1.5 text-center text-sm font-semibold text-marca-600 sm:text-sm md:text-base">
                            {{ $categoria->nombre }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 4 · Los tres respaldos, tal como los tiene su sitio: ícono rojo a la
         izquierda, título rojo y texto gris. Sustituye a la franja de tarjetas
         que habíamos puesto aquí, que el cliente pidió eliminar. --}}
    <section class="contenedor py-[60px]" data-revelar>
        <div class="grid gap-5 sm:gap-[25px] md:grid-cols-3 md:gap-[30px] lg:gap-[35px] xl:gap-10">
            @foreach ([
                ['Asesoría Especializada', 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0-6 1.9 2.4 3-.6.6 3L20.8 9 19.4 12l1.4 3-3.3 2.2-.6 3-3-.6L12 22l-1.9-2.4-3 .6-.6-3L3.2 15l1.4-3-1.4-3 3.3-2.2.6-3 3 .6L12 2Z'],
                ['Variedad de Marcas', 'M2.5 3.6A1.6 1.6 0 0 1 4.1 2h6.2c.4 0 .8.2 1.1.5l8.6 8.6a1.6 1.6 0 0 1 0 2.3l-6.2 6.2a1.6 1.6 0 0 1-2.3 0l-8.6-8.6a1.6 1.6 0 0 1-.4-1.1V3.6Zm4.4 4.1a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z'],
                ['Respaldo y Garantía', 'M12 1.5 3.8 4.9A1.5 1.5 0 0 0 2.9 6.3v5.3c0 5.8 3.9 11.2 9.1 12.4 5.2-1.2 9.1-6.6 9.1-12.4V6.3a1.5 1.5 0 0 0-.9-1.4L12 1.5Zm-1 13.7-3.2-3.2 1.4-1.4 1.8 1.8 4.4-4.4 1.4 1.4-5.8 5.8Z'],
            ] as $i => [$titulo, $trazo])
                <article class="flex items-start gap-[15px]" data-revelar data-retraso="{{ $i + 1 }}">
                    <span class="shrink-0 text-alerta-500" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="mt-0.5 size-8 sm:size-9 md:size-[38px] lg:size-10 xl:size-[45px]">
                            <path d="{{ $trazo }}"/>
                        </svg>
                    </span>
                    <div>
                        {{-- `alerta-600` y no `500`: estos tres van sobre el gris
                             del `body` (#f1f1f1), no sobre blanco, y ahí el rojo
                             de marca da 4,11:1 medido —por debajo del 4,5 que
                             exige un texto de 16 px en negrita—. El 600 da 5,22:1
                             y a ojo es el mismo rojo. --}}
                        <h3 class="text-sm font-bold leading-[1.2] text-alerta-600 sm:text-base md:text-base lg:text-lg xl:text-lg">
                            {{ contenido('respaldo.'.($i + 1).'.titulo', $titulo) }}
                        </h3>
                        {{-- Un texto por bloque. En su sitio los tres repiten el
                             mismo párrafo; se copió tal cual, pero ahora cada uno
                             tiene su clave y se corrige desde el panel. --}}
                        <p class="mt-2 text-xs leading-[1.5] text-tinta-600 sm:text-sm md:text-sm">
                            {{ contenido('respaldo.'.($i + 1).'.texto', 'Nuestro equipo cuenta con amplia experiencia en el sector automotriz para brindarte la mejor orientación.') }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- 5 · Nuestros Servicios.

         Dos tarjetas al 45 % con 40 px de aire, como en su sitio: la roja del
         historial de mantenimientos y, al lado, el video de envíos a ciudades y
         municipios. Esa segunda tarjeta es la «mensajería / domicilios» que el
         cliente pidió que no faltara: ya estaba en su página, y aquí faltaba.

         Antes había en su lugar un tablero de ejemplo con datos inventados.
         Fuera: nadie tiene historial antes de registrarse, y un tablero falso
         en la portada se lee como si lo tuviera. --}}
    <section class="px-[3vw] py-[60px]" data-revelar>
        <x-titulo-seccion :texto="contenido('inicio.servicios.titulo', 'Nuestros Servicios')" />

        <div class="contenedor mt-10 flex flex-wrap items-stretch justify-center gap-5 sm:gap-[25px] md:gap-[30px] lg:gap-[35px] xl:gap-10">

            {{-- Historial de mantenimientos --}}
            <div class="flex min-w-[300px] flex-1 basis-[45%]">
                <div class="flex w-full min-h-[211px] flex-col overflow-hidden rounded-[10px] bg-alerta-500 text-white sm:flex-row">
                    {{-- La foto quedaba INVISIBLE.

                         Cuatro clases de Tailwind se colaron dentro del `style`
                         del div: `bg-contain`, `bg-bottom`, `bg-no-repeat` y
                         `sm:basis-[40%]`. Un atributo `style` es CSS puro, no
                         Tailwind, asi que la propiedad `background-image` que
                         alli se construia era invalida y el navegador la
                         tiraba. La foto existia (200) y no se veia. --}}
                    <div class="min-h-[180px] shrink-0 bg-contain bg-bottom bg-no-repeat sm:min-h-[211px] sm:basis-[40%]"
                         style="background-image: url('{{ imagen_contenido('servicios.historial.imagen', '/img/promo/senor') }}-520.webp')"></div>

                    <div class="flex flex-1 flex-col justify-center p-6 sm:p-8">
                        <h3 class="text-2xl font-extrabold uppercase leading-[1.2] sm:text-[2rem]">
                            {{ contenido('servicios.historial.titulo', 'Historial de mantenimientos') }}
                        </h3>
                        <p class="mt-4 text-base">
                            {{ contenido('servicios.historial.texto', 'Regístrate en nuestra página web y lleva el seguimiento de todos los servicios y mantenimientos del vehículo.') }}
                        </p>

                        @if (config('portada.modulo_clientes'))
                            <a href="{{ auth()->check() ? route('cuenta') : route('registro') }}"
                               class="mt-6 w-fit rounded-[5px] bg-white px-6 py-3 font-bold text-alerta-500 transition hover:scale-105">
                                {{ auth()->check()
                                    ? contenido('servicios.historial.boton_dentro', 'Ver mi historial')
                                    : contenido('servicios.historial.boton', 'Registrar ahora') }}
                            </a>
                        @else
                            {{-- Sin módulo de clientes, «Registrar ahora» llevaría a un
                                 acceso donde no se puede crear cuenta. --}}
                            <a href="{{ route('mantenimientos') }}"
                               class="mt-6 w-fit rounded-[5px] bg-white px-6 py-3 font-bold text-alerta-500 transition hover:scale-105">
                                {{ contenido('servicios.historial.boton_como', 'Cómo funciona') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Envios a ciudades y municipios: video o foto, lo que suba el dueno.

                 El campo del panel acepta las dos cosas. Si sube una foto, se pinta;
                 si sube un video, se reproduce en bucle sin sonido; si no sube nada,
                 vuelve al `envios.mp4` de siempre. --}}
            <div class="flex min-w-[300px] flex-1 basis-[45%]">
                @php $envios = imagen_contenido('servicios.envios.medio', '/video/envios.mp4'); @endphp
                @if (preg_match('/\.(mp4|webm|mov)$/i', $envios))
                    {{-- `muted` no es decorativo: sin el, el navegador bloquea el
                         autoplay y la tarjeta queda en negro. Y sin `controls`,
                         porque no hay nada que escuchar ni que pausar. --}}
                    <video src="{{ $envios }}" autoplay muted loop playsinline preload="metadata"
                           aria-label="{{ contenido('servicios.envios.alt', 'Envíos a ciudades y municipios del país') }}"
                           class="min-h-[211px] w-full rounded-[10px] bg-white object-cover"></video>
                @else
                    <img src="{{ $envios }}"
                         alt="{{ contenido('servicios.envios.alt', 'Envíos a ciudades y municipios del país') }}"
                         class="min-h-[211px] w-full rounded-[10px] bg-white object-cover">
                @endif
            </div>
        </div>
    </section>

    {{-- 6 · Productos Destacados.

         El carrusel del sitio actual, copiado de su hoja de estilos: título
         centrado con la línea roja, flechas redondas azules a los lados de la
         pista, y tarjetas de 220 × 320 con el nombre y la referencia arriba
         sobre blanco y la foto abajo sobre azul, girada 10 grados.

         Lo único que se agrega es el botón de cotizar: en su sitio la tarjeta
         sólo enlaza, y este es un catálogo cuyo objetivo entero es que la pieza
         entre en una solicitud. Son piezas reales, así que el botón sí puede
         decir «cotizar» —una categoría no se cotiza. --}}
    @if ($destacados->isNotEmpty())
        <section class="px-[3vw] py-10"
                 x-data="{ desplazar(dir) { const p = this.$refs.pista; p.scrollBy({ left: dir * 200, behavior: 'smooth' }) } }">
            <x-titulo-seccion :texto="contenido('inicio.destacados.titulo', 'Productos Destacados')" />

            @if ($vehiculoActivo ?? null)
                <p class="mt-3 text-center text-tinta-500">para tu {{ $vehiculoActivo->nombre_completo }}</p>
            @endif

            <div class="contenedor mt-8 flex items-center justify-center gap-4 px-5">
                <button type="button" @click="desplazar(-1)" aria-label="Productos anteriores"
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-marca-700 text-lg text-white transition hover:scale-110 hover:bg-marca-800">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
                        <path d="M15 18 9 12l6-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <ul x-ref="pista"
                    class="flex flex-1 snap-x snap-mandatory gap-[15px] overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($destacados as $producto)
                        <li class="w-[120px] shrink-0 snap-start sm:w-[140px] md:w-[220px]">
                            <div class="flex h-full flex-col overflow-hidden rounded-lg border border-tinta-200 bg-white shadow-[0_4px_12px_rgba(0,0,0,0.08)] transition duration-300 hover:-translate-y-[5px] hover:shadow-[0_8px_20px_rgba(0,0,0,0.15)]">
                                <a href="{{ route('producto', $producto) }}" class="group flex flex-1 flex-col">
                                    <div class="flex h-[50px] shrink-0 flex-col items-center justify-center px-1.5 py-1.5 sm:h-[60px] md:h-20 md:px-2.5 md:py-3">
                                        <h3 class="line-clamp-2 text-center text-xs font-semibold leading-[1.2] text-tinta-900 group-hover:text-marca-700 sm:text-xs md:text-sm">
                                            {{-- Con carro elegido, el sufijo «OPTRA 1800 CHEVROLET»
                                                 se repite en las diez tarjetas: sobra en cada línea. --}}
                                            {{ $vehiculoActivo ? $producto->tipoParte->nombre : $producto->nombre }}
                                        </h3>
                                        <p class="truncate text-xs leading-[1.1] text-tinta-500 sm:text-xs md:text-xs">
                                            {{ $producto->referencia ? 'REF: '.$producto->referencia : $producto->tipoParte->categoria->nombre }}
                                        </p>
                                    </div>

                                    <div class="flex flex-1 items-center justify-center bg-marca-700 p-3 md:p-5">
                                        <img src="{{ $producto->imagen_mostrable }}" alt=""
                                             width="240" height="240" loading="lazy" decoding="async"
                                             class="max-h-full w-4/5 -rotate-[10deg] object-contain transition duration-300 group-hover:rotate-0">
                                    </div>
                                </a>

                                {{-- El sitio nunca habla de dinero: eso lo trata el asesor. --}}
                                <form method="post" action="{{ route('cotizacion.agregar', $producto) }}"
                                      x-data="agregarACotizacion" @submit.prevent="enviar($event)">
                                    @csrf
                                    <button type="submit" :disabled="enviando"
                                            class="w-full px-2 py-2.5 text-xs font-semibold text-white transition md:text-sm"
                                            :class="listo ? 'bg-marca-700' : 'bg-alerta-500 hover:bg-alerta-600'">
                                        <span x-show="!listo">{{ contenido('destacados.boton_cotizar', 'Cotizar') }}</span>
                                        <span x-show="listo" x-cloak>{{ contenido('destacados.boton_agregado', 'Agregado ✓') }}</span>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <button type="button" @click="desplazar(1)" aria-label="Productos siguientes"
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-marca-700 text-lg text-white transition hover:scale-110 hover:bg-marca-800">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-4" aria-hidden="true">
                        <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </section>
    @endif

    {{-- 7 · ¿Dónde estamos ubicados?

         Calcado del suyo: una sola caja blanca de radio 12 con sombra suave,
         el texto centrado a la izquierda —foto del mapa de 220 px, titular de
         28/800 en azul, párrafo de 16 en gris y la dirección en negrita— y el
         video del local a la derecha, en una columna fija de 320 px. En móvil
         se apila y la dirección baja debajo del video, que es como la ordenan
         ellos.

         Lo que se conserva de nuestra versión y allá no existe: los teléfonos
         se pueden tocar para llamar, y «Cómo llegar» abre el mapa aquí mismo
         en vez de sacar a la persona a Google Maps —enseñarle dónde queda la
         tienda mandándola a otro sitio es perder la visita. --}}
    <section class="px-[3vw] py-5" data-revelar
             x-data="{ mapa: false, abrirMapa() { this.mapa = true; $nextTick(() => $refs.marco?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })) } }">
        <div class="mx-auto w-[min(94vw,1200px)]">
            <div class="flex flex-wrap items-center justify-center gap-5 rounded-xl bg-white p-5 shadow-[0_6px_16px_rgba(0,0,0,0.08)]">

                {{-- Texto --}}
                <div class="order-1 min-w-0 flex-1 text-center">
                    @php $mapa = imagen_contenido('ubicacion.mapa', '/img/mapa/mapa-restrepo'); @endphp
                    <img src="{{ $mapa }}-440.webp"
                         srcset="{{ $mapa }}-220.webp 220w, {{ $mapa }}-440.webp 440w"
                         sizes="220px" width="440" height="330" loading="lazy" decoding="async"
                         alt="{{ contenido('ubicacion.mapa_alt', 'Mapa de la ubicación de Importadora Sur Alpine en el barrio Restrepo') }}"
                         class="mx-auto mb-2 block w-[220px] rounded-lg shadow-[0_2px_6px_rgba(0,0,0,0.15)]">

                    <h2 class="my-2 text-[28px] font-extrabold text-marca-700">
                        {{ contenido('ubicacion.titulo', '¿Dónde estamos ubicados?') }}
                    </h2>

                    <p class="my-2 text-base leading-[1.5] text-tinta-600">
                        {{ contenido('ubicacion.texto', 'Importadora Sur Alpine cuenta con un único punto de atención en Bogotá, con un equipo de asesores expertos que te ayudarán a encontrar la pieza exacta que necesita tu vehículo. Nuestra ubicación estratégica te permite llegar fácilmente y acceder rápidamente a soluciones confiables y de calidad.') }}
                    </p>

                    {{-- En escritorio va con el texto; en móvil, debajo del video. --}}
                    <p class="hidden text-base font-semibold text-black min-[769px]:block">
                        <span aria-hidden="true">📍</span> {{ $contacto->direccion() }}.
                        {{ $contacto->ciudad() }}
                    </p>

                    <ul class="mt-3 flex flex-wrap justify-center gap-x-5 gap-y-1 tabular-nums text-tinta-600">
                        <li><a href="tel:{{ $contacto->pbxTel() }}" class="hover:text-marca-700 hover:underline">PBX {{ $contacto->pbx() }}</a></li>
                        @foreach ($contacto->celulares() as $celular)
                            <li><a href="tel:{{ $celular['tel'] }}" class="hover:text-marca-700 hover:underline">{{ $celular['texto'] }}</a></li>
                        @endforeach
                    </ul>

                    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                        @php $comoLlegar = contenido('contacto.mapa.boton', 'Cómo llegar'); @endphp
                        <button type="button" @click="abrirMapa()" x-show="! mapa"
                                class="con-luz inline-flex items-center gap-2 rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-[0.06em] text-white transition hover:bg-marca-800">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="size-4" aria-hidden="true">
                                <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                            </svg>
                            {{ $comoLlegar }}
                        </button>

                        <a href="{{ $contacto->mapaUrl() }}" target="_blank" rel="noopener"
                           class="text-sm font-semibold text-marca-700 underline-offset-4 hover:underline">
                            {{ contenido('contacto.mapa.enlace', 'Abrir en Google Maps') }} ↗
                        </a>
                    </div>
                </div>

                {{-- Video del local, en automático como en su sitio: sin botón de
                     play, en silencio y en bucle.

                     Arranca cuando la sección entra en pantalla, no al cargar la
                     página. Son 9 MB: con `autoplay` a secas se los lleva todo el
                     que abre la portada, incluido quien nunca baja hasta aquí. --}}
                <div class="order-2 w-full min-[769px]:w-[320px] min-[769px]:shrink-0">
                    <video x-data="videoAlEntrar" muted loop playsinline preload="none"
                           poster="{{ imagen_contenido('ubicacion.mapa', '/img/mapa/mapa-restrepo') }}-440.webp"
                           class="block w-full rounded-[10px] bg-tinta-900"
                           aria-label="{{ contenido('ubicacion.video_aria', 'Video del local de Importadora Sur Alpine en el Restrepo') }}">
                        <source src="/video/local-restrepo.mp4" type="video/mp4">
                        {!! contenido('ubicacion.video_alterno', 'Tu navegador no soporta video. <a href="/video/local-restrepo.mp4">Descárgalo aquí</a>.') !!}
                    </video>
                </div>

                {{-- La dirección, sólo en móvil y debajo del video. --}}
                <p class="order-3 w-full text-center text-sm font-semibold text-black min-[769px]:hidden">
                    <span aria-hidden="true">📍</span> {{ $contacto->direccion() }}.
                    {{ $contacto->ciudad() }}
                </p>

                {{-- El mapa llega cuando se pide, no antes: un iframe de Google
                     cargado de entrada son 700 KB y cookies de terceros para
                     quien nunca lo iba a mirar. --}}
                <div x-show="mapa" x-cloak x-ref="marco" class="order-4 w-full">
                    <iframe title="{{ contenido('ubicacion.mapa_titulo', 'Mapa de Importadora Sur Alpine') }}"
                            src="https://www.google.com/maps?q={{ rawurlencode($contacto->direccionCompleta().', Colombia') }}&output=embed"
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            class="h-72 w-full rounded-[10px] border-0"></iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- 8 · Actualízate con Nosotros.

         Las cuatro tarjetas de noticias, con las medidas de su hoja de estilos:
         rejilla de cuatro columnas con 1,5rem de aire, tarjeta blanca de radio
         12 y sombra suave, foto en proporción 16:9 arriba, y abajo el titular
         (1,25rem/800, dos líneas), el arranque (tres líneas) y «Leer más »» en
         rojo pegado al fondo, para que todas las tarjetas midan igual aunque
         los textos no.

         En su sitio la tarjeta del kit de distribución apunta a `#`: el
         artículo existe, pero desde la portada no se llega a él. Aquí las
         cuatro llevan a su nota. --}}
    @if ($notas->isNotEmpty())
        <section class="px-[3vw] py-10" data-revelar>
            <x-titulo-seccion :texto="contenido('inicio.notas.titulo', 'Actualízate con Nosotros')" />

            <ul class="contenedor mt-10 grid gap-6 min-[641px]:grid-cols-2 min-[993px]:grid-cols-3 min-[1201px]:grid-cols-4">
                @foreach ($notas as $i => $nota)
                    <li class="h-full" data-revelar data-retraso="{{ $i + 1 }}">
                        <article class="flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-[0_2px_6px_rgba(0,0,0,0.08)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_8px_20px_rgba(0,0,0,0.14)]">
                            <a href="{{ route('nota', $nota) }}" tabindex="-1" aria-hidden="true"
                               class="block aspect-[100/56] w-full overflow-hidden bg-tinta-100">
                                @if ($nota->imagen)
                                    <img src="{{ $nota->imagen }}" alt=""
                                         @if ($nota->imagen_srcset)
                                             srcset="{{ $nota->imagen_srcset }}"
                                             sizes="(min-width: 1201px) 330px, (min-width: 641px) 45vw, 90vw"
                                         @endif
                                         width="1024" height="573" loading="lazy" decoding="async"
                                         class="size-full object-cover transition duration-500 hover:scale-105">
                                @endif
                            </a>

                            <div class="flex flex-1 flex-col gap-[0.6rem] px-[1.1rem] pb-[1.1rem] pt-4">
                                <h3 class="line-clamp-2 text-xl font-extrabold leading-[1.25] text-tinta-900">
                                    <a href="{{ route('nota', $nota) }}" class="hover:text-marca-700">{{ $nota->titulo }}</a>
                                </h3>
                                <p class="line-clamp-3 text-base text-tinta-600">{{ $nota->resumen }}</p>

                                {{-- `mt-auto` es lo que empareja las cuatro tarjetas:
                                     el enlace se pega al fondo aunque el texto de
                                     arriba ocupe dos líneas o cinco. --}}
                                <a href="{{ route('nota', $nota) }}"
                                   class="mt-auto w-fit text-base font-bold text-alerta-500 underline-offset-4 hover:text-alerta-700 hover:underline">
                                    Leer más <span aria-hidden="true">»</span>
                                    <span class="sr-only">sobre {{ $nota->titulo }}</span>
                                </a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 8 · Marcas.

         En rejilla estática parecían una hoja de cálculo. En cinta se leen como
         lo que son: el respaldo de dieciséis fabricantes. Rueda sola y se
         detiene al pasar el cursor o al llegar con el teclado, para poder mirar
         un logo con calma. --}}
    @if ($proveedores)
        <section class="contenedor py-12" data-revelar>
            {{-- «Marcas destacadas», que es como lo titulan ellos. Antes decía
                 «Trabajamos con» en versalitas grises, que no existe en su
                 sitio y no se leía como una sección. --}}
            <h2 class="text-center text-2xl font-bold text-marca-600 sm:text-3xl">
                {{ contenido('marcas.titulo', 'Marcas destacadas') }}
            </h2>

            <div class="cinta-marco mt-7 overflow-hidden">
                {{-- La lista va duplicada: es lo que hace que el giro no tenga
                     costura. La copia se esconde del lector de pantalla. --}}
                <ul class="cinta flex w-max items-center gap-12">
                    @foreach ($proveedores as $proveedor)
                        <li class="shrink-0">
                            <img src="{{ $proveedor['src'] }}" alt="{{ $proveedor['nombre'] }}"
                                 width="140" height="70" loading="lazy" decoding="async"
                                 class="h-16 w-auto object-contain transition duration-300 hover:scale-105">
                        </li>
                    @endforeach
                    @foreach ($proveedores as $proveedor)
                        <li class="shrink-0" aria-hidden="true">
                            <img src="{{ $proveedor['src'] }}" alt="" width="140" height="70"
                                 loading="lazy" decoding="async"
                                 class="h-16 w-auto object-contain transition duration-300 hover:scale-105">
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

@endsection
