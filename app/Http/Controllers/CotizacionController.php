<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Producto;
use App\Models\Vehiculo;
use App\Services\Cotizador;
use App\Services\EnviarSolicitud;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    public function __construct(private readonly Cotizador $cotizador) {}

    /**
     * El producto llega por enlace de modelo. En el sitio anterior este botón
     * agregaba siempre el mismo repuesto ajeno porque el id estaba fijo en la
     * plantilla; aquí no hay forma de agregar algo distinto a lo que se pidió.
     */
    public function agregar(Request $request, Producto $producto): RedirectResponse|JsonResponse
    {
        $cantidad = (int) $request->input('cantidad', 1);

        $this->cotizador->agregar($producto, max(1, $cantidad));

        $mensaje = "Agregamos «{$producto->nombre}» a tu cotización.";

        // Desde el carrusel de la portada se agrega sin recargar: recargar
        // devolvía al visitante al inicio de la página, y en un teléfono los
        // destacados están siete pantallas más abajo.
        if ($request->wantsJson()) {
            return response()->json([
                'mensaje' => $mensaje,
                'total' => $this->cotizador->totalItems(),
            ]);
        }

        return back()->with('mensaje', $mensaje);
    }

    public function actualizar(Request $request, Producto $producto): RedirectResponse
    {
        $this->cotizador->actualizar($producto->id, (int) $request->input('cantidad', 1));

        return back();
    }

    public function quitar(Producto $producto): RedirectResponse
    {
        $this->cotizador->quitar($producto->id);

        return back()->with('mensaje', 'Quitamos el repuesto de tu cotización.');
    }

    public function quitarVehiculo(Vehiculo $vehiculo): RedirectResponse
    {
        $this->cotizador->quitarVehiculo($vehiculo->id);

        return back()->with('mensaje', "Quitamos los repuestos de {$vehiculo->nombre_completo}.");
    }

    public function vaciar(): RedirectResponse
    {
        $this->cotizador->vaciar();

        return back()->with('mensaje', 'Vaciamos tu cotización.');
    }

    public function ver(): View
    {
        return view('cotizacion.ver', [
            'porVehiculo' => $this->cotizador->porVehiculo(),
            'totalItems' => $this->cotizador->totalItems(),
        ]);
    }

    public function enviar(Request $request, EnviarSolicitud $envio): RedirectResponse
    {
        if ($this->cotizador->vacio()) {
            return redirect()->route('cotizacion.ver')
                ->with('mensaje', 'Tu cotización está vacía. Agrega los repuestos que necesitas.');
        }

        // Campo trampa: los formularios automáticos lo llenan, las personas no.
        if (filled($request->input('sitio_web'))) {
            return redirect()->route('cotizacion.enviada');
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellidos' => ['nullable', 'string', 'max:80'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'acepta' => ['accepted'],
        ], [
            'acepta.accepted' => 'Necesitamos tu autorización para tratar tus datos y poder llamarte.',
        ]);

        $cotizacion = DB::transaction(function () use ($datos, $request) {
            $cotizacion = Cotizacion::create([
                'consecutivo' => Cotizacion::siguienteConsecutivo(),
                'user_id' => $request->user()?->id,
                'nombre' => $datos['nombre'],
                'apellidos' => $datos['apellidos'] ?? null,
                'telefono' => $datos['telefono'],
                'email' => $datos['email'],
                'notas' => $datos['notas'] ?? null,
                'ip' => $request->ip(),
            ]);

            // Los nombres se congelan: si mañana se edita o retira el producto,
            // la solicitud sigue diciendo qué fue lo que el cliente pidió.
            $cotizacion->items()->createMany(
                $this->cotizador->items()->map(fn ($item) => [
                    'producto_id' => $item->producto->id,
                    'vehiculo_id' => $item->producto->vehiculo_id,
                    'producto_nombre' => $item->producto->nombre,
                    'vehiculo_nombre' => $item->producto->vehiculo->nombre_completo,
                    'cantidad' => $item->cantidad,
                ])->all()
            );

            return $cotizacion;
        });

        $envio->despachar($cotizacion->load('items'));

        $this->cotizador->vaciar();

        return redirect()->route('cotizacion.enviada')
            ->with('consecutivo', $cotizacion->consecutivo);
    }

    public function enviada(): View
    {
        return view('cotizacion.enviada', [
            'consecutivo' => session('consecutivo'),
        ]);
    }
}
