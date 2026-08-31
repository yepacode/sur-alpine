{{--
    429 — demasiados intentos.

    Lo alcanza cualquiera que reintente un formulario que le falló: contacto
    está limitado a 5 por minuto. El mensaje tiene que decir cuánto esperar y
    dar la salida que no está limitada, que es el teléfono.
--}}
@extends('errors.layout')

@section('titulo', 'Demasiados intentos')
@section('codigo', '429')
@section('encabezado', 'Espera un momento antes de volver a intentar')

@section('explicacion')
    <p>Recibimos varios envíos seguidos desde tu conexión y los frenamos un minuto.
        No es nada que hayas hecho mal: es para que nadie llene el buzón del mostrador.</p>
    <p class="mt-2">Si es urgente, llámanos y te atendemos de una vez.</p>
@endsection

@section('salidas')
    <a href="{{ url()->previous() }}"
       class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-marca-800">
        Volver e intentar en un minuto
    </a>
@endsection
