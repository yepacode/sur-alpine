@props(['banners'])

{{--
    Carrusel del hero, igual que el del sitio actual: las diapositivas corren
    en horizontal y la siguiente entra por la derecha.

    La diferencia está en el peso. Allá las diez fotos bajan siempre y se llevan
    40 de los 59 MB de la portada; aquí las mismas campañas, en WebP, caben en
    poco más de medio mega entre todas.

    El público de este sitio está en un taller, con el teléfono en la mano: por
    eso el carrusel se puede parar con el dedo y no sólo con el puntero.
--}}
<section aria-label="Novedades"
         x-data="{
             actual: 0,
             total: {{ count($banners) }},
             temporizador: null,
             pausado: false,

             init() { this.arrancar() },
             destroy() { this.detener() },

             /* Una sola puerta de entrada al intervalo: siempre limpia el
                anterior, respeta la pausa manual y respeta a quien pidió menos
                movimiento. Sin esto, un `mouseleave` suelto apila intervalos y
                el carrusel empieza a saltar de a dos. */
             get puedeCorrer() {
                 return this.total > 1 && ! this.pausado
                     && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches;
             },
             arrancar() {
                 this.detener();
                 if (! this.puedeCorrer) return;
                 this.temporizador = setInterval(() => this.siguiente(), 6000);
             },
             detener() { clearInterval(this.temporizador); this.temporizador = null },

             alternarPausa() { this.pausado = ! this.pausado; this.arrancar() },

             siguiente() { this.actual = (this.actual + 1) % this.total },
             anterior() { this.actual = (this.actual - 1 + this.total) % this.total },

             /* Se cargan la actual, la siguiente y la anterior: las tres que
                puede pedir el próximo gesto. Una vez cargada, una campaña ya no
                se descarta, para no volver a bajarla en la segunda vuelta. */
             cargadas: [0],
             visible(i) {
                 const vecinas = [this.actual, (this.actual + 1) % this.total, (this.actual - 1 + this.total) % this.total];
                 if (vecinas.includes(i) && ! this.cargadas.includes(i)) this.cargadas.push(i);
                 return this.cargadas.includes(i);
             },

             /* Al navegar a mano se reinicia la cuenta, para no cambiar de
                diapositiva justo después de que alguien eligió una. */
             ir(i) { this.actual = i; this.arrancar() },
         }"
         @mouseenter="detener()" @mouseleave="arrancar()"
         @focusin="detener()" @focusout="arrancar()"
         class="group relative overflow-hidden bg-marca-900">

    {{-- Recorrido largo y salida rápida: así se ve el arrastre y no se queda
         quieto mirando cada campaña. --}}
    <div class="flex transition-transform duration-1000 ease-[cubic-bezier(0.22,0.61,0.36,1)]"
         :style="`transform: translateX(-${actual * 100}%)`">
        {{-- Sólo se descargan la campaña visible y la siguiente. Antes el
             intervalo traía las siete en veintidós segundos aunque el visitante
             no mirara: medio mega por una portada que nadie pidió. --}}
        @foreach ($banners as $i => $banner)
            {{-- El `src` apunta al chico a propósito: el rastreador de recursos
                 lo empieza a pedir antes de resolver el `srcset`, y en un
                 teléfono esa carrera la tiene que ganar el archivo pequeño. --}}
            <img @if ($i === 0)
                     src="{{ $banner['chico'] }}"
                     srcset="{{ $banner['srcset'] }}"
                     fetchpriority="high"
                 @else
                     :src="visible({{ $i }}) ? '{{ $banner['src'] }}' : null"
                     :srcset="visible({{ $i }}) ? '{{ $banner['srcset'] }}' : null"
                     loading="lazy"
                 @endif
                 sizes="(min-width: 1600px) 1600px, 100vw"
                 alt="{{ $banner['alt'] }}"
                 width="1600" height="522"
                 decoding="async"
                 :aria-hidden="actual !== {{ $i }}"
                 class="w-full shrink-0 bg-marca-900 object-cover">
        @endforeach
    </div>

    @if (count($banners) > 1)
        {{-- Las flechas se revelan con el puntero, y sólo donde hay puntero.
             El corte estaba en `sm` (640 px), o sea que una tablet táctil las
             tenía invisibles y ocupando zona tocable: se pulsaban sin querer y
             no se veían nunca. `@media (hover: hover)` pregunta lo que de
             verdad importa —si hay ratón— en vez de deducirlo del ancho. --}}
        <button type="button" @click="anterior(); arrancar()" aria-label="Novedad anterior"
                class="absolute left-2 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-white backdrop-blur-sm transition hover:bg-black/70 sm:left-3 [@media(hover:hover)]:opacity-0 focus-visible:opacity-100 [@media(hover:hover)]:group-hover:opacity-100">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-5" aria-hidden="true">
                <path d="M15 18 9 12l6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <button type="button" @click="siguiente(); arrancar()" aria-label="Novedad siguiente"
                class="absolute right-2 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-white backdrop-blur-sm transition hover:bg-black/70 sm:right-3 [@media(hover:hover)]:opacity-0 focus-visible:opacity-100 [@media(hover:hover)]:group-hover:opacity-100">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-5" aria-hidden="true">
                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1">
            {{-- El punto sigue midiendo 8 px, pero el área que responde al dedo
                 es de 24: antes había que acertarle a un objetivo de 8×8. --}}
            @foreach ($banners as $i => $banner)
                <button type="button" @click="ir({{ $i }})"
                        :aria-current="actual === {{ $i }}"
                        aria-label="Ir a la novedad {{ $i + 1 }} de {{ count($banners) }}"
                        class="grid size-6 place-items-center">
                    <span class="h-2 rounded-full transition-all"
                          :class="actual === {{ $i }} ? 'w-8 bg-white' : 'w-2 bg-white/50'"></span>
                </button>
            @endforeach

            {{-- Pausa visible: es el único control que cumple con quien no puede
                 leer siete campañas que se cambian solas. --}}
            <button type="button" @click="alternarPausa()"
                    :aria-pressed="pausado"
                    :aria-label="pausado ? 'Reanudar las novedades' : 'Pausar las novedades'"
                    class="ml-1 grid size-6 place-items-center rounded-full text-white/70 transition hover:text-white">
                <svg x-show="! pausado" viewBox="0 0 24 24" fill="currentColor" class="size-3" aria-hidden="true">
                    <rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>
                </svg>
                <svg x-show="pausado" x-cloak viewBox="0 0 24 24" fill="currentColor" class="size-3" aria-hidden="true">
                    <path d="M7 4.5v15l12-7.5z"/>
                </svg>
            </button>
        </div>

        {{-- Para quien no ve la pantalla: qué campaña está sonando ahora. --}}
        <p class="sr-only" role="status" aria-live="polite">
            Novedad <span x-text="actual + 1"></span> de {{ count($banners) }}
        </p>
    @endif
</section>
