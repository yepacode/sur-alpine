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
            'anio' => ['nullable', 'integer', 'min:1970', 'max:2100'],
        ]);

        // Guardamos tambien el ano exacto que eligio la persona, para poder
        // decir «para tu FIAT 128 1500 (1976)» en vez de «(1976-1982)». Al
        // cliente le daba miedo pensar que se le mostraban piezas de otros
        // anos; el rango es el rango del vehiculo en la base, no una lista de
        // anos que se filtran.
        $activo->guardar(Vehiculo::findOrFail($datos['vehiculo_id']), $datos['anio'] ?? null);

        // Redirige al catálogo filtrado por el vehículo activo: el buscador
        // vive en la portada y con `back()` volvía justo ahí, así que el
        // mecánico veía otra vez el hero y no encontraba sus repuestos.
        return redirect()->route('catalogo')
            ->with('mensaje', 'Listo, éstos son los repuestos de tu carro.');
    }

    public function olvidar(VehiculoActivo $activo): RedirectResponse
    {
        $activo->olvidar();

        return back()->with('mensaje', 'Quitamos el vehículo. Estás viendo el catálogo completo.');
    }

    /** Sugerencias mientras el usuario escribe en el buscador. */
    public function sugerencias(Request $request, VehiculoActivo $activo): JsonResponse
    {
        $termino = is_string($q = $request->query('q')) ? trim($q) : '';

        if (mb_strlen($termino) < 3) {
            return response()->json([]);
        }

        $vehiculo = $activo->get();

        $sugerencias = Producto::publicados()
            ->buscar($termino)
            ->paraVehiculo($vehiculo?->id)
            ->orderBy('nombre')
            ->limit(8)
            ->get(['nombre', 'slug'])
            ->map(fn (Producto $p) => ['t' => $p->nombre, 'u' => route('producto', $p)]);

        // Cuando no hay nada PARA ESE CARRO, se dice.
        //
        // Las sugerencias respetan el vehículo activo, igual que el catálogo, y
        // eso está bien. Lo que estaba mal es el silencio: quien tenía puesto un
        // carro con pocas piezas escribía «freno», no pasaba absolutamente nada
        // —la lista ni siquiera se abría— y se iba pensando que el buscador está
        // roto. Es exactamente lo que va a reportar cualquiera que pruebe el
        // sitio con un carro de catálogo corto.
        //
        // Se devuelve una fila que explica la causa y lleva al listado, donde ya
        // está el botón de quitar el filtro.
        if ($sugerencias->isEmpty() && $vehiculo) {
            $sugerencias = collect([[
                't' => "Nada para tu {$vehiculo->nombre_completo}. Buscar «{$termino}» en todo el catálogo",
                'u' => route('catalogo', ['q' => $termino]),
            ]]);
        }

        return response()->json($sugerencias);
    }
}
