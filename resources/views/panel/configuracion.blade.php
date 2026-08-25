@extends('panel.layout')

@section('titulo', 'Configuración')

@section('contenido')
    <h1 class="text-2xl font-bold tracking-tight">Configuración</h1>
    <p class="mt-1 max-w-2xl text-sm text-tinta-500">
        Lo que se cambia aquí no requiere tocar el código ni llamar a nadie.
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

    <form method="post" action="{{ route('panel.configuracion.guardar') }}" class="mt-6 max-w-2xl space-y-6">
        @csrf
        @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600 focus:outline-none'; @endphp

        <section class="rounded-xl border border-tinta-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-tinta-700">Solicitudes de cotización</h2>

            <div class="mt-4">
                <label for="correos_cotizacion" class="text-sm font-medium">Correos que reciben las solicitudes</label>
                <textarea id="correos_cotizacion" name="correos_cotizacion" rows="3" required
                          class="{{ $campo }}">{{ old('correos_cotizacion', $valores['correos_cotizacion']) }}</textarea>
                <p class="mt-1 text-xs text-tinta-500">Uno por línea, o separados por coma. Todos reciben copia.</p>

                @if ($destinos)
                    <p class="mt-2 text-xs text-tinta-600">
                        Ahora mismo llegan a:
                        @foreach ($destinos as $destino)
                            <span class="rounded bg-tinta-100 px-1.5 py-0.5">{{ $destino }}</span>
                        @endforeach
                    </p>
                @endif
            </div>
        </section>

        <section class="rounded-xl border border-tinta-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-tinta-700">Datos de contacto del sitio</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="telefono_pbx" class="text-sm font-medium">PBX</label>
                    <input id="telefono_pbx" name="telefono_pbx" value="{{ old('telefono_pbx', $valores['telefono_pbx']) }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="celulares" class="text-sm font-medium">Celulares</label>
                    <input id="celulares" name="celulares" value="{{ old('celulares', $valores['celulares']) }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="whatsapp" class="text-sm font-medium">WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $valores['whatsapp']) }}" class="{{ $campo }}"
                           placeholder="573134223861">
                </div>
                <div>
                    <label for="direccion" class="text-sm font-medium">Dirección</label>
                    <input id="direccion" name="direccion" value="{{ old('direccion', $valores['direccion']) }}" class="{{ $campo }}">
                    <p class="mt-1 text-xs text-tinta-500">Sólo la calle. La ciudad va en el campo siguiente.</p>
                </div>

                <div>
                    <label for="ciudad" class="text-sm font-medium">Ciudad</label>
                    <input id="ciudad" name="ciudad" value="{{ old('ciudad', $valores['ciudad']) }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="facebook" class="text-sm font-medium">Facebook</label>
                    <input id="facebook" type="url" name="facebook" value="{{ old('facebook', $valores['facebook']) }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="instagram" class="text-sm font-medium">Instagram</label>
                    <input id="instagram" type="url" name="instagram" value="{{ old('instagram', $valores['instagram']) }}" class="{{ $campo }}">
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-marca-700 px-6 py-3 font-semibold text-white hover:bg-marca-800">
                Guardar configuración
            </button>
        </div>
    </form>

    <form method="post" action="{{ route('panel.configuracion.probar') }}" class="mt-4 max-w-2xl">
        @csrf
        <button type="submit" class="rounded-lg border border-tinta-300 bg-white px-6 py-3 text-sm font-semibold text-tinta-700 hover:bg-tinta-50">
            Mandar un correo de prueba
        </button>
        <span class="ml-2 text-xs text-tinta-500">Usa la última solicitud recibida como muestra.</span>
    </form>
@endsection
