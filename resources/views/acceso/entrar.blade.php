@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

{{-- Nada de esto tiene por qué salir en Google: o es privado, o es un
     paso intermedio. Salían todas `index,follow`. --}}
@section('robots', 'noindex, nofollow')
@section('descripcion', 'Acceso al área del cliente y al panel de Importadora Sur Alpine.')

{{--
    Iniciar sesión.

    El reparto es el suyo —formulario a la izquierda, la foto del motor con su
    corte en diagonal a la derecha, botones azules apilados— pero con más
    cuerpo: la primera versión copiaba también sus medidas y la página se veía
    diminuta y perdida en el blanco. Aquí el formulario respira a 440 px, los
    campos y los botones miden 52 px de alto, y la foto sangra hasta el borde
    de la pantalla en vez de quedarse dentro del contenedor.

    El corte en diagonal no es CSS: viene recortado en el propio archivo, igual
    que en su web.
--}}
@section('contenido')
    {{-- `grow` y no una altura calculada: `main` es la caja flexible y esta
         sección se estira hasta el pie, mida lo que mida la cabecera. Antes
         iba `min-h-[calc(100svh-136px)]`, y como la cabecera acabó midiendo
         128, quedaba una franja blanca muerta entre el formulario y el pie
         —y en móvil, donde esa regla ni siquiera aplicaba, era peor. --}}
    <section class="relative grow overflow-hidden">
        <div class="grid h-full items-stretch md:grid-cols-[1fr_minmax(0,46%)]">

            {{-- ─── Formulario ─────────────────────────────────────────── --}}
            <div class="flex items-center justify-center px-[3vw] py-12 md:py-16">
                <div class="w-full max-w-[440px]">
                    <h1 class="text-[2.25rem] font-bold leading-tight text-tinta-900 sm:text-[2.75rem]">
                        Inicia sesión
                    </h1>
                    <p class="mt-2 text-tinta-500">
                        Tus vehículos, tus mantenimientos y tus cotizaciones, en un solo lugar.
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

                    @php
                        $campo = 'mt-1.5 h-[52px] w-full rounded-lg border border-tinta-200 bg-white px-4 text-base text-tinta-900 transition placeholder:text-tinta-400 hover:border-tinta-300 focus:border-marca-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600';
                        $rotulo = 'block text-base font-semibold text-marca-700';
                        // Los cuatro botones son el mismo botón: mismo alto,
                        // mismo ancho, mismo azul. En su página también, sólo
                        // que allá miden 210 px y aquí ocupan la columna.
                        $boton = 'flex h-[52px] w-full items-center justify-center gap-3 rounded-lg text-base font-bold transition';
                    @endphp

                    <form method="post" action="{{ route('entrar') }}" class="mt-7 space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="{{ $rotulo }}">
                                Correo electrónico <span class="text-alerta-500" aria-hidden="true">*</span>
                            </label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required @if (! $errors->any()) autofocus @endif
                                   autocomplete="username" placeholder="example@mail.com" class="{{ $campo }}">
                        </div>

                        {{-- El ojo para ver lo que se escribe, como en su
                             formulario. Aquí es un `button` y no un `<i>`: el
                             suyo no se puede pulsar con el teclado ni lo
                             anuncia un lector de pantalla. --}}
                        <div x-data="{ visible: false }">
                            <label for="password" class="{{ $rotulo }}">
                                Contraseña <span class="text-alerta-500" aria-hidden="true">*</span>
                            </label>
                            <div class="relative">
                                <input id="password" :type="visible ? 'text' : 'password'" type="password" name="password" required
                                       autocomplete="current-password" placeholder="···············" class="{{ $campo }} pr-12">
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

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="flex items-center gap-2.5 text-base text-tinta-700">
                                <input type="checkbox" name="recordarme" value="1" class="size-4 rounded border-tinta-300 text-marca-700">
                                No cerrar sesión en este equipo
                            </label>

                            {{-- Va aquí, al lado del campo donde se atasca la
                                 gente, y no escondido abajo con los legales. --}}
                            <a href="{{ route('clave.pedir') }}"
                               class="text-base font-semibold text-marca-700 underline-offset-2 hover:underline">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <button type="submit" class="{{ $boton }} con-luz bg-marca-700 text-white shadow-lg shadow-marca-700/20 hover:bg-marca-800">
                            {{ contenido('acceso.entrar.boton', 'Iniciar sesión') }}
                        </button>
                    </form>

                    <x-acceso-social :clase="$boton" />

                    @if (config('portada.modulo_clientes'))
                        <div class="mt-6 flex items-center gap-3" aria-hidden="true">
                            <span class="h-px flex-1 bg-tinta-200"></span>
                            <span class="text-xs font-semibold uppercase tracking-wider text-tinta-400">¿Aún no tienes cuenta?</span>
                            <span class="h-px flex-1 bg-tinta-200"></span>
                        </div>

                        {{-- Registrarse no es iniciar sesión: va en contorno y no
                             en relleno, para que el botón azul de arriba siga
                             siendo el que la mayoría busca. --}}
                        <a href="{{ route('registro') }}"
                           class="{{ $boton }} mt-4 border-2 border-marca-700 text-marca-700 hover:bg-marca-50">
                            Regístrate
                        </a>
                    @endif

                    <p class="mt-8 text-sm text-tinta-500">
                        <a href="{{ route('politica-datos') }}" class="hover:underline">Política de tratamiento de datos</a>
                    </p>
                </div>
            </div>

            {{-- ─── Foto ───────────────────────────────────────────────────
                 Sangra hasta el borde derecho de la pantalla; la diagonal viene
                 recortada en el archivo. Fuera en móvil: allá también se
                 esconde, y en un teléfono sólo le quitaría sitio al formulario. --}}
            {{-- La diagonal se hace aquí y no con el recorte del archivo: el
                 archivo la trae, pero al encajarlo en una columna alta el
                 recorte queda fuera de cuadro y el corte se pierde. Con
                 `clip-path` la diagonal se mantiene mida lo que mida la
                 pantalla, y la foto puede centrarse en el motor. --}}
            <div class="relative hidden md:block [clip-path:polygon(14%_0,100%_0,100%_100%,0_100%)]">
                @php $fotoAcceso = imagen_contenido('acceso.imagen', '/img/acceso/acceso-motor'); @endphp
                <img src="{{ $fotoAcceso }}-700.webp"
                     srcset="{{ $fotoAcceso }}-480.webp 480w, {{ $fotoAcceso }}-700.webp 700w"
                     sizes="(min-width: 768px) 46vw, 0px"
                     width="700" height="781" alt="" loading="lazy" decoding="async"
                     class="absolute inset-0 size-full object-cover object-center">
            </div>
        </div>
    </section>
@endsection
