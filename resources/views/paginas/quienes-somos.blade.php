@extends('layouts.app')

@section('titulo', '¿Quiénes somos?')
@section('descripcion', 'Importadora Sur Alpine: repuestos y autopartes en Bogotá desde 1982. Único punto de atención en el Barrio Restrepo. Este es nuestro único sitio oficial.')
@section('og-imagen', url('/img/cabeceras/banner-quienes-somos-1600.webp'))

{{--
    ¿Quiénes somos?

    El texto es el de ellos, palabra por palabra, tomado de su propia página. Su
    versión tiene además cuatro tarjetas de «Lorem ipsum» y un carrusel llamado
    «Carrusel-provisional»; los dos bloques van marcados en su HTML como
    `elementor-hidden-desktop / laptop / tablet / mobile`, es decir, ocultos en
    todos los tamaños. No se ven en ninguna parte y por eso no se copiaron:
    copiar «Lorem ipsum» a la página nueva sería copiar el andamio, no la obra.

    Lo único que se conserva de nuestra versión anterior es lo que no es
    invención nuestra: las tres cifras —salen del catálogo y de la fecha de
    fundación— y el aviso contra los sitios falsos, que es literalmente lo que
    ellos gritan en su propio banner de portada («ÚNICO SITIO WEB OFICIAL / ¡NO
    TE DEJES ENGAÑAR!»). La prosa que habíamos escrito nosotros salió: eran
    palabras que el cliente nunca dijo.

    Un detalle: en su párrafo la dirección dice «Av Caracas 19 –21 sur», pero su
    sección «¿Dónde estamos ubicados?» y su política de datos dicen 19-15. Aquí
    el número sale de la configuración —una sola fuente— para que el sitio no se
    contradiga consigo mismo de una página a otra.
--}}
@push('cabeza')
    <x-pagina-schema tipo="AboutPage" nombre="¿Quiénes somos?" :miga="['¿Quiénes somos?' => route('quienes-somos')]" />
@endpush

@section('contenido')

    {{-- La misma franja que «Contáctenos»: 280 px, la foto en `cover` y el
         degradado horizontal que hace legible el título. Medido sobre su
         página, no inventado. --}}
    <x-cabecera-pagina titulo="¿Quiénes somos?" imagen="{{ imagen_contenido('quienes.imagen', '/img/cabeceras/banner-quienes-somos') }}"
                       :ancho="2161" :alto="457" />

    <div class="contenedor px-[3vw] py-12">

        {{-- El párrafo de ellos, editable desde el panel.

             Mientras nadie lo toque, la dirección se pone sola desde
             «Configuración»: si mañana se mudan, este texto se muda con
             ellos. En cuanto el cliente lo edite, manda lo que él escriba
             —incluida la dirección que decida poner ahí. --}}
        @php
            $historia = 'Importadora Sur Alpine es una compañía fundada en el año 1982 con sede en la '
                .$contacto->direccion().'. En su metodología siempre está presente trabajar con esfuerzo, '
                .'dedicación y responsabilidad, y fue gracias a esto que la compañía está en constante '
                .'transformación e innovación en sus procesos. Siempre buscando el mejor servicio y calidad '
                .'para sus clientes, entendiendo y creando nuevas líneas de negocio; un ejemplo es el '
                .'servicio a domicilio, puesto que movilizarse en la ciudad es cada vez más difícil y toma '
                .'más tiempo. También expandiéndose a nivel nacional, llegando a diferentes municipios con '
                .'repuestos de alta calidad.';
        @endphp
        <p class="mx-auto max-w-3xl text-lg leading-relaxed text-tinta-700">
            {{ contenido('quienes.texto', $historia) }}
        </p>

        {{-- Las tres cifras que resumen la empresa. Se animan al entrar, igual
             que la de la portada: es lo que convierte un dato en un argumento. --}}
        <ul class="mx-auto mt-12 grid max-w-3xl gap-6 sm:grid-cols-3">
            @foreach ([
                [now()->year - 1982, 'años importando', null],
                [12, 'marcas de vehículo', null],
                [29272, 'repuestos en catálogo', route('catalogo')],
            ] as $i => [$cifra, $texto, $enlace])
                <li data-revelar data-retraso="{{ $i + 1 }}"
                    class="con-luz rounded-2xl bg-white px-6 py-7 text-center shadow-[0_6px_16px_rgba(0,0,0,0.08)] transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <p class="cifra text-4xl font-extrabold text-marca-700" data-contar="{{ $cifra }}">
                        @numero($cifra)
                    </p>
                    <p class="mt-1 text-base text-tinta-500">
                        @if ($enlace)
                            <a href="{{ $enlace }}" class="hover:text-marca-700 hover:underline">{{ $texto }}</a>
                        @else
                            {{ $texto }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>

        {{-- El aviso contra los suplantadores. No es invención nuestra: es lo
             que ellos mismos ponen en el banner de su portada. --}}
        <aside class="mx-auto mt-12 max-w-3xl rounded-2xl border-2 border-alerta-500/20 bg-alerta-500/5 p-6" data-revelar>
            <p class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.08em] text-alerta-700">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5 shrink-0" aria-hidden="true">
                    <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ contenido('quienes.aviso.titulo', 'Cuidado con los sitios falsos') }}
            </p>
            <p class="mt-3 text-base leading-relaxed text-tinta-700">
                Este es el <strong>{{ contenido('quienes.enfasis_oficial', 'único sitio web oficial') }}</strong> de Importadora Sur Alpine.
                {{ contenido('quienes.aviso.texto', 'Circulan páginas que usan nuestro nombre y nuestras fotos. Si tienes dudas, llámanos directamente:') }}
            </p>
            <a href="tel:{{ $contacto->pbxTel() }}"
               class="mt-3 inline-block text-lg font-bold tabular-nums text-alerta-700 hover:underline">
                {{ $contacto->pbx() }}
            </a>
        </aside>

        <div class="mx-auto mt-12 flex max-w-3xl flex-wrap gap-3">
            <a href="{{ route('catalogo') }}"
               class="con-luz rounded-xl bg-marca-700 px-6 py-3.5 text-sm font-bold uppercase tracking-[0.06em] text-white shadow-lg shadow-marca-700/25 transition hover:bg-marca-800">
                Ver el catálogo
            </a>
            <a href="{{ route('contacto') }}"
               class="rounded-xl border border-tinta-300 px-6 py-3.5 text-sm font-bold uppercase tracking-[0.06em] text-tinta-700 transition hover:bg-tinta-100">
                Contáctanos
            </a>
        </div>
    </div>
@endsection
