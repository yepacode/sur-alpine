@extends('panel.layout')

@section('titulo', $vehiculo->exists ? 'Editar vehículo' : 'Vehículo nuevo')

@section('contenido')
    <a href="{{ route('panel.catalogo') }}" class="text-sm font-medium text-marca-700 underline-offset-2 hover:underline">
        ← Catálogo
    </a>

    <h1 class="mt-4 text-2xl font-bold tracking-tight">
        {{ $vehiculo->exists ? 'Editar '.$vehiculo->nombre_completo : 'Vehículo nuevo' }}
    </h1>
    <p class="mt-1 max-w-2xl text-sm text-tinta-500">
        @if ($vehiculo->exists)
            Corregir el rango de años o el cilindraje no toca las piezas ya marcadas.
        @else
            Si la marca o el modelo no existen todavía, se crean solos. Después marcas las piezas que lleva.
        @endif
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

    <form method="post"
          action="{{ $vehiculo->exists ? route('panel.catalogo.editar-datos', $vehiculo) : route('panel.catalogo.guardar-vehiculo') }}"
          class="mt-6 grid max-w-2xl gap-4 rounded-xl border border-tinta-200 bg-white p-6 sm:grid-cols-2">
        @csrf
        @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600'; @endphp

        <div>
            <label for="marca" class="text-sm font-medium">Marca</label>
            <input id="marca" name="marca" value="{{ old('marca', $vehiculo->exists ? $vehiculo->modelo->marca->nombre : '') }}" required list="marcas" class="{{ $campo }}">
            <datalist id="marcas">
                @foreach ($marcas as $marca)
                    <option value="{{ $marca->nombre }}"></option>
                @endforeach
            </datalist>
        </div>

        <div>
            <label for="modelo" class="text-sm font-medium">Modelo</label>
            <input id="modelo" name="modelo" value="{{ old('modelo', $vehiculo->exists ? $vehiculo->modelo->nombre : '') }}" required class="{{ $campo }}">
        </div>

        <div>
            <label for="cilindraje" class="text-sm font-medium">Cilindraje</label>
            <input id="cilindraje" name="cilindraje" value="{{ old('cilindraje', $vehiculo->cilindraje) }}" required class="{{ $campo }}"
                   placeholder="1600, 1700 DIESEL, 1300 CARB…">
            <p class="mt-1 text-xs text-tinta-500">Admite texto: hay motores como «1700 DIESEL» o «1600 M.N».</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="anio_inicio" class="text-sm font-medium">Año inicial</label>
                <input id="anio_inicio" type="number" name="anio_inicio" value="{{ old('anio_inicio', $vehiculo->anio_inicio) }}"
                       required min="1950" max="{{ now()->year + 2 }}" class="{{ $campo }} tabular-nums">
            </div>
            <div>
                <label for="anio_fin" class="text-sm font-medium">Año final</label>
                <input id="anio_fin" type="number" name="anio_fin" value="{{ old('anio_fin', $vehiculo->anio_fin) }}"
                       required min="1950" max="{{ now()->year + 2 }}" class="{{ $campo }} tabular-nums">
            </div>
        </div>

        <div class="sm:col-span-2">
            <button type="submit" class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                {{ $vehiculo->exists ? 'Guardar cambios' : 'Guardar y marcar piezas' }}
            </button>
        </div>
    </form>
@endsection
