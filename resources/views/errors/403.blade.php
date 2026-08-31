{{--
    403.

    Antes era una pantalla negra con «403 | TU ROL NO TIENE ACCESO A ESTA
    SECCIÓN» en mayúsculas, sin logo y sin un solo enlace: un callejón sin
    salida dentro del propio panel.
--}}
@extends('errors.layout')

@section('titulo', 'Sin acceso a esta sección')
@section('codigo', '403')
@section('encabezado', 'Tu cuenta no tiene acceso a esta sección')

@section('explicacion')
    <p>Si crees que deberías poder entrar, pídele a un administrador que revise tu rol.</p>
@endsection

@section('salidas')
    @auth
        @if (auth()->user()->entraAlPanel())
            <a href="{{ route('panel.tablero') }}"
               class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-marca-800">
                Volver al panel
            </a>
        @else
            <a href="{{ route('cuenta') }}"
               class="rounded-lg bg-marca-700 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-marca-800">
                Ir a mi cuenta
            </a>
        @endif
    @endauth
    <a href="{{ route('inicio') }}"
       class="rounded-lg border border-tinta-300 bg-white px-6 py-3 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
        Ir al inicio
    </a>
@endsection
