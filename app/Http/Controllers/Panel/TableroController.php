<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Vehiculo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TableroController extends Controller
{
    /** Períodos que el equipo consulta a diario. */
    public const PERIODOS = [
        'hoy' => 'Hoy',
        '7' => 'Últimos 7 días',
        '30' => 'Últimos 30 días',
        '90' => 'Últimos 90 días',
        'personalizado' => 'Rango de fechas',
    ];

    public function __invoke(Request $request): View
    {
        [$desde, $hasta, $periodo] = $this->rango($request);

        // Calificada con la tabla: al unir con los ítems, `created_at` existe
        // en ambas y MySQL no adivina cuál queremos.
        $enRango = fn () => Cotizacion::query()->whereBetween('cotizaciones.created_at', [$desde, $hasta]);

        return view('panel.tablero', [
            'periodo' => $periodo,
            'desde' => $desde,
            'hasta' => $hasta,
            'periodos' => self::PERIODOS,

            'totalCotizaciones' => $enRango()->count(),
            'totalRepuestos' => (int) $enRango()->join('cotizacion_items', 'cotizaciones.id', '=', 'cotizacion_items.cotizacion_id')
                ->sum('cotizacion_items.cantidad'),
            'sinEnviar' => Cotizacion::sinEnviar()->count(),

            'porDia' => $this->porDia($desde, $hasta),
            'vehiculosTop' => $this->vehiculosTop($desde, $hasta),
            'partesTop' => $this->partesTop($desde, $hasta),

            'catalogo' => [
                'productos' => Producto::count(),
                'vehiculos' => Vehiculo::count(),
            ],
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function rango(Request $request): array
    {
        $periodo = (string) $request->query('periodo', '30');

        if ($periodo === 'personalizado') {
            $desde = $this->fecha($request->query('desde')) ?? now()->subDays(30);
            $hasta = $this->fecha($request->query('hasta')) ?? now();

            if ($hasta->lt($desde)) {
                [$desde, $hasta] = [$hasta, $desde];
            }

            return [$desde->startOfDay(), $hasta->endOfDay(), 'personalizado'];
        }

        if ($periodo === 'hoy') {
            return [now()->startOfDay(), now()->endOfDay(), 'hoy'];
        }

        $dias = in_array($periodo, ['7', '30', '90'], true) ? (int) $periodo : 30;

        return [now()->subDays($dias - 1)->startOfDay(), now()->endOfDay(), (string) $dias];
    }

    private function fecha(?string $valor): ?Carbon
    {
        try {
            return $valor ? Carbon::parse($valor) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Serie diaria completa: los días sin solicitudes también cuentan, si no
     * la gráfica miente saltándose los días flojos.
     */
    private function porDia(Carbon $desde, Carbon $hasta): array
    {
        $conteos = Cotizacion::query()
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->whereBetween('created_at', [$desde, $hasta])
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $serie = [];

        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            $clave = $dia->toDateString();
            $serie[$clave] = (int) ($conteos[$clave] ?? 0);
        }

        // Más de tres meses en barras diarias no se lee: se recorta a lo último.
        return count($serie) > 92 ? array_slice($serie, -92, null, true) : $serie;
    }

    private function vehiculosTop(Carbon $desde, Carbon $hasta): array
    {
        return DB::table('cotizacion_items')
            ->join('cotizaciones', 'cotizaciones.id', '=', 'cotizacion_items.cotizacion_id')
            ->whereBetween('cotizaciones.created_at', [$desde, $hasta])
            ->groupBy('cotizacion_items.vehiculo_nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck(DB::raw('SUM(cotizacion_items.cantidad) as total'), 'cotizacion_items.vehiculo_nombre')
            ->all();
    }

    private function partesTop(Carbon $desde, Carbon $hasta): array
    {
        return DB::table('cotizacion_items')
            ->join('cotizaciones', 'cotizaciones.id', '=', 'cotizacion_items.cotizacion_id')
            ->join('productos', 'productos.id', '=', 'cotizacion_items.producto_id')
            ->join('tipos_parte', 'tipos_parte.id', '=', 'productos.tipo_parte_id')
            ->whereBetween('cotizaciones.created_at', [$desde, $hasta])
            ->groupBy('tipos_parte.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck(DB::raw('SUM(cotizacion_items.cantidad) as total'), 'tipos_parte.nombre')
            ->all();
    }
}
