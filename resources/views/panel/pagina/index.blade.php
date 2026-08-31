@extends('panel.layout')

{{-- El título y el h1 dicen lo MISMO que el menú: el cliente hace clic
     en «Textos e imágenes» y tiene que aterrizar en «Textos e imágenes». --}}
@section('titulo', 'Textos e imágenes')

@section('contenido')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Textos e imágenes</h1>
            <p class="mt-1 max-w-2xl text-sm text-tinta-500">
                El sitio por bloques: buscador, cabecera, cotización, contacto, quiénes somos…
                Adentro de cada uno: los textos, botones y fotos que salen ahí, y su SEO
                (título y descripción para Google y modelos de IA).
            </p>
        </div>
    </div>

    {{-- Era la única vista del panel sin bloque de errores. El controlador sí
         los devuelve —una foto que GD no sabe leer, por ejemplo— pero la
         página recargaba igual y en silencio: el cliente sube un HEIC del
         iPhone, no pasa nada, y concluye que el panel está roto. --}}
    @if ($errors->any())
        <div role="alert" class="mt-6 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
            <p class="font-semibold">No pudimos guardar todo:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('panel.pagina.guardar') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf

        @foreach ($secciones as $slug => $s)
            @php
                $seoRow = isset($s['seo']) ? ($seo[$s['seo']['ruta']] ?? null) : null;
            @endphp

            {{-- La sección con el error queda abierta.
                 Antes un error cerraba las dieciséis: el cliente leía «no
                 pudimos guardar todo» y no tenía forma de saber en cuál de
                 ellas estaba el problema. --}}
            @php
                $tieneError = collect($s['textos'])
                    ->contains(fn ($t) => $errors->has('imagenes.'.($textos[$t['clave']]->id ?? 0)));
            @endphp

            <details @if ($tieneError) open @endif class="group rounded-xl border border-tinta-200 bg-white open:border-marca-300 open:shadow-sm">
                <summary class="flex cursor-pointer items-center justify-between gap-4 px-5 py-4 marker:hidden [&::-webkit-details-marker]:hidden">
                    <div>
                        <h2 class="font-titulo text-base font-semibold text-tinta-900">{{ $s['titulo'] }}</h2>
                        <p class="mt-0.5 text-xs text-tinta-500">{{ $s['subtitulo'] }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-tinta-500">
                        <span class="hidden rounded-full bg-tinta-100 px-2 py-1 font-semibold uppercase tracking-wide text-tinta-500 sm:inline">
                            {{-- «1 textos» delataba la plantilla. --}}
                            {{ count($s['textos']) }} {{ count($s['textos']) === 1 ? 'campo' : 'campos' }}
                            @if ($seoRow) · SEO @endif
                        </span>
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                             class="size-5 text-tinta-400 transition-transform group-open:rotate-180"
                             aria-hidden="true">
                            <polyline points="5 8 10 13 15 8"/>
                        </svg>
                    </div>
                </summary>

                <div class="border-t border-tinta-100 px-5 py-5 space-y-6">

                    {{-- Textos y botones de esta sección --}}
                    @if (count($s['textos']))
                        <div class="space-y-4">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-tinta-500">Textos y botones</h3>

                            @foreach ($s['textos'] as $t)
                                @php $fila = $textos[$t['clave']] ?? null; @endphp
                                @if ($fila)
                                <div class="grid gap-3 sm:grid-cols-[1fr_1.4fr] sm:items-start">
                                    <div>
                                        <label for="t-{{ $fila->id }}" class="block text-sm font-medium text-tinta-800">
                                            {{ $fila->rotulo }}
                                            @if ($fila->tipo === 'boton')
                                                <span class="ml-1 rounded bg-alerta-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-alerta-700">Botón</span>
                                            @endif
                                        </label>
                                        @if ($fila->valor_ejemplo && $fila->tipo !== 'imagen')
                                            <p class="mt-1 text-xs text-tinta-500">
                                                Original: <span class="italic">"{{ $fila->valor_ejemplo }}"</span>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($fila->tipo === 'imagen')
                                            {{-- La foto actual y el botón para cambiarla. El
                                                 archivo se convierte solo a WebP en los anchos
                                                 que esa pieza necesita; dejarlo vacío no borra
                                                 la que ya está. --}}
                                            <div class="flex flex-wrap items-center gap-4">
                                                @if ($fila->vista_previa)
                                                    <img src="{{ $fila->vista_previa }}" alt="" loading="lazy" decoding="async"
                                                         class="h-20 w-32 rounded border border-tinta-200 bg-tinta-100 object-cover">
                                                @else
                                                    <span class="grid h-20 w-32 place-items-center rounded border border-dashed border-tinta-300 text-xs text-tinta-500">
                                                        sin foto
                                                    </span>
                                                @endif
                                                <div class="min-w-48 flex-1">
                                                    <input id="t-{{ $fila->id }}" type="file"
                                                           name="imagenes[{{ $fila->id }}]"
                                                           accept="image/webp,image/jpeg,image/png"
                                                           class="w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-marca-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-marca-700">
                                                    <p class="mt-1 text-xs text-tinta-500">
                                                        JPG, PNG o WebP, hasta 8 MB. La dejamos liviana para el celular.
                                                        Si no eliges nada, se queda la de ahora.
                                                    </p>
                                                </div>
                                            </div>
                                        @elseif ($fila->tipo === 'parrafo')
                                            <textarea id="t-{{ $fila->id }}" name="textos[{{ $fila->id }}]"
                                                      rows="2" maxlength="500"
                                                      class="w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">{{ $fila->valor }}</textarea>
                                        @else
                                            <input id="t-{{ $fila->id }}" type="text"
                                                   name="textos[{{ $fila->id }}]"
                                                   value="{{ $fila->valor }}" maxlength="200"
                                                   class="w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        @endif
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- SEO COMPLETO de la página que esta sección representa --}}
                    @if ($seoRow)
                        @php $sid = $seoRow->id; @endphp
                        <div class="rounded-lg border border-marca-100 bg-marca-50/40 p-5 space-y-5"
                             x-data="{ subTab: 'basico' }">
                            {{-- Cerrado por defecto.

                                 Al abrir una sección de tres campos, el cliente
                                 se encontraba antes con `og:locale:alternate`,
                                 `max-snippet`, `noimageindex`, `changefreq` y
                                 `rel="prev"`. Lo que venía a cambiar quedaba
                                 sepultado bajo jerga que nadie le tradujo. --}}
                            <div x-data="{ abierto: false }">
                            <button type="button" @click="abierto = ! abierto"
                                    :aria-expanded="abierto"
                                    class="flex w-full flex-wrap items-center justify-between gap-2 text-left">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-marca-800">
                                    Cómo se ve en Google
                                    <span class="font-normal normal-case text-tinta-500">(avanzado)</span>
                                </h3>
                                <span class="text-xs font-semibold text-marca-700" x-text="abierto ? 'Cerrar' : 'Abrir'"></span>
                            </button>
                            <div x-show="abierto" x-cloak class="mt-4 space-y-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-marca-800">
                                    SEO de la página · <span class="font-mono normal-case text-[11px]">{{ $s['seo']['ruta'] }}</span>
                                </h3>
                                <p class="text-[11px] text-tinta-500">Deja en blanco cualquier campo para usar el valor original del sitio.</p>
                            </div>

                            {{-- Sub-pestañas para no volver la tarjeta un muro. --}}
                            <nav class="flex flex-wrap gap-1 border-b border-marca-100 text-xs">
                                @foreach ([
                                    'basico' => 'Básico',
                                    'og' => 'Compartir',
                                    'robots' => 'Robots + Sitemap',
                                    'avanzado' => 'Avanzado',
                                ] as $k => $l)
                                    <button type="button" @click="subTab = '{{ $k }}'"
                                            :class="subTab === '{{ $k }}' ? 'border-marca-700 text-marca-800' : 'border-transparent text-tinta-500 hover:text-tinta-700'"
                                            class="border-b-2 px-3 py-2 font-semibold uppercase tracking-wide">
                                        {{ $l }}
                                    </button>
                                @endforeach
                            </nav>

                            {{-- ── BÁSICO ────────────────────────────── --}}
                            <div x-show="subTab === 'basico'" class="space-y-3">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-tinta-600">Meta title <span class="text-tinta-400 normal-case">(≤ 60 car.)</span></label>
                                        <input type="text" name="seo[{{ $sid }}][titulo]" value="{{ $seoRow->titulo }}" maxlength="200"
                                               placeholder="Se arma solo si lo dejas vacío"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                                        {{-- Aviso puesto porque paso: alguien escribio aqui un titulo
                                             sin la marca y esa pagina quedo siendo la unica del sitio
                                             que no dice «Importadora Sur Alpine» en la pestana ni en el
                                             resultado de Google. Vacio se arma solo y siempre la lleva. --}}
                                        <p class="mt-1 text-xs text-tinta-500">
                                            Si escribes algo aquí, es EXACTAMENTE lo que sale: acuérdate de terminar
                                            en «· Importadora Sur Alpine». Vacío se arma solo y ya la lleva.
                                        </p>
                                    </div>
                                </div>

                                {{-- Aquí había tres campos que se guardaban y no
                                     cambiaban NADA: «Slug de la URL», «H1 propio»
                                     y «Focus keyword».

                                     El del slug era el peor: avisaba en rojo que
                                     «cambiarlo rompe enlaces» sobre un campo que
                                     no enruta nada —las URLs viven en
                                     `routes/web.php`—, así que asustaba sin
                                     motivo. El H1 se guardaba y ninguna vista lo
                                     leía. Y la «focus keyword» era para un
                                     diagnóstico que no existe.

                                     Un campo que se guarda y no hace nada es peor
                                     que no tenerlo: el cliente cree que editó
                                     algo. Las columnas siguen en base por si
                                     algún día se usan de verdad. --}}

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-tinta-600">Meta description <span class="text-tinta-400 normal-case">(≤ 160 car.)</span></label>
                                    <textarea name="seo[{{ $sid }}][descripcion]" rows="2" maxlength="400"
                                              class="mt-1 w-full rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">{{ $seoRow->descripcion }}</textarea>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-tinta-600">Palabras clave <span class="text-tinta-400 normal-case">(coma separadas)</span></label>
                                        <input type="text" name="seo[{{ $sid }}][palabras_clave]" value="{{ $seoRow->palabras_clave }}" maxlength="300"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-tinta-600">Canonical <span class="text-tinta-400 normal-case">(URL absoluta)</span></label>
                                        <input type="url" name="seo[{{ $sid }}][canonical]" value="{{ $seoRow->canonical }}" maxlength="300"
                                               placeholder="{{ url('/'.($seoRow->slug ?? '')) }}"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 bg-white px-3 py-2 text-sm">
                                    </div>
                                </div>
                            </div>

                            {{-- ── COMPARTIR (OG + Twitter) ──────────── --}}
                            <div x-show="subTab === 'og'" class="space-y-4" x-cloak>
                                <p class="text-xs text-tinta-600">Cómo se ve la tarjeta cuando alguien pega el enlace en WhatsApp, Facebook o X.</p>

                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3 space-y-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Open Graph (Facebook / WhatsApp)</legend>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:title</label>
                                            <input type="text" name="seo[{{ $sid }}][og_titulo]" value="{{ $seoRow->og_titulo }}" maxlength="200"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:type</label>
                                            <select name="seo[{{ $sid }}][og_tipo]" class="selector mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                                @foreach (['website', 'article', 'product', 'profile'] as $t)
                                                    <option value="{{ $t }}" @selected($seoRow->og_tipo === $t)>{{ $t }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-tinta-600">og:description</label>
                                        <textarea name="seo[{{ $sid }}][og_descripcion]" rows="2" maxlength="400"
                                                  class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">{{ $seoRow->og_descripcion }}</textarea>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-[2fr_1fr]">
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:image (URL absoluta)</label>
                                            <input type="url" name="seo[{{ $sid }}][og_imagen]" value="{{ $seoRow->og_imagen }}" maxlength="300"
                                                   placeholder="https://…"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:image:alt</label>
                                            <input type="text" name="seo[{{ $sid }}][og_imagen_alt]" value="{{ $seoRow->og_imagen_alt }}" maxlength="200"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-4">
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:locale</label>
                                            <input type="text" name="seo[{{ $sid }}][og_locale]" value="{{ $seoRow->og_locale }}" maxlength="20"
                                                   placeholder="es_CO"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm font-mono">
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs text-tinta-600">og:locale:alternate <span class="text-tinta-400">(coma separadas)</span></label>
                                            <input type="text" name="seo[{{ $sid }}][og_locale_alternate]" value="{{ $seoRow->og_locale_alternate }}" maxlength="200"
                                                   placeholder="en_US, pt_BR"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm font-mono">
                                        </div>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:image:width (px)</label>
                                            <input type="number" name="seo[{{ $sid }}][og_imagen_ancho]" value="{{ $seoRow->og_imagen_ancho }}" min="0"
                                                   placeholder="1200"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm tabular-nums">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">og:image:height (px)</label>
                                            <input type="number" name="seo[{{ $sid }}][og_imagen_alto]" value="{{ $seoRow->og_imagen_alto }}" min="0"
                                                   placeholder="630"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm tabular-nums">
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3 space-y-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Twitter / X</legend>
                                    <div class="grid gap-3 sm:grid-cols-[1fr_2fr]">
                                        <div>
                                            <label class="block text-xs text-tinta-600">Card</label>
                                            <select name="seo[{{ $sid }}][twitter_card]" class="selector mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                                @foreach (['summary_large_image', 'summary', 'app', 'player'] as $c)
                                                    <option value="{{ $c }}" @selected($seoRow->twitter_card === $c)>{{ $c }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">twitter:title</label>
                                            <input type="text" name="seo[{{ $sid }}][twitter_titulo]" value="{{ $seoRow->twitter_titulo }}" maxlength="200"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-tinta-600">twitter:description</label>
                                        <textarea name="seo[{{ $sid }}][twitter_descripcion]" rows="2" maxlength="400"
                                                  class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">{{ $seoRow->twitter_descripcion }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-tinta-600">twitter:image (URL absoluta)</label>
                                        <input type="url" name="seo[{{ $sid }}][twitter_imagen]" value="{{ $seoRow->twitter_imagen }}" maxlength="300"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs text-tinta-600">twitter:site <span class="text-tinta-400">(handle @cuenta)</span></label>
                                            <input type="text" name="seo[{{ $sid }}][twitter_sitio]" value="{{ $seoRow->twitter_sitio }}" maxlength="40"
                                                   placeholder="@suralpine"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm font-mono">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">twitter:creator <span class="text-tinta-400">(autor)</span></label>
                                            <input type="text" name="seo[{{ $sid }}][twitter_creador]" value="{{ $seoRow->twitter_creador }}" maxlength="40"
                                                   placeholder="@autor"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm font-mono">
                                        </div>
                                    </div>
                                </fieldset>
                            </div>

                            {{-- ── ROBOTS + SITEMAP ──────────────────── --}}
                            <div x-show="subTab === 'robots'" class="space-y-4" x-cloak>
                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Indexación (meta robots)</legend>
                                    <div class="mt-2 grid gap-2 text-sm text-tinta-700 sm:grid-cols-2">
                                        <input type="hidden" name="seo[{{ $sid }}][indexable]" value="0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="seo[{{ $sid }}][indexable]" value="1"
                                                   @checked($seoRow->indexable) class="size-4 rounded border-tinta-300 text-marca-700">
                                            Permitir indexar (<code>index</code>)
                                        </label>
                                        <input type="hidden" name="seo[{{ $sid }}][seguir_enlaces]" value="0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="seo[{{ $sid }}][seguir_enlaces]" value="1"
                                                   @checked($seoRow->seguir_enlaces) class="size-4 rounded border-tinta-300 text-marca-700">
                                            Seguir enlaces (<code>follow</code>)
                                        </label>

                                        <input type="hidden" name="seo[{{ $sid }}][noarchive]" value="0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="seo[{{ $sid }}][noarchive]" value="1"
                                                   @checked($seoRow->noarchive) class="size-4 rounded border-tinta-300 text-marca-700">
                                            <code>noarchive</code> (sin caché de Google)
                                        </label>
                                        <input type="hidden" name="seo[{{ $sid }}][nosnippet]" value="0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="seo[{{ $sid }}][nosnippet]" value="1"
                                                   @checked($seoRow->nosnippet) class="size-4 rounded border-tinta-300 text-marca-700">
                                            <code>nosnippet</code> (sin extracto)
                                        </label>
                                        <input type="hidden" name="seo[{{ $sid }}][noimageindex]" value="0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="seo[{{ $sid }}][noimageindex]" value="1"
                                                   @checked($seoRow->noimageindex) class="size-4 rounded border-tinta-300 text-marca-700">
                                            <code>noimageindex</code> (no indexar sus imágenes)
                                        </label>
                                        <input type="hidden" name="seo[{{ $sid }}][notranslate]" value="0">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="seo[{{ $sid }}][notranslate]" value="1"
                                                   @checked($seoRow->notranslate) class="size-4 rounded border-tinta-300 text-marca-700">
                                            <code>notranslate</code>
                                        </label>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="block text-xs text-tinta-600">max-snippet</label>
                                            <input type="number" name="seo[{{ $sid }}][max_snippet]" value="{{ $seoRow->max_snippet }}"
                                                   placeholder="-1 = sin límite"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm tabular-nums">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">max-image-preview</label>
                                            <select name="seo[{{ $sid }}][max_image_preview]"
                                                    class="selector mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                                @foreach (['large', 'standard', 'none'] as $v)
                                                    <option value="{{ $v }}" @selected($seoRow->max_image_preview === $v)>{{ $v }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">max-video-preview</label>
                                            <input type="number" name="seo[{{ $sid }}][max_video_preview]" value="{{ $seoRow->max_video_preview }}"
                                                   placeholder="-1 = sin límite"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm tabular-nums">
                                        </div>
                                    </div>

                                    <p class="mt-3 text-[11px] text-tinta-500">
                                        Etiqueta actual: <code class="font-mono">{{ $seoRow->metaRobots() }}</code>
                                    </p>
                                </fieldset>

                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Sitemap por página</legend>
                                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            @if ($seoRow->ruta === 'inicio')
                                                {{-- La portada va siempre. La casilla se ve, para que
                                                     el cuadro no quede raro, pero no se puede apagar:
                                                     sacar la raíz del sitio del sitemap no es algo que
                                                     nadie quiera, y no se nota hasta perder el tráfico. --}}
                                                <label class="flex items-center gap-2 pt-6 text-sm text-tinta-500">
                                                    <input type="checkbox" checked disabled
                                                           class="size-4 rounded border-tinta-300 text-tinta-400">
                                                    Incluir en <code>sitemap.xml</code>
                                                </label>
                                                <p class="mt-1 text-xs text-tinta-500">La portada va siempre.</p>
                                            @else
                                                <input type="hidden" name="seo[{{ $sid }}][sitemap_incluir]" value="0">
                                                <label class="flex items-center gap-2 pt-6 text-sm">
                                                    <input type="checkbox" name="seo[{{ $sid }}][sitemap_incluir]" value="1"
                                                           @checked($seoRow->sitemap_incluir) class="size-4 rounded border-tinta-300 text-marca-700">
                                                    Incluir en <code>sitemap.xml</code>
                                                </label>
                                            @endif
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">changefreq</label>
                                            <select name="seo[{{ $sid }}][sitemap_frecuencia]"
                                                    class="selector mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                                @foreach (['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'] as $v)
                                                    <option value="{{ $v }}" @selected($seoRow->sitemap_frecuencia === $v)>{{ $v }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">priority (0.0 – 1.0)</label>
                                            <input type="number" step="0.1" min="0" max="1"
                                                   name="seo[{{ $sid }}][sitemap_prioridad]" value="{{ $seoRow->sitemap_prioridad }}"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm tabular-nums">
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3 space-y-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Paginación seriada</legend>
                                    <div>
                                        <label class="block text-xs text-tinta-600">rel="prev"</label>
                                        <input type="url" name="seo[{{ $sid }}][rel_prev]" value="{{ $seoRow->rel_prev }}" maxlength="300"
                                               placeholder="URL de la página anterior"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-tinta-600">rel="next"</label>
                                        <input type="url" name="seo[{{ $sid }}][rel_next]" value="{{ $seoRow->rel_next }}" maxlength="300"
                                               placeholder="URL de la página siguiente"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                    </div>
                                </fieldset>
                            </div>

                            {{-- ── AVANZADO ─────────────────────────── --}}
                            <div x-show="subTab === 'avanzado'" class="space-y-4" x-cloak>

                                {{-- Schema.org sugerido y JSON-LD extra --}}
                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3 space-y-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Datos estructurados (Schema.org)</legend>
                                    <div>
                                        <label class="block text-xs text-tinta-600">Tipo principal sugerido</label>
                                        <select name="seo[{{ $sid }}][schema_tipo]"
                                                class="selector mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                            <option value="">— sin cambio —</option>
                                            {{-- La lista sale del controlador, que es quien la valida al guardar. --}}
                                            @foreach (\App\Http\Controllers\Panel\ConfiguracionPaginaController::TIPOS_DE_SCHEMA as $t)
                                                <option value="{{ $t }}" @selected($seoRow->schema_tipo === $t)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-tinta-600">JSON-LD adicional (opcional)</label>
                                        <p class="mt-0.5 text-[11px] text-tinta-500">Se inyecta tal cual dentro de un <code>&lt;script type="application/ld+json"&gt;</code>.</p>
                                        <textarea name="seo[{{ $sid }}][json_ld_extra]" rows="4"
                                                  placeholder='{ "@@context": "https://schema.org", "@@type": "Article", ... }'
                                                  class="mt-1 w-full rounded-lg border border-tinta-300 bg-white px-3 py-2 font-mono text-xs">{{ $seoRow->json_ld_extra }}</textarea>
                                    </div>
                                </fieldset>

                                {{-- Article: sólo tiene sentido si la página es tipo post --}}
                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3 space-y-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Article (para posts / notas)</legend>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs text-tinta-600">Publicado en</label>
                                            <input type="datetime-local" name="seo[{{ $sid }}][article_publicado_en]"
                                                   value="{{ optional($seoRow->article_publicado_en)->format('Y-m-d\TH:i') }}"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">Modificado en</label>
                                            <input type="datetime-local" name="seo[{{ $sid }}][article_modificado_en]"
                                                   value="{{ optional($seoRow->article_modificado_en)->format('Y-m-d\TH:i') }}"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">Sección</label>
                                            <input type="text" name="seo[{{ $sid }}][article_seccion]" value="{{ $seoRow->article_seccion }}" maxlength="80"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-tinta-600">Autor</label>
                                            <input type="text" name="seo[{{ $sid }}][article_autor]" value="{{ $seoRow->article_autor }}" maxlength="120"
                                                   class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-tinta-600">Etiquetas <span class="text-tinta-400">(coma separadas)</span></label>
                                        <input type="text" name="seo[{{ $sid }}][article_etiquetas]" value="{{ $seoRow->article_etiquetas }}" maxlength="200"
                                               class="mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                    </div>
                                </fieldset>

                                {{-- hreflang: idiomas alternativos --}}
                                <fieldset class="rounded-lg border border-tinta-200 bg-white p-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-tinta-500">Idiomas alternativos (hreflang)</legend>
                                    <div class="mt-2 space-y-2"
                                         x-data="{ pares: {{ (int) max(3, count($seoRow->hreflang ?? [])) }} }">
                                        <template x-for="i in pares" :key="i">
                                            <div class="grid gap-2 sm:grid-cols-[1fr_3fr]">
                                                <input type="text" :name="`seo[{{ $sid }}][hreflang][${i-1}][lang]`"
                                                       :value="({{ json_encode(array_values($seoRow->hreflang ?? [])) }}[i-1] || {}).lang || ''"
                                                       placeholder="es-CO / en / x-default"
                                                       maxlength="20"
                                                       class="rounded-lg border border-tinta-300 px-3 py-2 text-sm font-mono">
                                                <input type="url" :name="`seo[{{ $sid }}][hreflang][${i-1}][href]`"
                                                       :value="({{ json_encode(array_values($seoRow->hreflang ?? [])) }}[i-1] || {}).href || ''"
                                                       placeholder="https://…"
                                                       maxlength="300"
                                                       class="rounded-lg border border-tinta-300 px-3 py-2 text-sm">
                                            </div>
                                        </template>
                                        <button type="button" @click="pares++"
                                                class="rounded-lg border border-tinta-300 bg-white px-3 py-1.5 text-xs font-semibold text-tinta-700 hover:bg-tinta-50">
                                            + Agregar idioma
                                        </button>
                                    </div>
                                </fieldset>

                                {{-- HTML libre en el <head> --}}
                                <fieldset class="rounded-lg border border-alerta-200 bg-alerta-50/40 p-3">
                                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-alerta-700">HTML extra en el &lt;head&gt;</legend>
                                    <p class="mt-1 text-[11px] text-alerta-700">
                                        Se inyecta tal cual: sirve para pegar Google Tag Manager, Meta Pixel o verificaciones de propietario. Pegar algo mal escrito puede romper la página.
                                    </p>
                                    <textarea name="seo[{{ $sid }}][head_extra]" rows="4"
                                              placeholder='<!-- Ejemplo: GTM, Pixel, verificación... -->'
                                              class="mt-2 w-full rounded-lg border border-tinta-300 bg-white px-3 py-2 font-mono text-xs">{{ $seoRow->head_extra }}</textarea>
                                </fieldset>
                            </div>
                            </div>{{-- cierra el cuerpo colapsable --}}
                            </div>{{-- cierra el x-data del colapsable --}}
                        </div>
                    @endif

                    @if ($slug === 'catalogo')
                        <p class="rounded-lg bg-tinta-50 p-3 text-xs text-tinta-500">
                            La imagen y el nombre de cada categoría se editan en la sección
                            <a href="{{ route('panel.categorias') }}" class="font-semibold text-marca-700 hover:underline">Categorías del catálogo</a>.
                        </p>
                    @endif
                </div>
            </details>
        @endforeach

        <div class="sticky bottom-4 z-10 flex justify-end pt-2">
            <button type="submit"
                    class="rounded-lg bg-marca-700 px-6 py-2.5 font-semibold text-sm text-white shadow-lg hover:bg-marca-800">
                Guardar cambios
            </button>
        </div>
    </form>
@endsection
