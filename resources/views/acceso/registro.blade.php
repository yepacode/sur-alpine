@extends('layouts.app')

@section('titulo', 'Crear mi cuenta')
@section('descripcion', 'Crea tu cuenta en Sur Alpine y lleva el historial de mantenimiento de tu carro: kilometraje, fechas y avisos del próximo cambio.')

@section('contenido')
    <div class="mx-auto max-w-md px-4 py-12">
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Crear mi cuenta</h1>
        <p class="mt-2 text-tinta-600">
            Para llevar el historial de mantenimiento de tus carros. Es gratis y toma un minuto.
        </p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('registro.crear') }}" class="mt-8 space-y-4">
            @csrf
            @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600'; @endphp

            {{-- Campo trampa: invisible para una persona, irresistible para un robot. --}}
            <div class="hidden" aria-hidden="true">
                <label for="sitio_web">No llenar</label>
                <input id="sitio_web" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="name" class="text-sm font-medium">Nombre</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="{{ $campo }}">
            </div>

            <div>
                <label for="telefono" class="text-sm font-medium">Teléfono</label>
                <input id="telefono" name="telefono" value="{{ old('telefono') }}" required
                       inputmode="tel" autocomplete="tel" class="{{ $campo }}">
                <p class="mt-1 text-xs text-tinta-500">Es por donde te contactamos cuando cotices.</p>
            </div>

            <div>
                <label for="email" class="text-sm font-medium">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       autocomplete="email" class="{{ $campo }}">
            </div>

            <div>
                <label for="password" class="text-sm font-medium">Contraseña</label>
                <input id="password" type="password" name="password" required minlength="8"
                       autocomplete="new-password" class="{{ $campo }}">
                <p class="mt-1 text-xs text-tinta-500">Mínimo 8 caracteres.</p>
            </div>

            <div>
                <label for="password_confirmation" class="text-sm font-medium">Repetir contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       autocomplete="new-password" class="{{ $campo }}">
            </div>

            <label class="flex items-start gap-2 pt-2 text-sm text-tinta-600">
                <input type="checkbox" name="acepta" value="1" @checked(old('acepta'))
                       class="mt-0.5 size-4 shrink-0 rounded border-tinta-300 text-marca-700">
                Autorizo el tratamiento de mis datos para que Sur Alpine me contacte.
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-alerta-500 px-6 py-3 font-semibold text-white transition hover:bg-alerta-600">
                Crear mi cuenta
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-tinta-600">
            ¿Ya tienes cuenta?
            <a href="{{ route('acceso') }}" class="font-semibold text-marca-700 hover:underline">Inicia sesión</a>
        </p>
    </div>
@endsection
