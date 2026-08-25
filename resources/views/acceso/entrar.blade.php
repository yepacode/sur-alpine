{{-- Con el módulo de clientes activo, quien llega sin cuenta necesita ver la
     salida: antes esta página era un callejón sin salida para un mecánico. --}}
@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="mx-auto max-w-md px-4 py-16">
        <h1 class="text-2xl font-bold tracking-tight">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-tinta-500">Para el equipo de Sur Alpine y clientes registrados.</p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('entrar') }}" class="mt-6 space-y-4">
            @csrf
            @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600 focus:outline-none'; @endphp

            <div>
                <label for="email" class="text-sm font-medium">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" class="{{ $campo }}">
            </div>

            <div>
                <label for="password" class="text-sm font-medium">Contraseña</label>
                <input id="password" type="password" name="password" required
                       autocomplete="current-password" class="{{ $campo }}">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="recordarme" value="1" class="size-4 rounded border-tinta-300 text-marca-700">
                Recordarme en este equipo
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-marca-700 px-5 py-3 font-semibold text-white hover:bg-marca-800">
                Entrar
            </button>
        </form>

        @if (config('portada.modulo_clientes'))
            <p class="mt-6 text-center text-sm text-tinta-600">
                ¿No tienes cuenta?
                <a href="{{ route('registro') }}" class="font-semibold text-marca-700 hover:underline">Créala aquí</a>
            </p>
        @endif
    </div>
@endsection
