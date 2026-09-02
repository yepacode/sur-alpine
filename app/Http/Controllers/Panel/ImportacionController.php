<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\ImportadorCatalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Carga masiva desde el Excel que el equipo ya usa.
 *
 * El flujo es subir → ver qué va a pasar → confirmar. Nunca se escribe en la
 * base sin que alguien haya visto antes el resumen: con 29.272 productos en
 * juego, una importación a ciegas es un riesgo que no vale la pena.
 */
class ImportacionController extends Controller
{
    private const CARPETA = 'importaciones';

    public function formulario(): View
    {
        return view('panel.catalogo.importar', ['resultado' => null, 'archivo' => null]);
    }

    public function previsualizar(Request $request, ImportadorCatalogo $importador): View
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ], [
            'archivo.mimes' => 'Sube el archivo en formato Excel (.xlsx).',
        ]);

        // Antes de subir la nueva, barrer las previsualizaciones abandonadas.
        //
        // Sólo `confirmar` borraba el archivo, así que toda previsualización
        // que alguien dejara a medias se quedaba para siempre: el catálogo
        // completo del cliente, hasta 20 MB cada uno, acumulándose sin
        // caducidad. Había uno de hacía once días cuando se revisó. Un día es
        // margen de sobra para retomar una importación.
        $this->barrerAbandonados();

        $ruta = $request->file('archivo')->store(self::CARPETA, 'local');

        return view('panel.catalogo.importar', [
            'resultado' => $importador->importar(Storage::disk('local')->path($ruta), simular: true),
            'archivo' => $ruta,
            'nombreOriginal' => $request->file('archivo')->getClientOriginalName(),
        ]);
    }

    /** Las previsualizaciones de más de un día que nadie confirmó. */
    private function barrerAbandonados(): void
    {
        $disco = Storage::disk('local');

        foreach ($disco->files(self::CARPETA) as $archivo) {
            if ($disco->lastModified($archivo) < now()->subDay()->timestamp) {
                $disco->delete($archivo);
            }
        }
    }

    public function confirmar(Request $request, ImportadorCatalogo $importador): RedirectResponse
    {
        $datos = $request->validate(['archivo' => ['required', 'string']]);

        // Sólo rutas dentro de la carpeta de importaciones: el nombre viene de
        // un formulario y no puede apuntar a cualquier archivo del servidor.
        //
        // El `..` se rechaza aquí explícitamente. Hoy también lo frena
        // Flysystem, pero lanzando una excepción sin capturar —un 500, con
        // traza si `APP_DEBUG` quedara encendido en el servidor— y sobre todo
        // dejando creer que el guardián es el `str_starts_with`, que por sí
        // solo no cubre `importaciones/../../../.env`.
        if (str_contains($datos['archivo'], '..')
            || ! str_starts_with($datos['archivo'], self::CARPETA.'/')
            || ! Storage::disk('local')->exists($datos['archivo'])) {
            return redirect()->route('panel.catalogo.importar')
                ->with('problema', 'El archivo ya no está disponible. Vuelve a subirlo.');
        }

        $resultado = $importador->importar(Storage::disk('local')->path($datos['archivo']));

        Storage::disk('local')->delete($datos['archivo']);

        if (! $resultado->cuadra()) {
            return redirect()->route('panel.catalogo')->with('mensaje', sprintf(
                'Atención: el Excel marca %s piezas pero quedaron %s en el catálogo. Revisa el archivo.',
                number_format($resultado->celdasMarcadas),
                number_format($resultado->productosEnBase)
            ));
        }

        $mensaje = sprintf(
            'Importación lista: %d %s y %s %s.',
            $resultado->vehiculosNuevos,
            plural($resultado->vehiculosNuevos, 'vehículo nuevo', 'vehículos nuevos'),
            number_format($resultado->productosCreados),
            plural($resultado->productosCreados, 'pieza nueva', 'piezas nuevas')
        );

        // Lo que ya no viene en el Excel se avisa pero no se toca: retirarlo es
        // una decisión del negocio, y una celda desmarcada por error dejaría al
        // cliente sin repuestos que sí vende.
        if ($resultado->sobrantes > 0) {
            $mensaje .= sprintf(
                ' Atención: %s %s siguen en el catálogo y ya no vienen en el Excel (no se tocaron).',
                number_format($resultado->sobrantes),
                plural($resultado->sobrantes, 'pieza', 'piezas')
            );
        }

        return redirect()->route('panel.catalogo')->with('mensaje', $mensaje);
    }

    /** La plantilla es el propio formato del cliente, para no cambiarle el hábito. */
    public function plantilla()
    {
        $ruta = storage_path('app/catalogo/formato-importacion.xlsx');

        abort_unless(is_file($ruta), 404, 'Todavía no hay una plantilla cargada.');

        return response()->download($ruta, 'formato-importacion-suralpine.xlsx');
    }
}
