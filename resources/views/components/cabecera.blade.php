@php
    /*
     * La cabecera del sitio actual, medida sobre él y reconstruida aquí.
     *
     * El cliente pidió expresamente que la página no se viera distinta: tienen
     * un problema de suplantación —hay copias de su sitio circulando— y sus
     * clientes de años reconocen esta barra. Así que las cifras de abajo no son
     * de gusto, son las del sitio de producción:
     *
     *   · barra azul  #1866E0, 63 px, enlaces de 12 px/700 con 13/20 de padding;
     *   · fila blanca de 89 px, tres columnas iguales, la tercera con 60 px de
     *     sangría izquierda;
     *   · logo de 67 × 83 px, desbordando la fila hacia arriba;
     *   · buscador de 384 + 44 px, fondo #F4F6F8, radio 5 px;
     *   · rótulos de 13,6 px en negrita.
     *
     * Lo que no se copió: el original es texto plano donde debería haber
     * enlaces (el teléfono no se puede tocar para llamar) y su panel de
     * «Productos» abre vacío. Eso sí se arregló.
     */
    $lineasContacto = [
        ['texto' => 'PBX: '.$contacto->pbx(), 'tel' => $contacto->pbxTel()],
    ];

    if ($celulares = $contacto->celulares()) {
        $lineasContacto[] = [
            'texto' => 'Cel: '.collect($celulares)->pluck('texto')->implode(' - '),
            'tel' => $celulares[0]['tel'],
        ];
    }

    $enlacesSuperiores = [
        ['texto' => contenido('menu.sobre', '¿Quiénes somos?'), 'ruta' => 'quienes-somos', 'icono' => 'persona'],
        ['texto' => contenido('menu.contacto', 'Contáctanos'), 'ruta' => 'contacto', 'icono' => 'telefono'],
    ];
@endphp

<header x-data="{
            productos: false,
            menu: false,
            vehiculo: false,

            abrirVehiculo() {
                this.productos = this.menu = false;
                this.vehiculo = true;

                // El foco entra al modal: si se quedara en el boton de atras,
                // el tabulador recorreria toda la pagina tapada antes de
                // llegar al buscador.
                //
                // Va en el siguiente cuadro de animacion y sobre el primero
                // que SE VEA. Con `$nextTick` a secas el modal todavia estaba
                // en `display: none` y el `.focus()` fallaba en silencio: el
                // foco se quedaba detras del velo. Y apuntarle al primer
                // `select` tampoco servia, porque esta `:disabled` mientras
                // baja el arbol de vehiculos.
                this.enfocarModal(90);
            },

            /*
             * Pone el foco dentro del modal en cuanto haya algo que enfocar.
             *
             * Reintenta unos cuadros porque el modal entra con transicion: en
             * el instante del clic sigue en `display: none`, y ahi `.focus()`
             * no hace nada y falla sin decir nada —el foco se queda detras del
             * velo, en la pagina que la persona ya no ve—. Ademas el primer
             * `select` esta `:disabled` mientras baja el arbol de vehiculos,
             * asi que hay que buscar el primero que de verdad se pueda usar.
             */
            enfocarModal(intentos) {
                // `setTimeout` y no `requestAnimationFrame`: el navegador
                // congela los cuadros de animacion en una pestana que no se
                // esta pintando, y ahi el foco no llegaba nunca a entrar.
                setTimeout(() => {
                    if (! this.vehiculo) return;

                    const modal = this.$refs.modalVehiculo;
                    if (! modal) return;

                    // Paso 1: el foco entra al dialogo YA.
                    //
                    // El contenedor lleva un `tabindex` de -1 justo para esto. Lo
                    // importante es que en ningun momento se quede detras del
                    // velo, en la pagina que la persona ya no ve.
                    if (! modal.contains(document.activeElement) && this.seVe(modal)) {
                        modal.focus();
                    }

                    // Paso 2: cuando el campo de MARCA se pueda usar, se le
                    // pasa el foco. Esta `:disabled` mientras baja el arbol de
                    // vehiculos, asi que hay que esperarlo unos cuadros; si
                    // tarda demasiado -o falla la carga- el foco se queda en
                    // el dialogo, que sigue siendo correcto.
                    const marca = [...modal.querySelectorAll('select:not([disabled])')]
                        .find((e) => this.seVe(e));

                    if (marca) {
                        marca.focus();
                    } else if (intentos > 0) {
                        this.enfocarModal(intentos - 1);
                    }
                }, 16);
            },

            cerrarVehiculo() {
                this.vehiculo = false;
                this.$nextTick(() => this.$refs.abreVehiculo?.focus());
            },

            /*
             * Si el elemento se ve de verdad.
             *
             * NO sirve `offsetParent !== null`, que es lo que uno escribe por
             * costumbre: dentro de un contenedor `position: fixed` —como este
             * modal— `offsetParent` es `null` para TODO, así que ese filtro
             * habría dejado la lista vacía y la trampa sin nada que atrapar.
             * Las cajas de cliente sí distinguen lo oculto de lo visible.
             */
            seVe(elemento) {
                return elemento.type !== 'hidden'
                    && (elemento.offsetWidth || elemento.offsetHeight || elemento.getClientRects().length) > 0;
            },

            /*
             * El tabulador no se sale del modal mientras esta abierto. Sin esto
             * se puede tabular hasta la pagina de atras —que esta tapada por el
             * velo negro— y quedarse escribiendo en campos que no se ven.
             *
             * El filtro de visibilidad no es un detalle: la lista terminaba en
             * un enlace de error que esta oculto, y como ese «ultimo» nunca
             * podia tener el foco, la rama que lo devuelve al principio no se
             * disparaba nunca. Con un Tab real desde «Buscar» el foco saltaba
             * al carrusel de atras: la trampa parecia puesta y no atrapaba.
             */
            atraparFoco(evento) {
                const focales = [...this.$refs.modalVehiculo.querySelectorAll(
                    'a[href], button:not([disabled]), select:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex=\'-1\'])'
                )]
                    .filter((e) => this.seVe(e));

                if (! focales.length) return;

                const primero = focales[0];
                const ultimo = focales[focales.length - 1];

                if (evento.shiftKey && document.activeElement === primero) {
                    evento.preventDefault();
                    ultimo.focus();
                } else if (! evento.shiftKey && document.activeElement === ultimo) {
                    evento.preventDefault();
                    primero.focus();
                }
            },
        }"
        x-effect="document.body.classList.toggle('overflow-hidden', vehiculo);
                  document.querySelectorAll('main, footer').forEach((e) => e.toggleAttribute('inert', vehiculo))"
        {{-- Cualquier parte de la página puede pedir el buscador: la barra
             lateral del catálogo lo hace con `$dispatch('abrir-buscador')`. --}}
        @abrir-buscador.window="abrirVehiculo()"
        @keydown.escape.window="productos = false; menu = false; if (vehiculo) cerrarVehiculo()">

    {{-- ─── Barra azul ─────────────────────────────────────────────────── --}}
    <div class="bg-marca-600">
        <div class="contenedor flex flex-wrap items-center justify-between gap-x-4 px-2.5 sm:flex-nowrap sm:justify-start">

            {{-- El reparto 1/3–2/3 del original sólo a partir de `xl`. Con la letra
                 en 16 px los dos enlaces ya no caben en un tercio hasta los 1280,
                 y por debajo de esa anchura el bloque del teléfono se les montaba encima. Más
                 abajo cada uno ocupa lo que necesita. --}}
            <nav class="flex shrink-0 items-center xl:w-1/3" aria-label="Secundaria">
                @foreach ($enlacesSuperiores as $enlace)
                    {{-- El `aria-label` porque a <640 px el texto va `hidden` y
                         quedaban dos enlaces sin nombre: sólo un dibujo. --}}
                    <a href="{{ route($enlace['ruta']) }}"
                       aria-label="{{ $enlace['texto'] }}"
                       class="flex min-h-11 items-center gap-1 whitespace-nowrap px-3 py-[13px] text-sm font-bold text-white transition hover:text-marca-100 sm:gap-1 sm:px-5">
                        @if ($enlace['icono'] === 'persona')
                            <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5 shrink-0" aria-hidden="true">
                                <circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0z"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="currentColor" class="size-3.5 shrink-0" aria-hidden="true">
                                <path d="M6.6 2.5a1.6 1.6 0 0 1 1.5 1l1.2 2.9a1.6 1.6 0 0 1-.4 1.8L7.7 9.2a12 12 0 0 0 5.4 5.4l1-1.2a1.6 1.6 0 0 1 1.8-.4l2.9 1.2a1.6 1.6 0 0 1 1 1.5v2.7A1.9 1.9 0 0 1 17.8 20 15.9 15.9 0 0 1 4 6.2 1.9 1.9 0 0 1 5.9 4.3h.7z"/>
                            </svg>
                        @endif
                        <span class="hidden sm:inline">{{ $enlace['texto'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- «Contáctenos: PBX… / Cel…», alternando cada 2,8 s. --}}
            <p class="flex min-w-0 flex-1 items-baseline gap-2 py-2 text-white xl:w-2/3 xl:flex-none"
               x-data="telefonosRotando(@js($lineasContacto))">
                <span class="hidden shrink-0 text-base font-bold sm:inline">{{ contenido('cabecera.contactenos', 'Contáctenos:') }}</span>

                <a :href="'tel:' + telefono"
                   class="caer min-w-0 truncate text-base font-bold tabular-nums underline-offset-4 hover:underline"
                   aria-hidden="true" tabindex="-1">
                    <template x-for="c in caracteres" :key="indice + '-' + c.posicion">
                        <span :style="`animation-delay:${c.posicion * 22}ms`" x-text="c.letra"></span>
                    </template>
                </a>

                {{-- El mismo dato, quieto y completo, para lectores de pantalla. --}}
                <span class="sr-only">
                    {{ contenido('cabecera.contactenos', 'Contáctenos:') }}
                    @foreach ($lineasContacto as $linea)
                        <a href="tel:{{ $linea['tel'] }}">{{ $linea['texto'] }}</a>
                    @endforeach
                </span>
            </p>
        </div>
    </div>

    {{-- ─── Fila blanca ────────────────────────────────────────────────── --}}
    <div class="bg-white">
        <div class="contenedor grid grid-cols-[auto_1fr_auto] items-center gap-x-4 px-2 lg:h-[89px] xl:grid-cols-3 xl:gap-x-0">

            {{-- Columna 1 · marca y accesos al catálogo --}}
            <div class="flex items-center py-3 lg:py-0">
                <a href="{{ route('inicio') }}" class="shrink-0" aria-label="Sur Alpine, inicio">
                    <img src="/img/logo/logo-en-png-sur-alpine.webp" alt=""
                         width="280" height="351" fetchpriority="high" decoding="async"
                         class="h-14 w-auto rounded-[3px] lg:h-[83px]">
                </a>

                {{-- El original pone aquí un ícono de menú con el rótulo
                     «Productos» que no abre nada. Aquí abre las categorías,
                     que es lo que ese ícono promete. --}}
                <button type="button" @click="productos = ! productos" :aria-expanded="productos"
                        aria-controls="panel-productos"
                        class="ml-3 hidden items-center gap-2 py-2 pl-2 pr-4 text-base font-bold text-black transition hover:text-marca-700 lg:flex">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-6 shrink-0" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round"/>
                    </svg>
                    {{ contenido('menu.catalogo', 'Productos') }}
                </button>

                {{-- Igual que el original: el mismo sitio dice «Agregar
                     vehículo» o el carro que ya se eligió. Abre el buscador en
                     una ventana sobre la página, sin sacar a nadie de donde
                     está: desde una ficha de repuesto, mandar a la portada a
                     elegir el carro era perder el sitio en el que iba. --}}
                <button type="button" x-ref="abreVehiculo" @click="abrirVehiculo()"
                        :aria-expanded="vehiculo" aria-haspopup="dialog"
                        class="hidden items-center gap-1 text-base font-bold text-tinta-900 transition hover:text-marca-700 lg:flex">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-7 shrink-0" aria-hidden="true">
                        <path d="M2.5 15.5v-2.2l1.6-4a2 2 0 0 1 1.9-1.3h6.6" stroke-linecap="round"/>
                        <path d="M2.5 15.5h11" stroke-linecap="round"/>
                        <circle cx="6" cy="17" r="1.6"/><circle cx="12.5" cy="17" r="1.6"/>
                        <path d="M18 6.5v7M14.5 10h7" stroke-linecap="round"/>
                    </svg>
                    <span class="max-w-44 truncate">
                        {{ $vehiculoActivo->nombre_completo ?? contenido('menu.vehiculo', 'Agregar vehículo') }}
                    </span>
                </button>
            </div>

            {{-- Columna 2 · buscador --}}
            <div class="lg:px-2">
                <form action="{{ route('catalogo') }}" method="get" role="search"
                      class="relative w-full lg:max-w-[431px]"
                      role="search" aria-label="Buscar repuesto por nombre o referencia"
                      x-data="buscadorSugerencias('{{ route('sugerencias') }}')" @click.outside="cerrar()">
                    {{-- El autocompletado, anunciado.
                         Funcionaba con el ratón y era mudo con lector de
                         pantalla: no decía que hubiera aparecido una lista, ni
                         cuántas opciones, ni cuál estaba señalada, y `Escape`
                         no la cerraba. Con el patrón de `combobox` se puede
                         recorrer con las flechas y elegir con Enter. --}}
                    <label for="buscar" class="sr-only">Buscar repuesto o referencia</label>
                    <input id="buscar" type="search" name="q" placeholder="{{ contenido('cabecera.buscador.placeholder', 'Buscar...') }}" autocomplete="off"
                           value="{{ is_string(request('q')) ? request('q') : '' }}"
                           role="combobox" aria-autocomplete="list" aria-controls="sugerencias-buscador"
                           :aria-expanded="abierto"
                           :aria-activedescendant="senalada >= 0 ? 'sugerencia-' + senalada : null"
                           x-model="termino" @input="escribir()" @focus="escribir()"
                           x-on:keydown.arrow-down.prevent="mover(1)"
                           x-on:keydown.arrow-up.prevent="mover(-1)"
                           x-on:keydown.enter="elegir($event)"
                           {{-- `.prevent`: sin él, el navegador vacía el campo de
                                búsqueda al pulsar Escape. Lo que pide el patrón
                                es cerrar la lista y CONSERVAR lo escrito: con
                                mala señal, volver a teclear «pastillas freno»
                                duele. --}}
                           x-on:keydown.escape.stop.prevent="cerrar()"
                           class="h-11 w-full rounded-[5px] border-0 bg-[#f4f6f8] pl-4 pr-12 text-base font-light text-tinta-900 placeholder:text-tinta-500 lg:h-[52px]">
                    <button type="submit" aria-label="Buscar"
                            class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-[5px] text-tinta-900 hover:text-marca-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="7"/><path d="m20 20-4.2-4.2" stroke-linecap="round"/>
                        </svg>
                    </button>

                    {{-- Ancho propio y con scroll.
                         Heredaba los 134 px del campo en un teléfono, así que
                         cada nombre se partía en tres o cuatro líneas y las
                         ocho sugerencias medían 892 px de alto con
                         `overflow: hidden`: la mitad quedaba fuera de la
                         pantalla y sin forma de llegar a ella. --}}
                    <ul id="sugerencias-buscador" role="listbox" aria-label="Sugerencias de búsqueda"
                        x-show="abierto" x-cloak
                        class="absolute right-0 top-full z-50 mt-1 max-h-[60vh] w-[min(88vw,24rem)] overflow-y-auto rounded-[5px] border border-tinta-200 bg-white shadow-lg sm:left-0 sm:w-auto sm:min-w-full">
                        <template x-for="(s, i) in sugerencias" :key="s.u">
                            <li role="option" :id="'sugerencia-' + i" :aria-selected="senalada === i">
                                <a :href="s.u" x-text="s.t" tabindex="-1"
                                   x-on:mouseenter="senalada = i"
                                   :class="senalada === i ? 'bg-marca-50 text-marca-700' : ''"
                                   class="block truncate px-4 py-2.5 text-base hover:bg-marca-50 hover:text-marca-700"></a>
                            </li>
                        </template>
                    </ul>

                    {{-- Lo que un lector de pantalla anuncia al aparecer la lista. --}}
                    <p class="sr-only" role="status" aria-live="polite" x-text="anuncio"></p>
                </form>
            </div>

            {{-- Columna 3 · perfil y cotización.

                 Los dos van con el mismo traje —ícono de 18 px, rótulo de
                 13,6/700, mismo color y misma separación— porque son un par y
                 antes no lo parecían: uno llevaba etiqueta y el otro era un
                 ícono suelto de otro tamaño.

                 El original tampoco rotula el carrito. Aquí sí: es el botón
                 que sostiene el negocio entero, y un ícono de carrito solo se
                 lee como «comprar», que es justo lo que este sitio no hace. --}}
            @php
                // `p-[13px] -m-[13px]`: por debajo de `lg` el rótulo va
                // `hidden` y el ancla se encogía al tamaño del SVG —18×18 px
                // medidos—, o sea menos que la yema de un dedo, en el segundo
                // control más importante del sitio.
                //
                // Trece y no ocho: 18 + 13 + 13 = 44, que es el mínimo que
                // recomienda la guía; con `p-2` la cuenta daba 34 y seguía
                // quedándose corto. El margen negativo lo compensa, así que en
                // pantalla no se mueve nada.
                $accionCabecera = 'flex items-center gap-2.5 whitespace-nowrap p-[13px] -m-[13px] text-base font-bold text-tinta-900 transition hover:text-marca-700';
            @endphp
            {{-- `gap-3` hasta `sm`: con los tres controles a 44 px de zona
                 tocable, en una pantalla de 320 px la fila pedía 120 px y sólo
                 había 118. Dos píxeles que arrastraban la página entera. --}}
            <div class="flex items-center justify-end gap-2 sm:gap-5 lg:gap-10 lg:pl-[60px]">
                <a href="{{ auth()->check() ? (auth()->user()->entraAlPanel() ? route('panel.tablero') : route('cuenta')) : route('acceso') }}"
                   class="{{ $accionCabecera }}"
                   {{-- El `aria-label` va SIEMPRE. Sin sesión este enlace lleva
                        al acceso y, por debajo de `lg`, su rótulo está oculto:
                        se quedaba sin nombre accesible justo para quien más lo
                        necesita. --}}
                   aria-label="@auth Mi perfil ({{ auth()->user()->primer_nombre }}) @else Entrar a mi cuenta @endauth">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-[18px] shrink-0" aria-hidden="true">
                        <circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0z"/>
                    </svg>
                    {{-- Siempre «Mi perfil», haya sesión o no: es lo que dice el
                         botón en su sitio, y el nombre de pila ahí arriba se lee
                         como el título de una sección, no como una persona. Quién
                         entró se ve en la cuenta y en el panel, que es donde
                         importa. --}}
                    <span class="hidden lg:inline">{{ contenido('menu.perfil', 'Mi perfil') }}</span>
                </a>

                <a href="{{ route('cotizacion.ver') }}" class="{{ $accionCabecera }}"
                   aria-label="{{ contenido('menu.cotizar', 'Mi cotización') }}">
                    <span class="relative shrink-0">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="size-[18px]" aria-hidden="true">
                            <path d="M6 6h15l-1.7 8.3a2 2 0 0 1-2 1.6H9.4a2 2 0 0 1-2-1.6L5.4 3.6H2.2v-1.6h4.4z"/>
                            <circle cx="9.5" cy="20" r="1.7"/><circle cx="17.5" cy="20" r="1.7"/>
                        </svg>

                        {{-- El contador se actualiza solo cuando se agrega sin
                             recargar. Va sobre el ícono y no detrás del rótulo
                             porque en móvil el rótulo no está. --}}
                        <span x-data="{ n: {{ $itemsCotizacion ?? 0 }} }"
                              @cotizacion-actualizada.window="n = $event.detail.total"
                              x-show="n > 0" x-cloak
                              class="absolute -right-2.5 -top-2 grid min-w-[18px] place-items-center rounded-full bg-alerta-500 px-1 text-xs font-bold leading-[18px] tabular-nums text-white">
                            <span x-text="n">{{ $itemsCotizacion ?? 0 }}</span>
                        </span>
                    </span>
                    <span class="hidden lg:inline">{{ contenido('menu.cotizar', 'Mi cotización') }}</span>
                </a>

                <button type="button" x-ref="hamburguesa" @click="menu = ! menu"
                        :aria-expanded="menu" aria-controls="menu-movil"
                        :aria-label="menu ? 'Cerrar menú' : 'Abrir menú'"
                        class="-mr-0.5 grid size-11 place-items-center rounded-[5px] text-tinta-900 hover:bg-tinta-100 lg:hidden">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-6" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Panel de categorías que abre «Productos». --}}
    <div id="panel-productos" x-show="productos" x-cloak @click.outside="productos = false"
         x-transition.origin.top.duration.180ms
         class="relative z-40 hidden border-t border-tinta-200 bg-white shadow-lg lg:block">
        <div class="contenedor grid gap-x-6 gap-y-1 px-2 py-6 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($categoriasCabecera ?? [] as $categoria)
                <a href="{{ route('categoria', $categoria) }}"
                   class="rounded px-2 py-2 text-base text-tinta-700 transition hover:bg-marca-50 hover:text-marca-700">
                    {{ $categoria->nombre }}
                </a>
            @endforeach
            <a href="{{ route('catalogo') }}" class="rounded px-2 py-2 text-base font-bold text-marca-700 hover:underline">
                {{ contenido('cabecera.ver_todo', 'Ver todo el catálogo →') }}
            </a>
        </div>
    </div>

    {{-- Menú móvil --}}
    <div id="menu-movil" x-show="menu" x-cloak
         @keydown.escape="menu = false; $refs.hamburguesa.focus()"
         class="border-t border-tinta-200 bg-white lg:hidden">
        <nav class="contenedor px-2 py-3" aria-label="Menú móvil">
            <button type="button" @click="abrirVehiculo()"
                    class="flex w-full items-center gap-2 rounded px-4 py-2.5 text-left text-base font-bold text-tinta-900 hover:bg-tinta-100">
                {{ $vehiculoActivo->nombre_completo ?? contenido('menu.vehiculo', 'Agregar vehículo') }}
            </button>
            <a href="{{ route('catalogo') }}" class="block rounded px-4 py-2.5 text-base font-bold text-tinta-900 hover:bg-tinta-100">
                {{ contenido('menu.catalogo', 'Productos') }}
            </a>
            <a href="{{ route('mantenimientos') }}" class="block rounded px-4 py-2.5 text-base font-bold text-tinta-900 hover:bg-tinta-100">
                {{ contenido('menu.mantenimientos', 'Mantenimientos') }}
            </a>
            @foreach ($enlacesSuperiores as $enlace)
                <a href="{{ route($enlace['ruta']) }}" class="block rounded px-4 py-2.5 text-base font-bold text-tinta-900 hover:bg-tinta-100">
                    {{ $enlace['texto'] }}
                </a>
            @endforeach

            {{-- «Mi cotización» y «Mi perfil» también aquí.
                 En un teléfono estas dos sólo existían como los íconos de la
                 fila blanca, sin rótulo. El menú listaba productos, contacto y
                 teléfonos, y dejaba fuera justo las dos cosas que la persona
                 viene a hacer: ver lo que armó y entrar a su cuenta. --}}
            <div class="mt-2 border-t border-tinta-200 pt-2">
                <a href="{{ route('cotizacion.ver') }}" class="flex items-center justify-between gap-2 rounded px-4 py-2.5 text-base font-bold text-tinta-900 hover:bg-tinta-100">
                    {{ contenido('menu.cotizar', 'Mi cotización') }}
                    @if (($itemsCotizacion ?? 0) > 0)
                        <span class="grid min-w-6 place-items-center rounded-full bg-alerta-500 px-1.5 text-xs font-bold leading-6 tabular-nums text-white">{{ $itemsCotizacion }}</span>
                    @endif
                </a>
                <a href="{{ auth()->check() ? (auth()->user()->entraAlPanel() ? route('panel.tablero') : route('cuenta')) : route('acceso') }}"
                   class="block rounded px-4 py-2.5 text-base font-bold text-tinta-900 hover:bg-tinta-100">
                    @auth {{ contenido('menu.perfil', 'Mi perfil') }} @else Entrar o crear cuenta @endauth
                </a>
            </div>

            <p class="mt-3 border-t border-tinta-200 px-4 pt-3 text-sm tabular-nums text-tinta-600">
                @foreach ($lineasContacto as $linea)
                    <a href="tel:{{ $linea['tel'] }}" class="block py-0.5 font-semibold text-marca-700">{{ $linea['texto'] }}</a>
                @endforeach
            </p>
        </nav>
    </div>

    @if ($vehiculoActivo ?? null)
        <div class="border-t border-tinta-200 bg-marca-50">
            <div class="contenedor flex items-center gap-x-3 px-2 py-1.5 text-sm">
                <p class="min-w-0 truncate text-marca-900">
                    <span class="hidden sm:inline">{{ contenido('cabecera.viendo_repuestos_de', 'Viendo repuestos de') }} </span><strong>{{ $vehiculoActivo->nombre_completo }}</strong>
                </p>
                <form method="post" action="{{ route('vehiculo.olvidar') }}" class="ml-auto shrink-0">
                    @csrf
                    <button type="submit" class="font-semibold text-marca-700 underline-offset-2 hover:underline">{{ contenido('cabecera.quitar_carro', 'Quitar mi carro') }}</button>
                </form>
            </div>
        </div>
    @endif

    {{-- ─── Buscador en ventana ─────────────────────────────────────────────

         El velo negro no es decoración: es lo que hace que los cuatro
         desplegables sean lo único que se ve. Entra en 200 ms y la tarjeta
         sube un poco al aparecer, lo justo para que se lea como algo que se
         abrió y no como algo que ya estaba.

         Mientras está abierto el `body` no hace scroll —si no, la página de
         atrás se mueve bajo el velo y marea—, `Escape` cierra, el tabulador no
         se escapa, y al cerrar el foco vuelve al botón que lo abrió. --}}
    <div x-show="vehiculo" x-cloak role="dialog" aria-modal="true"
         aria-labelledby="modal-titulo"
         class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center sm:p-6">

        {{-- El velo: negro casi entero y con un desenfoque de 4 px. El
             desenfoque es lo que hace que la página de atrás deje de leerse
             como página y pase a ser fondo. --}}
        <div x-show="vehiculo"
             x-transition:enter="transition duration-300 ease-out"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition duration-200 ease-in"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="cerrarVehiculo()" aria-hidden="true"
             class="fixed inset-0 bg-black/[0.92] backdrop-blur-[4px]"></div>

        {{-- La tarjeta entra con una curva de salida larga —`0.16, 1, 0.3, 1`—:
             arranca rápido y frena despacio, que es lo que se lee como algo que
             se abrió y no como algo que apareció. Al cerrar es al revés y en la
             mitad de tiempo: nadie quiere esperar a que se vaya. --}}
        {{-- `tabindex="-1"`: es el ultimo recurso del foco al abrir. Sin el,
             si por lo que sea no hay nada enfocable dentro, `.focus()` sobre
             este div no hace nada y el foco se queda detras del velo. --}}
        <div x-ref="modalVehiculo" tabindex="-1" x-show="vehiculo"
             x-transition:enter="transition duration-[420ms] ease-[cubic-bezier(0.16,1,0.3,1)]"
             x-transition:enter-start="opacity-0 translate-y-10 scale-[0.94]"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition duration-[180ms] ease-in"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-[0.97]"
             @keydown.tab="atraparFoco($event)"
             class="relative my-auto w-full max-w-3xl drop-shadow-[0_30px_60px_rgba(0,0,0,0.6)]">

            <button type="button" @click="cerrarVehiculo()" aria-label="Cerrar el buscador"
                    class="absolute -top-11 right-0 grid size-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:-right-11 sm:top-0">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/>
                </svg>
            </button>

            {{-- `prefijo="modal"`: en la portada este componente ya está pintado
                 una vez, y sin esto los cuatro `id` saldrían repetidos. El
                 titular que genera (`#modal-titulo`) es la etiqueta del
                 diálogo. --}}
            <x-buscador-vehiculo prefijo="modal" />
        </div>
    </div>
</header>
