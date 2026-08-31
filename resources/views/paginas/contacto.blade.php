@extends('layouts.app')

@section('titulo', 'Contáctenos')
{{-- La dirección sale del panel, no escrita a mano: es justo el texto que
     se lee en el resultado de Google, y si el cliente se muda cambiaban la
     página y el schema pero esta línea se quedaba con el local viejo. --}}
@section('descripcion', 'Escríbenos o llámanos. Importadora Sur Alpine atiende en la '
    .rtrim(app(\App\Services\Contacto::class)->direccionCompleta(), '. ')
    .'. Parqueadero vigilado y envíos al país.')
@section('og-imagen', url('/img/cabeceras/banner-contactenos-1600.webp'))

{{--
    Contáctenos, medida sobre la suya.

    La estructura es una columna centrada de 957 px con tarjetas blancas
    apiladas —no dos columnas, que fue como la hice la primera vez y por eso no
    se parecía—:

      1. franja de cabecera de 280 px con «Contáctenos»;
      2. tarjeta blanca con el formulario: rótulos azules de 16/600, campos de
         40 px a todo el ancho de la tarjeta, botón «Enviar» azul de 174×46
         pegado a la izquierda;
      3. tarjeta blanca con «Número de contacto» y «Horarios de atención»: los
         titulares a la izquierda y los datos centrados, como los tienen ellos;
      4. «Oficinas» fuera de tarjeta, centrado, con el titular a 33,6 px;
      5. tarjeta blanca con la foto del local a la izquierda y el mapa a la
         derecha, los dos de 370 px de alto.

    Lo que cambia por dentro y no se ve: su formulario lo maneja un plugin y el
    mensaje sólo sale por correo, así que si el correo rebota nadie se entera de
    que alguien escribió. Aquí se guarda primero y se manda después.
--}}
@push('cabeza')
    <x-pagina-schema tipo="ContactPage" nombre="Contáctenos" :miga="['Contáctenos' => route('contacto')]" />
@endpush

@section('contenido')
    <x-cabecera-pagina titulo="Contáctenos" imagen="{{ imagen_contenido('contacto.imagen', '/img/cabeceras/banner-contactenos') }}" :titulo-en-la-imagen="true" />

    @php
        // La columna de ellos: 957 px centrados, y las tarjetas blancas con la
        // misma sombra suave del resto del sitio.
        $tarjeta = 'rounded-xl bg-white p-6 shadow-[0_6px_16px_rgba(0,0,0,0.08)] sm:p-8';
        // La misma tarjeta, pero sin relleno: la usa el formulario, que lleva
        // su titular sobre una banda de borde a borde.
        $conBanda = 'overflow-hidden rounded-xl bg-white shadow-[0_6px_16px_rgba(0,0,0,0.08)]';
        $titular = 'text-xl font-bold text-tinta-900';
    @endphp

    <div class="mx-auto w-[min(94vw,957px)] space-y-8 py-10">

        {{-- ─── 1 · Formulario ─────────────────────────────────────────── --}}
        <section class="{{ $conBanda }}">
            {{-- La banda clara detrás del titular es la suya: separa el
                 encabezado del formulario sin necesidad de una línea. --}}
            <h2 class="{{ $titular }} border-b border-tinta-100 bg-marca-50/60 px-6 py-4 sm:px-8">Información de contacto</h2>
            <div class="p-6 sm:p-8">

            @if (session('mensaje_enviado'))
                <p role="status"
                   class="rounded-lg border-l-4 border-emerald-500 bg-emerald-50 px-5 py-4 text-base text-emerald-900">
                    <strong>Listo, recibimos tu mensaje.</strong>
                    Te respondemos al correo que nos dejaste. Si es urgente, llámanos al
                    <a href="tel:{{ $contacto->pbxTel() }}" class="font-semibold underline underline-offset-2">{{ $contacto->pbx() }}</a>.
                </p>
            @else
                <form method="post" action="{{ route('contacto.enviar') }}" class="space-y-5">
                    @csrf

                    {{-- El resumen, enfocado al llegar.
                         Los mensajes en línea estaban bien escritos, pero nadie
                         los oía: los campos salían sin `aria-invalid` ni
                         `aria-describedby`, el foco se quedaba en el `body`, y
                         quien tabulaba al campo escuchaba «Su nombre, campo de
                         texto» y nunca el error. Este bloque se enfoca solo al
                         cargar, así que lo primero que se lee es qué falta. --}}
                    @if ($errors->any())
                        <div role="alert" tabindex="-1" x-init="$el.focus()"
                             class="rounded-lg border border-alerta-500 bg-alerta-50 p-4">
                            <p class="text-sm font-bold text-alerta-600">
                                Revisa {{ $errors->count() === 1 ? 'este dato' : 'estos datos' }} antes de enviar:
                            </p>
                            <ul class="mt-1.5 list-inside list-disc text-sm text-tinta-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $campo = 'mt-1 h-10 w-full rounded-md border border-tinta-200 bg-white px-3 text-base text-tinta-900 transition placeholder:text-tinta-500 hover:border-tinta-300 focus:border-marca-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca-600';
                        $rotulo = 'block text-base font-semibold text-marca-600';
                    @endphp

                    <div>
                        <label for="nombre" class="{{ $rotulo }}">Su nombre <span class="text-alerta-500" aria-hidden="true">*</span></label>
                        <input id="nombre" name="nombre" type="text" required maxlength="120"
                               autocomplete="name" placeholder="Nombre Apellido"
                               value="{{ old('nombre', auth()->user()?->name) }}"
                               @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror
                               class="{{ $campo }} @error('nombre') border-alerta-500 @enderror">
                        @error('nombre') <p id="nombre-error" class="mt-1.5 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="{{ $rotulo }}">Correo electrónico <span class="text-alerta-500" aria-hidden="true">*</span></label>
                        <input id="email" name="email" type="email" required maxlength="190"
                               autocomplete="email" placeholder="ejemplo@mail.com"
                               value="{{ old('email', auth()->user()?->email) }}"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                               class="{{ $campo }} @error('email') border-alerta-500 @enderror">
                        @error('email') <p id="email-error" class="mt-1.5 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mensaje" class="{{ $rotulo }}">Sugerencia o comentario <span class="text-alerta-500" aria-hidden="true">*</span></label>
                        <textarea id="mensaje" name="mensaje" rows="5" required minlength="10" maxlength="3000"
                                  placeholder="Escribe tu sugerencia..."
                                  @error('mensaje') aria-invalid="true" aria-describedby="mensaje-error" @enderror
                                  class="{{ str_replace('h-10 ', '', $campo) }} py-2.5 leading-relaxed @error('mensaje') border-alerta-500 @enderror">{{ old('mensaje') }}</textarea>
                        @error('mensaje') <p id="mensaje-error" class="mt-1.5 text-sm font-medium text-alerta-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Trampa para robots: fuera de pantalla y del tabulador. --}}
                    <div class="absolute -left-[9999px]" aria-hidden="true">
                        <label for="contacto-sitio">No llenes este campo</label>
                        <input id="contacto-sitio" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
                    </div>

                    <button type="submit"
                            class="con-luz h-[46px] w-[174px] rounded-md bg-marca-600 text-sm font-bold text-white transition hover:bg-marca-700">
                        Enviar
                    </button>

                    <p class="text-sm text-tinta-500">
                        Al enviar aceptas nuestra
                        <a href="{{ route('politica-datos') }}" class="font-medium text-marca-700 underline underline-offset-2">política de tratamiento de datos</a>.
                    </p>
                </form>
            @endif
            </div>
        </section>

        {{-- ─── 2 · Teléfonos y horarios ───────────────────────────────── --}}
        <section class="{{ $tarjeta }}">
            <h2 class="{{ $titular }}">Número de contacto</h2>
            <ul class="mt-5 flex flex-wrap justify-center gap-x-2 gap-y-1 text-base tabular-nums text-tinta-900">
                <li>
                    PBX: <a href="tel:{{ $contacto->pbxTel() }}" class="hover:text-marca-700 hover:underline">{{ $contacto->pbx() }}</a>
                </li>
                <li>
                    Celular:
                    @foreach ($contacto->celulares() as $celular)
                        <a href="tel:{{ $celular['tel'] }}" class="hover:text-marca-700 hover:underline">{{ $celular['texto'] }}</a>{{ ! $loop->last ? ' -' : '' }}
                    @endforeach
                </li>
            </ul>

            @if ($contacto->whatsappUrl())
                <p class="mt-3 text-center">
                    <a href="{{ $contacto->whatsappUrl('Hola *Sur Alpine*. Necesito más información.') }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 font-semibold text-[#0f6e63] hover:underline">
                        <svg viewBox="0 0 32 32" fill="currentColor" class="size-5 shrink-0" aria-hidden="true">
                            <path d="M16 3C8.8 3 3 8.8 3 16c0 2.3.6 4.5 1.7 6.4L3 29l6.8-1.8c1.9 1 4 1.6 6.2 1.6 7.2 0 13-5.8 13-13S23.2 3 16 3Zm0 23.6c-2 0-3.9-.5-5.6-1.5l-.4-.2-4 1 1.1-3.9-.3-.4A10.5 10.5 0 0 1 5.4 16C5.4 10.2 10.2 5.4 16 5.4S26.6 10.2 26.6 16 21.8 26.6 16 26.6Zm5.9-7.8c-.3-.2-1.9-1-2.2-1-.3-.1-.5-.2-.7.1l-1 1.3c-.2.2-.4.3-.7.1-1.7-.8-2.8-1.5-3.9-3.5-.3-.5.3-.5.8-1.5.1-.2 0-.4 0-.5l-1-2.3c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.2 3.4 5.4 4.7 2 .9 2.8.9 3.8.8.6-.1 1.9-.8 2.2-1.6.3-.8.3-1.4.2-1.6l-.6-.3Z"/>
                        </svg>
                        Escríbenos por WhatsApp
                    </a>
                </p>
            @endif

            <h2 class="{{ $titular }} mt-10">Horarios de atención</h2>
            <ul class="mt-5 space-y-1 text-center text-base text-tinta-900">
                {{-- El texto por defecto se arma con el MISMO rango que alimenta
                     la ficha de Google, para que no puedan contradecirse. Si el
                     cliente escribe algo distinto en «Textos e imágenes», eso
                     manda. --}}
                <li>{{ contenido('contacto.horario.semana', $contacto->horarioTexto('horario_semana', 'Lunes a viernes') ?? 'Lunes a viernes de 8:00 a.m. a 6:00 p.m.') }}</li>
                <li>{{ contenido('contacto.horario.sabado', $contacto->horarioTexto('horario_sabado', 'Sábados') ?? 'Sábados de 8:00 a.m. a 4:00 p.m.') }}</li>
                <li>{{ contenido('contacto.horario.festivo', $contacto->horarioTexto('horario_festivo', 'Festivos') ?? 'Festivos de 9:00 a.m. a 1:00 p.m.') }}</li>
            </ul>
        </section>

        {{-- ─── 3 · Oficinas. Fuera de tarjeta, como en su página. ─────── --}}
        <section class="text-center">
            <h2 class="text-[1.75rem] font-bold text-tinta-900 sm:text-[33.6px]">Oficinas</h2>
            <p class="mx-auto mt-4 max-w-[541px] text-base leading-relaxed text-tinta-900">
                Nuestras oficinas están ubicadas en la
                <strong>{{ $contacto->direccion() }}</strong>, Barrio Restrepo,
                {{ $contacto->ciudad() }}. {{ contenido('contacto.oficinas.nota', 'Parqueadero vigilado.') }}
            </p>
            <a href="{{ $contacto->mapaUrl() }}" target="_blank" rel="noopener"
               class="mt-3 inline-block text-sm font-semibold text-marca-700 underline-offset-4 hover:underline">
                {{ contenido('contacto.mapa.enlace', 'Abrir en Google Maps') }} ↗
            </a>
        </section>

        {{-- ─── 4 · La foto del local y el mapa, lado a lado. ──────────── --}}
        <section class="{{ $tarjeta }}" aria-label="El local y su ubicación">
            <div class="grid gap-6 md:grid-cols-[518fr_347fr]">
                @php $fotoLocal = imagen_contenido('contacto.local', '/img/fotos/local-contactenos'); @endphp
                <img src="{{ $fotoLocal }}-1040.webp"
                     srcset="{{ $fotoLocal }}-520.webp 520w, {{ $fotoLocal }}-1040.webp 1040w"
                     sizes="(min-width: 768px) 518px, 90vw"
                     width="1040" height="780" loading="lazy" decoding="async"
                     alt="Fachada de Importadora Sur Alpine en el barrio Restrepo"
                     class="h-[260px] w-full rounded-lg object-cover md:h-[370px]">

                {{-- El mapa es perezoso: un iframe de Google son unos 700 KB y
                     cookies de terceros para quien quizá nunca baje hasta aquí. --}}
                <iframe title="Ubicación de Importadora Sur Alpine en el mapa"
                        src="https://www.google.com/maps?q={{ rawurlencode($contacto->direccionCompleta().', Colombia') }}&output=embed&z=16"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        class="h-[260px] w-full rounded-lg border-0 md:h-[370px]"></iframe>
            </div>
        </section>
    </div>
@endsection
