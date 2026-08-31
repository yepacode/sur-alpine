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
        // Una pieza despublicada no puede meterse al carrito: llegaría al correo
        // del equipo como una solicitud imposible.
        abort_unless($producto->publicado, 404);

        $cantidad = (int) $request->input('cantidad', 1);

        $cupo = $this->cotizador->agregar($producto, max(1, $cantidad));

        $mensaje = $cupo
            ? "Agregamos «{$producto->nombre}» a tu cotización."
            : 'Tu cotización llegó al máximo de 200 repuestos. Envíala y arma otra, o llámanos y te ayudamos.';

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
        // Validado, no convertido a la fuerza. `(int) 'dos'` da 0, y para el
        // cotizador un 0 significa «quítalo»: escribir cualquier cosa en la
        // casilla hacía desaparecer la línea. Y un POST sin el campo la
        // reseteaba a 1 en silencio.
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'cantidad.required' => 'Escribe cuántas unidades necesitas.',
            'cantidad.integer' => 'La cantidad va en números. Pon 0 para quitarlo.',
        ]);

        $this->cotizador->actualizar($producto->id, $datos['cantidad']);

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

    public function ver(Request $request): View
    {
        $usuario = $request->user();

        // Quien llega con repuestos y sin sesión va a tener que entrar para
        // enviar. Dejar aquí la vuelta anotada hace que al iniciar sesión
        // caiga otra vez en su cotización y no en la portada, buscando dónde
        // quedó lo que ya había armado.
        if (! $usuario && ! $this->cotizador->vacio()) {
            $request->session()->put('url.intended', route('cotizacion.ver'));
        }

        return view('cotizacion.ver', [
            'porVehiculo' => $this->cotizador->porVehiculo(),
            'totalItems' => $this->cotizador->totalItems(),
            'usuario' => $usuario,
        ]);
    }

    public function enviar(Request $request, EnviarSolicitud $envio): RedirectResponse
    {
        // Enviar exige sesión, como pidió el cliente: así la solicitud queda
        // colgada de una cuenta y el mostrador sabe con quién habla.
        //
        // La puerta va aquí y no en un middleware `auth` a propósito: el
        // middleware guardaría como «vuelta» esta misma URL, que es POST, y
        // al iniciar sesión el visitante recibiría un 405 en la cara. Así
        // vuelve a su cotización, con todo lo que traía.
        if (! $request->user()) {
            $request->session()->put('url.intended', route('cotizacion.ver'));

            return redirect()->route('acceso')
                ->with('mensaje', 'Inicia sesión para enviar tu cotización. No pierdes lo que ya agregaste.');
        }

        // `items()` y no `vacio()`: el segundo mira la sesión cruda y el
        // primero la lista ya depurada. Si todas las piezas del carrito se
        // borraron o se despublicaron, `vacio()` decía que no y la solicitud
        // se creaba igual —con su consecutivo, su correo al asesor y CERO
        // repuestos dentro—. Una solicitud vacía es peor que ninguna: el
        // mostrador llama sin saber a qué responde.
        if ($this->cotizador->items()->isEmpty()) {
            return redirect()->route('cotizacion.ver')
                ->with('mensaje', 'Tu cotización está vacía. Agrega los repuestos que necesitas.');
        }

        // Campo trampa: los formularios automáticos lo llenan, las personas no.
        //
        // Se vacía el carrito igual que en un envío bueno. Antes no: quien
        // usa un gestor de contraseñas que rellena todo lo que encuentra veía
        // la pantalla de «recibimos tu solicitud», con el carrito intacto y
        // sin número de solicitud —y nadie recibía nada—. Que la respuesta sea
        // idéntica es el punto de la trampa; dejar el carrito lleno la
        // delataba y además engañaba a una persona real.
        if (filled($request->input('sitio_web'))) {
            $this->cotizador->vaciar();

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

        // Tres intentos, no uno.
        //
        // `siguienteConsecutivo` se apoya en `lockForUpdate`, y eso no
        // serializa la PRIMERA fila del año: sin filas que bloquear, InnoDB
        // toma bloqueos de hueco, que no chocan entre sí, y dos envíos
        // simultáneos acaban en interbloqueo. O sea, falla justo el 1 de enero
        // y el día del estreno —y sale como un 500 en la cara del cliente—.
        // Reintentar es la respuesta correcta: el segundo intento ya encuentra
        // la fila del primero y calcula bien.
        // La fila del contador se crea FUERA de la transacción: si dos envíos
        // simultáneos son los primeros del año, uno la crea y el otro la
        // ignora, y ninguno bloquea nada todavía. Hacerlo dentro devolvería el
        // mismo interbloqueo que se vino a quitar.
        Cotizacion::prepararContador();

        $cotizacion = DB::transaction(function () use ($datos, $request) {
            // Habeas Data: si el que cotiza tiene sesión y todavía no había
            // aceptado los términos (o aceptó una versión vieja), la fecha
            // y la versión vigente quedan clavadas en su usuario.
            if ($usuario = $request->user()) {
                $versionActual = version_habeas();
                if ($usuario->politica_version !== $versionActual) {
                    $usuario->forceFill([
                        'acepto_en' => now(),
                        'politica_version' => $versionActual,
                    ])->save();
                }
            }

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
                    // Congelado como los otros dos: si mañana retiran esta
                    // pieza del catálogo, el tablero tiene que seguir sabiendo
                    // qué se pidió.
                    'tipo_parte_nombre' => $item->producto->tipoParte?->nombre,
                    'vehiculo_nombre' => $item->producto->vehiculo->nombre_completo,
                    'cantidad' => $item->cantidad,
                ])->all()
            );

            return $cotizacion;
        }, 3);

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
