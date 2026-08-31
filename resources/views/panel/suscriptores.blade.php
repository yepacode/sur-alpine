@extends('panel.layout')

@section('titulo', 'Suscriptores')

@section('contenido')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Suscriptores al newsletter</h1>
            <p class="mt-1 max-w-2xl text-sm text-tinta-500">
                Los correos que entran por el formulario del pie.
                <strong class="tabular-nums text-tinta-800">{{ number_format($total) }}</strong>
                {{ $total === 1 ? 'activo' : 'activos' }}.
            </p>
        </div>

        @if ($suscriptores->isNotEmpty())
            <a href="{{ route('panel.suscriptores.exportar') }}"
               class="rounded-lg border border-tinta-300 bg-white px-4 py-2.5 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
                Descargar CSV
            </a>
        @endif
    </div>

    <p class="mt-4 rounded-xl bg-marca-50 px-4 py-3 text-sm text-marca-900 ring-1 ring-marca-100">
        No se pueden agregar correos a mano: un correo que escriba el equipo no tiene
        el consentimiento de nadie detrás, y eso es justo lo que la Ley 1581 pide poder
        demostrar. Si alguien pide que lo saquen, dale de baja aquí mismo: la ley te
        da un plazo para hacerlo y la fecha queda registrada.
    </p>

    @if ($suscriptores->isEmpty())
        <p class="mt-8 rounded-xl border border-dashed border-tinta-300 bg-white px-4 py-10 text-center text-sm text-tinta-500">
            Todavía nadie se ha suscrito.
        </p>
    @else
        <div class="mt-6 overflow-hidden rounded-xl border border-tinta-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-tinta-50 text-left text-xs uppercase tracking-wide text-tinta-500">
                    <tr>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Se suscribió desde</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-tinta-100">
                    @foreach ($suscriptores as $s)
                        <tr class="hover:bg-tinta-50/60">
                            <td class="px-4 py-3 font-medium text-tinta-900">{{ $s->correo }}</td>
                            <td class="px-4 py-3 max-w-xs truncate font-mono text-xs text-tinta-500">{{ $s->origen ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-tinta-600">{{ $s->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700' => ! $s->baja_en,
                                    'bg-tinta-100 text-tinta-600' => (bool) $s->baja_en,
                                ])>{{ $s->baja_en ? 'De baja' : 'Activo' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @unless ($s->baja_en)
                                    <form method="post" action="{{ route('panel.suscriptores.baja', $s) }}"
                                          onsubmit="return confirm('¿Dar de baja a {{ $s->correo }}? Deja de recibir correos y queda la fecha registrada.')">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-tinta-500 underline-offset-2 hover:text-alerta-600 hover:underline">
                                            Dar de baja
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs tabular-nums text-tinta-400">{{ $s->baja_en->format('d/m/Y') }}</span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $suscriptores->links() }}</div>
    @endif
@endsection
