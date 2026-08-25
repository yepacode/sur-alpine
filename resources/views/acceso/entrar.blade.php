@extends('layouts.app')

@section('titulo', 'Iniciar sesión')
@section('descripcion', 'Acceso al área del cliente y al panel de Importadora Sur Alpine.')

@section('contenido')
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-marca-900 via-tinta-900 to-noche" aria-hidden="true"></div>
        <div class="aurora absolute -left-32 top-0 size-[34rem] rounded-full bg-marca-500/25 blur-[90px]" aria-hidden="true"></div>
        <div class="aurora aurora-lenta absolute -right-24 -bottom-32 size-[26rem] rounded-full bg-alerta-500/20 blur-[100px]" aria-hidden="true"></div>
        <div class="absolute inset-0 opacity-[0.06] [background-image:linear-gradient(to_right,white_1px,transparent_1px),linear-gradient(to_bottom,white_1px,transparent_1px)] [background-size:56px_56px]" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:py-20 lg:grid-cols-[1.05fr_1fr] lg:items-center lg:gap-16">

            {{-- Columna izquierda: mensaje que le da a la página aire de marca
                 en vez de ser una caja con dos campos. --}}
            <div class="text-white">
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-marca-300">Área del cliente y del equipo</p>
                <h1 class="mt-3 text-[2rem] font-extrabold leading-[1.02] text-white text-balance sm:text-[2.75rem]">
                    Entra a<br>
                    <span class="text-marca-300">tu rincón</span>
                </h1>
                <p class="mt-5 max-w-lg text-lg text-marca-100">
                    Tus vehículos, tus mantenimientos y tus cotizaciones —o la bandeja
                    y el catálogo, si eres del equipo de Sur Alpine.
                </p>

                <ul class="mt-8 space-y-3 text-sm text-marca-100">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 grid size-6 shrink-0 place-items-center rounded-full bg-marca-500/20 text-marca-200">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" class="size-3.5"><polyline points="4 11 8 15 16 6"/></svg>
                        </span>
                        <span>Guarda tus carros con placa y alias.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 grid size-6 shrink-0 place-items-center rounded-full bg-marca-500/20 text-marca-200">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" class="size-3.5"><polyline points="4 11 8 15 16 6"/></svg>
                        </span>
                        <span>Lleva el historial de cada mantenimiento.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 grid size-6 shrink-0 place-items-center rounded-full bg-marca-500/20 text-marca-200">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" class="size-3.5"><polyline points="4 11 8 15 16 6"/></svg>
                        </span>
                        <span>Recibe recordatorios antes del próximo cambio.</span>
                    </li>
                </ul>
            </div>

            {{-- Columna derecha: la tarjeta con el formulario. Vive sobre el
                 mismo fondo, apoyada en un panel de vidrio. --}}
            <div class="relative">
                <div class="rounded-2xl bg-white/95 p-6 shadow-2xl shadow-noche/40 backdrop-blur sm:p-8">

                    {{-- Un aviso puesto en sesión por, por ejemplo, `salir()` o el
                         middleware `cuenta.activa`. Va arriba del formulario. --}}
                    @if (session('mensaje'))
                        <div role="status" class="mb-5 rounded-lg border border-marca-200 bg-marca-50 p-3 text-sm text-marca-800">
                            {{ session('mensaje') }}
                        </div>
                    @endif

                    <h2 class="font-titulo text-2xl font-bold text-tinta-900">Iniciar sesión</h2>
                    <p class="mt-1 text-sm text-tinta-500">
                        Entra con el correo y la contraseña de tu cuenta.
                    </p>

                    @if ($errors->any())
                        <div role="alert" class="mt-5 rounded-lg border border-alerta-500 bg-alerta-500/5 p-3 text-sm text-alerta-700">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="post" action="{{ route('entrar') }}" class="mt-5 space-y-4">
                        @csrf
                        @php $campo = 'mt-1 w-full rounded-xl border-2 border-tinta-200 px-3.5 py-3 text-sm text-tinta-900 transition hover:border-tinta-300 focus:border-marca-600 focus:outline-none'; @endphp

                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-tinta-500">Correo electrónico</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                   autocomplete="username" class="{{ $campo }}">
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-tinta-500">Contraseña</label>
                            <input id="password" type="password" name="password" required
                                   autocomplete="current-password" class="{{ $campo }}">
                        </div>

                        <label class="flex items-center gap-2 text-sm text-tinta-700">
                            <input type="checkbox" name="recordarme" value="1" class="size-4 rounded border-tinta-300 text-marca-700">
                            Recordarme en este equipo
                        </label>

                        <button type="submit"
                                class="con-luz w-full rounded-xl bg-alerta-500 px-6 py-3.5 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-white shadow-lg shadow-alerta-500/25 transition hover:bg-alerta-600">
                            {{ contenido('acceso.entrar.boton', 'Entrar') }}
                        </button>
                    </form>

                    @if (config('portada.modulo_clientes'))
                        <p class="mt-6 text-center text-sm text-tinta-600">
                            ¿No tienes cuenta?
                            <a href="{{ route('registro') }}" class="font-semibold text-marca-700 hover:underline">Créala aquí</a>
                        </p>
                    @endif

                    <p class="mt-3 text-center text-xs text-tinta-500">
                        <a href="{{ route('politica-datos') }}" class="hover:underline">Política de tratamiento de datos</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
