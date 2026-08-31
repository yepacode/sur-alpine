@extends('panel.layout')

@section('titulo', $nota->exists ? 'Editar nota' : 'Nueva nota')

@section('contenido')
    <a href="{{ route('panel.notas') }}" class="text-sm text-marca-700 hover:underline">← Volver a noticias</a>

    <h1 class="mt-3 text-2xl font-bold tracking-tight">
        {{ $nota->exists ? 'Editar nota' : 'Escribir una nota' }}
    </h1>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-alerta-400 bg-alerta-50 px-4 py-3 text-sm text-alerta-700">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" enctype="multipart/form-data"
          action="{{ $nota->exists ? route('panel.notas.actualizar', $nota) : route('panel.notas.guardar') }}"
          class="mt-6 grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="space-y-5 lg:col-span-2">
            <div class="rounded-xl border border-tinta-200 bg-white p-5">
                <label for="titulo" class="block text-sm font-semibold text-tinta-800">Título</label>
                <input id="titulo" name="titulo" type="text" maxlength="200" required
                       value="{{ old('titulo', $nota->titulo) }}"
                       class="mt-2 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm">
                @if ($nota->exists)
                    <p class="mt-2 text-xs text-tinta-500">
                        La dirección no cambia aunque cambies el título:
                        <span class="font-mono">/noticias/{{ $nota->slug }}</span>. Así no se rompen los
                        enlaces que ya pasaste por WhatsApp.
                    </p>
                @endif
            </div>

            <div class="rounded-xl border border-tinta-200 bg-white p-5">
                <label for="resumen" class="block text-sm font-semibold text-tinta-800">Arranque</label>
                <p class="mt-1 text-xs text-tinta-500">
                    Las dos o tres líneas que se leen en la tarjeta de la portada. Termina la frase:
                    aquí no se corta sola.
                </p>
                <textarea id="resumen" name="resumen" rows="3" maxlength="400" required
                          class="mt-2 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm">{{ old('resumen', $nota->resumen) }}</textarea>
            </div>

            <div class="rounded-xl border border-tinta-200 bg-white p-5">
                <label for="cuerpo" class="block text-sm font-semibold text-tinta-800">Texto de la nota</label>
                <p class="mt-1 text-xs text-tinta-500">
                    Un renglón por párrafo. Empieza un renglón con
                    <code class="rounded bg-tinta-100 px-1 font-mono">##</code> para un subtítulo, o con
                    <code class="rounded bg-tinta-100 px-1 font-mono">-</code> para una viñeta.
                </p>
                <textarea id="cuerpo" name="cuerpo" rows="22" maxlength="20000" required
                          class="mt-2 w-full rounded-lg border border-tinta-300 px-3 py-2.5 font-mono text-[13px] leading-relaxed">{{ old('cuerpo', $nota->cuerpo) }}</textarea>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl border border-tinta-200 bg-white p-5">
                <label for="categoria" class="block text-sm font-semibold text-tinta-800">Categoría</label>
                <input id="categoria" name="categoria" type="text" maxlength="60" required list="categorias-notas"
                       value="{{ old('categoria', $nota->categoria ?: 'Noticias') }}"
                       class="mt-2 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm">
                <datalist id="categorias-notas">
                    <option value="Noticias"></option>
                    <option value="Tips"></option>
                    <option value="De interés"></option>
                </datalist>
            </div>

            <div class="rounded-xl border border-tinta-200 bg-white p-5">
                <label for="imagen" class="block text-sm font-semibold text-tinta-800">Foto</label>
                <p class="mt-1 text-xs text-tinta-500">
                    Apaisada, tipo 16:9. WebP, JPG o PNG, máximo 4 MB.
                </p>

                @if ($nota->imagen)
                    <img src="{{ $nota->imagen }}" alt="" class="mt-3 aspect-[100/56] w-full rounded-lg object-cover">
                @endif

                <input id="imagen" name="imagen" type="file" accept="image/webp,image/jpeg,image/png"
                       class="mt-3 w-full text-sm">
            </div>

            <div class="space-y-4 rounded-xl border border-tinta-200 bg-white p-5">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="publicada" value="0">
                    <input type="checkbox" name="publicada" value="1" class="size-4"
                           @checked(old('publicada', $nota->publicada ?? true))>
                    <span class="text-sm font-semibold text-tinta-800">Publicada</span>
                </label>
                <p class="text-xs text-tinta-500">
                    Sin marcar, la nota queda como borrador: no sale en la portada ni en
                    <span class="font-mono">/noticias</span>, y su dirección da 404.
                </p>

                <div>
                    <label for="publicada_en" class="block text-sm font-semibold text-tinta-800">Fecha</label>
                    <input id="publicada_en" name="publicada_en" type="date"
                           value="{{ old('publicada_en', $nota->publicada_en?->toDateString() ?? now()->toDateString()) }}"
                           class="mt-2 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm">
                    <p class="mt-1 text-xs text-tinta-500">
                        Si la pones en el futuro, la nota espera hasta ese día para asomarse.
                    </p>
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-marca-700 px-4 py-3 text-sm font-semibold text-white hover:bg-marca-800">
                Guardar nota
            </button>
        </div>
    </form>

    @if ($nota->exists)
        {{-- Fuera del formulario de arriba: un formulario dentro de otro no es
             HTML válido y el navegador lo desarma donde quiere. --}}
        <form method="post" action="{{ route('panel.notas.borrar', $nota) }}" class="mt-8"
              {{-- Dice las dos cosas que faltaban: que la dirección que ya
                   circula por WhatsApp queda rota, y que existe una salida
                   reversible —desmarcar «Publicada» ya la esconde—. --}}
              onsubmit="return confirm('¿Eliminar «{{ addslashes($nota->titulo) }}»?

No se puede deshacer, y su dirección web deja de funcionar para quien ya la tenga guardada o la haya recibido por WhatsApp.

Si sólo quieres esconderla, cancela y desmarca «Publicada».')">
            @csrf
            <button type="submit" class="text-sm font-semibold text-alerta-600 underline-offset-2 hover:underline">
                Eliminar esta nota
            </button>
        </form>
    @endif
@endsection
