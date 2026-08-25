<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Vehiculo;
use App\Services\ArbolVehiculos;
use App\Services\VehiculoActivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    /**
     * El árbol completo en una sola respuesta. Se cachea en el navegador: el
     * catálogo de vehículos cambia cuando el equipo importa, no cada visita.
     */
    public function arbol(ArbolVehiculos $arbol): JsonResponse
    {
        $datos = $arbol->paraSelector();

        return response()
            ->json($datos)
            ->setEtag(md5(serialize($datos)))
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function guardar(Request $request, VehiculoActivo $activo): RedirectResponse
    {
        $datos = $request->validate([
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
        ]);

        $activo->guardar(Vehiculo::findOrFail($datos['vehiculo_id']));

        return back()->with('mensaje', 'Listo, ya estás viendo los repuestos de tu carro.');
    }

    public function olvidar(VehiculoActivo $activo): RedirectResponse
    {
        $activo->olvidar();

        return back()->with('mensaje', 'Quitamos el vehículo. Estás viendo el catálogo completo.');
    }

    /** Sugerencias mientras el usuario escribe en el buscador. */
    public function sugerencias(Request $request, VehiculoActivo $activo): JsonResponse
    {
        $termino = trim((string) $request->query('q'));

        if (mb_strlen($termino) < 3) {
            return response()->json([]);
        }

        $sugerencias = Producto::publicados()
            ->buscar($termino)
            ->paraVehiculo($activo->id())
            ->orderBy('nombre')
            ->limit(8)
            ->get(['nombre', 'slug'])
            ->map(fn (Producto $p) => ['t' => $p->nombre, 'u' => route('producto', $p)]);

        return response()->json($sugerencias);
    }
}
