@extends('layouts.app')

@section('titulo', 'Crear una contraseña nueva')

{{-- Por `@section` y no por `@push`: con el push quedaban DOS etiquetas
     `robots` contradictorias en el mismo `<head>`. --}}
@section('robots', 'noindex, nofollow')

@section('contenido')
    @php
        $campo = 'mt-1.5 h-[52px] w-full rounded-lg border border-tinta-200 bg-white px-4 text-base text-tinta-900 transition placeholder:text-tinta-400 hover:border-tinta-300 focus:border-marca-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600';
        $rotulo = 'block text-base font-semibold text-marca-700';
    @endphp

    <section class="grow bg-tinta-50">
        <div class="mx-auto flex max-w-[520px] flex-col justify-center px-[3vw] py-14 md:py-20">

            <h1 class="text-[2rem] font-bold leading-tight text-tinta-900 sm:text-[2.5rem]">
                Crea tu contraseña nueva
            </h1>
            <p class="mt-2 text-tinta-600">
                Escríbela dos veces para que no quede un dedazo dentro.
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

            <form method="post" action="{{ route('clave.restablecer') }}" class="mt-7 space-y-5"
                  x-data="{ visible: false }">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="{{ $rotulo }}">
                        Correo electrónico <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    {{-- Viene del enlace y se muestra, no se esconde: quien tiene
                         dos correos necesita ver a cuál cuenta le está cambiando
                         la contraseña. --}}
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required
                           readonly autocomplete="username"
                           class="{{ $campo }} bg-tinta-100 text-tinta-700">
                </div>

                <div>
                    <label for="password" class="{{ $rotulo }}">
                        Contraseña nueva <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <div class="relative">
                        <input id="password" :type="visible ? 'text' : 'password'" type="password" name="password"
                               required @if (! $errors->any()) autofocus @endif autocomplete="new-password" minlength="8"
                               placeholder="Al menos 8 caracteres" class="{{ $campo }} pr-12">
                        <button type="button" @click="visible = ! visible"
                                :aria-label="visible ? 'Ocultar la contraseña' : 'Mostrar la contraseña'"
                                :aria-pressed="visible"
                                class="absolute inset-y-0 right-0 mt-1.5 grid w-12 place-items-center text-tinta-400 hover:text-tinta-700">
                            <svg x-show="! visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg x-show="visible" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7c2 0 3.7.7 5.2 1.6M22 12s-3.6 7-10 7c-2 0-3.8-.7-5.3-1.6" stroke-linecap="round"/>
                                <path d="m3 3 18 18" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $rotulo }}">
                        Repite la contraseña <span class="text-alerta-500" aria-hidden="true">*</span>
                    </label>
                    <input id="password_confirmation" :type="visible ? 'text' : 'password'" type="password"
                           name="password_confirmation" required autocomplete="new-password" minlength="8"
                           placeholder="La misma de arriba" class="{{ $campo }}">
                </div>

                <button type="submit"
                        class="con-luz flex h-[52px] w-full items-center justify-center rounded-lg bg-marca-700 text-base font-bold text-white shadow-lg shadow-marca-700/20 transition hover:bg-marca-800">
                    Guardar y volver a entrar
                </button>
            </form>
        </div>
    </section>
@endsection
