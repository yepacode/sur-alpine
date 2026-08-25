@extends('panel.layout')

@section('titulo', 'Categorías')

@section('contenido')
    <div class="flex items-end justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Categorías del catálogo</h1>
            <p class="mt-1 text-sm text-tinta-500">
                Edita nombre, orden y foto de cada categoría. Los cambios entran a la portada al instante.
            </p>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-tinta-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-tinta-50 text-left text-xs uppercase tracking-wide text-tinta-500">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3 text-right">Orden</th>
                    <th class="px-4 py-3 text-right">Piezas</th>
                    <th class="px-4 py-3 text-right">Foto</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-tinta-100">
                @foreach ($categorias as $categoria)
                    <tr class="hover:bg-tinta-50/60">
                        <td class="px-4 py-3 font-medium text-tinta-900">{{ $categoria->nombre }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-tinta-500">{{ $categoria->slug }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $categoria->orden ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-tinta-600">{{ number_format($categoria->productos_count) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($categoria->imagen)
                                <img src="{{ $categoria->imagen }}" alt="" class="ml-auto size-10 rounded object-cover">
                            @else
                                <span class="text-xs text-alerta-600">sin foto</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('panel.categorias.editar', $categoria) }}"
                               class="rounded-lg border border-tinta-300 bg-white px-3 py-1.5 text-xs font-semibold text-tinta-700 hover:bg-tinta-50">
                                Editar
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
