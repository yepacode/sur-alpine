@extends('layouts.app')

@section('titulo', $producto->nombre)
@section('descripcion', $producto->nombre.' para '.$producto->vehiculo->nombre_completo.'. Pide tu cotización a Importadora Sur Alpine.')

@if ($producto->imagen_mostrable)
    @section('og-imagen', url($producto->imagen_mostrable))
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
        ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($ficha, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('contenido')
    <div class="mx-auto max-w-7xl px-4 py-8">

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

        <div class="grid gap-8 lg:grid-cols-2">

            <div class="flex aspect-4/3 items-center justify-center rounded-2xl border border-tinta-200 bg-white p-8 shadow-sm">
                @if ($producto->imagen_mostrable)
                    {{-- Sin `width`/`height`: la caja ya reserva el espacio con su
                         proporción fija, y la foto puede venir de la categoría
                         (cuadrada) o del producto (de cualquier tamaño). --}}
                    <img src="{{ $producto->imagen_mostrable }}" alt="{{ $producto->nombre }}"
                         loading="eager" fetchpriority="high"
                         class="max-h-full w-auto object-contain">
                @else
                    <p class="text-center text-sm text-tinta-400">
                        Foto no disponible todavía.<br>
                        <span class="text-tinta-500">Escríbenos y te la enviamos.</span>
                    </p>
                @endif
            </div>

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
                    <form method="post" action="{{ route('cotizacion.agregar', $producto) }}"
                          class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="cantidad" class="text-xs font-medium text-marca-900">Cantidad</label>
                            <input id="cantidad" type="number" name="cantidad" value="1" min="1" max="99"
                                   class="mt-1 w-24 rounded-lg border border-marca-200 bg-white px-3 py-2.5 text-center text-sm tabular-nums">
                        </div>
                        <button type="submit"
                                class="con-luz flex-1 rounded-xl bg-alerta-500 px-5 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-white shadow-lg shadow-alerta-500/25 transition hover:bg-alerta-600">
                            Agregar a mi cotización
                        </button>
                    </form>

                    @if ($enCotizacion)
                        <p class="mt-3 text-center text-sm text-marca-800">
                            Ya está en tu solicitud.
                            <a href="{{ route('cotizacion.ver') }}" class="font-semibold underline underline-offset-2">Ver mi cotización</a>
                        </p>
                    @endif
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
                                <h3 class="font-titulo text-[15px] font-bold leading-snug text-tinta-900 group-hover:text-marca-700">
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
