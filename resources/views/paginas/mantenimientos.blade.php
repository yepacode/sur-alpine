@extends('layouts.app')

@section('titulo', 'Historial de mantenimientos')
@section('descripcion', 'Lleva el control de los mantenimientos de tu vehículo: kilometraje, fechas y avisos del próximo cambio.')

@push('cabeza')
    <x-pagina-schema tipo="WebPage" nombre="Mantenimientos" :miga="['Mantenimientos' => route('mantenimientos')]" />
@endpush

@section('contenido')
    <div class="mx-auto max-w-3xl px-4 py-16">
        <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">
            {{ contenido('mant.rotulo', 'Para mecánicos') }}
        </p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">
            {{ contenido('mant.titulo', 'Historial de mantenimientos') }}
        </h1>
        <p class="mt-4 text-lg text-tinta-600">
            {{ contenido('mant.texto', 'Registra qué le hiciste a tu carro y cuándo. Nosotros calculamos cuándo toca el próximo cambio y te avisamos.') }}
        </p>

        <ol class="mt-10 space-y-4">
            {{-- Los tres pasos salen del panel: son lo que el cliente más
                 querrá afinar cuando vea cómo lo lee la gente del taller. --}}
            @foreach ([
                [contenido('mant.paso1.titulo', 'Registra tu vehículo'), contenido('mant.paso1.texto', 'Placa, marca, modelo y cilindraje. Puedes tener varios.')],
                [contenido('mant.paso2.titulo', 'Anota cada servicio'), contenido('mant.paso2.texto', 'Kilometraje, fecha, qué se cambió y tus notas.')],
                [contenido('mant.paso3.titulo', 'Te avisamos'), contenido('mant.paso3.texto', 'Según los kilómetros o el tiempo que definas para cada mantenimiento.')],
            ] as $i => [$titulo, $texto])
                <li data-revelar data-retraso="{{ $i + 1 }}" class="con-luz flex gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-alerta-500/10 font-titulo text-lg font-extrabold tabular-nums text-alerta-500">
                        {{ $i + 1 }}
                    </span>
                    <p>
                        <strong class="block font-titulo text-lg font-bold">{{ $titulo }}</strong>
                        <span class="text-sm text-tinta-600">{{ $texto }}</span>
                    </p>
                </li>
            @endforeach
        </ol>

        @if (config('portada.modulo_clientes'))
            <a href="{{ auth()->check() ? route('cuenta') : route('registro') }}"
               class="mt-10 inline-block rounded-lg bg-alerta-500 px-8 py-3 font-semibold text-white transition hover:bg-alerta-600">
                {{ auth()->check() ? 'Ir a mi cuenta' : 'Crear mi cuenta' }}
            </a>
        @else
            {{-- Mientras no exista el registro, la salida honesta es el teléfono:
                 el equipo ya lleva estos historiales a mano. --}}
            <div class="mt-10 rounded-xl border border-tinta-200 bg-white p-6">
                <p class="font-semibold">Todavía no está abierto el registro en línea</p>
                <p class="mt-2 text-sm text-tinta-600">
                    Estamos terminándolo. Mientras tanto, llámanos y un asesor te lleva el
                    control de los mantenimientos de tu carro.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="tel:{{ $contacto->pbxTel() }}"
                       class="rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                        Llamar al {{ $contacto->pbx() }}
                    </a>
                    <a href="{{ route('catalogo') }}"
                       class="rounded-lg border border-tinta-300 px-6 py-3 font-semibold text-tinta-700 transition hover:bg-tinta-100">
                        Ver el catálogo
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
