@extends('panel.layout')

@section('titulo', 'Noticias')

@section('contenido')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Noticias y novedades</h1>
            <p class="mt-1 max-w-2xl text-sm text-tinta-500">
                Son las notas de «Actualízate con Nosotros». En la portada salen las cuatro
                más recientes; el resto queda en <a href="{{ route('noticias') }}" class="font-medium text-marca-700 underline underline-offset-2">/noticias</a>.
            </p>
        </div>

        <a href="{{ route('panel.notas.crear') }}"
           class="rounded-lg bg-marca-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
            Escribir una nota
        </a>
    </div>

    @if ($notas->isEmpty())
        <p class="mt-10 rounded-xl border border-dashed border-tinta-300 bg-white px-4 py-10 text-center text-sm text-tinta-500">
            Todavía no hay notas. La sección «Actualízate con Nosotros» no aparece en la portada hasta que haya al menos una.
        </p>
    @else
        <div class="mt-6 overflow-hidden rounded-xl border border-tinta-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-tinta-50 text-left text-xs uppercase tracking-wide text-tinta-500">
                    <tr>
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Título</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-tinta-100">
                    @foreach ($notas as $nota)
                        <tr class="hover:bg-tinta-50/60">
                            <td class="px-4 py-3">
                                @if ($nota->imagen)
                                    <img src="{{ $nota->imagen }}" alt="" class="h-10 w-16 rounded object-cover">
                                @else
                                    <span class="text-xs text-alerta-600">sin foto</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-tinta-900">{{ $nota->titulo }}</span>
                                <span class="mt-0.5 block font-mono text-xs text-tinta-500">/noticias/{{ $nota->slug }}</span>
                            </td>
                            <td class="px-4 py-3 text-tinta-600">{{ $nota->categoria }}</td>
                            <td class="px-4 py-3 tabular-nums text-tinta-600">
                                {{ $nota->publicada_en?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @php $enLinea = $nota->publicada && (! $nota->publicada_en || ! $nota->publicada_en->isFuture()); @endphp
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $enLinea,
                                    'bg-tinta-100 text-tinta-600' => ! $enLinea,
                                ])>
                                    {{ $enLinea ? 'Publicada' : ($nota->publicada ? 'Programada' : 'Borrador') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @if ($enLinea)
                                        <a href="{{ route('nota', $nota) }}" target="_blank" rel="noopener"
                                           class="rounded-lg border border-tinta-300 bg-white px-3 py-1.5 text-xs font-semibold text-tinta-700 hover:bg-tinta-50">
                                            Ver
                                        </a>
                                    @endif
                                    <a href="{{ route('panel.notas.editar', $nota) }}"
                                       class="rounded-lg border border-tinta-300 bg-white px-3 py-1.5 text-xs font-semibold text-tinta-700 hover:bg-tinta-50">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $notas->links() }}</div>
    @endif
@endsection
