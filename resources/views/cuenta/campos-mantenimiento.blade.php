{{--
    Los campos de un mantenimiento, compartidos por «anotar» y «corregir».

    Existe para que los dos formularios no se separen: si mañana se agrega un
    campo y sólo se toca uno de ellos, el que quede atrás guarda a medias sin
    que nada falle.

    · $m       — el mantenimiento a corregir, o null si es uno nuevo.
    · $prefijo — para que los `id` y los `for` no se repitan cuando hay diez
                 formularios de corrección en la misma página.
--}}
@php
    $m ??= null;
    $prefijo ??= 'nuevo';
    $campo = 'mt-1 w-full rounded-lg border border-tinta-300 px-3 py-2.5 text-sm focus:border-marca-600';

    // `old()` sólo aplica al formulario que se acaba de enviar, y para saber
    // cuál fue está el campo oculto `_editando`.
    //
    // Antes se decidía sólo por «¿hay un $m?», así que corregir el registro
    // nº 7 y equivocarse en un dato devolvía ese formulario a los valores de
    // la base: lo que la persona había escrito desaparecía sin dejar rastro, y
    // el error que veía arriba parecía ser del formulario de «anotar uno
    // nuevo», que además se abría solo. Se perdía el trabajo y encima el aviso
    // señalaba al sitio equivocado.
    $esteSeEnvio = $m
        ? (string) old('_editando') === (string) $m->id
        : old('_editando') === null;

    $viejo = fn (string $llave, $porDefecto = null) => $esteSeEnvio
        ? old($llave, $m ? ($m->{$llave} ?? $porDefecto) : $porDefecto)
        : ($m ? ($m->{$llave} ?? $porDefecto) : $porDefecto);
@endphp

<div>
    <label for="{{ $prefijo }}-placa" class="text-sm font-medium">Placa</label>
    <input id="{{ $prefijo }}-placa" name="placa" value="{{ $viejo('placa') }}" required maxlength="10"
           list="mis-placas" placeholder="ABC 123" class="{{ $campo }} uppercase tabular-nums">
</div>

<div>
    <label for="{{ $prefijo }}-vehiculo" class="text-sm font-medium">
        Vehículo <span class="font-normal text-tinta-500">(opcional)</span>
    </label>
    <select id="{{ $prefijo }}-vehiculo" name="vehiculo_id" class="{{ $campo }}">
        <option value="">Sin asociar</option>
        @foreach ($vehiculos as $vehiculo)
            <option value="{{ $vehiculo->id }}" @selected($viejo('vehiculo_id') == $vehiculo->id)>
                {{ $vehiculo->pivot->alias ?: $vehiculo->nombre_completo }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label for="{{ $prefijo }}-tipo" class="text-sm font-medium">Qué se le hizo</label>
    <input id="{{ $prefijo }}-tipo" name="tipo" value="{{ $viejo('tipo') }}" required maxlength="80"
           list="tipos-comunes" placeholder="Cambio de aceite" class="{{ $campo }}">
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label for="{{ $prefijo }}-fecha" class="text-sm font-medium">Fecha</label>
        <input id="{{ $prefijo }}-fecha" type="date" name="fecha"
               value="{{ $m ? $m->fecha->toDateString() : old('fecha', today()->toDateString()) }}"
               required max="{{ today()->toDateString() }}" class="{{ $campo }} tabular-nums">
    </div>
    <div>
        <label for="{{ $prefijo }}-km" class="text-sm font-medium">Kilometraje</label>
        <input id="{{ $prefijo }}-km" type="number" name="kilometraje" value="{{ $viejo('kilometraje') }}"
               required min="0" inputmode="numeric" class="{{ $campo }} tabular-nums">
    </div>
</div>

<div class="sm:col-span-2">
    <p class="text-sm font-medium">Avísame del próximo</p>
    <div class="mt-1 flex flex-wrap items-center gap-2">
        <span class="text-sm text-tinta-600">cada</span>
        <input type="number" name="periodicidad_valor" value="{{ $viejo('periodicidad_valor', 6) }}"
               required min="1" aria-label="Cada cuánto"
               class="w-24 rounded-lg border border-tinta-300 px-3 py-2.5 text-sm tabular-nums">
        <select name="periodicidad_tipo" aria-label="Unidad"
                class="rounded-lg border border-tinta-300 px-3 py-2.5 text-sm">
            @foreach (\App\Models\Mantenimiento::PERIODICIDADES as $valor => $texto)
                <option value="{{ $valor }}" @selected($viejo('periodicidad_tipo', 'meses') === $valor)>
                    {{ $texto }}
                </option>
            @endforeach
        </select>
    </div>
    <p class="mt-1 text-xs text-tinta-500">
        Por kilómetros se suman a los de hoy; por días o meses se cuentan desde la fecha.
    </p>
</div>

<div class="sm:col-span-2">
    <label for="{{ $prefijo }}-notas" class="text-sm font-medium">
        Notas <span class="font-normal text-tinta-500">(opcional)</span>
    </label>
    <textarea id="{{ $prefijo }}-notas" name="notas" rows="2" maxlength="1000"
              placeholder="Marca del aceite, taller, lo que quieras recordar"
              class="{{ $campo }}">{{ $viejo('notas') }}</textarea>
</div>
