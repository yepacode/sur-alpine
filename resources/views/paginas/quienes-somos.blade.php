@extends('layouts.app')

@section('titulo', 'Quiénes somos')
@section('descripcion', 'Importadora Sur Alpine distribuye repuestos y autopartes para vehículos livianos en Bogotá desde 1982, desde un solo punto en el Barrio Restrepo.')

@section('contenido')

    {{-- Encabezado oscuro, con el mismo lenguaje del hero de la portada: los
         años son lo que esta empresa tiene y la competencia no. --}}
    <section class="relative overflow-hidden bg-tinta-900">
        <div class="absolute inset-0 bg-gradient-to-br from-marca-900 via-tinta-900 to-noche" aria-hidden="true"></div>
        <div class="aurora absolute -left-32 top-0 size-[34rem] rounded-full bg-marca-500/25 blur-[90px]" aria-hidden="true"></div>
        <div class="absolute inset-0 opacity-[0.06] [background-image:linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] [background-size:56px_56px]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-4xl px-4 py-16 sm:py-24">
            <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-marca-300">Quiénes somos</p>
            <h1 class="mt-3 text-[2rem] font-extrabold leading-[1.02] text-white text-balance sm:text-[3.25rem]">
                Desde 1982 buscando<br>
                <span class="text-marca-300">la pieza que nadie tiene</span>
            </h1>
            <p class="mt-6 max-w-xl text-lg text-marca-100">
                Importadora Sur Alpine distribuye repuestos y autopartes para vehículos livianos
                desde un solo punto de atención en Bogotá.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-4xl px-4 py-14">

        {{-- Las tres cifras que resumen la empresa. Se animan al entrar, igual
             que la de la portada: es lo que convierte un dato en un argumento. --}}
        <ul class="grid gap-6 sm:grid-cols-3">
            @foreach ([
                [now()->year - 1982, 'años importando', null],
                [12, 'marcas de vehículo', null],
                [29272, 'repuestos en catálogo', route('catalogo')],
            ] as $i => [$cifra, $texto, $enlace])
                <li data-revelar data-retraso="{{ $i + 1 }}"
                    class="con-luz rounded-2xl bg-white px-6 py-7 text-center shadow-sm ring-1 ring-black/5 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <p class="cifra font-titulo text-4xl font-extrabold text-marca-800" data-contar="{{ $cifra }}">
                        @numero($cifra)
                    </p>
                    <p class="mt-1 text-[15px] text-tinta-500">
                        @if ($enlace)
                            <a href="{{ $enlace }}" class="hover:text-marca-700 hover:underline">{{ $texto }}</a>
                        @else
                            {{ $texto }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>

        <div class="mt-14 grid gap-10 lg:grid-cols-[1.4fr_1fr]" data-revelar>
            <div>
                <h2 class="font-titulo text-2xl font-bold sm:text-3xl">Un solo punto, y todo el país</h2>
                <p class="mt-4 text-lg leading-relaxed text-tinta-600">
                    Trabajamos con esfuerzo, dedicación y responsabilidad desde la
                    {{ $contacto->direccion() }}, en el Barrio Restrepo. Ahí es donde están
                    los asesores que conocen los carros que rueda esta ciudad.
                </p>
                <p class="mt-4 text-lg leading-relaxed text-tinta-600">
                    Y hacemos envíos a ciudades y municipios de todo el país: no hace falta
                    estar en Bogotá para conseguir la pieza.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('catalogo') }}"
                       class="con-luz rounded-xl bg-marca-700 px-6 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-white shadow-lg shadow-marca-700/25 transition hover:bg-marca-800">
                        Ver el catálogo
                    </a>
                    <a href="{{ route('contacto') }}"
                       class="rounded-xl border border-tinta-300 px-6 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-tinta-700 transition hover:bg-tinta-100">
                        Contáctanos
                    </a>
                </div>
            </div>

            {{-- El aviso contra los suplantadores. El cliente pelea con sitios
                 que lo imitan, así que aquí se dice con todas las letras. --}}
            <aside class="self-start rounded-2xl border-2 border-alerta-500/20 bg-alerta-500/5 p-6">
                <p class="flex items-center gap-2 font-titulo text-sm font-bold uppercase tracking-[0.08em] text-alerta-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 shrink-0" aria-hidden="true">
                        <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Cuidado con los sitios falsos
                </p>
                <p class="mt-3 text-[15px] leading-relaxed text-tinta-700">
                    Este es el <strong>único sitio web oficial</strong> de Importadora Sur Alpine.
                    Circulan páginas que usan nuestro nombre y nuestras fotos.
                </p>
                <p class="mt-4 text-[15px] text-tinta-700">
                    Si tienes dudas, llámanos directamente:
                </p>
                <a href="tel:{{ $contacto->pbxTel() }}"
                   class="mt-2 inline-block font-titulo text-lg font-bold text-alerta-700 hover:underline">
                    {{ $contacto->pbx() }}
                </a>
            </aside>
        </div>
    </div>
@endsection
