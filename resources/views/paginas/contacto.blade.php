@extends('layouts.app')

@section('titulo', 'Contáctenos')

@section('contenido')
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold tracking-tight">Contáctenos</h1>
        <p class="mt-4 text-tinta-600">{{ $contacto->direccionCompleta() }}</p>
        <ul class="mt-4 space-y-1 tabular-nums text-tinta-700">
            <li><a href="tel:{{ $contacto->pbxTel() }}" class="hover:underline">PBX {{ $contacto->pbx() }}</a></li>
            @foreach ($contacto->celulares() as $celular)
                <li><a href="tel:{{ $celular['tel'] }}" class="hover:underline">{{ $celular['texto'] }}</a></li>
            @endforeach
        </ul>
        <p class="mt-8 rounded-xl bg-tinta-100 p-5 text-sm text-tinta-600">
            El formulario de contacto y el mapa llegan en la tarea M1.5.4.
        </p>
    </div>
@endsection
