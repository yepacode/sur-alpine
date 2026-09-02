{{--
    500.

    Aquí no se explica nada técnico —no le sirve a nadie— pero sí se dice lo
    único que le importa a quien estaba cotizando: que llame, que del otro lado
    hay gente.
--}}
@extends('errors.layout')

@section('titulo', 'Tuvimos un problema')
@section('codigo', '500')
@section('encabezado', 'Se nos cayó algo de nuestro lado')

@section('explicacion')
    <p>No es tu conexión ni algo que hayas hecho. Ya quedó registrado y lo estamos mirando.
        Intenta de nuevo en un momento.</p>
@endsection

@section('salidas')
    <a href="{{ route('inicio') }}"
       class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-marca-800">
        Ir al inicio
    </a>
@endsection

@section('explicacion-panel')
    <p>Algo falló de nuestro lado. Ya quedó registrado y lo estamos mirando; si te urge, avísanos.</p>
@endsection
