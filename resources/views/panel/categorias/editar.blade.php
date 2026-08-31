@extends('panel.layout')

@section('titulo', $categoria->nombre)

@section('contenido')
    <nav class="mb-4 text-sm">
        <a href="{{ route('panel.categorias') }}" class="text-marca-700 hover:underline">← Todas las categorías</a>
    </nav>

    <h1 class="text-2xl font-bold tracking-tight">Editar «{{ $categoria->nombre }}»</h1>
    <p class="mt-1 text-sm text-tinta-500">
        Ruta pública:
        <a href="{{ route('categoria', $categoria) }}" target="_blank" rel="noopener"
           class="rounded bg-tinta-100 px-1.5 py-0.5 font-mono text-xs text-marca-700 underline-offset-2 hover:underline">{{ route('categoria', $categoria) }}</a>
        <span class="text-tinta-400">(se abre en otra pestaña)</span>
    </p>

    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('panel.categorias.guardar', $categoria) }}"
          enctype="multipart/form-data" class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        @csrf

        <div class="space-y-4 rounded-xl border border-tinta-200 bg-white p-5">
            <div>
                <label for="nombre" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">Nombre</label>
                <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $categoria->nombre) }}"
                       required maxlength="80"
                       class="w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label for="orden" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">
                    Orden <span class="text-tinta-400">(número: primero el más bajo)</span>
                </label>
                <input id="orden" type="number" name="orden" value="{{ old('orden', $categoria->orden) }}"
                       min="0" max="999"
                       class="w-32 rounded-lg border border-tinta-300 px-3 py-2 text-sm tabular-nums">
            </div>

            <div>
                <label for="imagen" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-tinta-500">
                    Foto de la categoría
                </label>
                <input id="imagen" type="file" name="imagen" accept="image/webp,image/jpeg,image/png"
                       class="block w-full text-sm text-tinta-600 file:mr-4 file:rounded-lg file:border-0 file:bg-marca-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-marca-700 hover:file:bg-marca-100">
                <p class="mt-1 text-xs text-tinta-500">
                    Ancho recomendado 640 px. La foto se reemplaza al reeditar; no se guardan versiones viejas.
                </p>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="rounded-lg bg-marca-700 px-5 py-2.5 font-semibold text-sm text-white hover:bg-marca-800">
                    Guardar
                </button>
            </div>
        </div>

        <aside class="rounded-xl border border-tinta-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-tinta-500">Vista previa</p>
            @if ($categoria->imagen)
                <img src="{{ $categoria->imagen }}" alt="" class="mt-3 aspect-video w-full rounded-lg object-cover">
                <p class="mt-2 break-all text-xs text-tinta-500">{{ $categoria->imagen }}</p>
            @else
                <p class="mt-3 rounded-lg border border-dashed border-tinta-300 p-6 text-center text-sm text-tinta-500">
                    Todavía no tiene foto.
                </p>
            @endif
        </aside>
    </form>
@endsection
