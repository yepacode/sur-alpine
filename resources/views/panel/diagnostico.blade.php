@extends('panel.layout')

@section('titulo', 'Diagnóstico del servidor')

@section('contenido')
    <style>
        .diag h2 { font-weight: 700; color: #1866E0; margin: 24px 0 8px; font-size: 17px; }
        .diag h3 { font-weight: 600; margin: 12px 0 6px; font-size: 14px; }
        .diag table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 8px; }
        .diag th, .diag td { padding: 8px 10px; border-bottom: 1px solid #E5E7EB; text-align: left; vertical-align: top; }
        .diag th { background: #F3F4F6; font-weight: 600; }
        .diag code { background: #F3F4F6; padding: 2px 6px; border-radius: 3px; font-family: Consolas, monospace; font-size: 12px; word-break: break-all; }
        .diag .ok  { color: #15803D; font-weight: 700; }
        .diag .no  { color: #B91C1C; font-weight: 700; }
        .diag .aviso { color: #B45309; font-weight: 700; }
        .diag .caja { background: white; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .diag pre { background: #111827; color: #F9FAFB; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 11px; line-height: 1.4; max-height: 400px; }
        .diag .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
        .diag .badge-ok { background: #D1FAE5; color: #065F46; }
        .diag .badge-no { background: #FEE2E2; color: #991B1B; }
        .diag .badge-warn { background: #FEF3C7; color: #92400E; }
    </style>

    <div class="diag mx-auto max-w-5xl px-4 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-tinta-900">Diagnóstico del servidor</h1>
            <p class="mt-2 text-sm text-tinta-600">
                Esta pantalla mira permisos y symlinks del sistema de archivos.
                No cambia nada, sólo reporta. Cópiale al desarrollador todo lo que sale aquí.
            </p>
        </div>

        {{-- ─── SYMLINK ─────────────────────────────────────────────────── --}}
        <div class="caja">
            <h2>1 · Enlace simbólico <code>public/storage</code></h2>
            <table>
                <tr>
                    <th style="width: 40%;">Existe algo en la ruta</th>
                    <td><span class="badge {{ $symlink['existe'] ? 'badge-ok' : 'badge-no' }}">{{ $symlink['existe'] ? 'SÍ' : 'NO' }}</span></td>
                </tr>
                <tr>
                    <th>Es un symlink</th>
                    <td>
                        @if ($symlink['es_symlink'])
                            <span class="badge badge-ok">SÍ</span>
                        @elseif ($symlink['existe'])
                            <span class="badge badge-warn">NO — es carpeta normal</span>
                        @else
                            <span class="badge badge-no">NO EXISTE</span>
                        @endif
                    </td>
                </tr>
                @if ($symlink['es_symlink'])
                    <tr>
                        <th>Apunta a</th>
                        <td><code>{{ $symlink['apunta_a'] }}</code></td>
                    </tr>
                    <tr>
                        <th>El destino existe</th>
                        <td><span class="badge {{ $symlink['destino_real_existe'] ? 'badge-ok' : 'badge-no' }}">{{ $symlink['destino_real_existe'] ? 'SÍ' : 'NO — symlink roto' }}</span></td>
                    </tr>
                    <tr>
                        <th>Apunta al sitio correcto</th>
                        <td>
                            <span class="badge {{ $symlink['destino_correcto'] ? 'badge-ok' : 'badge-warn' }}">{{ $symlink['destino_correcto'] ? 'SÍ' : 'NO' }}</span>
                            <br><small>Esperado: <code>{{ $symlink['destino_esperado'] }}</code></small>
                        </td>
                    </tr>
                @endif
            </table>
        </div>

        {{-- ─── CARPETAS ────────────────────────────────────────────────── --}}
        <div class="caja">
            <h2>2 · Carpetas de subida — ¿puede el sitio escribir?</h2>
            <p class="text-xs text-tinta-500 mb-2">
                Prueba de escritura REAL: crea un archivo temporal, comprueba el resultado, y lo borra.
                Si dice «NO», el problema del banner o de la foto está aquí.
            </p>
            <table>
                <thead>
                    <tr>
                        <th style="width: 35%;">Carpeta</th>
                        <th>Existe</th>
                        <th>Permisos</th>
                        <th>¿Puede escribir?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($carpetas as $etiqueta => $c)
                        <tr>
                            <td>
                                <code>{{ $etiqueta }}</code>
                                @if ($c['error_escritura'])
                                    <br><small class="text-red-700">{{ $c['error_escritura'] }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $c['existe'] ? 'badge-ok' : 'badge-no' }}">{{ $c['existe'] ? 'SÍ' : 'NO' }}</span>
                            </td>
                            <td><code>{{ $c['permisos'] ?? '—' }}</code></td>
                            <td>
                                @if ($c['escribible'] === true)
                                    <span class="badge badge-ok">SÍ</span>
                                @elseif ($c['escribible'] === false)
                                    <span class="badge badge-no">NO</span>
                                @else
                                    <span class="badge badge-warn">no probada</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h3 class="mt-4">Últimos archivos guardados</h3>
            @foreach ($carpetas as $etiqueta => $c)
                @if (! empty($c['ultimos_archivos']))
                    <p class="text-xs mt-3 text-tinta-700"><strong>{{ $etiqueta }}</strong></p>
                    <table>
                        @foreach ($c['ultimos_archivos'] as $a)
                            <tr>
                                <td style="width: 50%;"><code>{{ $a['nombre'] }}</code></td>
                                <td style="width: 30%;">{{ $a['fecha'] }}</td>
                                <td>{{ $a['tamano'] ? number_format($a['tamano'] / 1024, 1).' KB' : '(carpeta)' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            @endforeach
        </div>

        {{-- ─── DISCO ───────────────────────────────────────────────────── --}}
        <div class="caja">
            <h2>3 · Espacio en disco</h2>
            <table>
                <tr><th style="width: 40%;">Libre</th><td>{{ $disco['libre_legible'] }}</td></tr>
                <tr><th>Total</th><td>{{ $disco['total_legible'] }}</td></tr>
                <tr>
                    <th>Uso</th>
                    <td>
                        {{ $disco['usado_pct'] ?? '—' }}%
                        @if ($disco['usado_pct'] >= 95)
                            <span class="badge badge-no">CRÍTICO — puede impedir escritura</span>
                        @elseif ($disco['usado_pct'] >= 85)
                            <span class="badge badge-warn">alto</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- ─── PHP ─────────────────────────────────────────────────────── --}}
        <div class="caja">
            <h2>4 · Entorno PHP</h2>
            <table>
                <tr><th style="width: 40%;">Versión PHP</th><td>{{ $php['version'] }}</td></tr>
                <tr>
                    <th>Extensión GD (redimensionar imágenes)</th>
                    <td>
                        @if ($php['gd'])
                            <span class="badge badge-ok">CARGADA</span> {{ $php['gd_info'] }}
                        @else
                            <span class="badge badge-no">FALTA</span> Sin esto no se pueden procesar imágenes.
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Extensión Fileinfo (detectar tipo)</th>
                    <td>
                        @if ($php['fileinfo'])
                            <span class="badge badge-ok">CARGADA</span>
                        @else
                            <span class="badge badge-no">FALTA</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th><code>open_basedir</code></th>
                    <td>
                        <code>{{ $php['open_basedir'] }}</code>
                        @if ($php['open_basedir'] !== '(sin restriccion)')
                            <br><small class="text-amber-800">Si el sitio intenta escribir fuera de estas rutas, PHP lo bloquea sin decir por qué.</small>
                        @endif
                    </td>
                </tr>
                <tr><th>Tamaño máximo de subida</th><td>{{ $php['upload_max_filesize'] }} · POST: {{ $php['post_max_size'] }}</td></tr>
                <tr><th>Memoria / Tiempo</th><td>{{ $php['memory_limit'] }} · {{ $php['max_execution_time'] }}s</td></tr>
            </table>
        </div>

        {{-- ─── LOG ─────────────────────────────────────────────────────── --}}
        <div class="caja">
            <h2>5 · Últimas 30 líneas del log de Laravel</h2>
            <p class="text-xs text-tinta-500 mb-2">
                Si al subir una imagen el proceso falla, aquí sale el error de verdad. Busca líneas que empiecen con
                <code>local.ERROR</code>.
            </p>
            @if (empty($log))
                <p class="text-sm text-tinta-500">El log está vacío o no se pudo leer.</p>
            @else
                <pre>@foreach ($log as $linea){{ $linea }}
@endforeach</pre>
            @endif
        </div>

        <div class="caja" style="border-color: #FBBF24; background: #FEF3C7;">
            <h2 style="color:#92400E;">Qué hacer con este diagnóstico</h2>
            <p class="text-sm">
                Toma una captura de esta pantalla completa (con Windows + Mayúsculas + S, seleccionando de arriba a abajo)
                y pásasela al desarrollador. Especialmente importante:
            </p>
            <ul class="text-sm mt-2" style="list-style: disc; padding-left: 20px;">
                <li>La tabla de <strong>«¿Puede escribir?»</strong> de la sección 2.</li>
                <li>El estado del <strong>symlink</strong> de la sección 1.</li>
                <li><strong>Las últimas líneas del log</strong> — sobre todo si al recargar esta pantalla ves un error nuevo tras haber intentado subir un banner.</li>
            </ul>
        </div>

    </div>
@endsection
