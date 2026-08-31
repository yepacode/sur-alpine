@extends('layouts.app')

@section('titulo', 'Olvidé mi contraseña')
@section('descripcion', 'Recupera el acceso a tu cuenta de Importadora Sur Alpine.')

{{-- Sin indexar: es una pantalla de servicio, no una página del sitio. --}}
{{-- Por `@section` y no por `@push`: con el push quedaban DOS etiquetas
     `robots` contradictorias en el mismo `<head>`. --}}
@section('robots', 'noindex, nofollow')

@section('contenido')
    <section class="grow bg-tinta-50">
        <div class="mx-auto flex max-w-[520px] flex-col justify-center px-[3vw] py-14 md:py-20">

            <a href="{{ route('acceso') }}" class="text-sm font-medium text-marca-700 hover:underline">
                <span aria-hidden="true">←</span> Volver a iniciar sesión
            </a>

            <h1 class="mt-5 text-[2rem] font-bold leading-tight text-tinta-900 sm:text-[2.5rem]">
                ¿Olvidaste tu contraseña?
            </h1>
            <p class="mt-2 text-tinta-600">
                Escribe el correo con el que te registraste y te mandamos un enlace
                para crear una nueva.
            </p>

            @if (session('mensaje'))
                <div role="status" class="mt-6 rounded-lg border-l-4 border-marca-500 bg-marca-50 px-4 py-3 text-sm text-marca-900">
                    {{ session('mensaje') }}
                </div>
            @endif

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

            <form method="post" action="{{ route('clave.enviar') }}" class="mt-7">
                @csrf

                <label for="email" class="block text-base font-semibold text-marca-700">
                    Correo electrónico <span class="text-alerta-500" aria-hidden="true">*</span>
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required @if (! $errors->any()) autofocus @endif
                       autocomplete="username" placeholder="example@mail.com"
                       class="mt-1.5 h-[52px] w-full rounded-lg border border-tinta-200 bg-white px-4 text-base text-tinta-900 transition placeholder:text-tinta-400 hover:border-tinta-300 focus:border-marca-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600">

                <button type="submit"
                        class="con-luz mt-5 flex h-[52px] w-full items-center justify-center rounded-lg bg-marca-700 text-base font-bold text-white shadow-lg shadow-marca-700/20 transition hover:bg-marca-800">
                    Enviarme el enlace
                </button>
            </form>

            {{-- El taller contesta el teléfono más rápido que el correo. Quien
                 ya no tiene acceso a su bandeja no se queda sin salida. --}}
            <p class="mt-8 text-sm text-tinta-500">
                ¿Ya no usas ese correo? Llámanos al
                <a href="tel:{{ $contacto->pbxTel() }}" class="font-semibold text-marca-700 hover:underline">
                    PBX {{ $contacto->pbx() }}
                </a>
                y te ayudamos.
            </p>
        </div>
    </section>
@endsection
