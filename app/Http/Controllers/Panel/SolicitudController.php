<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Services\EnviarSolicitud;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SolicitudController extends Controller
{
    public function index(Request $request): View
    {
        return view('panel.solicitudes.index', [
            'solicitudes' => $this->consulta($request)
                ->withCount('items')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'filtros' => $request->only(['q', 'estado', 'desde', 'hasta']),
            'sinEnviar' => Cotizacion::sinEnviar()->count(),
        ]);
    }

    public function show(Cotizacion $solicitud): View
    {
        return view('panel.solicitudes.ver', [
            'solicitud' => $solicitud->load('items'),
            'porVehiculo' => $solicitud->porVehiculo(),
        ]);
    }

    /**
     * Reenvía una solicitud cuyo correo no salió. Es la razón por la que se
     * guarda el registro: sin él, esa solicitud se habría perdido en silencio.
     */
    public function reenviar(Cotizacion $solicitud, EnviarSolicitud $envio): RedirectResponse
    {
        $ok = $envio->despachar($solicitud->load('items'));

        return back()->with('mensaje', $ok
            ? "Reenviamos la solicitud {$solicitud->consecutivo}."
            : 'No pudimos reenviarla. Revisa la configuración de correo.');
    }

    public function exportar(Request $request): StreamedResponse
    {
        $solicitudes = $this->consulta($request)->with('items')->latest('id')->get();
        $nombre = 'solicitudes-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($solicitudes) {
            $salida = fopen('php://output', 'w');

            // BOM para que Excel en Windows respete las tildes.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'Consecutivo', 'Fecha', 'Nombre', 'Teléfono', 'Correo',
                'Vehículo', 'Repuesto', 'Cantidad', 'Notas', 'Correo enviado',
            ], ';');

            foreach ($solicitudes as $solicitud) {
                foreach ($solicitud->items as $item) {
                    fputcsv($salida, [
                        $solicitud->consecutivo,
                        $solicitud->created_at->format('d/m/Y H:i'),
                        $solicitud->nombre_completo,
                        $solicitud->telefono,
                        $solicitud->email,
                        $item->vehiculo_nombre,
                        $item->producto_nombre,
                        $item->cantidad,
                        $solicitud->notas,
                        $solicitud->seEnvio() ? 'Sí' : 'No',
                    ], ';');
                }
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function consulta(Request $request)
    {
        return Cotizacion::query()
            ->when(is_string($q = $request->query('q')) ? $q : '', function ($q, $termino) {
                $q->where(fn ($sub) => $sub
                    ->where('consecutivo', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('apellidos', 'like', "%{$termino}%")
                    ->orWhere('telefono', 'like', "%{$termino}%")
                    ->orWhere('email', 'like', "%{$termino}%"));
            })
            ->when($request->query('estado') === 'sin-enviar', fn ($q) => $q->sinEnviar())
            ->when($request->query('estado') === 'enviadas', fn ($q) => $q->whereNotNull('correo_enviado_en'))
            ->when($request->query('desde'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('hasta'), fn ($q, $h) => $q->whereDate('created_at', '<=', $h));
    }
}
