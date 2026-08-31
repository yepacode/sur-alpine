@extends('panel.layout')

@section('titulo', 'Datos y correos')

@section('contenido')
    <h1 class="text-2xl font-bold tracking-tight">Datos y correos</h1>
    <p class="mt-1 max-w-2xl text-sm text-tinta-500">
        Lo que se cambia aquí no requiere tocar el código ni llamar a nadie. Debajo de
        cada campo dice dónde se ve, para no tener que adivinar.
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
        @php $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600'; @endphp

        <section class="rounded-xl border border-tinta-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-tinta-700">Solicitudes de cotización</h2>

            <div class="mt-4">
                <label for="correos_cotizacion" class="text-sm font-medium">Correos que reciben las solicitudes</label>
                <textarea id="correos_cotizacion" name="correos_cotizacion" rows="3" required
                          class="{{ $campo }}">{{ old('correos_cotizacion', $valores['correos_cotizacion']) }}</textarea>
                <p class="mt-1 text-xs text-tinta-500">
                    Uno por línea, o separados por coma. <strong>Todos</strong> reciben copia de cada
                    solicitud que llega por la web. Si aquí no hay ninguno, las solicitudes se
                    siguen guardando en «Solicitudes» pero nadie recibe el aviso.
                </p>

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
                    <p class="mt-1 text-xs text-tinta-500">
                        Sale en la barra azul de arriba, en el pie y en los correos. Escríbelo como
                        quieras que se lea: <span class="whitespace-nowrap">(601) 366 0066</span>.
                    </p>
                </div>
                <div>
                    <label for="celulares" class="text-sm font-medium">Celulares</label>
                    <input id="celulares" name="celulares" value="{{ old('celulares', $valores['celulares']) }}" class="{{ $campo }}">
                    <p class="mt-1 text-xs text-tinta-500">
                        Separados por coma. Se turnan con el PBX en la barra azul, uno cada
                        pocos segundos.
                    </p>
                </div>
                <div>
                    <label for="whatsapp" class="text-sm font-medium">WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $valores['whatsapp']) }}" class="{{ $campo }}"
                           placeholder="573134223861">
                    <p class="mt-1 text-xs text-tinta-500">
                        Con el <strong>57</strong> adelante y sin espacios ni signos: así lo pide
                        WhatsApp. Es el botón verde que flota abajo a la derecha; si lo dejas en
                        blanco, ese botón desaparece.
                    </p>
                </div>
                <div>
                    <label for="direccion" class="text-sm font-medium">Dirección</label>
                    <input id="direccion" name="direccion" value="{{ old('direccion', $valores['direccion']) }}" class="{{ $campo }}">
                    <p class="mt-1 text-xs text-tinta-500">
                        Sólo la calle; la ciudad va en el campo siguiente. Sale en «Dónde estamos»,
                        en el pie y en el correo de cotización.
                    </p>
                </div>

                <div>
                    <label for="ciudad" class="text-sm font-medium">Ciudad</label>
                    <input id="ciudad" name="ciudad" value="{{ old('ciudad', $valores['ciudad']) }}" class="{{ $campo }}">
                    <p class="mt-1 text-xs text-tinta-500">
                        También la usa Google para saber dónde queda el negocio.
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium">Horario para Google</p>
                    <p class="mt-1 text-xs text-tinta-500">
                        En formato de 24 horas, así: <code>08:00-18:00</code>. Esto NO es lo que se
                        lee en «Contáctenos» —eso se escribe en «Textos e imágenes»—: es lo que
                        Google usa para mostrar «Abierto ahora» y para confirmar que esta web y el
                        local del Restrepo son el mismo negocio. Déjalo vacío si prefieres no
                        publicarlo; un horario equivocado manda gente a un local cerrado.
                    </p>
                    <div class="mt-2 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="horario_semana" class="text-xs font-medium uppercase tracking-wide text-tinta-500">Lunes a viernes</label>
                            <input id="horario_semana" name="horario_semana" placeholder="08:00-18:00"
                                   value="{{ old('horario_semana', $valores['horario_semana'] ?? '') }}" class="{{ $campo }} tabular-nums">
                        </div>
                        <div>
                            <label for="horario_sabado" class="text-xs font-medium uppercase tracking-wide text-tinta-500">Sábados</label>
                            <input id="horario_sabado" name="horario_sabado" placeholder="08:00-16:00"
                                   value="{{ old('horario_sabado', $valores['horario_sabado'] ?? '') }}" class="{{ $campo }} tabular-nums">
                        </div>
                        <div>
                            <label for="horario_festivo" class="text-xs font-medium uppercase tracking-wide text-tinta-500">Festivos</label>
                            <input id="horario_festivo" name="horario_festivo" placeholder="09:00-13:00"
                                   value="{{ old('horario_festivo', $valores['horario_festivo'] ?? '') }}" class="{{ $campo }} tabular-nums">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="facebook" class="text-sm font-medium">Facebook</label>
                    <input id="facebook" type="url" name="facebook" value="{{ old('facebook', $valores['facebook']) }}" class="{{ $campo }}">
                    <p class="mt-1 text-xs text-tinta-500">
                        La dirección completa de la página. En blanco, el enlace del pie no aparece.
                    </p>
                </div>
                <div>
                    <label for="instagram" class="text-sm font-medium">Instagram</label>
                    <input id="instagram" type="url" name="instagram" value="{{ old('instagram', $valores['instagram']) }}" class="{{ $campo }}">
                    <p class="mt-1 text-xs text-tinta-500">
                        Igual que Facebook: la dirección completa, o en blanco para esconderlo.
                    </p>
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
        <span class="ml-2 text-xs text-tinta-500">
            Manda una solicitud de ejemplo —inventada, no la de ningún cliente— a tu propio
            correo. Sirve para comprobar que salen y que no caen en «no deseados».
        </span>
    </form>
@endsection
