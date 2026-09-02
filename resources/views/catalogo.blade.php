@extends('layouts.app')

@section('titulo', $titulo)
@if ($descripcion)
    @section('descripcion', $descripcion)
@endif

@php
    // Sólo estos tres parámetros forman parte de una dirección de verdad.
    //
    // El paginador conserva TODA la cadena de consulta, así que el canonical
    // salía con lo que trajera la URL: `/repuestos?utm_source=facebook` se
    // declaraba a sí mismo el original. Eso son copias ilimitadas del catálogo
    // entero —basta con enlazar `?x=1`, `?x=2`, `?x=3`—, cada una diciéndole a
    // Google que ella es la buena. Es la misma puerta que cierra el 301 de
    // mayúsculas, abierta por otro lado, y es el problema por el que este
    // cliente contrató el sitio.
    //
    // `robots.txt` bloquea `orden`, pero eso tapa UN NOMBRE, no el eje: no
    // cubre `utm_*`, `gclid`, `fbclid` ni nada que a alguien se le ocurra. Y
    // encima ese `robots.txt` ni siquiera se estaba sirviendo: en el servidor
    // hay un archivo de Hostinger tapandolo.
    //
    // `orden` NO esta en la lista a proposito, y antes si lo estaba. Al estar,
    // se colaba tal cual en el canonical: `?orden=basura` respondia 200,
    // `index,follow` y se declaraba a si misma original. Era una fabrica de
    // URLs indexables —cualquiera siembra `?orden=1`, `?orden=2`…— y ademas
    // `?orden=a-z`, que es el valor por defecto del formulario, generaba un
    // canonical distinto al de la URL limpia: un duplicado autoinfligido.
    //
    // Fuera de la lista, un listado ordenado cae solo en la rama de
    // `$paramsRaros`: `noindex,follow`. Que es lo correcto —ordenar no crea
    // una pagina nueva, ensena la misma mercancia en otro orden— sin cortarle
    // a Google el camino hacia las fichas, que es lo que si importa.
    $paramsBuenos = ['page', 'q'];
    $paramsRaros = array_diff(array_keys(request()->query()), $paramsBuenos);
@endphp

{{-- Una búsqueda no se indexa; el catálogo sí.
     `/repuestos?q=freno` respondía 200 con `index,follow` y con el mismo
     título y la misma descripción que `/repuestos`: un espacio infinito de
     páginas casi idénticas al que además invita a entrar el `SearchAction` del
     schema. `noindex,follow`, y no una regla en robots.txt, porque una URL
     bloqueada ni siquiera puede enseñar su propio canonical.

     Lo mismo con cualquier parámetro que no reconozcamos. --}}
@if (filled(request('q')) || $paramsRaros !== [])
    @section('robots', 'noindex,follow')
@endif

{{-- Canonical propio en cada pagina del paginador, y `prev`/`next`.
     Hacian falta las dos cosas: el catalogo tiene 1.220 paginas alcanzables
     -mas las de cada categoria y tipo, unas 3.900 en total- y todas
     canonicalizaban a la pagina 1. Eso le dice a Google que las 3.900 son la
     misma pagina, y lo que hay en la 700 deja de existir para el.
     Lo que Google pide es exactamente esto: cada pagina apuntandose a si
     misma, encadenadas con prev/next. --}}
{{-- Cuatro tipos de parte viven en dos categorias, asi que hay cuatro pares
     de paginas de aterrizaje con el mismo titulo —«terminal-direccion» es de
     las consultas de mas intencion del catalogo y su autoridad se partia en
     dos—. La secundaria apunta a la principal. --}}
@if (($tipoParte ?? null) && ! $tipoParte->esPrincipal())
    @php $tipoBueno = $tipoParte->principal(); @endphp
    @section('canonical', route('tipo-parte', [$tipoBueno->categoria, $tipoBueno]))
@elseif ($productos->hasPages())
    @php
        // La dirección se reconstruye con los parámetros buenos y nada más, en
        // vez de tomar la del paginador tal cual: el paginador conserva TODA la
        // cadena de consulta, así que `?utm_source=facebook` acababa dentro del
        // canonical y esa URL se declaraba a sí misma la original.
        //
        // Y la primera página es `/repuestos` a secas: `?page=1` es la misma
        // página con otra dirección, o sea el duplicado que estas etiquetas
        // vienen a evitar.
        $limpia = function (?int $pagina) use ($paramsBuenos) {
            if ($pagina === null) {
                return null;
            }

            $query = array_filter(
                array_merge(
                    array_intersect_key(request()->query(), array_flip($paramsBuenos)),
                    ['page' => $pagina > 1 ? $pagina : null]
                ),
                fn ($v) => $v !== null && $v !== ''
            );

            return url()->current().($query ? '?'.http_build_query($query) : '');
        };
    @endphp
    @section('canonical', $limpia($productos->currentPage()))
    @section('rel-prev', $limpia($productos->currentPage() > 1 ? $productos->currentPage() - 1 : null) ?? '')
    @section('rel-next', $productos->hasMorePages() ? $limpia($productos->currentPage() + 1) : '')
@endif

{{-- G · Datos estructurados del catálogo.
     · BreadcrumbList: Inicio → Repuestos → Categoría → Tipo, según se vea.
     · CollectionPage con ItemList: le dice a Google (y a los rastreadores
       de IA) que la página es un LISTADO de productos, no una ficha suelta.
     De aquí sale el «Menciona 12 productos» de los resultados enriquecidos. --}}
@push('cabeza')
    @php
        $miga = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Repuestos', 'item' => route('catalogo')],
        ];
        if ($categoria ?? null) {
            $miga[] = ['@type' => 'ListItem', 'position' => 3,
                       'name' => $categoria->nombre,
                       'item' => route('categoria', $categoria)];
        }
        if ($tipoParte ?? null) {
            $miga[] = ['@type' => 'ListItem', 'position' => 4,
                       'name' => $tipoParte->nombre,
                       'item' => route('tipo-parte', [$categoria, $tipoParte])];
        }
        $breadcrumb = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $miga];

        // ItemList corto (primeros 20 productos), que es lo que Google
        // muestra en las tarjetas enriquecidas.
        $items = $productos->take(20)->values()->map(function ($p, $i) {
            return [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => route('producto', $p),
                'name' => $p->nombre,
            ];
        })->all();
        $coleccion = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $titulo,
            'url' => request()->url(),
            'isPartOf' => ['@type' => 'WebSite', '@id' => url('/').'#sitio'],
            'about' => ['@type' => 'AutoPartsStore', '@id' => url('/').'#negocio'],
            'mainEntity' => [
                '@type' => 'ItemList',
                // Los que de verdad van en la lista, no los del catalogo
                // entero: `numberOfItems` describe ESTE `itemListElement`, y
                // decir 29.272 con veinte adentro es una contradiccion dentro
                // del mismo nodo -y el mismo numero se repetia identico en
                // cada pagina del paginador-.
                'numberOfItems' => count($items),
                'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
                'itemListElement' => $items,
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode($coleccion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('contenido')
    <div class="contenedor py-8">

        <nav aria-label="Migas de pan" class="mb-6 text-sm text-tinta-500">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('inicio') }}" class="hover:text-marca-700 hover:underline">Inicio</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('catalogo') }}" class="hover:text-marca-700 hover:underline">Repuestos</a></li>
                @if ($categoria)
                    <li aria-hidden="true">/</li>
                    <li>
                        @if ($tipoParte)
                            <a href="{{ route('categoria', $categoria) }}" class="hover:text-marca-700 hover:underline">{{ $categoria->nombre }}</a>
                        @else
                            <span class="text-tinta-900">{{ $categoria->nombre }}</span>
                        @endif
                    </li>
                @endif
                @if ($tipoParte)
                    <li aria-hidden="true">/</li>
                    <li class="text-tinta-900">{{ $tipoParte->nombre }}</li>
                @endif
            </ol>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-tinta-200 pb-4">
            <div>
                <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-alerta-600">
                    {{ $categoria?->nombre ?? 'Catálogo' }}
                </p>
                {{-- El h1 dice lo que se está viendo.
                     Con un carro puesto decía «Todos los repuestos» encima de
                     «184 repuestos en el catálogo»: el texto más grande de la
                     página contradecía el resultado, y «en el catálogo» hacía
                     pensar que Sur Alpine sólo tiene 184 piezas. --}}
                <h1 class="mt-1.5 text-[1.75rem] font-extrabold sm:text-4xl">
                    {{ $titulo }}@if ($vehiculoActivo ?? null) <span class="text-tinta-500">para tu {{ $vehiculoActivo->nombre_completo }}</span>@endif
                </h1>
                <p class="mt-1.5 text-base text-tinta-500">
                    <span class="tabular-nums">@numero($productos->total())</span>
                    @if (is_string(request('q')) && request('q') !== '')
                        {{ plural($productos->total(), 'resultado', 'resultados') }} para
                        <span class="font-medium text-tinta-700">«{{ request('q') }}»</span>
                    @elseif ($vehiculoActivo ?? null)
                        {{ plural($productos->total(), 'repuesto', 'repuestos') }} que le sirven
                    @else
                        {{ plural($productos->total(), 'repuesto', 'repuestos') }} en el catálogo
                    @endif
                </p>
            </div>

            {{-- `min-w-0` y el select encogible: a 320 px el desplegable más el
                 botón pedían más ancho del que hay y empujaban la fila entera,
                 arrastrando la página de lado. --}}
            <form method="get" class="flex min-w-0 items-center gap-2">
                {{-- Sólo escalares: si alguien pide `?q[]=freno` el valor llega
                     como arreglo y `{{ }}` reventaría en `htmlspecialchars`, con un
                     500 en las tres URLs con más peso SEO del sitio. --}}
                {{-- Sólo `q`: el formulario de ordenar reinyectaba en campos
                     ocultos TODO lo que trajera la URL, así que un
                     `?utm_source=facebook` volvía a salir en el siguiente
                     enlace y se propagaba solo por el catálogo. --}}
                @foreach (request()->only(['q']) as $campo => $valor)
                    @if (is_scalar($valor))
                        <input type="hidden" name="{{ $campo }}" value="{{ $valor }}">
                    @endif
                @endforeach
                <label for="orden" class="text-sm text-tinta-500">Ordenar</label>
                {{-- Con el ratón se aplica solo; con el teclado, no.
                     `onchange="submit()"` recargaba la página en CADA opción por
                     la que pasaba alguien moviéndose con las flechas: nunca
                     llegaba a la que quería, y eso es un cambio de contexto que
                     el usuario no pidió. Aquí, si hubo teclas de por medio, se
                     espera a Enter o a salir del campo, y el botón de al lado
                     queda visible para que la salida sea evidente. --}}
                <select id="orden" name="orden"
                        x-data="{ conTeclado: false }"
                        x-on:keydown="conTeclado = true"
                        x-on:change="if (! conTeclado) $el.form.requestSubmit()"
                        x-on:keydown.enter.prevent="$el.form.requestSubmit()"
                        x-on:blur="if (conTeclado) $el.form.requestSubmit()"
                        class="selector min-w-0 rounded-lg border border-tinta-300 bg-white px-2 py-2 text-sm sm:px-3">
                    <option value="a-z" @selected(request('orden', 'a-z') === 'a-z')>Nombre A-Z</option>
                    <option value="z-a" @selected(request('orden') === 'z-a')>Nombre Z-A</option>
                    <option value="recientes" @selected(request('orden') === 'recientes')>Más recientes</option>
                </select>
                <button type="submit" class="rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm font-medium text-tinta-700 hover:bg-tinta-50">
                    Aplicar
                </button>
            </form>
        </div>

        {{-- Responsive: en tablet (≥ md) ya cabe el sidebar al lado. Antes
             se apilaba hasta 1024 px y en tablet el usuario veía primero
             todo el filtro y tenía que bajar mucho para llegar a las piezas. --}}
        <div class="mt-8 grid gap-6 md:grid-cols-[14rem_1fr] md:gap-8 lg:grid-cols-[16rem_1fr]">

            {{-- Los filtros, plegados en el teléfono.
                 El `aside` va antes en el DOM, así que a 360 px el orden era:
                 migas, título, contador, ordenar, tarjeta del vehículo y DOCE
                 filas de categorías antes del primer repuesto. Es también el
                 orden de tabulación: trece enlaces de filtro antes de ver una
                 pieza. En pantalla ancha el filtro va al lado y no estorba, así
                 que ahí se queda abierto y sin el desplegable. --}}
            <aside class="lg:self-start">
                <details class="md:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-tinta-200 bg-white px-4 py-3 text-sm font-semibold text-tinta-800 marker:hidden [&::-webkit-details-marker]:hidden">
                        Filtrar
                        <span class="text-xs font-normal text-tinta-500">
                            {{ $vehiculoActivo?->nombre_completo ?? ($tipoParte?->nombre ?? $categoria?->nombre ?? 'todo el catálogo') }}
                        </span>
                    </summary>
                    <div class="pt-4">
                        @include('catalogo-filtros')
                    </div>
                </details>

                <div class="hidden md:block">
                    @include('catalogo-filtros')
                </div>
            </aside>


            {{-- `min-w-0`: un hijo de rejilla trae `min-width: auto`, así que
                 no se encoge y el scroll propio de la fila de tipos de parte
                 no servía de nada: en un teléfono arrastraba la página entera
                 4.559 px de lado. --}}
            <div class="min-w-0">
                @if ($productos->isEmpty())
                    {{-- El vacío tiene que nombrar la causa real y dar la salida
                         que corresponde.
                         Antes decía siempre «no encontramos repuestos con esa
                         búsqueda» —aunque no hubiera habido búsqueda— y ofrecía
                         «Ver todo el catálogo», que llevaba al mismo listado
                         SEGUÍA filtrado por el carro. La salida no sacaba. --}}
                    <div class="rounded-2xl border border-dashed border-tinta-300 bg-white p-10 text-center sm:p-12" data-revelar>
                        @if ($vehiculoActivo ?? null)
                            <p class="text-lg font-semibold">
                                Para tu {{ $vehiculoActivo->nombre_completo }} no manejamos
                                {{ $tipoParte?->nombre ?? $categoria?->nombre ?? 'esas piezas' }}
                            </p>
                            <p class="mt-2 text-sm text-tinta-500">
                                @if (is_string(request('q')) && request('q') !== '')
                                    Tu búsqueda de «{{ request('q') }}» está limitada a ese carro.
                                    Quita el filtro y busca en todo el catálogo.
                                @else
                                    Quita el filtro para ver esta sección completa, o llámanos:
                                    muchas veces la conseguimos.
                                @endif
                            </p>

                            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                <form method="post" action="{{ route('vehiculo.olvidar') }}">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
                                        Quitar el filtro de mi carro
                                    </button>
                                </form>
                                <a href="tel:{{ $contacto->pbxTel() }}"
                                   class="rounded-lg border border-tinta-300 bg-white px-5 py-2.5 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
                                    Llamar al {{ $contacto->pbx() }}
                                </a>
                            </div>
                        @else
                            <p class="text-lg font-semibold">
                                {{ contenido('catalogo.vacio.titulo', 'No encontramos repuestos con esa búsqueda') }}
                            </p>
                            <p class="mt-2 text-sm text-tinta-500">
                                {{ contenido('catalogo.vacio.texto', 'Prueba con el nombre de la pieza, por ejemplo «pastillas freno» o «filtro aceite».') }}
                            </p>
                            <a href="{{ route('catalogo') }}"
                               class="mt-6 inline-block rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
                                Ver todo el catálogo
                            </a>
                        @endif
                    </div>
                @else
                    {{-- Los tipos de parte, en una fila sobre las piezas.
                         Bajaron aquí desde la barra lateral porque el cliente
                         la quiere quieta: doce categorías siempre iguales, sin
                         desplegar nada. Pero dentro de Frenos hay veinte tipos
                         —bandas, discos, pastillas, bomba— y son la única
                         manera de acotar 2.877 piezas a las que uno busca; sin
                         ellos habría que perderlos, y con ellos 287 páginas de
                         aterrizaje se quedarían sin puerta de entrada.

                         Aquí ocupan una línea, se desbordan en horizontal y no
                         mueven nada de sitio. --}}
                    @if ($categoria && $tiposParte->isNotEmpty())
                        <div class="mb-5 flex snap-x gap-2 overflow-x-auto pb-2"
                             x-data
                             {{-- Trae la pastilla marcada a la vista.

                                  En Caja de Cambios son 39 pastillas y en Motor
                                  Externo 38: si entras a una del final del
                                  alfabeto, la marcada queda fuera de pantalla a la
                                  derecha y la pagina parece no tener nada elegido.

                                  `scrollLeft` y no `scrollIntoView`: lo segundo
                                  tambien desplaza la pagina en vertical y te deja
                                  el listado a media altura.

                                  Y la cuenta va con `getBoundingClientRect`, no con
                                  `offsetLeft`. `offsetLeft` se mide contra el ancestro
                                  POSICIONADO mas cercano, y aqui no hay ninguno: sale
                                  medido contra `body`, o sea con el ancho del lateral
                                  sumado dentro. La fila se iba 300 px de mas y la
                                  pastilla marcada quedaba igual de fuera de pantalla,
                                  solo que por el otro lado. --}}
                             x-init="$nextTick(() => { const marcada = $el.querySelector('[aria-current]'); if (marcada) $el.scrollLeft += marcada.getBoundingClientRect().left - $el.getBoundingClientRect().left - 16 })">
                            <a href="{{ route('categoria', $categoria) }}"
                               @class([
                                   'shrink-0 snap-start whitespace-nowrap rounded-full border px-4 py-2 text-sm transition',
                                   'border-marca-700 bg-marca-700 font-semibold text-white' => ! $tipoParte,
                                   'border-tinta-300 bg-white text-tinta-700 hover:border-marca-300' => (bool) $tipoParte,
                               ])
                               @if (! $tipoParte) aria-current="page" @endif>
                                Todo en {{ $categoria->nombre }}
                            </a>

                            @foreach ($tiposParte as $tipo)
                                @continue((int) $tipo->productos_count === 0)
                                <a href="{{ route('tipo-parte', [$categoria, $tipo]) }}"
                                   @class([
                                       'shrink-0 snap-start whitespace-nowrap rounded-full border px-4 py-2 text-sm transition',
                                       'border-marca-700 bg-marca-700 font-semibold text-white' => $tipoParte?->id === $tipo->id,
                                       'border-tinta-300 bg-white text-tinta-700 hover:border-marca-300' => $tipoParte?->id !== $tipo->id,
                                   ])
                                   @if ($tipoParte?->id === $tipo->id) aria-current="page" @endif>
                                    {{ $tipo->nombre }}
                                    @if ($contarFiltros)
                                        <span class="ml-1 tabular-nums text-xs opacity-70">@numero($tipo->productos_count)</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($productos as $producto)
                            {{-- La tarjeta entera es el enlace: en un celular
                                 acertarle a un botón de 4 mm con guantes puestos
                                 no es razonable. El botón se queda como señal
                                 visual de que hay algo que pulsar. --}}
                            <li data-revelar class="group relative">
                                <a href="{{ route('producto', $producto) }}"
                                   class="con-luz flex h-full flex-col rounded-2xl border border-tinta-200 bg-white p-5 transition duration-300 hover:-translate-y-1 hover:border-marca-300 hover:shadow-lg">
                                    <p class="font-titulo text-xs font-bold uppercase tracking-[0.14em] text-marca-600">
                                        {{ $producto->tipoParte->categoria->nombre }}
                                    </p>
                                    <h3 class="mt-1.5 font-titulo text-lg font-bold leading-snug text-tinta-900 group-hover:text-marca-700">
                                        {{ $producto->nombre }}
                                    </h3>
                                    {{-- Con un carro puesto esta línea repite lo que
                                         ya dice el nombre —«Aceite AVEO 1400
                                         CHEVROLET» encima de «CHEVROLET AVEO 1400
                                         2006-2009»— y en un teléfono empuja hacia
                                         abajo la palabra que de verdad distingue.
                                         Con el filtro puesto sólo quedan los años. --}}
                                    <p class="mt-2 text-sm text-tinta-500">
                                        @unless ($vehiculoActivo ?? null)
                                            {{ $producto->vehiculo->modelo->marca->nombre }}
                                            {{ $producto->vehiculo->modelo->nombre }}
                                            {{ $producto->vehiculo->cilindraje }}
                                        @endunless
                                        <span class="cifra">{{ $producto->vehiculo->anio_inicio }}-{{ $producto->vehiculo->anio_fin }}</span>
                                    </p>
                                    <span class="mt-5 inline-flex items-center gap-1.5 self-start rounded-xl bg-alerta-500 px-4 py-2.5 font-titulo text-xs font-bold uppercase tracking-[0.06em] text-white transition group-hover:bg-alerta-600">
                                        Ver y cotizar
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-3.5 transition group-hover:translate-x-0.5" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </a>

                                {{-- Agregar sin abrir la ficha.
                                     Quien ya sabe qué pieza quiere tenía que entrar
                                     a la ficha, esperar, bajar, pulsar y volver
                                     atrás: para ocho repuestos eran dieciséis
                                     cargas de página en un taller con mala señal.
                                     Va FUERA del ancla —un formulario dentro de un
                                     enlace no es HTML válido— y por encima de ella
                                     con `z-10`, en la esquina donde no compite con
                                     el «Ver y cotizar». --}}
                                <form method="post" action="{{ route('cotizacion.agregar', $producto) }}"
                                      x-data="agregarACotizacion" @submit.prevent="enviar($event)"
                                      class="absolute bottom-5 right-5 z-10">
                                    @csrf
                                    <button type="submit" :disabled="enviando"
                                            aria-label="Agregar {{ $producto->nombre }} a mi cotización"
                                            class="grid size-11 place-items-center rounded-xl border border-tinta-200 bg-white text-tinta-600 shadow-sm transition hover:border-marca-300 hover:text-marca-700"
                                            :class="listo && 'border-marca-300 text-marca-700'">
                                        <span x-show="!listo" aria-hidden="true" class="text-xl font-bold leading-none">+</span>
                                        <span x-show="listo" x-cloak aria-hidden="true" class="text-lg font-bold leading-none">✓</span>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8">
                        {{ $productos->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
