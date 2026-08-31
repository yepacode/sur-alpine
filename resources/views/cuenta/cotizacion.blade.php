@extends('layouts.app')

@section('titulo', 'Cotización '.$cotizacion->consecutivo)

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

{{--
    Una cotización pasada.

    Los nombres vienen guardados en la propia solicitud, no del catálogo: si
    una pieza cambió de nombre o se despublicó, aquí sigue leyéndose lo que el
    cliente pidió ese día. Eso es lo que hace que este historial sirva.
--}}
@section('contenido')
    <div class="mx-auto max-w-3xl px-4 py-10">

        <a href="{{ route('cuenta.cotizaciones') }}" class="text-sm font-medium text-marca-700 hover:underline">
            <span aria-hidden="true">←</span> Mis cotizaciones
        </a>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Cotización</p>
                <h1 class="mt-1.5 text-[1.75rem] font-extrabold tabular-nums sm:text-4xl">
                    {{ $cotizacion->consecutivo }}
                </h1>
                <p class="mt-1 text-tinta-600">
                    Recibida el {{ $cotizacion->created_at->translatedFormat('d \d\e F \d\e Y') }}
                    a las {{ $cotizacion->created_at->format('g:i a') }}
                </p>
            </div>

            {{-- Lo que hace que este historial valga: un taller pide el mismo
                 filtro cada tanto y no quiere armar la lista otra vez. --}}
            <form method="post" action="{{ route('cuenta.cotizacion.repetir', $cotizacion) }}" class="shrink-0">
                @csrf
                <button type="submit"
                        class="con-luz rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                    Volver a pedir lo mismo
                </button>
            </form>
        </div>

        <section class="mt-8 overflow-hidden rounded-2xl border border-tinta-200 bg-white shadow-sm">
            @foreach ($cotizacion->porVehiculo() as $vehiculo => $items)
                <div class="flex items-center gap-3 border-b border-tinta-200 bg-tinta-50 px-5 py-3">
                    <h2 class="font-semibold text-tinta-800">{{ $vehiculo ?: 'Sin vehículo' }}</h2>
                    <span class="rounded-full bg-marca-100 px-2 py-0.5 text-xs font-bold tabular-nums text-marca-700">
                        {{ $items->count() }}
                    </span>
                </div>

                <ul class="divide-y divide-tinta-100">
                    @foreach ($items as $item)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4">
                            <p class="min-w-48 flex-1 font-medium">{{ $item->producto_nombre }}</p>
                            <p class="shrink-0 text-sm tabular-nums text-tinta-600">
                                Cant. {{ $item->cantidad }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </section>

        <section class="mt-6 rounded-2xl border border-tinta-200 bg-white p-6 shadow-sm">
            <h2 class="font-titulo text-lg font-bold">Datos con los que la enviaste</h2>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex flex-wrap justify-between gap-x-4">
                    <dt class="text-tinta-500">Nombre</dt>
                    <dd class="font-medium">{{ $cotizacion->nombre_completo }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-x-4">
                    <dt class="text-tinta-500">Teléfono</dt>
                    <dd class="font-medium tabular-nums">{{ $cotizacion->telefono }}</dd>
                </div>
                <div class="flex flex-wrap justify-between gap-x-4">
                    <dt class="text-tinta-500">Correo</dt>
                    <dd class="font-medium">{{ $cotizacion->email }}</dd>
                </div>
                @if ($cotizacion->notas)
                    <div class="border-t border-tinta-200 pt-3">
                        <dt class="text-tinta-500">Comentarios</dt>
                        <dd class="mt-1">{{ $cotizacion->notas }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        {{-- «Un asesor te contacta» es la única promesa del sitio, y esta es la
             única pantalla donde el cliente vendría a comprobarla. Pedirle que
             llame es honesto, pero es pedirle justo la llamada que el sitio
             existía para ahorrarle. El botón de WhatsApp lleva el consecutivo
             ya escrito: del otro lado saben de qué solicitud se habla sin
             preguntar nada. --}}
        <div class="mt-6 rounded-2xl border border-tinta-200 bg-white p-5">
            <p class="text-sm font-semibold text-tinta-800">¿Quieres saber cómo va?</p>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                @if ($wa = $contacto->whatsappUrl('Hola, pregunto por mi cotización '.$cotizacion->consecutivo.'.'))
                    <a href="{{ $wa }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg bg-[#0f6e63] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0c5a51]">
                        Preguntar por WhatsApp
                    </a>
                @endif
                <a href="tel:{{ $contacto->pbxTel() }}"
                   class="rounded-lg border border-tinta-300 px-5 py-2.5 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
                    Llamar al {{ $contacto->pbx() }}
                </a>
            </div>

            <p class="mt-3 text-sm text-tinta-500">
                Ten a mano el número <strong class="font-semibold text-tinta-700">{{ $cotizacion->consecutivo }}</strong>.
            </p>
        </div>
    </div>
@endsection
