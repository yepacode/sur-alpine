@extends('panel.layout')

@section('titulo', 'Campañas de la portada')

@section('contenido')
    @php
        $campo = 'w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm focus:border-marca-600';
    @endphp

    <div>
        <h1 class="text-2xl font-bold tracking-tight">Campañas de la portada</h1>
        <p class="mt-1 max-w-2xl text-sm text-tinta-500">
            Son las imágenes que rotan arriba del todo. Se ordenan por el número:
            el más bajo va primero. Los cambios entran a la portada al instante.
        </p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ─── Subir una nueva ─────────────────────────────────────────────── --}}
    <section class="mt-6 rounded-xl border border-tinta-200 bg-white p-5">
        <h2 class="font-bold">Subir una campaña</h2>

        <form method="post" action="{{ route('panel.banners.guardar') }}" enctype="multipart/form-data"
              class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            @csrf

            <div>
                <label for="imagen" class="text-sm font-medium">Imagen</label>
                <input id="imagen" type="file" name="imagen" required accept="image/webp,image/jpeg,image/png"
                       class="{{ $campo }} file:mr-3 file:rounded file:border-0 file:bg-marca-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-marca-700">
                {{-- Se convierte sola: el diseñador manda JPG de 3000 px y no
                     puede depender de que alguien se acuerde de comprimirlo. --}}
                <p class="mt-1 text-xs text-tinta-500">
                    JPG, PNG o WebP, hasta 8 MB. Nosotros la convertimos y la
                    dejamos liviana para el celular.
                </p>
            </div>

            <div>
                <label for="alt" class="text-sm font-medium">De qué es</label>
                <input id="alt" name="alt" value="{{ old('alt') }}" required maxlength="150"
                       placeholder="Amortiguadores Gabriel" class="{{ $campo }}">
                <p class="mt-1 text-xs text-tinta-500">
                    Es lo que lee quien no ve la imagen, y lo que aparece si no carga.
                </p>
            </div>

            <button type="submit"
                    class="rounded-lg bg-marca-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-marca-800">
                Subir
            </button>
        </form>
    </section>

    {{-- ─── Las que ya están ────────────────────────────────────────────── --}}
    @if ($banners->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-tinta-300 bg-white p-8 text-center text-sm text-tinta-600">
            No hay campañas. Mientras no haya ninguna, la portada arranca directo
            en el buscador por vehículo.
        </p>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($banners as $banner)
                <li @class([
                    'overflow-hidden rounded-xl border bg-white',
                    'border-tinta-200' => $banner->activo,
                    'border-dashed border-tinta-300 opacity-70' => ! $banner->activo,
                ])>
                    <img src="/img/banners/{{ $banner->archivo }}-900.webp" alt="{{ $banner->alt }}"
                         loading="lazy" decoding="async"
                         class="h-28 w-full bg-tinta-100 object-cover object-left">

                    <form method="post" action="{{ route('panel.banners.actualizar', $banner) }}"
                          class="grid gap-3 border-t border-tinta-200 p-4 sm:grid-cols-[1fr_6rem_auto_auto] sm:items-end">
                        @csrf

                        <div>
                            <label for="alt-{{ $banner->id }}" class="text-xs font-medium uppercase tracking-wide text-tinta-500">
                                De qué es
                            </label>
                            <input id="alt-{{ $banner->id }}" name="alt" value="{{ $banner->alt }}" required
                                   maxlength="150" class="{{ $campo }} mt-1">
                        </div>

                        <div>
                            <label for="orden-{{ $banner->id }}" class="text-xs font-medium uppercase tracking-wide text-tinta-500">
                                Orden
                            </label>
                            <input id="orden-{{ $banner->id }}" type="number" name="orden" value="{{ $banner->orden }}"
                                   required min="0" max="999" class="{{ $campo }} mt-1 tabular-nums">
                        </div>

                        <label class="flex items-center gap-2 pb-2.5 text-sm">
                            {{-- Apagar sin borrar: la campaña de diciembre vuelve
                                 el año que viene y el archivo ya está subido. --}}
                            <input type="checkbox" name="activo" value="1" @checked($banner->activo)
                                   class="size-4 rounded border-tinta-300 text-marca-700">
                            Se muestra
                        </label>

                        <button type="submit"
                                class="rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-marca-800">
                            Guardar
                        </button>
                    </form>

                    <div class="flex items-center justify-between gap-4 border-t border-tinta-100 bg-tinta-50/60 px-4 py-2">
                        <span class="font-mono text-xs text-tinta-500">{{ $banner->archivo }}</span>

                        <form method="post" action="{{ route('panel.banners.borrar', $banner) }}"
                              onsubmit="return confirm('¿Borrar esta campaña? También se borran sus imágenes del servidor.')">
                            @csrf
                            <button type="submit"
                                    class="text-xs font-semibold text-tinta-500 hover:text-alerta-600">
                                Borrar
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
