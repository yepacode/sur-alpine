@extends('layouts.app')

@section('titulo', $nota->titulo)
@section('descripcion', $nota->resumen)

@if ($nota->imagen)
    @section('og-imagen', url($nota->imagen))
@endif

{{-- Estas notas son lo que un asesor pasa por WhatsApp cuando alguien pregunta
     «¿cada cuánto se cambia el kit?». El dato estructurado es lo que decide si
     el enlace llega con foto y titular o pelado. --}}
@push('cabeza')
    @php
        $ficha = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $nota->titulo,
            'description' => $nota->resumen,
            'image' => $nota->imagen ? url($nota->imagen) : null,
            'datePublished' => $nota->publicada_en?->toIso8601String(),
            'dateModified' => $nota->updated_at?->toIso8601String(),
            'articleSection' => $nota->categoria,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('nota', $nota)],
            // `author` es obligatorio en Article: sin el, estas cinco notas no
            // pueden aparecer como resultado enriquecido. No hay columna de
            // autor en `notas` -las escribe la empresa, no una persona-, asi
            // que el autor es la organizacion, que es lo correcto aqui.
            'author' => ['@type' => 'Organization', '@id' => url('/').'#negocio', 'name' => 'Importadora Sur Alpine'],
            'publisher' => ['@type' => 'Organization', '@id' => url('/').'#negocio', 'name' => 'Importadora Sur Alpine'],
        ]);

        $miga = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Noticias', 'item' => route('noticias')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $nota->titulo, 'item' => route('nota', $nota)],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ficha, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode($miga, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('contenido')
    <article class="contenedor px-[3vw] py-10">

        <nav aria-label="Migas de pan" class="mb-6 text-sm text-tinta-500">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('inicio') }}" class="hover:text-marca-700 hover:underline">Inicio</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('noticias') }}" class="hover:text-marca-700 hover:underline">Noticias</a></li>
            </ol>
        </nav>

        <div class="mx-auto max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-alerta-600">{{ $nota->categoria }}</p>
            <h1 class="mt-2 text-[1.75rem] font-extrabold leading-[1.15] sm:text-4xl">{{ $nota->titulo }}</h1>

            <p class="mt-3 text-sm text-tinta-500">
                @if ($nota->publicada_en)
                    <time datetime="{{ $nota->publicada_en->toDateString() }}">
                        {{ $nota->publicada_en->translatedFormat('j \d\e F \d\e Y') }}
                    </time>
                    <span aria-hidden="true"> · </span>
                @endif
                {{ $nota->minutos_de_lectura }} min de lectura
            </p>

            @if ($nota->imagen)
                <img src="{{ $nota->imagen }}" alt=""
                     @if ($nota->imagen_srcset) srcset="{{ $nota->imagen_srcset }}" sizes="(min-width: 768px) 768px, 94vw" @endif
                     width="1024" height="573" fetchpriority="high" decoding="async"
                     class="mt-7 w-full rounded-xl object-cover shadow-[0_2px_6px_rgba(0,0,0,0.08)]">
            @endif

            {{-- El cuerpo llega como texto plano y se pinta bloque a bloque. No
                 hay `{!! !!}` en ninguna parte a propósito: lo que escribe un
                 asesor en el panel no debe poder inyectar HTML en el sitio. --}}
            <div class="mt-8 space-y-5 text-lg leading-relaxed text-tinta-800">
                @foreach ($nota->bloques() as $bloque)
                    @if ($bloque['tipo'] === 'titulo')
                        <h2 class="pt-3 text-xl font-extrabold text-marca-700 sm:text-2xl">{{ $bloque['texto'] }}</h2>
                    @elseif ($bloque['tipo'] === 'lista')
                        <ul class="space-y-2 pl-1">
                            @foreach ($bloque['puntos'] as $punto)
                                <li class="flex gap-3">
                                    <span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span>
                                    <span>{{ $punto }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p>{{ $bloque['texto'] }}</p>
                    @endif
                @endforeach
            </div>

            {{-- El cierre que estas notas ya piden en su propio texto: todas
                 terminan invitando a llamar. Aquí el teléfono se puede tocar. --}}
            <aside class="mt-12 rounded-2xl bg-marca-50 p-6 ring-1 ring-marca-100">
                <p class="text-marca-900">
                    <strong>¿Necesitas el repuesto?</strong>
                    Busca por tu vehículo y arma tu solicitud; un asesor te contacta para
                    confirmarte disponibilidad.
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('inicio') }}#buscador"
                       class="rounded-lg bg-alerta-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-alerta-600">
                        Buscar por mi vehículo
                    </a>
                    <a href="tel:{{ $contacto->pbxTel() }}"
                       class="rounded-lg border border-marca-300 px-5 py-2.5 text-sm font-semibold text-marca-800 transition hover:bg-white">
                        PBX {{ $contacto->pbx() }}
                    </a>
                </div>
            </aside>
        </div>

        @if ($otras->isNotEmpty())
            <section class="mt-16">
                <h2 class="text-xl font-bold">Sigue leyendo</h2>
                <ul class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($otras as $otra)
                        <li class="h-full">
                            <article class="flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-[0_2px_6px_rgba(0,0,0,0.08)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_8px_20px_rgba(0,0,0,0.14)]">
                                <a href="{{ route('nota', $otra) }}" tabindex="-1" aria-hidden="true"
                                   class="block aspect-[100/56] w-full overflow-hidden bg-tinta-100">
                                    @if ($otra->imagen)
                                        <img src="{{ $otra->imagen }}" alt=""
                                             width="1024" height="573" loading="lazy" decoding="async"
                                             class="size-full object-cover">
                                    @endif
                                </a>
                                <div class="flex flex-1 flex-col gap-[0.6rem] px-[1.1rem] pb-[1.1rem] pt-4">
                                    <h3 class="line-clamp-2 text-lg font-extrabold leading-[1.25]">
                                        <a href="{{ route('nota', $otra) }}" class="hover:text-marca-700">{{ $otra->titulo }}</a>
                                    </h3>
                                    <p class="line-clamp-3 text-base text-tinta-600">{{ $otra->resumen }}</p>
                                    <a href="{{ route('nota', $otra) }}"
                                       class="mt-auto w-fit text-base font-bold text-alerta-500 underline-offset-4 hover:text-alerta-700 hover:underline">
                                        Leer más <span aria-hidden="true">»</span>
                                        <span class="sr-only">sobre {{ $otra->titulo }}</span>
                                    </a>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
