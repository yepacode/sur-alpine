@extends('layouts.app')

@section('titulo', 'Contáctenos')
@section('descripcion', 'Escríbenos o llámanos: Importadora Sur Alpine atiende desde el Barrio Restrepo, en Bogotá, y hace envíos a todo el país.')

@section('contenido')
    <div class="mx-auto max-w-4xl px-4 py-14">

        <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Hablemos</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Contáctenos</h1>
        <p class="mt-3 max-w-xl text-lg text-tinta-600">
            Un asesor te ayuda a encontrar la pieza exacta que necesita tu vehículo.
        </p>

        <div class="mt-10 grid gap-5 sm:grid-cols-2">

            {{-- Llamar es lo que de verdad hace la gente en este negocio, así que
                 va primero y ocupa la tarjeta más grande. --}}
            <div data-revelar class="con-luz rounded-2xl bg-white p-7 shadow-sm ring-1 ring-black/5 sm:col-span-2">
                <p class="font-titulo text-[11px] font-bold uppercase tracking-[0.16em] text-tinta-500">Llámanos</p>
                <ul class="mt-4 flex flex-wrap gap-x-10 gap-y-4">
                    <li>
                        <a href="tel:{{ $contacto->pbxTel() }}" class="group block">
                            <span class="block text-xs text-tinta-500">PBX</span>
                            <span class="cifra font-titulo text-2xl font-bold text-marca-800 group-hover:text-marca-600">
                                {{ $contacto->pbx() }}
                            </span>
                        </a>
                    </li>
                    @foreach ($contacto->celulares() as $celular)
                        <li>
                            <a href="tel:{{ $celular['tel'] }}" class="group block">
                                <span class="block text-xs text-tinta-500">Celular</span>
                                <span class="cifra font-titulo text-2xl font-bold text-marca-800 group-hover:text-marca-600">
                                    {{ $celular['texto'] }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div data-revelar data-retraso="1" class="con-luz rounded-2xl bg-white p-7 shadow-sm ring-1 ring-black/5">
                <p class="font-titulo text-[11px] font-bold uppercase tracking-[0.16em] text-tinta-500">Visítanos</p>
                <p class="mt-4 flex items-start gap-2 font-semibold leading-snug">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         class="mt-0.5 size-5 shrink-0 text-alerta-500" aria-hidden="true">
                        <path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>
                    </svg>
                    {{ $contacto->direccion() }}, Barrio Restrepo<br>{{ $contacto->ciudad() }}
                </p>
                <a href="{{ $contacto->mapaUrl() }}" target="_blank" rel="noopener"
                   class="mt-5 inline-block text-sm font-semibold text-marca-700 underline-offset-4 hover:underline">
                    Cómo llegar ↗
                </a>
            </div>

            <div data-revelar data-retraso="2" class="con-luz rounded-2xl bg-white p-7 shadow-sm ring-1 ring-black/5">
                <p class="font-titulo text-[11px] font-bold uppercase tracking-[0.16em] text-tinta-500">
                    ¿Buscas una pieza?
                </p>
                <p class="mt-4 leading-relaxed text-tinta-600">
                    Arma tu solicitud en el catálogo y te contactamos con la disponibilidad
                    y el precio del día.
                </p>
                <a href="{{ route('catalogo') }}"
                   class="con-luz mt-5 inline-block rounded-xl bg-alerta-500 px-5 py-3 font-titulo text-xs font-bold uppercase tracking-[0.06em] text-white transition hover:bg-alerta-600">
                    Buscar mi repuesto
                </a>
            </div>
        </div>

        {{-- El formulario todavía no existe: decirlo es mejor que dejar la
             página con una promesa a medias. --}}
        <p class="mt-10 rounded-2xl bg-tinta-100 p-5 text-sm text-tinta-600">
            El formulario de contacto en línea llega en una próxima entrega. Mientras tanto,
            el teléfono es la vía más rápida: el equipo responde en el momento.
        </p>
    </div>
@endsection
