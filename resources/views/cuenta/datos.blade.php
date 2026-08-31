@extends('layouts.app')

@section('titulo', 'Mis datos')

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')

{{--
    Dos formularios y no uno.

    Si fueran el mismo, corregir un teléfono obligaría a escribir la
    contraseña, y cambiar la contraseña obligaría a repasar el teléfono. Son
    dos gestos distintos y se hacen por separado.
--}}
@section('contenido')
    @php
        $campo = 'mt-1.5 h-[52px] w-full rounded-lg border border-tinta-200 bg-white px-4 text-base text-tinta-900 transition placeholder:text-tinta-400 hover:border-tinta-300 focus:border-marca-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600';
        $rotulo = 'block text-base font-semibold text-marca-700';
        $boton = 'flex h-[52px] w-full items-center justify-center rounded-lg bg-marca-700 text-base font-bold text-white transition hover:bg-marca-800 sm:w-auto sm:px-8';
        $conClave = $usuario->password !== null;
    @endphp

    <div class="mx-auto max-w-2xl px-4 py-10">

        <a href="{{ route('cuenta') }}" class="text-sm font-medium text-marca-700 hover:underline">
            <span aria-hidden="true">←</span> Mi cuenta
        </a>

        <p class="mt-4 font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">Mi cuenta</p>
        <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">Mis datos</h1>
        <p class="mt-1 text-tinta-600">
            Con estos te contactamos cuando pides una cotización.
        </p>

        @if ($errors->any())
            {{-- El aviso se enfoca al cargar: un `role="alert"` que ya viene en el
     HTML no lo anuncia ningún lector de pantalla —la región viva sirve
     para lo que aparece DESPUÉS—, y aquí el foco se quedaba en el `body`
     o se lo llevaba el `autofocus` del primer campo. --}}
                <div role="alert" tabindex="-1" x-data x-init="$el.focus()" class="mt-6 rounded-lg border-l-4 border-alerta-500 bg-alerta-500/5 px-4 py-3 text-sm text-alerta-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('mensaje'))
            <div role="status" class="mt-6 rounded-lg border-l-4 border-marca-500 bg-marca-50 px-4 py-3 text-sm text-marca-900">
                {{ session('mensaje') }}
            </div>
        @endif

        {{-- Confirmar el correo se ofrece, no se exige: no hay nada cerrado
             detrás. Lo que resuelve es que un dedazo en la dirección se
             descubra ahora y no cuando rebote la confirmación de una
             cotización. --}}
        @unless ($usuario->hasVerifiedEmail())
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-alerta-200 bg-alerta-50/50 px-5 py-4">
                <div>
                    <p class="font-semibold text-tinta-900">Tu correo no está confirmado</p>
                    <p class="mt-1 text-sm text-tinta-600">
                        Confírmalo y así sabemos que las respuestas a tus cotizaciones te llegan.
                    </p>
                </div>
                <form method="post" action="{{ route('verificacion.reenviar') }}" class="shrink-0">
                    @csrf
                    <button type="submit"
                            class="rounded-lg border border-alerta-300 bg-white px-5 py-2.5 text-sm font-semibold text-alerta-700 transition hover:bg-alerta-100">
                        Enviarme el enlace
                    </button>
                </form>
            </div>
        @endunless

        {{-- ─── Nombre, teléfono y correo ──────────────────────────────── --}}
        <section class="mt-6 rounded-2xl border border-tinta-200 bg-white p-6 shadow-sm sm:p-7">
            <h2 class="font-titulo text-lg font-bold">Datos de contacto</h2>

            <form method="post" action="{{ route('cuenta.datos.guardar') }}" class="mt-5 space-y-5"
                  x-data="{ correo: @js($usuario->email) }">
                @csrf

                <div>
                    <label for="name" class="{{ $rotulo }}">
                        Nombre completo <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <input id="name" name="name" value="{{ old('name', $usuario->name) }}" required
                           maxlength="120" autocomplete="name" class="{{ $campo }}">
                </div>

                <div>
                    <label for="telefono" class="{{ $rotulo }}">
                        Teléfono <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <input id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" required
                           maxlength="30" inputmode="tel" autocomplete="tel" class="{{ $campo }}">
                    <p class="mt-1.5 text-sm text-tinta-500">Es por donde te devolvemos la llamada.</p>
                </div>

                <div>
                    <label for="email" class="{{ $rotulo }}">
                        Correo electrónico <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email', $usuario->email) }}" required
                           maxlength="120" autocomplete="email" x-model="correo" class="{{ $campo }}">
                </div>

                {{-- Sólo aparece si de verdad está cambiando el correo. Pedirla
                     siempre convertiría en un trámite corregir un teléfono. --}}
                @if ($conClave)
                    <div x-show="correo.toLowerCase() !== @js(mb_strtolower($usuario->email))" x-cloak
                         x-transition.opacity>
                        <label for="password_actual" class="{{ $rotulo }}">
                            Confirma tu contraseña actual <span class="text-alerta-500" aria-hidden="true">*</span>
                        </label>
                        <input id="password_actual" type="password" name="password_actual"
                               autocomplete="current-password" class="{{ $campo }}">
                        <p class="mt-1.5 text-sm text-tinta-500">
                            Cambiar el correo es cambiar por dónde se recupera la cuenta, así que
                            preferimos asegurarnos de que eres tú.
                        </p>
                    </div>
                @endif

                <button type="submit" class="{{ $boton }} con-luz">Guardar cambios</button>
            </form>
        </section>

        {{-- ─── Contraseña ─────────────────────────────────────────────── --}}
        <section class="mt-6 rounded-2xl border border-tinta-200 bg-white p-6 shadow-sm sm:p-7">
            <h2 class="font-titulo text-lg font-bold">
                {{ $conClave ? 'Cambiar mi contraseña' : 'Ponerle una contraseña a mi cuenta' }}
            </h2>

            @unless ($conClave)
                {{-- Entró con Facebook o Google: nunca ha tenido contraseña. --}}
                <p class="mt-2 text-sm text-tinta-600">
                    Entraste con {{ ucfirst($usuario->proveedor ?? 'un proveedor') }}, así que tu cuenta
                    todavía no tiene contraseña. Ponerle una te deja entrar también con tu correo.
                </p>
            @endunless

            <form method="post" action="{{ route('cuenta.clave') }}" class="mt-5 space-y-5">
                @csrf

                @if ($conClave)
                    <div>
                        <label for="clave_actual" class="{{ $rotulo }}">
                            Contraseña actual <span class="text-alerta-500" aria-hidden="true">*</span>
                        </label>
                        <input id="clave_actual" type="password" name="password_actual" required
                               autocomplete="current-password" class="{{ $campo }}">
                    </div>
                @endif

                <div>
                    <label for="password" class="{{ $rotulo }}">
                        Contraseña nueva <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <input id="password" type="password" name="password" required minlength="8"
                           autocomplete="new-password" placeholder="Al menos 8 caracteres" class="{{ $campo }}">
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $rotulo }}">
                        Repite la contraseña nueva <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           minlength="8" autocomplete="new-password" placeholder="La misma de arriba" class="{{ $campo }}">
                </div>

                <button type="submit" class="{{ $boton }}">
                    {{ $conClave ? 'Cambiar contraseña' : 'Crear contraseña' }}
                </button>

                <p class="text-sm text-tinta-500">
                    Al cambiarla se cierran las sesiones abiertas en otros dispositivos.
                </p>
            </form>
        </section>

        {{-- Habeas Data · el otro derecho. Cerrar la cuenta ya estaba en «Mi
             cuenta»; consultar lo que tenemos suyo faltaba, y la política que
             ya está publicada lo promete. --}}
        <section class="mt-6 rounded-2xl border border-tinta-200 bg-white p-6 shadow-sm sm:p-7">
            <h2 class="font-titulo text-lg font-bold">Mis datos, para llevar</h2>
            <p class="mt-2 max-w-prose text-sm text-tinta-600">
                Descarga todo lo que tenemos guardado tuyo: tu cuenta, tus carros, tus
                mantenimientos y tus cotizaciones. Es un archivo de datos, no un
                documento para imprimir.
            </p>

            <a href="{{ route('cuenta.descargar') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-lg border border-marca-300 px-5 py-2.5 text-sm font-semibold text-marca-700 transition hover:bg-marca-50">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4" aria-hidden="true">
                    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 19h16" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Descargar mis datos
            </a>
        </section>
    </div>
@endsection
