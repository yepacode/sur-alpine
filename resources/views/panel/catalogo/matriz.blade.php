@extends('panel.layout')

@section('titulo', $vehiculo->nombre_completo)

@section('contenido')
    <a href="{{ route('panel.catalogo') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
        ← Todos los vehículos
    </a>

    <form method="post" action="{{ route('panel.catalogo.matriz', $vehiculo) }}"
          x-data="{
              total: {{ $marcados->count() }},
              contar() { this.total = this.$el.querySelectorAll('input[name=&quot;tipos[]&quot;]:checked').length },
          }"
          @change="contar()">
        @csrf

        {{-- Segundo paso: si el guardado desmarcaba piezas con referencia,
             imagen o descripción, no las borramos en silencio. Aquí las
             mostramos y pedimos confirmación explícita. --}}
        @if (session('confirmar_retiro'))
            @php $aviso = session('confirmar_retiro'); @endphp
            <div role="alert" class="mt-6 rounded-2xl border-2 border-alerta-500/40 bg-alerta-500/5 p-6">
                <p class="font-titulo text-lg font-bold text-alerta-800">
                    Estás a punto de retirar {{ count($aviso['piezas']) }}
                    {{ plural(count($aviso['piezas']), 'pieza', 'piezas') }} de {{ $aviso['vehiculo'] }}
                </p>
                <p class="mt-2 text-sm text-tinta-700">
                    Estas fichas tienen datos que el equipo cargó a mano —
                    referencia, foto o descripción— y se perderán al retirarlas.
                    Volver a marcar la casilla <strong>no las recupera</strong>: crea otra ficha en blanco.
                </p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($aviso['piezas'] as $p)
                        <li class="flex flex-wrap items-baseline gap-2 rounded-lg bg-white/70 px-3 py-2">
                            <span class="font-semibold">{{ $p['nombre'] }}</span>
                            @if ($p['referencia'])
                                <span class="cifra rounded bg-marca-100 px-2 py-0.5 text-xs font-medium text-marca-800">
                                    Ref. {{ $p['referencia'] }}
                                </span>
                            @endif
                            @if ($p['tiene_imagen'])
                                <span class="text-xs text-tinta-600">· con foto</span>
                            @endif
                            @if ($p['tiene_descripcion'])
                                <span class="text-xs text-tinta-600">· con descripción</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit" name="confirmar_retiro" value="1"
                            class="rounded-xl bg-alerta-500 px-6 py-3 font-titulo text-sm font-bold uppercase tracking-[0.06em] text-white hover:bg-alerta-600">
                        Sí, retirarlas
                    </button>
                    <a href="{{ route('panel.catalogo.editar', $vehiculo) }}"
                       class="text-sm font-semibold text-marca-700 underline-offset-4 hover:underline">
                        No, cancelar
                    </a>
                </div>
            </div>
        @endif

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ $vehiculo->nombre_completo }}</h1>
                <p class="mt-1 text-sm text-tinta-500">
                    Marca las piezas que lleva este carro. Cada casilla es un repuesto del catálogo.
                </p>
            </div>

            <div class="sticky top-4 z-10 flex items-center gap-4 rounded-xl border border-tinta-200 bg-white px-5 py-3 shadow-sm">
                <p class="text-sm text-tinta-500">
                    <span class="text-xl font-bold tabular-nums text-tinta-900" x-text="total">{{ $marcados->count() }}</span>
                    piezas
                </p>
                <button type="submit" class="rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
                    Guardar cambios
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($categorias as $categoria)
                @continue ($categoria->tiposParte->isEmpty())

                <section class="rounded-xl border border-tinta-200 bg-white"
                         x-data="{
                             marcarTodo(valor) {
                                 this.$refs.lista.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = valor);
                             },
                         }">
                    <header class="flex items-center gap-2 border-b border-tinta-200 px-4 py-3">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-tinta-700">{{ $categoria->nombre }}</h2>
                        <span class="text-xs tabular-nums text-tinta-400">{{ $categoria->tiposParte->count() }}</span>
                        <span class="ml-auto flex gap-2 text-xs">
                            <button type="button" @click="marcarTodo(true); contar()"
                                    class="font-medium text-marca-700 underline-offset-2 hover:underline">Todas</button>
                            <button type="button" @click="marcarTodo(false); contar()"
                                    class="font-medium text-tinta-500 underline-offset-2 hover:underline">Ninguna</button>
                        </span>
                    </header>

                    {{-- El scroll interno sólo desde `lg`. En la tablet del
                         mostrador eran doce cajas con scroll propio dentro de
                         una página que también tiene scroll: arrastrar el dedo
                         dentro de una tarjeta movía la tarjeta y no la página,
                         y se veían 8 piezas de 40. Con el dedo eso se siente
                         trabado; con el ratón y una rueda, no. --}}
                    <ul x-ref="lista" class="space-y-0.5 p-2 lg:max-h-80 lg:overflow-y-auto">
                        @foreach ($categoria->tiposParte as $tipo)
                            <li class="flex items-center gap-1 rounded pr-2 hover:bg-tinta-50">
                                <label class="flex grow cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm">
                                    <input type="checkbox" name="tipos[]" value="{{ $tipo->id }}"
                                           @checked(old('tipos', $marcados->keys()->all()) && in_array($tipo->id, (array) old('tipos', $marcados->keys()->all()), true))
                                           class="size-4 shrink-0 rounded border-tinta-300 text-marca-700">
                                    <span>{{ $tipo->nombre }}</span>
                                </label>

                                {{-- La única puerta a la ficha de la pieza.
                                     La pantalla existía y funcionaba —referencia,
                                     descripción, foto, visible— pero no había un
                                     solo enlace hacia ella en todo el panel: para
                                     entrar había que teclear la URL a mano. --}}
                                @if ($pieza = $marcados->get($tipo->id))
                                    <a href="{{ route('panel.catalogo.producto', $pieza) }}"
                                       title="Editar referencia, foto y descripción de esta pieza"
                                       class="shrink-0 rounded px-1.5 py-1 text-xs font-semibold text-marca-700 underline-offset-2 hover:underline">Editar</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                Guardar cambios
            </button>
        </div>
    </form>
@endsection
