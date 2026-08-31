@extends('panel.layout')

@section('titulo', 'Mensajes')

@section('contenido')
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Mensajes de «Contáctenos»</h1>
        <p class="mt-1 max-w-2xl text-sm text-tinta-500">
            Lo que llega por el formulario de la página de contacto.
            @if ($pendientes)
                <strong class="text-alerta-700">{{ $pendientes }}</strong>
                {{ $pendientes === 1 ? 'sin atender' : 'sin atender' }}.
            @else
                Todos atendidos.
            @endif
        </p>
    </div>

    @if ($mensajes->isEmpty())
        <p class="mt-8 rounded-xl border border-dashed border-tinta-300 bg-white px-4 py-10 text-center text-sm text-tinta-500">
            Todavía no ha escrito nadie.
        </p>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($mensajes as $mensaje)
                <li @class([
                    'rounded-xl border bg-white p-5',
                    'border-tinta-200' => (bool) $mensaje->atendido_en,
                    'border-marca-300 ring-1 ring-marca-100' => ! $mensaje->atendido_en,
                ])>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-tinta-900">{{ $mensaje->nombre }}</p>
                            <p class="text-sm text-tinta-500">
                                <a href="mailto:{{ $mensaje->email }}" class="text-marca-700 hover:underline">{{ $mensaje->email }}</a>
                                <span aria-hidden="true"> · </span>
                                <span class="tabular-nums">{{ $mensaje->created_at->format('d/m/Y H:i') }}</span>
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($mensaje->atendido_en)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Atendido {{ $mensaje->atendido_en->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="rounded-full bg-marca-50 px-2.5 py-1 text-xs font-semibold text-marca-700">Sin atender</span>
                            @endif

                            @if (! $mensaje->correo_enviado_en)
                                <span class="rounded-full bg-alerta-50 px-2.5 py-1 text-xs font-semibold text-alerta-700"
                                      title="{{ $mensaje->error_envio }}">
                                    El correo no salió
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Texto escrito por un desconocido: se pinta escapado y
                         respetando sus saltos de línea, nunca como HTML. --}}
                    <p class="mt-4 whitespace-pre-line text-[15px] leading-relaxed text-tinta-700">{{ $mensaje->mensaje }}</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="mailto:{{ $mensaje->email }}?subject={{ rawurlencode('Re: tu mensaje a Importadora Sur Alpine') }}"
                           class="rounded-lg bg-marca-700 px-4 py-2 text-xs font-semibold text-white hover:bg-marca-800">
                            Responder
                        </a>

                        <form method="post" action="{{ route('panel.mensajes.atender', $mensaje) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-tinta-300 bg-white px-4 py-2 text-xs font-semibold text-tinta-700 hover:bg-tinta-50">
                                {{ $mensaje->atendido_en ? 'Marcar sin atender' : 'Marcar atendido' }}
                            </button>
                        </form>

                        @if (! $mensaje->correo_enviado_en)
                            <form method="post" action="{{ route('panel.mensajes.reenviar', $mensaje) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-alerta-300 bg-white px-4 py-2 text-xs font-semibold text-alerta-700 hover:bg-alerta-50">
                                    Reintentar el correo
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $mensajes->links() }}</div>
    @endif
@endsection
