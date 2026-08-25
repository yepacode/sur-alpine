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

                    <ul x-ref="lista" class="max-h-80 space-y-0.5 overflow-y-auto p-2">
                        @foreach ($categoria->tiposParte as $tipo)
                            <li>
                                <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-tinta-50">
                                    <input type="checkbox" name="tipos[]" value="{{ $tipo->id }}"
                                           @checked($marcados->has($tipo->id))
                                           class="size-4 shrink-0 rounded border-tinta-300 text-marca-700">
                                    <span>{{ $tipo->nombre }}</span>
                                </label>
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
