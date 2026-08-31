<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Suscriptor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Los correos del newsletter, para que el administrador pueda verlos y sacarlos.
 *
 * No hay alta manual: un correo escrito a mano por el equipo no tiene el
 * consentimiento de nadie detrás, y eso es justo lo que la Ley 1581 pide poder
 * demostrar. La baja sí se puede hacer desde aquí —es una obligación del
 * responsable— y se marca con fecha en vez de borrar la fila.
 */
class SuscriptorController extends Controller
{
    public function index(): View
    {
        return view('panel.suscriptores', [
            'suscriptores' => Suscriptor::query()->latest()->paginate(50),
            'total' => Suscriptor::query()->whereNull('baja_en')->count(),
        ]);
    }

    /**
     * Dar de baja a alguien desde el panel.
     *
     * Antes la pantalla decía «para dar de baja a alguien, escríbenos y lo
     * marcamos». La supresión es una obligación del responsable del
     * tratamiento —Ley 1581, y el plazo corre desde que la persona la pide—,
     * así que dejar al dueño dependiendo de una llamada a la agencia lo pone a
     * él en falta, no a nosotros.
     *
     * Se marca `baja_en`, no se borra la fila: si mañana esa persona se vuelve
     * a suscribir sola, tiene que quedar constancia de las dos cosas.
     */
    public function darDeBaja(Suscriptor $suscriptor): RedirectResponse
    {
        if ($suscriptor->baja_en) {
            return back()->with('mensaje', "{$suscriptor->correo} ya estaba de baja.");
        }

        $suscriptor->update(['baja_en' => now()]);

        return back()->with('mensaje', "Dimos de baja a {$suscriptor->correo}. No vuelve a recibir correos.");
    }

    /** El CSV que se abre en Excel: separador de coma y BOM para las tildes. */
    public function exportar(): StreamedResponse
    {
        $nombre = 'suscriptores-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $salida = fopen('php://output', 'w');

            // Sin el BOM, Excel en Windows abre «Bogotá» como «BogotÃ¡».
            fwrite($salida, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($salida, ['Correo', 'Origen', 'Fecha de alta', 'Baja']);

            Suscriptor::query()->orderBy('id')->chunk(500, function ($filas) use ($salida) {
                foreach ($filas as $s) {
                    // Blindado, igual que la exportación de solicitudes:
                    // este formulario es público y el correo puede empezar
                    // por `=`, que Excel evalúa como fórmula.
                    fputcsv($salida, array_map('celda_csv', [
                        $s->correo,
                        $s->origen,
                        $s->created_at?->format('d/m/Y H:i'),
                        $s->baja_en?->format('d/m/Y'),
                    ]));
                }
            });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
