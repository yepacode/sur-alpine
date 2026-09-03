@extends('layouts.app')

@section('titulo', 'Solicitud enviada')

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

@section('contenido')
    <div class="mx-auto max-w-2xl px-4 py-20 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-marca-600">{{ contenido('enviada.antetitulo', 'Listo') }}</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-balance">
            {{ contenido('cotizacion.gracias', 'Recibimos tu solicitud') }}
        </h1>

        @if ($consecutivo)
            <p class="mt-4 text-tinta-600">
                Tu número de solicitud es
                <strong class="tabular-nums text-tinta-900">{{ $consecutivo }}</strong>.
                Tenlo a mano cuando te llamemos.
            </p>
        @endif

        <p class="mt-3 text-tinta-600">
            Un asesor te contacta en horario de oficina para atender tu solicitud.
            También te mandamos copia por correo.
        </p>

        {{-- Éste es el momento de decirlo: acaba de enviar algo y quiere saber
             que no se perdió. Después ya no vuelve a preguntárselo. --}}
        @auth
            <p class="mt-3 text-tinta-600">
                Queda guardada en
                <a href="{{ route('cuenta.cotizaciones') }}"
                   class="font-semibold text-marca-700 underline-offset-2 hover:underline">{{ contenido('enviada.enlace_mis_cotizaciones', 'mis cotizaciones') }}</a>,
                por si después quieres volver a pedir lo mismo.
            </p>
        @endauth

        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="{{ route('catalogo') }}"
               class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                {{ contenido('enviada.seguir_viendo', 'Seguir viendo repuestos') }}
            </a>
            <a href="tel:{{ $contacto->pbxTel() }}"
               class="rounded-lg border border-tinta-300 px-6 py-3 font-semibold text-tinta-700 hover:bg-tinta-100">
                Llamar al {{ $contacto->pbx() }}
            </a>
        </div>
    </div>
@endsection
