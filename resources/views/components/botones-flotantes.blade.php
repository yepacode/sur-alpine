{{--
    Lo que flota sobre la página: el aviso de cookies, el botón de cotización y
    el de WhatsApp.

    Los tres viven en un solo componente y comparten un `x-data` a propósito. Es
    justo el problema que tiene su sitio: allá el aviso de cookies lo pone un
    plugin y el carrito flotante otro, ninguno sabe del otro, y el carrito
    termina montado encima del aviso tapando el enlace de «Personalice». Aquí,
    mientras el aviso está abierto, los dos botones suben.

    En su web esto son dos plugins que arrastran jQuery y unos 90 KB entre los
    dos; aquí es Blade y el Alpine que ya está cargado.
--}}
@php
    $whatsapp = $contacto->whatsappUrl('Hola *Sur Alpine*. Necesito más información.');
@endphp

<div x-data="{
        cookies: false,
        globo: false,

        init() {
            // `localStorage` y `sessionStorage` lanzan en modo privado o con
            // las cookies bloqueadas. Si no se pueden leer, no se muestra
            // nada: el sitio funciona igual y nadie se queda con un aviso
            // que no puede cerrar.
            try {
                this.cookies = localStorage.getItem('aviso-cookies') !== 'visto';
            } catch (e) {}

            try {
                if (sessionStorage.getItem('wa-globo') === 'visto') return;
            } catch (e) { return; }

            // El globo espera a que el aviso de cookies se haya resuelto: dos
            // cosas pidiendo atención a la vez no las lee nadie.
            setTimeout(() => { if (! this.cookies) this.abrirGlobo() }, 3000);
        },

        aceptarCookies() {
            this.cookies = false;
            try { localStorage.setItem('aviso-cookies', 'visto') } catch (e) {}
            setTimeout(() => this.abrirGlobo(), 1200);
        },

        /*
         * El saludo se muestra y se retira solo a los siete segundos.
         *
         * Es un globo de 240 x 126 px anclado a la esquina de abajo, y en un
         * teléfono ahí SIEMPRE hay algo debajo: medido, tapaba el 77 % del
         * selector MODELO de la portada, el «Agregar a mi cotización» de la
         * ficha —`elementFromPoint` en su centro devolvía el icono de
         * WhatsApp—, el «Iniciar sesión» del acceso y el «Vaciar todo» de la
         * cotización. El dedo abría WhatsApp en vez de hacer lo que la persona
         * quería.
         *
         * Un saludo que no se va no es un saludo: es un cartel. Se queda lo
         * justo para que se lea, y el botón redondo sigue ahí siempre para
         * quien de verdad quiera escribir.
         */
        abrirGlobo() {
            this.globo = true;

            setTimeout(() => { if (this.globo) this.cerrarGlobo() }, 7000);
        },

        cerrarGlobo() {
            this.globo = false;
            try { sessionStorage.setItem('wa-globo', 'visto') } catch (e) {}
        },
     }"
     class="print:hidden">

    {{-- ─── Aviso de cookies ────────────────────────────────────────────────

         El de ellos ofrece tres niveles de privacidad y un panel de
         preferencias. Aquí no hay nada que preferir: este sitio no carga
         analítica, ni píxeles, ni fuentes de Google —van servidas desde el
         propio dominio—, así que las únicas cookies son la de sesión y la que
         recuerda la cotización. Ofrecer un selector de tres niveles sobre eso
         sería teatro.

         Si algún día se agrega Meta Pixel o Analytics, esto se queda corto y
         hay que volver a un consentimiento granular de verdad. --}}
    {{-- Una barra al pie, no una tarjeta encima del contenido.
         Medía 370 px de alto —el 47 % de una pantalla de 812— y es `fixed`,
         así que perseguía a la persona mientras hacía scroll: tapaba el correo
         y la contraseña del registro, y en la portada el botón BUSCAR, que es
         el CTA principal del sitio. Un aviso informativo sobre dos cookies
         técnicas no puede costar eso. --}}
    <div x-show="cookies" x-cloak x-transition.opacity.duration.300ms
         role="dialog" aria-modal="false" aria-labelledby="cookies-titulo"
         class="fixed inset-x-0 bottom-0 z-50 border-t border-tinta-200 bg-white px-4 py-3 shadow-[0_-4px_20px_rgba(0,0,0,0.12)]">
        <div class="contenedor flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
            <p class="min-w-0 flex-1 text-sm leading-snug text-tinta-700">
                <span id="cookies-titulo" class="font-semibold text-tinta-900">{{ contenido('cookies.titulo', 'Sólo cookies necesarias:') }}</span>
                {{ contenido('cookies.mensaje', 'las que te dejan entrar y no perder tu cotización. Nada de publicidad ni seguimiento.') }}
                <a href="{{ route('politica-datos') }}"
                   class="font-semibold text-marca-700 underline-offset-2 hover:underline">{{ contenido('cookies.ver_politica', 'Ver la política') }}</a>.
            </p>

            <button type="button" @click="aceptarCookies()"
                    class="shrink-0 rounded-lg bg-marca-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-marca-800">
                {{ contenido('cookies.aceptar', 'Entendido') }}
            </button>
        </div>
    </div>

    {{-- ─── Cotización · abajo a la izquierda ─────────────────────────────── --}}
    {{-- Menos en la propia página de la cotización: allí es un enlace a la
         página en la que ya estás, y encima tapaba el «Seguir agregando
         repuestos». --}}
    @unless (request()->routeIs('cotizacion.ver'))
    <a href="{{ route('cotizacion.ver') }}"
       x-data="{ n: {{ $itemsCotizacion ?? 0 }} }"
       @cotizacion-actualizada.window="n = $event.detail.total"
       :class="cookies ? 'bottom-28 sm:bottom-24' : 'bottom-5'"
       class="fixed left-5 z-40 grid size-14 place-items-center rounded-2xl bg-white text-tinta-800 shadow-[0_6px_24px_rgba(0,0,0,0.22)] transition-all duration-300 hover:-translate-y-0.5 hover:text-marca-700 hover:shadow-[0_10px_28px_rgba(0,0,0,0.28)]"
       aria-label="{{ contenido('menu.cotizar', 'Mi cotización') }}">
        <svg viewBox="0 0 24 24" fill="currentColor" class="size-7" aria-hidden="true">
            <path d="M6 6h15l-1.7 8.3a2 2 0 0 1-2 1.6H9.4a2 2 0 0 1-2-1.6L5.4 3.6H2.2v-1.6h4.4z"/>
            <circle cx="9.5" cy="20" r="1.7"/><circle cx="17.5" cy="20" r="1.7"/>
        </svg>

        <span x-show="n > 0" x-cloak
              class="absolute -right-1.5 -top-1.5 grid min-w-6 place-items-center rounded-full bg-marca-600 px-1.5 text-xs font-bold leading-6 tabular-nums text-white ring-2 ring-white">
            <span x-text="n">{{ $itemsCotizacion ?? 0 }}</span>
        </span>
    </a>
    @endunless

    @if ($whatsapp)
        {{-- ─── WhatsApp · abajo a la derecha ─────────────────────────────── --}}
        <div :class="cookies ? 'bottom-28 sm:bottom-24' : 'bottom-5'"
             class="fixed right-5 z-40 flex flex-col items-end gap-3 transition-all duration-300">

            {{-- El saludo, sólo en la portada.
                 Es la única pantalla donde no hay un botón importante en esa
                 esquina: en la ficha tapaba el «Agregar a mi cotización», en
                 el acceso el «Iniciar sesión» y en la cotización el «Vaciar
                 todo». El botón redondo sí va en todas, que es lo que hace
                 falta para escribirles. --}}
            @if (request()->routeIs('inicio'))
            <div x-show="globo" x-cloak x-transition.opacity.duration.300ms
                 class="relative max-w-[15rem] rounded-2xl rounded-br-sm bg-white px-4 py-3 text-sm shadow-[0_6px_24px_rgba(0,0,0,0.22)]">
                <p class="pr-4 text-tinta-800">Hola 👋<br>¿En qué podemos ayudarte?</p>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener"
                   class="mt-1.5 inline-block font-semibold text-[#0f6e63] underline-offset-2 hover:underline">
                    {{ contenido('whatsapp.boton_abrir', 'Abrir chat') }}
                </a>

                <button type="button" @click="cerrarGlobo()" aria-label="Cerrar el mensaje"
                        class="absolute right-1.5 top-1.5 grid size-6 place-items-center rounded-full text-tinta-400 hover:bg-tinta-100 hover:text-tinta-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-3.5" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            @endif

            <a href="{{ $whatsapp }}" target="_blank" rel="noopener" @click="cerrarGlobo()"
               class="relative grid size-14 place-items-center rounded-full bg-[#25d366] text-white shadow-[0_6px_24px_rgba(0,0,0,0.28)] transition hover:-translate-y-0.5 hover:bg-[#1fbe5a]"
               aria-label="Escríbenos por WhatsApp al {{ $contacto->whatsapp() }}">
                <svg viewBox="0 0 32 32" fill="currentColor" class="size-8" aria-hidden="true">
                    <path d="M16 3C8.8 3 3 8.8 3 16c0 2.3.6 4.5 1.7 6.4L3 29l6.8-1.8c1.9 1 4 1.6 6.2 1.6 7.2 0 13-5.8 13-13S23.2 3 16 3Zm0 23.6c-2 0-3.9-.5-5.6-1.5l-.4-.2-4 1 1.1-3.9-.3-.4A10.5 10.5 0 0 1 5.4 16C5.4 10.2 10.2 5.4 16 5.4S26.6 10.2 26.6 16 21.8 26.6 16 26.6Zm5.9-7.8c-.3-.2-1.9-1-2.2-1-.3-.1-.5-.2-.7.1l-1 1.3c-.2.2-.4.3-.7.1-1.7-.8-2.8-1.5-3.9-3.5-.3-.5.3-.5.8-1.5.1-.2 0-.4 0-.5l-1-2.3c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.2 3.4 5.4 4.7 2 .9 2.8.9 3.8.8.6-.1 1.9-.8 2.2-1.6.3-.8.3-1.4.2-1.6l-.6-.3Z"/>
                </svg>

                {{-- El «1» del original: es lo que hace que el ojo vuelva al botón.
                     Aquí desaparece en cuanto la persona interactúa, para no fingir
                     un mensaje sin leer que nunca existió. --}}
                <span x-show="globo" x-cloak
                      class="absolute -right-0.5 -top-0.5 grid size-5 place-items-center rounded-full bg-alerta-500 text-xs font-bold leading-5 text-white ring-2 ring-white">
                    1
                </span>
            </a>
        </div>
    @endif
</div>
