{{--
    419 — la página se venció.

    El peor de todos y el más fácil de arreglar. El celular se queda en el
    bolsillo dos horas, el mecánico vuelve, pulsa «Enviar mi solicitud» y antes
    veía una pantalla blanca que decía «Page Expired».

    Lo importante es lo que NADIE le estaba diciendo: su cotización no se
    perdió. Sigue en la sesión, entera. Por eso el botón principal lleva ahí.
--}}
@extends('errors.layout')

@section('titulo', 'Se venció la página')
@section('codigo', '419')
@section('encabezado', 'Se venció la página por seguridad')

@section('explicacion')
    <p><strong class="font-semibold text-tinta-800">Tu cotización sigue completa.</strong>
        Esto pasa cuando la página estuvo abierta mucho rato. Vuelve a abrirla y envíala otra vez.</p>
@endsection

@section('salidas')
    <a href="{{ route('cotizacion.ver') }}"
       class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-marca-800">
        Ver mi cotización
    </a>
    <a href="{{ route('inicio') }}"
       class="rounded-lg border border-tinta-300 bg-white px-6 py-3 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
        Ir al inicio
    </a>
@endsection
