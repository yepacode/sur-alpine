{{--
    404.

    Es el error que más se ve: el sitemap publica 29.272 fichas y basta con que
    el equipo retire una pieza para que su enlace —el que un asesor ya mandó por
    WhatsApp— deje de existir. Por eso la salida principal no es «volver», es
    «busca tu carro»: quien llegó aquí venía por un repuesto.
--}}
@extends('errors.layout')

@section('titulo', 'No encontramos esa página')
@section('codigo', '404')
@section('encabezado', 'Esta página no existe o ya no está')

@section('explicacion')
    <p>Puede que el enlace esté mal copiado, o que hayamos retirado esa pieza del catálogo.
        Lo que buscas casi seguro lo tenemos: son más de 29.000 repuestos.</p>
@endsection

@section('salidas')
    <a href="{{ route('catalogo') }}"
       class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-marca-800">
        Buscar mi repuesto
    </a>
    <a href="{{ route('inicio') }}"
       class="rounded-lg border border-tinta-300 bg-white px-6 py-3 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
        Ir al inicio
    </a>
@endsection

@section('explicacion-panel')
    <p>Esa pantalla o ese registro ya no existe. Puede que lo hayan borrado, o que el enlace esté mal copiado.</p>
@endsection
