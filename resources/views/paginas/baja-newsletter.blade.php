@extends('layouts.app')

@section('titulo', 'Te sacamos de la lista')

{{-- El canonical apunta al inicio a propósito: la URL de esta página lleva el
     correo de la persona, y no tiene por qué acabar en una etiqueta que se
     copia, se comparte y se indexa. --}}
@section('canonical', route('inicio'))

@section('robots', 'noindex, nofollow')

@section('contenido')
    <section class="grow">
        <div class="mx-auto max-w-xl px-[3vw] py-20 text-center">
            <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">{{ contenido('baja.antetitulo', 'Newsletter') }}</p>
            <h1 class="mt-2 text-[2rem] font-bold leading-tight text-tinta-900 sm:text-[2.5rem]">
                Listo, te sacamos de la lista
            </h1>

            <p class="mt-4 text-tinta-600">
                No volveremos a escribirte al boletín. Si alguna vez cambias de opinión,
                puedes suscribirte otra vez desde el pie de cualquier página.
            </p>

            {{-- Lo que se sigue enviando NO es el boletín, y conviene decirlo:
                 si la persona cree que se dio de baja de todo y luego recibe la
                 confirmación de su cotización, marca el correo como no deseado
                 y perdemos también los que sí pidió. --}}
            <p class="mt-3 text-sm text-tinta-500">
                Esto no afecta los correos de tus cotizaciones ni los avisos de
                mantenimiento: ésos los pediste tú y siguen llegando.
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('inicio') }}"
                   class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white transition hover:bg-marca-800">
                    Volver al inicio
                </a>
                <a href="tel:{{ $contacto->pbxTel() }}"
                   class="rounded-lg border border-tinta-300 px-6 py-3 font-semibold text-tinta-700 transition hover:bg-tinta-100">
                    Llamar al {{ $contacto->pbx() }}
                </a>
            </div>
        </div>
    </section>
@endsection
