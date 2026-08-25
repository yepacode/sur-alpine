@extends('panel.layout')

@section('titulo', 'Cargar catálogo')

@section('contenido')
    <a href="{{ route('panel.catalogo') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
        ← Catálogo
    </a>

    <h1 class="mt-4 text-2xl font-bold tracking-tight">Cargar catálogo desde Excel</h1>
    <p class="mt-1 max-w-2xl text-sm text-tinta-500">
        Sube el <strong>Formato Importación Suralpine</strong> tal como lo trabajan: una fila por vehículo y
        un 1 en cada pieza que lleva. Primero te mostramos qué va a pasar, y sólo escribimos si confirmas.
    </p>

    @if ($errors->any())
        <div role="alert" class="mt-6 max-w-2xl rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $resultado)
        <form method="post" action="{{ route('panel.catalogo.previsualizar') }}" enctype="multipart/form-data"
              class="mt-6 max-w-2xl rounded-xl border border-tinta-200 bg-white p-6">
            @csrf
            <label for="archivo" class="text-sm font-medium">Archivo Excel</label>
            <input id="archivo" type="file" name="archivo" accept=".xlsx,.xls" required
                   class="mt-2 block w-full rounded-lg border border-tinta-300 p-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-marca-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-marca-700">
            <p class="mt-2 text-xs text-tinta-500">Hasta 20 MB. Se lee la primera hoja del libro.</p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
                    Revisar archivo
                </button>
                <a href="{{ route('panel.catalogo.plantilla') }}" class="text-sm text-marca-700 underline-offset-2 hover:underline">
                    Descargar el formato
                </a>
            </div>
        </form>
    @else
        <section class="mt-6 rounded-xl border border-tinta-200 bg-white p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-tinta-500">
                Vista previa · {{ $nombreOriginal ?? 'archivo' }}
            </h2>

            @if ($resultado->errores)
                <div role="alert" class="mt-4 rounded-lg border border-alerta-500 bg-alerta-500/5 p-4 text-sm text-alerta-700">
                    <p class="font-semibold">{{ count($resultado->errores) }} fila(s) con problemas:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach (array_slice($resultado->errores, 0, 10) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    @if (count($resultado->errores) > 10)
                        <p class="mt-2">… y {{ count($resultado->errores) - 10 }} más.</p>
                    @endif
                    <p class="mt-3">Esas filas se van a saltar. El resto sí se importa.</p>
                </div>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-tinta-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-tinta-500">Vehículos en el archivo</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">@numero($resultado->vehiculosLeidos)</p>
                </div>
                <div class="rounded-lg bg-tinta-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-tinta-500">Piezas marcadas</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">@numero($resultado->celdasMarcadas)</p>
                </div>
                <div class="rounded-lg bg-tinta-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-tinta-500">Filas con error</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ count($resultado->errores) }}</p>
                </div>
            </div>

            @if ($resultado->vehiculos)
                <h3 class="mt-6 text-sm font-semibold uppercase tracking-wide text-tinta-500">
                    Vehículos que trae el archivo
                </h3>
                <div class="mt-2 max-h-96 overflow-y-auto rounded-lg border border-tinta-200">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 border-b border-tinta-200 bg-tinta-50 text-left text-xs uppercase tracking-wide text-tinta-500">
                            <tr>
                                <th class="px-3 py-2 font-medium">Marca</th>
                                <th class="px-3 py-2 font-medium">Modelo</th>
                                <th class="px-3 py-2 font-medium">Cilindraje</th>
                                <th class="px-3 py-2 font-medium">Años</th>
                                <th class="px-3 py-2 text-right font-medium">Piezas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-tinta-200">
                            @foreach ($resultado->vehiculos as $fila)
                                <tr>
                                    <td class="px-3 py-2">{{ $fila['marca'] }}</td>
                                    <td class="px-3 py-2">{{ $fila['modelo'] }}</td>
                                    <td class="px-3 py-2">{{ $fila['cilindraje'] }}</td>
                                    <td class="px-3 py-2 tabular-nums text-tinta-600">{{ $fila['anios'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fila['partes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-6 rounded-lg bg-marca-50 p-4 text-sm text-marca-900">
                Los datos que carga el equipo —referencia, foto y descripción— <strong>no se pierden</strong>.
                La importación sólo agrega y actualiza la compatibilidad.
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <form method="post" action="{{ route('panel.catalogo.confirmar') }}">
                    @csrf
                    <input type="hidden" name="archivo" value="{{ $archivo }}">
                    <button type="submit" class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                        Confirmar e importar
                    </button>
                </form>
                <a href="{{ route('panel.catalogo.importar') }}"
                   class="rounded-lg border border-tinta-300 px-6 py-3 font-semibold text-tinta-700 hover:bg-tinta-50">
                    Cancelar
                </a>
            </div>
        </section>
    @endif
@endsection
