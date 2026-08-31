{{--
    La barra lateral del catálogo: el vehículo activo y los filtros por parte.

    Vive aparte porque se pinta DOS veces: plegada dentro de un `details` en el
    teléfono —donde si no, había que pasar doce filas de filtros y trece
    paradas del tabulador antes del primer repuesto— y abierta al lado en
    pantalla ancha, que es donde no estorba.
--}}
<h2 class="font-titulo text-xs font-bold uppercase tracking-[0.16em] text-tinta-500">{{ contenido('catalogo.filtro.vehiculo', 'Tu vehículo') }}</h2>
<div class="mt-3 rounded-2xl border border-tinta-200 bg-white p-5">
    @if ($vehiculoActivo ?? null)
        <p class="text-sm font-semibold">{{ $vehiculoActivo->nombre_completo }}</p>
        <form method="post" action="{{ route('vehiculo.olvidar') }}" class="mt-2">
            @csrf
            <button type="submit" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
                Quitar filtro
            </button>
        </form>
    @else
        <p class="text-sm text-tinta-600">
            Elige tu carro y te mostramos sólo las piezas que le sirven.
        </p>
        {{-- Abre el buscador aquí mismo, sin sacar a nadie de donde está.
             Antes llevaba a la portada: quien estaba mirando frenos perdía la
             página y tenía que volver a encontrarla. El modal ya existía para
             exactamente esto. --}}
        <button type="button" x-data @click="$dispatch('abrir-buscador')"
                class="mt-2 inline-flex min-h-11 items-center text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
            Elegir vehículo
        </button>
    @endif
</div>

<h2 class="mt-8 font-titulo text-xs font-bold uppercase tracking-[0.16em] text-tinta-500">{{ contenido('catalogo.filtro.parte', 'Filtrar por parte') }}</h2>

@if ($tiposParte->isNotEmpty())
    <p class="mt-3 text-sm">
        <a href="{{ route('categoria', $categoria) }}"
           class="font-medium text-marca-700 hover:underline">← Todo en {{ $categoria->nombre }}</a>
    </p>
    <ul class="mt-2 max-h-[28rem] space-y-1 overflow-y-auto pr-2 text-sm">
        @foreach ($tiposParte as $tipo)
            <li>
                {{-- Sin resultados no es un enlace: decir "0" y dejarlo clicable
                     manda al usuario a una página vacía. --}}
                @if ((int) $tipo->productos_count === 0)
                    <span class="flex min-h-11 items-center justify-between gap-2 rounded px-2 py-1.5 text-tinta-400"
                          title="No manejamos esta pieza para el vehículo seleccionado">
                        <span>{{ $tipo->nombre }}</span>
                        <span class="shrink-0 text-xs">—</span>
                    </span>
                @else
                    <a href="{{ route('tipo-parte', [$categoria, $tipo]) }}"
                       @class([
                           'flex items-center justify-between gap-2 rounded px-2 py-1.5 hover:bg-tinta-100',
                           'bg-marca-50 font-semibold text-marca-700' => $tipoParte?->id === $tipo->id,
                       ])>
                        <span>{{ $tipo->nombre }}</span>
                        @if ($contarFiltros)
                            <span class="shrink-0 tabular-nums text-xs text-tinta-400">@numero($tipo->productos_count)</span>
                        @endif
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <ul class="mt-3 space-y-1 text-sm">
        @foreach ($categorias as $cat)
            <li>
                {{-- El mismo guardián que la lista de arriba, que
                     aquí faltaba: con un vehículo puesto, «Transmisión 0»
                     seguía siendo un enlace vivo hacia una página
                     que dice «0 repuestos en el catálogo» —falso,
                     el catálogo tiene 29.272— y cuya única salida
                     volvía al mismo listado filtrado. --}}
                @if ($contarFiltros && (int) $cat->productos_count === 0)
                    <span class="flex min-h-11 items-center justify-between gap-2 rounded px-2 py-1.5 text-tinta-400"
                          title="No manejamos piezas de este sistema para el vehículo seleccionado">
                        <span>{{ $cat->nombre }}</span>
                        <span class="shrink-0 text-xs">—</span>
                    </span>
                @else
                    <a href="{{ route('categoria', $cat) }}"
                       class="flex min-h-11 items-center justify-between gap-2 rounded px-2 py-1.5 hover:bg-tinta-100">
                        <span>{{ $cat->nombre }}</span>
                        @if ($contarFiltros)
                            <span class="shrink-0 tabular-nums text-xs text-tinta-400">@numero($cat->productos_count)</span>
                        @endif
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
@endif
