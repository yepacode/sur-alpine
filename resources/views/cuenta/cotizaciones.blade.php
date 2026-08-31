@extends('layouts.app')

@section('titulo', 'Mis cotizaciones')

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

@section('contenido')
    <div class="mx-auto max-w-5xl px-4 py-10">

        <a href="{{ route('cuenta') }}" class="text-sm font-medium text-marca-700 hover:underline">
            <span aria-hidden="true">←</span> Mi cuenta
        </a>

        <p class="mt-4 font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Mi cuenta</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Mis cotizaciones</h1>
        <p class="mt-1 text-tinta-600">
            Todo lo que has pedido, con su número y sus repuestos.
        </p>

        @if ($cotizaciones->isEmpty())
            <div class="mt-8 rounded-xl border border-dashed border-tinta-300 bg-white p-10 text-center">
                <p class="font-semibold">Todavía no has pedido ninguna cotización</p>
                <p class="mx-auto mt-2 max-w-sm text-sm text-tinta-600">
                    Busca los repuestos que necesitas, arma tu lista y un asesor te
                    contacta para confirmarte disponibilidad.
                </p>
                <a href="{{ route('catalogo') }}"
                   class="mt-5 inline-block rounded-lg bg-alerta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-alerta-600">
                    Ver el catálogo
                </a>
            </div>
        @else
            <ul class="mt-8 divide-y divide-tinta-200 overflow-hidden rounded-2xl border border-tinta-200 bg-white shadow-sm">
                @foreach ($cotizaciones as $cotizacion)
                    <li>
                        <a href="{{ route('cuenta.cotizacion', $cotizacion) }}"
                           class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4 transition hover:bg-marca-50/50">
                            <div class="min-w-48 flex-1">
                                <p class="font-semibold tabular-nums">{{ $cotizacion->consecutivo }}</p>
                                <p class="text-sm text-tinta-600">
                                    {{ $cotizacion->created_at->translatedFormat('d M Y') }}
                                    <span aria-hidden="true">·</span>
                                    {{ $cotizacion->items_count }} {{ $cotizacion->items_count === 1 ? 'repuesto' : 'repuestos' }}
                                </p>
                            </div>
                            <span aria-hidden="true" class="shrink-0 text-tinta-400">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $cotizaciones->links() }}</div>
        @endif
    </div>
@endsection
