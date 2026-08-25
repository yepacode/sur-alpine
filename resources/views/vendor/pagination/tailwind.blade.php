{{--
    Paginador con los tokens del sitio, en español y con `aria-current` real.

    Reescrito desde cero porque el que trae Laravel viene en inglés, con
    paleta `gray-*` en vez de `tinta-*`, con variantes `dark:` que se activan
    en una página que no tiene modo oscuro, y con «29272» sin separador
    de miles. Aparece en las 1.220 páginas del catálogo, así que era lo
    primero que se veía y lo que hacía que todo lo demás pareciera menos
    cuidado de lo que está.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación de resultados"
         class="flex flex-wrap items-center justify-between gap-3">

        <p class="text-sm text-tinta-500 cifra">
            @if ($paginator->total() === 1)
                <span class="font-medium">1</span> resultado
            @else
                <span class="font-medium">{{ number_format($paginator->firstItem(), 0, ',', '.') }}</span>
                a
                <span class="font-medium">{{ number_format($paginator->lastItem(), 0, ',', '.') }}</span>
                de
                <span class="font-medium">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
            @endif
        </p>

        <ul class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <li>
                    <span aria-label="Página anterior" aria-disabled="true"
                          class="grid size-10 place-items-center rounded-lg text-tinta-300">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4" aria-hidden="true">
                            <path d="M15 18 9 12l6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" aria-label="Página anterior" rel="prev"
                       class="grid size-10 place-items-center rounded-lg text-tinta-700 transition hover:bg-tinta-100 hover:text-marca-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4" aria-hidden="true">
                            <path d="M15 18 9 12l6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span aria-hidden="true" class="grid size-10 place-items-center text-tinta-400">…</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="cifra grid size-10 place-items-center rounded-lg bg-marca-700 text-sm font-semibold text-white">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="Ir a la página {{ $page }}"
                                   class="cifra grid size-10 place-items-center rounded-lg text-sm font-medium text-tinta-700 transition hover:bg-tinta-100 hover:text-marca-700">
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" aria-label="Página siguiente" rel="next"
                       class="grid size-10 place-items-center rounded-lg text-tinta-700 transition hover:bg-tinta-100 hover:text-marca-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
            @else
                <li>
                    <span aria-label="Página siguiente" aria-disabled="true"
                          class="grid size-10 place-items-center rounded-lg text-tinta-300">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="size-4" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
