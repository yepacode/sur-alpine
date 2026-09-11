<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Diagnostico de sistema de archivos para producciones sin acceso SSH.
 *
 * En Hostinger compartido no hay `chmod`, `storage:link` ni ver logs por
 * shell. Cuando el sitio dice «no puedo escribir aqui» en silencio, no queda
 * mas que traerse el diagnostico desde el propio sitio. Esta pantalla mira
 * cinco cosas:
 *
 *   1. Symlink `public/storage`: existe, apunta a donde debe, o esta roto.
 *   2. Escritura real en las carpetas de subida (banners, notas, editables).
 *      NO consulta permisos —eso miente en muchos hostings, «755» puede ser
 *      read-only en la practica—: escribe un archivo temporal, lo lee y lo
 *      borra. Si no puede escribir, se ve aqui.
 *   3. Espacio libre del disco.
 *   4. Ultimos 5 archivos de cada carpeta, con fecha.
 *   5. Ultimas 30 lineas del log de Laravel.
 *   6. Version de PHP, extensiones (GD, Fileinfo), `open_basedir`.
 *
 * Solo la ve un administrador —vive dentro del grupo `rol:admin` del panel—.
 * No cambia nada: mira.
 */
class DiagnosticoController extends Controller
{
    public function index(): View
    {
        $rutas = [
            'public/storage'         => public_path('storage'),
            'public/img/banners'     => public_path('img/banners'),
            'public/img/categorias'  => public_path('img/categorias'),
            'storage/app/public'     => storage_path('app/public'),
            'storage/app/public/notas'     => storage_path('app/public/notas'),
            'storage/app/public/editables' => storage_path('app/public/editables'),
        ];

        $carpetas = [];
        foreach ($rutas as $etiqueta => $ruta) {
            $carpetas[$etiqueta] = $this->examinarCarpeta($ruta);
        }

        $symlink = $this->examinarSymlink(public_path('storage'), storage_path('app/public'));

        return view('panel.diagnostico', [
            'carpetas' => $carpetas,
            'symlink' => $symlink,
            'disco' => $this->examinarDisco(),
            'php' => $this->examinarPhp(),
            'log' => $this->ultimasLineasLog(),
        ]);
    }

    private function examinarCarpeta(string $ruta): array
    {
        $info = [
            'ruta' => $ruta,
            'existe' => is_dir($ruta),
            'permisos' => null,
            'escribible' => null,
            'error_escritura' => null,
            'ultimos_archivos' => [],
        ];

        if (! $info['existe']) {
            return $info;
        }

        $info['permisos'] = substr(sprintf('%o', @fileperms($ruta)), -4);

        // Prueba de escritura REAL: crea un archivo, lo lee, lo borra.
        // No sirve `is_writable`: en muchos hostings devuelve true y luego
        // `fopen` falla por `open_basedir` o cuota. Aqui probamos de verdad.
        $prueba = $ruta.DIRECTORY_SEPARATOR.'.diagnostico-'.uniqid().'.txt';
        try {
            $puestos = @file_put_contents($prueba, 'ok');
            if ($puestos !== 2) {
                $info['escribible'] = false;
                $err = error_get_last();
                $info['error_escritura'] = $err['message'] ?? 'file_put_contents devolvio '.var_export($puestos, true);
            } else {
                $info['escribible'] = true;
                @unlink($prueba);
            }
        } catch (\Throwable $e) {
            $info['escribible'] = false;
            $info['error_escritura'] = $e->getMessage();
        }

        // Ultimos 5 archivos con fecha, para saber cuando fue la ultima subida.
        $archivos = @glob($ruta.DIRECTORY_SEPARATOR.'*') ?: [];
        usort($archivos, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($archivos, 0, 5) as $a) {
            if (str_contains(basename($a), '.diagnostico-')) {
                continue;
            }
            $info['ultimos_archivos'][] = [
                'nombre' => basename($a),
                'fecha' => date('Y-m-d H:i:s', filemtime($a)),
                'tamano' => is_file($a) ? filesize($a) : null,
            ];
        }

        return $info;
    }

    private function examinarSymlink(string $ruta, string $destinoEsperado): array
    {
        $info = [
            'ruta' => $ruta,
            'destino_esperado' => $destinoEsperado,
            'existe' => file_exists($ruta),
            'es_symlink' => is_link($ruta),
            'apunta_a' => null,
            'destino_real_existe' => null,
            'destino_correcto' => null,
        ];

        if ($info['es_symlink']) {
            $info['apunta_a'] = @readlink($ruta);
            $info['destino_real_existe'] = $info['apunta_a'] ? file_exists($info['apunta_a']) : false;
            // Comparar por realpath: los symlinks pueden ser relativos.
            $destinoLimpio = realpath($info['apunta_a']) ?: $info['apunta_a'];
            $esperadoLimpio = realpath($destinoEsperado) ?: $destinoEsperado;
            $info['destino_correcto'] = $destinoLimpio === $esperadoLimpio;
        }

        return $info;
    }

    private function examinarDisco(): array
    {
        $libre = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());

        return [
            'libre_bytes' => $libre,
            'total_bytes' => $total,
            'libre_legible' => $libre ? $this->tamano($libre) : 'desconocido',
            'total_legible' => $total ? $this->tamano($total) : 'desconocido',
            'usado_pct' => ($libre && $total) ? round(($total - $libre) / $total * 100, 1) : null,
        ];
    }

    private function examinarPhp(): array
    {
        return [
            'version' => PHP_VERSION,
            'gd' => extension_loaded('gd'),
            'gd_info' => extension_loaded('gd') ? (gd_info()['GD Version'] ?? '') : '',
            'fileinfo' => extension_loaded('fileinfo'),
            'open_basedir' => ini_get('open_basedir') ?: '(sin restriccion)',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
    }

    private function ultimasLineasLog(int $lineas = 30): array
    {
        $log = storage_path('logs/laravel.log');
        if (! is_file($log)) {
            return [];
        }

        // Leer solo el final: `tail` en PHP puro para no cargar log de 100MB.
        $tamano = filesize($log);
        $bytes = min($tamano, 32 * 1024); // ultimos 32KB
        $f = fopen($log, 'r');
        fseek($f, -$bytes, SEEK_END);
        $contenido = fread($f, $bytes);
        fclose($f);

        $lineasLog = array_filter(explode("\n", $contenido));

        return array_slice($lineasLog, -$lineas);
    }

    private function tamano(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$unidades[$i];
    }
}
