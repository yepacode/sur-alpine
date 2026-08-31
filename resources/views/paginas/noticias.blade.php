@extends('layouts.app')

@section('titulo', 'Noticias y novedades')
@section('descripcion', 'Consejos, tips y novedades sobre el mantenimiento de tu vehículo, escritos por el equipo de Importadora Sur Alpine.')

{{-- Cada página del listado se apunta a sí misma, como en el catálogo. Sin
     esto, `url()->current()` borra la cadena de consulta y `/noticias?page=2`
     canonicalizaba a `/noticias`: le decía a Google que las dos son la misma
     página, y lo que hubiera en la segunda dejaba de existir para él. --}}
@if ($notas->hasPages())
    @php
        $paginaNoticias = fn (?int $n) => $n === null
            ? null
            : url()->current().($n > 1 ? '?page='.$n : '');
    @endphp
    @section('canonical', $paginaNoticias($notas->currentPage()))
    @section('rel-prev', $paginaNoticias($notas->currentPage() > 1 ? $notas->currentPage() - 1 : null) ?? '')
    @section('rel-next', $notas->hasMorePages() ? $paginaNoticias($notas->currentPage() + 1) : '')
@endif

@push('cabeza')
    <x-pagina-schema tipo="Blog" nombre="Noticias y consejos" :miga="['Noticias' => route('noticias')]" />
@endpush

@section('contenido')
    <div class="contenedor px-[3vw] py-12">
        {{-- `h1`: aquí este bloque no separa una sección, ES el título de la
             página, y sin esto «Noticias» se quedaba sin encabezado principal. --}}
        <x-titulo-seccion como="h1" :texto="contenido('inicio.notas.titulo', 'Actualízate con Nosotros')" />

        <p class="mx-auto mt-5 max-w-2xl text-center text-tinta-600">
            {{ contenido('noticias.texto', 'Consejos, tips y novedades sobre el cuidado de tu carro, escritos por el equipo que atiende el mostrador.') }}
        </p>

        @if ($notas->isEmpty())
            <p class="mt-12 text-center text-tinta-500">
                {{ contenido('noticias.vacio', 'Todavía no hay notas publicadas.') }}
            </p>
        @else
            <ul class="mt-10 grid gap-6 min-[641px]:grid-cols-2 min-[993px]:grid-cols-3 min-[1201px]:grid-cols-4">
                @foreach ($notas as $i => $nota)
                    <li class="h-full" data-revelar data-retraso="{{ ($i % 4) + 1 }}">
                        {{-- La misma tarjeta de la portada. Si cambia una, cambia la
                             otra: por eso las dos leen las mismas clases. --}}
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
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-alerta-600">{{ $nota->categoria }}</p>
                                <h2 class="line-clamp-2 text-xl font-extrabold leading-[1.25] text-tinta-900">
                                    <a href="{{ route('nota', $nota) }}" class="hover:text-marca-700">{{ $nota->titulo }}</a>
                                </h2>
                                <p class="line-clamp-3 text-base text-tinta-600">{{ $nota->resumen }}</p>
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

            <div class="mt-10">{{ $notas->links() }}</div>
        @endif
    </div>
@endsection
