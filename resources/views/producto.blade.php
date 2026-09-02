@extends('layouts.app')

@section('titulo', $producto->nombre)

{{-- 890 fichas del catalogo son pares identicos: la misma pieza del mismo
     carro, importada bajo dos categorias porque su tipo de parte esta en las
     dos. Titulo y descripcion coinciden byte a byte y las dos se declaraban a
     si mismas la original. La secundaria sigue respondiendo 200 —hay enlaces
     circulando— pero apunta a la buena y se queda fuera del sitemap. --}}
@php $fichaBuena = $producto->fichaPrincipal(); @endphp
@if ($fichaBuena->isNot($producto))
    @section('canonical', route('producto', $fichaBuena))
@endif
@section('descripcion', $producto->nombre.' para '.$producto->vehiculo->nombre_completo.'. Pide tu cotización a Importadora Sur Alpine.')

{{-- Sólo si hay foto DE ESTA PIEZA.
     `imagen_mostrable` nunca es nulo —cae en el dibujo genérico—, así que
     esta condición se cumplía siempre y las 29.272 fichas mandaban a WhatsApp
     la misma ilustración de 525×465: por debajo del umbral de la tarjeta
     grande, o sea que todos los enlaces llegaban pelados y todos iguales. Sin
     foto propia es mejor la tarjeta de marca, que además dice «sitio
     oficial». --}}
@if ($producto->imagen)
    @section('og-imagen', url($producto->imagen))
    {{-- El texto alternativo de la tarjeta describe LA PIEZA cuando la foto es
         suya. Sin foto propia se queda el de la tarjeta de marca, que es lo
         que de verdad se está viendo. --}}
    @section('og-imagen-alt', $producto->nombre.' para '.$producto->vehiculo->nombre_completo)
@endif

{{-- La ficha es la página que el asesor pasa por WhatsApp, así que es la que
     más gana con esto. Sin precio ni disponibilidad a propósito: aquí no se
     habla de dinero, y anunciar una oferta que no existe sería peor que nada. --}}
@push('cabeza')
    @php
        $ficha = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $producto->nombre,
            'description' => $producto->descripcion ?: $producto->nombre.' para '.$producto->vehiculo->nombre_completo.'.',
            'category' => $producto->tipoParte->categoria->nombre,
            'sku' => $producto->referencia,
            'image' => $producto->imagen_mostrable ? url($producto->imagen_mostrable) : null,
            'brand' => ['@type' => 'Brand', 'name' => $producto->vehiculo->modelo->marca->nombre],
            'isAccessoryOrSparePartFor' => [
                '@type' => 'Vehicle',
                'name' => $producto->vehiculo->nombre_completo,
                'vehicleEngine' => ['@type' => 'EngineSpecification', 'name' => $producto->vehiculo->cilindraje],
            ],
            'seller' => ['@type' => 'AutoPartsStore', '@id' => url('/').'#negocio'],

            // NO lleva `offers`, y conviene dejar escrito por qué, porque
            // la decisión se tomó dos veces y al revés.
            //
            // Antes iba un `Offer` sin `price` para que el nodo no saliera
            // «no elegible». Eso cambia un aviso por un ERROR: Google exige
            // `price` y `priceCurrency` dentro de `offers`, así que eran
            // 28.827 fichas en rojo permanente en Search Console, y el rojo de
            // verdad —el día que lo haya— se pierde entre ellas.
            //
            // Y arrastraba dos afirmaciones que el negocio no puede sostener:
            // `InStock` en las 28.827 piezas sin llevar control de existencias,
            // y `businessFunction: Sell` en un sitio que no vende en línea.
            //
            // Sin precio esta ficha no va a ser elegible para el resultado
            // enriquecido de ninguna manera —el cliente prohibió publicar
            // precios—, así que el `Offer` no compraba nada y costaba caro.
            // Lo que de verdad posiciona ya está puesto: `brand`, `category` y
            // sobre todo `isAccessoryOrSparePartFor`, que es lo que conecta la
            // pieza con el carro y responde a «radiador para un Rio 1500».
        ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($ficha, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

    {{-- G · BreadcrumbList: le dice a Google (y a los rastreadores de IA) el
         camino jerárquico de la ficha. Al indexar aparece como «Inicio ›
         Motor Externo › Filtros de Aceite › Filtro AVEO», que se lee mucho
         mejor en el resultado que la URL cruda. --}}
    @php
        $miga = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2,
                 'name' => $producto->tipoParte->categoria->nombre,
                 'item' => route('categoria', $producto->tipoParte->categoria)],
                ['@type' => 'ListItem', 'position' => 3,
                 'name' => $producto->tipoParte->nombre,
                 'item' => route('tipo-parte', [$producto->tipoParte->categoria, $producto->tipoParte])],
                ['@type' => 'ListItem', 'position' => 4,
                 'name' => $producto->nombre,
                 'item' => route('producto', $producto)],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($miga, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('contenido')
    <div class="contenedor py-8">

        <nav aria-label="Migas de pan" class="mb-6 text-sm text-tinta-500">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('inicio') }}" class="hover:text-marca-700 hover:underline">Inicio</a></li>
                <li aria-hidden="true">/</li>
                <li>
                    <a href="{{ route('categoria', $producto->tipoParte->categoria) }}" class="hover:text-marca-700 hover:underline">
                        {{ $producto->tipoParte->categoria->nombre }}
                    </a>
                </li>
                <li aria-hidden="true">/</li>
                <li>
                    <a href="{{ route('tipo-parte', [$producto->tipoParte->categoria, $producto->tipoParte]) }}"
                       class="hover:text-marca-700 hover:underline">
                        {{ $producto->tipoParte->nombre }}
                    </a>
                </li>
            </ol>
        </nav>

        <div class="grid gap-6 md:grid-cols-2 md:gap-8">

            {{-- Foto propia y dibujo genérico son dos cosas distintas.
                 Antes se pintaban igual, y como `imagen_mostrable` NUNCA es
                 nulo —cae siempre en el genérico— la rama de abajo no se veía
                 jamás: el texto honesto que explica que la foto no está
                 llegaba a cero personas. Peor en un teléfono, donde ese dibujo
                 de unos mecánicos ocupaba 540 de los 844 px de pantalla y
                 empujaba bajo el pliegue el nombre, la referencia y el botón
                 de agregar. --}}
            @php $fotoPropia = $producto->imagen ?: $producto->tipoParte?->imagen_defecto; @endphp

            @if ($fotoPropia)
                <div class="flex aspect-4/3 items-center justify-center rounded-2xl border border-tinta-200 bg-white p-8 shadow-sm">
                    {{-- Sin `width`/`height`: la caja ya reserva el espacio con su
                         proporción fija, y la foto puede venir de la categoría
                         (cuadrada) o del producto (de cualquier tamaño). --}}
                    <img src="{{ $fotoPropia }}" alt="{{ $producto->nombre }}"
                         loading="eager" fetchpriority="high"
                         class="max-h-full w-auto object-contain">
                </div>
            @else
                <div class="flex items-center justify-center gap-4 rounded-2xl border border-tinta-200 bg-white p-5 shadow-sm md:aspect-4/3 md:flex-col md:p-8">
                    {{-- `alt=""`: es una ilustración decorativa, no la pieza.
                         Anunciarla con el nombre del repuesto le dice a quien
                         usa lector de pantalla que hay una foto que no hay. --}}
                    <img src="{{ \App\Models\Producto::IMAGEN_GENERICA }}" alt=""
                         width="96" height="85" loading="lazy"
                         class="w-20 shrink-0 opacity-60 md:w-40">
                    <p class="text-sm text-tinta-400 md:text-center">
                        Foto no disponible todavía.<br>
                        <span class="text-tinta-500">Escríbenos y te la enviamos.</span>
                    </p>
                </div>
            @endif

            <div>
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">
                    {{ $producto->tipoParte->categoria->nombre }}
                </p>
                <h1 class="mt-2 text-[1.75rem] font-extrabold leading-tight sm:text-4xl">{{ $producto->nombre }}</h1>

                <dl class="mt-6 divide-y divide-tinta-200 border-y border-tinta-200 text-sm">
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-tinta-500">Vehículo</dt>
                        <dd class="text-right font-medium">{{ $producto->vehiculo->nombre_completo }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-tinta-500">Tipo de parte</dt>
                        <dd class="text-right font-medium">{{ $producto->tipoParte->nombre }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-tinta-500">Referencia</dt>
                        <dd class="text-right font-medium">{{ $producto->referencia ?: 'Consúltala con el asesor' }}</dd>
                    </div>
                </dl>

                <div class="mt-7 rounded-2xl bg-marca-50 p-6 ring-1 ring-marca-100">
                    <p class="text-sm text-marca-900">
                        <strong>Arma tu solicitud</strong> con los repuestos que necesitas y un asesor
                        te contacta para confirmarte disponibilidad.
                    </p>

                    {{-- El producto va en la ruta. No hay forma de que este botón
                         agregue algo distinto a la pieza que se está viendo. --}}
                    {{-- Sin recargar, como en la portada.
                         El componente estaba escrito y bien resuelto, pero sólo
                         cableado al carrusel de destacados: la ficha —que es
                         DONDE se agrega de verdad— mandaba un POST plano. Para
                         ocho repuestos en un taller con mala señal eso eran unas
                         dieciséis cargas de página. Si el `fetch` falla, el
                         propio componente manda el formulario a la antigua. --}}
                    <form method="post" action="{{ route('cotizacion.agregar', $producto) }}"
                          x-data="agregarACotizacion" @submit.prevent="enviar($event)"
                          class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="cantidad" class="text-xs font-medium text-marca-900">Cantidad</label>
                            <input id="cantidad" type="number" name="cantidad" value="1" min="1" max="99"
                                   inputmode="numeric"
                                   class="mt-1 w-24 rounded-lg border border-marca-200 bg-white px-3 py-2.5 text-center text-sm tabular-nums">
                        </div>
                        <button type="submit" :disabled="enviando"
                                class="con-luz flex-1 rounded-xl px-5 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-white shadow-lg shadow-alerta-500/25 transition"
                                :class="listo ? 'bg-marca-700' : 'bg-alerta-500 hover:bg-alerta-600'">
                            <span x-show="!listo">Agregar a mi cotización</span>
                            <span x-show="listo" x-cloak>Agregado ✓ · sigue buscando</span>
                        </button>
                    </form>

                    {{-- El enlace a la cotización aparece TAMBIÉN tras agregar sin
                         recargar. Antes esta línea la decidía el servidor, así
                         que quien agregaba con el botón nuevo no la veía nunca:
                         el botón volvía a su texto a los dos segundos y la única
                         señal que quedaba era el contador de la cabecera. --}}
                    <p x-data="{ dentro: {{ $enCotizacion ? 'true' : 'false' }} }"
                       @cotizacion-actualizada.window="dentro = true"
                       x-show="dentro" @if (! $enCotizacion) x-cloak @endif
                       class="mt-3 text-center text-sm text-marca-800">
                        Ya está en tu solicitud.
                        <a href="{{ route('cotizacion.ver') }}" class="font-semibold underline underline-offset-2">Ver mi cotización</a>
                    </p>
                </div>
            </div>
        </div>

        @if ($relacionados->isNotEmpty())
            <section class="mt-16">
                <h2 class="font-titulo text-xl font-bold">
                    Otras piezas de {{ $producto->tipoParte->categoria->nombre }} para este carro
                </h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relacionados as $otro)
                        <li data-revelar>
                            <a href="{{ route('producto', $otro) }}"
                               class="con-luz group flex h-full flex-col rounded-2xl border border-tinta-200 bg-white p-5 transition duration-300 hover:-translate-y-1 hover:border-marca-300 hover:shadow-lg">
                                <h3 class="font-titulo text-base font-bold leading-snug text-tinta-900 group-hover:text-marca-700">
                                    {{ $otro->nombre }}
                                </h3>
                                <p class="mt-1.5 text-xs text-tinta-500">{{ $otro->tipoParte->nombre }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
