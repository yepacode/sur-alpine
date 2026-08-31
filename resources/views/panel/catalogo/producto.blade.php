@extends('panel.layout')

@section('titulo', $producto->nombre)

@section('contenido')
    <a href="{{ route('panel.catalogo.editar', $producto->vehiculo) }}"
       class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
        ← {{ $producto->vehiculo->nombre_completo }}
    </a>

    <h1 class="mt-4 text-2xl font-bold tracking-tight">{{ $producto->nombre }}</h1>
    <p class="mt-1 text-sm text-tinta-500">
        {{ $producto->tipoParte->categoria->nombre }} · {{ $producto->tipoParte->nombre }}
        · <a href="{{ route('producto', $producto) }}" target="_blank" rel="noopener" class="text-marca-700 hover:underline">Ver en el sitio</a>
    </p>

    <form method="post" action="{{ route('panel.catalogo.guardar-producto', $producto) }}" enctype="multipart/form-data"
          class="mt-6 grid max-w-3xl gap-4 rounded-xl border border-tinta-200 bg-white p-6">
        @csrf
        @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600'; @endphp

        <div>
            <label for="referencia" class="text-sm font-medium">Referencia del fabricante</label>
            <input id="referencia" name="referencia" value="{{ old('referencia', $producto->referencia) }}" class="{{ $campo }}">
            <p class="mt-1 text-xs text-tinta-500">Es por donde busca un mecánico. El buscador del sitio la indexa.</p>
        </div>

        <div>
            <label for="descripcion" class="text-sm font-medium">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4" class="{{ $campo }}">{{ old('descripcion', $producto->descripcion) }}</textarea>
        </div>

        <div>
            <label for="imagen" class="text-sm font-medium">Foto del repuesto</label>
            @if ($producto->imagen)
                <img src="{{ $producto->imagen }}" alt="" width="160" height="160"
                     class="mt-2 size-40 rounded-lg border border-tinta-200 object-contain p-2">
            @endif
            <input id="imagen" type="file" name="imagen" accept="image/*"
                   class="mt-2 block w-full rounded-lg border border-tinta-300 p-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-marca-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-marca-700">
            <p class="mt-1 text-xs text-tinta-500">Hasta 4 MB. Si no hay foto propia se usa la de la categoría.</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="publicado" value="1" @checked(old('publicado', $producto->publicado))
                   class="size-4 rounded border-tinta-300 text-marca-700">
            Visible en el sitio
        </label>

        <div>
            <button type="submit" class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                Guardar ficha
            </button>
        </div>
    </form>
@endsection
