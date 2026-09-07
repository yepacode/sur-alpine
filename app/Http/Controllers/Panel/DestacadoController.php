<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Los productos destacados de la portada: elegir cuales salen y en que orden.
 *
 * El cliente lo pidio expresamente. Antes se elegian solos por «veces
 * cotizado» y no habia manera de empujar una promo o retirar una pieza vieja.
 *
 * `productos.destacado_orden` es NULLABLE. `null` = no destacado. Un numero =
 * destacado, en orden ascendente. Con ninguno marcado el carrusel cae al
 * automatico —lo controla `CatalogoController::destacados()`—.
 */
class DestacadoController extends Controller
{
    /** Ficha del sitio: lista actual + buscador para agregar. */
    public function index(Request $request): View
    {
        $actuales = Producto::query()
            ->whereNotNull('destacado_orden')
            ->with(['vehiculo.modelo.marca', 'tipoParte.categoria'])
            ->orderBy('destacado_orden')
            ->orderBy('id')
            ->get();

        // Buscador arriba: acepta nombre o referencia. Se muestra solo tras
        // escribir 2 caracteres, para no descargar el catalogo entero cada vez
        // que se abre la pantalla.
        $termino = trim((string) $request->input('q', ''));
        $resultados = collect();
        if (mb_strlen($termino) >= 2) {
            $resultados = Producto::publicados()
                ->whereNull('destacado_orden')
                ->with(['vehiculo.modelo.marca', 'tipoParte.categoria'])
                ->buscar($termino)
                ->limit(20)
                ->get();
        }

        return view('panel.destacados.index', [
            'actuales' => $actuales,
            'resultados' => $resultados,
            'termino' => $termino,
        ]);
    }

    /**
     * Marca una pieza como destacada. Va al final del carrusel.
     *
     * `firstOrCreate` no vale: hay que actualizar filas existentes y buscar el
     * mayor `destacado_orden` antes de escribir. La operacion es una escritura
     * simple pero el efecto es no dejar dos piezas con el mismo orden.
     */
    public function agregar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
        ]);

        $producto = Producto::findOrFail($datos['producto_id']);

        if ($producto->destacado_orden === null) {
            $siguiente = (int) (Producto::whereNotNull('destacado_orden')->max('destacado_orden') ?? 0) + 1;
            $producto->update(['destacado_orden' => $siguiente]);
        }

        Cache::increment('destacados.version') ?: Cache::forever('destacados.version', 2);

        return redirect()->route('panel.destacados')
            ->with('mensaje', "«{$producto->nombre}» ya sale en el carrusel de destacados.");
    }

    /** Quita una pieza del carrusel. `null` en `destacado_orden`. */
    public function quitar(Producto $producto): RedirectResponse
    {
        $nombre = $producto->nombre;
        $producto->update(['destacado_orden' => null]);

        Cache::increment('destacados.version') ?: Cache::forever('destacados.version', 2);

        return redirect()->route('panel.destacados')
            ->with('mensaje', "«{$nombre}» ya no sale en el carrusel de destacados.");
    }

    /**
     * Guarda el orden que el admin escribio en la tabla.
     *
     * Recibe `orden[producto_id] = numero`. Se admite cualquier numero entero;
     * lo importante es el orden RELATIVO, no los huecos. Si dos productos
     * comparten numero (por ejemplo por descuido), gana el de id menor —el
     * `orderBy('id')` que ya trae la portada—.
     *
     * Solo se toca la fila si el numero cambio de verdad. Asi el correo con
     * las actualizaciones no se llena de escrituras identicas y la caducidad
     * del cache no se dispara sin motivo.
     */
    public function orden(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'orden' => ['required', 'array'],
            'orden.*' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $cambios = 0;
        foreach ($datos['orden'] as $id => $numero) {
            $numero = (int) $numero;
            if ($numero < 1) {
                continue;
            }
            $tocadas = Producto::where('id', (int) $id)
                ->whereNotNull('destacado_orden')
                ->where('destacado_orden', '<>', $numero)
                ->update(['destacado_orden' => $numero]);
            $cambios += $tocadas;
        }

        if ($cambios > 0) {
            Cache::increment('destacados.version') ?: Cache::forever('destacados.version', 2);
        }

        return redirect()->route('panel.destacados')
            ->with('mensaje', $cambios > 0
                ? 'Orden guardado. Los destacados salen en el nuevo orden.'
                : 'No cambiaste ningún número, así que no hay nada nuevo que guardar.');
    }
}
