<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Mantenimiento;
use App\Models\Producto;
use App\Models\Vehiculo;
use App\Services\Cotizador;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as ReglaClave;

/**
 * El área del cliente: sus vehículos y sus mantenimientos.
 *
 * Todo lo de aquí es del usuario que entró. No hay una sola consulta que no
 * cuelgue de `$request->user()`: es la forma de que nadie vea, por cambiar un
 * número en la URL, la placa ni el historial de otro.
 */
class CuentaController extends Controller
{
    public function inicio(Request $request): View
    {
        $usuario = $request->user();

        return view('cuenta.inicio', [
            'vehiculos' => $usuario->vehiculosGuardados()->with('modelo.marca')->get(),
            // Lo que toca pronto va primero: es a lo que el mecánico entra.
            // Incluye los de kilometraje. Antes se filtraban con
            // `whereNotNull('proximo_fecha')` y los de km guardan `null` ahí,
            // así que quien lleva el carro por kilómetros —lo normal en un
            // taller— nunca veía nada en su tablero.
            // Primero lo vencido, luego lo que viene por fecha, y los de
            // kilometraje intercalados en vez de al final.
            //
            // El orden se hace en PHP y no en SQL: `COALESCE(fecha, …)` los
            // mandaba a todos al fondo y el corte de cinco los dejaba fuera
            // —alguien con seis por kilómetros veía uno—, y la expresión que
            // lo arreglaba era de MySQL, que aquí no sirve porque las pruebas
            // corren sobre SQLite. Son pocas filas por persona: ordenarlas en
            // memoria cuesta nada y se lee sin traducir.
            'proximos' => $usuario->mantenimientos()
                ->where(fn ($q) => $q
                    ->whereNotNull('proximo_fecha')
                    ->orWhereNotNull('proximo_kilometraje'))
                ->get()
                // Los de kilometraje NO pueden ir todos al final.
                //
                // Ya iba el segundo intento: primero el `whereNotNull` los
                // excluía, después el `COALESCE(fecha, '9999-12-31')` los
                // mandaba al fondo, y ahora era el `PHP_INT_MAX`. Siempre el
                // mismo resultado: alguien con seis mantenimientos por
                // kilómetros veía uno. Aquí se les reserva sitio de verdad:
                // se ordenan aparte y se mezclan.
                ->partition(fn ($m) => $m->proximo_fecha !== null)
                ->pipe(fn ($grupos) => $grupos[0]
                    ->sortBy(fn ($m) => [$m->vencido ? 0 : 1, $m->proximo_fecha->timestamp])
                    ->take(4)
                    ->concat($grupos[1]->sortBy('proximo_kilometraje')->take(2)))
                ->values(),
            'totalMantenimientos' => $usuario->mantenimientos()->count(),
        ]);
    }

    /** Guardar un carro en «mis vehículos», con su placa y su alias. */
    public function guardarVehiculo(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
            'placa' => ['nullable', 'string', 'max:10'],
            'alias' => ['nullable', 'string', 'max:60'],
        ]);

        // Sólo se mandan las llaves que llegaron: `syncWithoutDetaching`
        // con `null` sobrescribe, y el mecánico que reguardaba su carro sólo
        // para ponerle un alias perdía la placa guardada.
        $pivote = array_filter([
            'placa' => filled($datos['placa'] ?? null) ? mb_strtoupper($datos['placa']) : null,
            'alias' => $datos['alias'] ?? null,
        ], fn ($v) => filled($v));

        $request->user()->vehiculosGuardados()->syncWithoutDetaching([
            $datos['vehiculo_id'] => $pivote,
        ]);

        return back()->with('mensaje', 'Vehículo guardado en tu cuenta.');
    }

    public function quitarVehiculo(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $request->user()->vehiculosGuardados()->detach($vehiculo->id);

        return back()->with('mensaje', 'Quitamos el vehículo de tu cuenta.');
    }

    /**
     * El perfil de uno de sus carros.
     *
     * Hasta ahora un carro guardado sólo se podía quitar: no había forma de
     * abrirlo, ver qué se le ha hecho ni corregir la placa que se escribió
     * mal. Aquí está todo lo del carro en una página.
     */
    public function verVehiculo(Request $request, Vehiculo $vehiculo): View
    {
        $guardado = $this->suVehiculo($request, $vehiculo);
        $placa = $guardado->pivot->placa;

        return view('cuenta.vehiculo', [
            'vehiculo' => $guardado,
            // Por `vehiculo_id` y también por placa: quien anotó un
            // mantenimiento escribiendo la placa, sin elegir el carro de la
            // lista, igual lo tiene que ver aquí.
            'mantenimientos' => $request->user()->mantenimientos()
                ->where(fn ($q) => $q
                    ->where('vehiculo_id', $vehiculo->id)
                    ->when($placa, fn ($q2, $p) => $q2->orWhere('placa', $p)))
                ->orderByDesc('fecha')
                ->get(),
            'piezas' => $vehiculo->productos()->count(),
        ]);
    }

    /** Corregir la placa o cómo le dice al carro. */
    public function actualizarVehiculo(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $this->suVehiculo($request, $vehiculo);

        $datos = $request->validate([
            'placa' => ['nullable', 'string', 'max:10'],
            'alias' => ['nullable', 'string', 'max:60'],
        ]);

        // Aquí sí se manda `null`: a diferencia de guardar un carro nuevo,
        // este formulario trae los dos campos siempre, así que borrar el
        // contenido de uno es una orden de borrarlo y no un descuido.
        $request->user()->vehiculosGuardados()->updateExistingPivot($vehiculo->id, [
            'placa' => filled($datos['placa'] ?? null) ? mb_strtoupper($datos['placa']) : null,
            'alias' => $datos['alias'] ?: null,
        ]);

        return redirect()->route('cuenta.vehiculo', $vehiculo)
            ->with('mensaje', 'Actualizamos los datos del carro.');
    }

    /**
     * El carro tiene que ser suyo, no sólo existir.
     *
     * Sin esto, `/mi-cuenta/vehiculos/{slug}` abriría el perfil de cualquiera
     * de los 224 vehículos del catálogo, con la placa y el historial de otra
     * persona si alguien coincide.
     */
    private function suVehiculo(Request $request, Vehiculo $vehiculo): Vehiculo
    {
        $guardado = $request->user()->vehiculosGuardados()
            ->with('modelo.marca')
            ->find($vehiculo->id);

        abort_if($guardado === null, 404);

        return $guardado;
    }

    // ── Sus cotizaciones ────────────────────────────────────────────────────

    /**
     * Lo que ha pedido.
     *
     * Faltaba entero: el cliente enviaba una solicitud y del lado de él
     * desaparecía. Quedaba guardada, con su consecutivo y sus repuestos, pero
     * sólo la veía el mostrador.
     */
    public function cotizaciones(Request $request): View
    {
        return view('cuenta.cotizaciones', [
            'cotizaciones' => $request->user()->cotizaciones()
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function verCotizacion(Request $request, Cotizacion $cotizacion): View
    {
        $this->suCotizacion($request, $cotizacion);

        $cotizacion->load('items');

        return view('cuenta.cotizacion', ['cotizacion' => $cotizacion]);
    }

    /**
     * «Volver a pedir lo mismo».
     *
     * Un taller pide el mismo filtro y las mismas pastillas cada tanto, y
     * armar la lista de nuevo pieza por pieza es el trabajo que nadie quiere
     * repetir.
     *
     * Los ítems guardan el nombre del repuesto tal como estaba el día del
     * pedido, así que la lista vieja siempre se lee completa. Pero volver a
     * pedirlo depende de que la pieza siga existiendo y publicada: lo que ya
     * no está no se agrega en silencio, se dice cuántas fueron.
     */
    public function repetirCotizacion(Request $request, Cotizacion $cotizacion, Cotizador $cotizador): RedirectResponse
    {
        $this->suCotizacion($request, $cotizacion);

        $productos = Producto::whereIn('id', $cotizacion->items->pluck('producto_id')->filter())
            ->where('publicado', true)
            ->get()
            ->keyBy('id');

        $agregados = 0;
        $sinCupo = 0;

        foreach ($cotizacion->items as $item) {
            // Mirando el retorno: `agregar()` devuelve `false` cuando la
            // cotización ya llegó al tope de 200 referencias. Sin esto se
            // contaba igual, y el cliente leía «Listo, pusimos otra vez esos
            // repuestos» para irse al carrito y no encontrarlos.
            if ($producto = $productos->get($item->producto_id)) {
                if ($cotizador->agregar($producto, $item->cantidad)) {
                    $agregados++;
                } else {
                    $sinCupo++;
                }
            }
        }

        // «No cupo» y «ya no existe» son dos cosas distintas y hay que
        // decirlas distinto: en la primera el cliente puede hacer algo
        // —enviar lo que tiene y volver—, en la segunda no.
        if ($agregados === 0) {
            return redirect()->route('cuenta.cotizacion', $cotizacion)->with('mensaje', $sinCupo > 0
                ? 'Tu cotización ya está llena (200 repuestos). Envíala y vuelve a intentarlo.'
                : 'Ninguno de esos repuestos sigue disponible. Llámanos y te ayudamos a encontrar el equivalente.');
        }

        $noEstan = $cotizacion->items->count() - $agregados - $sinCupo;

        $aviso = "Agregamos {$agregados} de los repuestos.";

        if ($noEstan > 0) {
            $aviso .= $noEstan === 1 ? ' Uno ya no está disponible.' : " {$noEstan} ya no están disponibles.";
        }

        if ($sinCupo > 0) {
            $aviso .= ' El resto no cupo: tu cotización llegó al máximo de 200.';
        }

        return redirect()->route('cotizacion.ver')->with(
            'mensaje',
            $noEstan === 0 && $sinCupo === 0
                ? 'Listo, pusimos otra vez esos repuestos en tu cotización.'
                : $aviso
        );
    }

    /** Una cotización se ve si es suya. Punto. */
    private function suCotizacion(Request $request, Cotizacion $cotizacion): void
    {
        abort_unless($cotizacion->user_id === $request->user()->id, 404);
    }

    // ── Sus datos ───────────────────────────────────────────────────────────

    public function datos(Request $request): View
    {
        return view('cuenta.datos', ['usuario' => $request->user()]);
    }

    /**
     * Nombre, teléfono y correo.
     *
     * El teléfono es el dato que más se mueve y el que más importa: es por
     * donde el mostrador devuelve la llamada. Hasta ahora quedaba congelado
     * en lo que la persona escribió al registrarse.
     *
     * Cambiar el CORREO pide la contraseña actual. El nombre y el teléfono
     * no: si alguien llegó a tener la sesión abierta, cambiarle el correo es
     * quedarse con la cuenta —se pide el enlace de «olvidé mi contraseña» y
     * llega a la dirección nueva—, mientras que cambiarle el nombre es una
     * molestia. La fricción va donde está el daño.
     */
    public function guardarDatos(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $correoNuevo = mb_strtolower(trim((string) $request->input('email')));
        $cambiaCorreo = $correoNuevo !== mb_strtolower($usuario->email);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:120', Rule::unique('users')->ignore($usuario->id)],
            // Quien entró con Facebook o Google no tiene contraseña que pedirle.
            'password_actual' => [Rule::requiredIf($cambiaCorreo && $usuario->password !== null), 'nullable', 'current_password'],
        ], [
            'telefono.required' => 'Déjanos un teléfono: es por donde te contactamos.',
            'email.unique' => 'Ese correo ya está en uso.',
            'password_actual.required' => 'Para cambiar tu correo, confirma tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña no coincide con la de tu cuenta.',
        ]);

        $usuario->fill([
            'name' => $datos['name'],
            'telefono' => $datos['telefono'],
            // En minúsculas, igual que la comparación de arriba. Si no,
            // cambiar sólo el CASE del correo no contaba como cambio —no se
            // pedía la contraseña— pero sí se guardaba, y quedaban dos formas
            // de escribir la misma dirección en la base.
            'email' => $correoNuevo,
        ]);

        // Todavía no verificamos correos, pero el día que se active, una
        // dirección nueva no puede heredar el visto bueno de la anterior.
        if ($cambiaCorreo) {
            $usuario->email_verified_at = null;
        }

        $usuario->save();

        if ($cambiaCorreo) {
            // A la dirección NUEVA, que es la que hay que comprobar. Si se
            // equivocó al teclearla, es aquí donde se va a dar cuenta.
            $usuario->sendEmailVerificationNotification();

            return redirect()->route('cuenta.datos')
                ->with('mensaje', 'Guardamos tus datos. Te enviamos un enlace al correo nuevo para confirmarlo.');
        }

        return redirect()->route('cuenta.datos')->with('mensaje', 'Guardamos tus datos.');
    }

    /**
     * La contraseña.
     *
     * Va en su propio formulario para que guardar el teléfono no obligue a
     * escribir la contraseña, ni al revés.
     */
    public function guardarClave(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $request->validate([
            // Quien entró con Facebook o Google no tiene una anterior: para
            // esa cuenta esto no es cambiarla, es ponerle la primera.
            'password_actual' => [Rule::requiredIf($usuario->password !== null), 'nullable', 'current_password'],
            'password' => ['required', 'confirmed', ReglaClave::min(8)],
        ], [
            'password_actual.required' => 'Confirma tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no coincide.',
            'password.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
        ]);

        $usuario->forceFill(['password' => $request->input('password')])->save();

        // Las demás sesiones se caen: si la contraseña se cambia porque
        // alguien más entró, dejarle la suya abierta no arregla nada.
        Auth::logoutOtherDevices($request->input('password'));

        return redirect()->route('cuenta.datos')->with('mensaje', 'Cambiamos tu contraseña.');
    }

    /**
     * Habeas Data · llevarse sus datos.
     *
     * La ley 1581 le da al titular derecho a CONSULTAR lo que tenemos suyo, y
     * la política que ya está publicada lo promete. Faltaba la forma de
     * ejercerlo: hasta ahora sólo se podía cerrar la cuenta, que es el otro
     * derecho.
     *
     * Va en JSON y no en PDF a propósito: esto es para llevárselo, no para
     * leerlo bonito. Lo legible ya está en pantalla.
     */
    public function descargarDatos(Request $request): StreamedResponse
    {
        $usuario = $request->user();

        $datos = [
            'generado_en' => now()->toIso8601String(),
            'cuenta' => [
                'nombre' => $usuario->name,
                'correo' => $usuario->email,
                'telefono' => $usuario->telefono,
                'creada_en' => $usuario->created_at?->toIso8601String(),
                'autorizacion_datos' => [
                    'aceptada_en' => $usuario->acepto_en?->toIso8601String(),
                    'version_politica' => $usuario->politica_version,
                ],
                // De dónde viene la cuenta, si entró con Facebook o Google. El
                // id del proveedor no se incluye: es de ellos, no suyo.
                'entra_con' => $usuario->proveedor ?? 'correo y contraseña',
            ],
            'vehiculos' => $usuario->vehiculosGuardados()->with('modelo.marca')->get()
                ->map(fn ($v) => [
                    'vehiculo' => $v->nombre_completo,
                    'placa' => $v->pivot->placa,
                    'alias' => $v->pivot->alias,
                    'guardado_en' => $v->pivot->created_at?->toIso8601String(),
                ])->all(),
            'mantenimientos' => $usuario->mantenimientos()->orderBy('fecha')->get()
                ->map(fn ($m) => [
                    'tipo' => $m->tipo,
                    'placa' => $m->placa,
                    'fecha' => $m->fecha?->toDateString(),
                    'kilometraje' => $m->kilometraje,
                    'cada' => $m->periodicidad_valor.' '.$m->periodicidad_tipo,
                    'proximo' => $m->aviso,
                    'notas' => $m->notas,
                ])->all(),
            // Los mensajes de «Contáctenos» y el boletín también son suyos:
            // la política dice «todo lo que tenemos suyo» y estas dos tablas
            // faltaban. No cuelgan de `user_id` —el formulario de contacto no
            // pide sesión— así que se buscan por correo, que es el único
            // vínculo que existe.
            'mensajes_de_contacto' => \App\Models\Mensaje::where('email', $usuario->email)
                ->orderBy('created_at')->get()
                ->map(fn ($m) => [
                    'fecha' => $m->created_at?->toIso8601String(),
                    'nombre' => $m->nombre,
                    'mensaje' => $m->mensaje,
                ])->all(),
            'boletin' => \App\Models\Suscriptor::where('correo', $usuario->email)->first()?->only(['correo', 'origen', 'created_at', 'baja_en']),
            'cotizaciones' => $usuario->cotizaciones()->with('items')->orderBy('created_at')->get()
                ->map(fn ($c) => [
                    'consecutivo' => $c->consecutivo,
                    'fecha' => $c->created_at?->toIso8601String(),
                    'nombre' => $c->nombre_completo,
                    'telefono' => $c->telefono,
                    'correo' => $c->email,
                    'notas' => $c->notas,
                    // La IP también es un dato personal y también la
                    // guardamos: si la política promete «todo lo que tenemos
                    // suyo», tiene que salir aquí.
                    'ip_desde_la_que_se_envio' => $c->ip,
                    'repuestos' => $c->items->map(fn ($i) => [
                        'repuesto' => $i->producto_nombre,
                        'vehiculo' => $i->vehiculo_nombre,
                        'cantidad' => $i->cantidad,
                    ])->all(),
                ])->all(),
        ];

        $nombre = 'mis-datos-sur-alpine-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            fn () => print (json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $nombre,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    /** El historial completo, con el filtro por placa que pide un mecánico. */
    public function mantenimientos(Request $request): View
    {
        $usuario = $request->user();

        return view('cuenta.mantenimientos', [
            'mantenimientos' => $usuario->mantenimientos()
                ->with('vehiculo.modelo.marca')
                ->when($request->query('placa'), fn ($q, $placa) => $q->where('placa', mb_strtoupper($placa)))
                ->orderByDesc('fecha')
                ->paginate(20)
                ->withQueryString(),
            'placas' => $usuario->mantenimientos()->distinct()->orderBy('placa')->pluck('placa'),
            'vehiculos' => $usuario->vehiculosGuardados()->with('modelo.marca')->get(),
            'placaFiltrada' => $request->query('placa'),
        ]);
    }

    public function guardarMantenimiento(Request $request): RedirectResponse
    {
        $datos = $this->validarMantenimiento($request);

        $mantenimiento = new Mantenimiento($datos);
        $mantenimiento->user_id = $request->user()->id;
        $mantenimiento->save();

        return redirect()->route('cuenta.mantenimientos')
            ->with('mensaje', "Anotamos «{$mantenimiento->tipo}» para la placa {$mantenimiento->placa}.");
    }

    public function actualizarMantenimiento(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->soloSuyo($request, $mantenimiento);

        $mantenimiento->fill($this->validarMantenimiento($request))->save();

        return redirect()->route('cuenta.mantenimientos')->with('mensaje', 'Mantenimiento actualizado.');
    }

    public function borrarMantenimiento(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->soloSuyo($request, $mantenimiento);

        $mantenimiento->delete();

        return back()->with('mensaje', 'Borramos el registro.');
    }

    /** @return array<string, mixed> */
    private function validarMantenimiento(Request $request): array
    {
        $datos = $request->validate([
            'placa' => ['required', 'string', 'max:10'],
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
            'tipo' => ['required', 'string', 'max:80'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'kilometraje' => ['required', 'integer', 'min:0', 'max:9999999'],
            'periodicidad_tipo' => ['required', 'in:dias,meses,kilometraje'],
            // El tope depende del tipo, porque no todos van a una fecha.
            //
            // Con `max:999999` para los tres, «cada 999999 meses» pasaba la
            // validación y reventaba al calcular cuándo toca: año 85.359,
            // fuera del rango DATE de MySQL. O sea un 500 con un valor que el
            // propio formulario había aceptado.
            //
            // Los kilómetros no son una fecha y no tienen ese problema: 60.000
            // entre kits de distribución es de lo más normal.
            'periodicidad_valor' => [
                'required', 'integer', 'min:1',
                'max:'.match ($request->input('periodicidad_tipo')) {
                    'meses' => 600,      // cincuenta años
                    'dias' => 18250,     // los mismos cincuenta, en días
                    default => 999999,   // kilómetros
                },
            ],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'fecha.before_or_equal' => 'La fecha no puede ser futura: es lo que ya se hizo.',
            'tipo.required' => 'Escribe qué se le hizo al carro.',
        ]);

        $datos['placa'] = mb_strtoupper($datos['placa']);

        return $datos;
    }

    /** El historial de otro no se toca, ni cambiando el número de la URL. */
    private function soloSuyo(Request $request, Mantenimiento $mantenimiento): void
    {
        abort_unless($mantenimiento->user_id === $request->user()->id, 403);
    }

    /**
     * Habeas Data · La baja de cuenta que pide el titular.
     *
     * Se hace en dos verbos para que quede en la vista un formulario POST con
     * su casilla de confirmación: sin esa casilla marcada no procede. El
     * usuario queda desactivado (el middleware `cuenta.activa` lo saca al
     * momento del próximo request) y sus vehículos y mantenimientos se
     * borran; se conservan las cotizaciones históricas porque las exige el
     * régimen tributario, pero el `user_id` se pone en `null` para no
     * asociarlas con la cuenta cerrada.
     */
    public function darDeBaja(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmo' => ['accepted'],
            // Quien entró con Facebook o Google no tiene contraseña que
            // confirmar. Con `required` a secas, esa persona no podía cerrar
            // su cuenta de ninguna manera: un derecho que la política promete,
            // bloqueado por una regla. Es el mismo `requiredIf` que ya usan
            // `guardarDatos` y `guardarClave` aquí mismo.
            'password' => [Rule::requiredIf($request->user()->password !== null), 'nullable', 'current_password'],
        ], [
            'confirmo.accepted' => 'Necesitamos que confirmes que quieres cerrar tu cuenta.',
            'password.current_password' => 'La contraseña no coincide con la de tu cuenta.',
        ]);

        $usuario = $request->user();

        // Y fuera del boletín. Quien pide que borremos sus datos no puede
        // seguir en la lista de correos que el panel exporta en CSV.
        \App\Models\Suscriptor::where('correo', $usuario->email)
            ->whereNull('baja_en')
            ->update(['baja_en' => now()]);

        // Los mensajes de «Contáctenos» se anonimizan.
        //
        // Se quedaban enteros en `/panel/mensajes` —nombre, correo, teléfono y
        // el texto— de alguien que pidió que lo borráramos. No hay `user_id`
        // que anular: el formulario no pide sesión, así que el único vínculo
        // es el correo, y una vez anonimizado el de `users` ya no habría forma
        // de encontrarlos. Por eso se hace AQUÍ y no después.
        \App\Models\Mensaje::where('email', $usuario->email)->update([
            'nombre' => 'Dado de baja',
            'email' => 'baja+'.$usuario->id.'@suralpine.invalid',
        ]);

        // Las cotizaciones se desvinculan Y se anonimizan sus datos de contacto.
        //
        // Sólo anular el `user_id` no bastaba: la tabla `cotizaciones` guarda
        // su PROPIA copia de nombre, apellidos, teléfono, correo, comentarios
        // e IP —congelada a propósito, para que la solicitud siga diciendo con
        // quién se habló—. Quien ejercía su derecho de supresión seguía ahí
        // entero, y esas filas se descargan completas desde el CSV del panel.
        //
        // Lo que la retención tributaria justifica es el DOCUMENTO: el
        // consecutivo, la fecha y qué repuestos se pidieron. No el teléfono ni
        // el correo, que es lo único explotable de ese CSV. Así que se
        // conserva el histórico y se van los datos de contacto.
        $usuario->cotizaciones()->update([
            'user_id' => null,
            'nombre' => 'Dado de baja',
            'apellidos' => null,
            'telefono' => '',
            'email' => 'baja+'.$usuario->id.'@suralpine.invalid',
            'notas' => null,
            'ip' => null,
        ]);

        $usuario->mantenimientos()->delete();
        $usuario->vehiculosGuardados()->detach();

        // El correo se libera. Antes la fila se quedaba con él —único en la
        // tabla— y esa persona entraba en un callejón sin salida: no podía
        // registrarse otra vez («ya puedes iniciar sesión», decía), no podía
        // entrar («tu cuenta está desactivada») y «olvidé mi contraseña»
        // cortaba en silencio. Tres mensajes que se contradecían.
        //
        // La fila se conserva por trazabilidad —las cotizaciones históricas
        // las exige el régimen tributario— pero con el correo anonimizado y
        // el dominio `.invalid`, que por norma no existe y nunca se resolverá.
        $usuario->forceFill([
            'email' => 'baja+'.$usuario->id.'@suralpine.invalid',
            'email_verified_at' => null,
            // El nombre y el teléfono también. Se quedaban en la fila
            // indefinidamente: son datos personales, y conservarlos no aporta
            // nada a la trazabilidad que justifica no borrar la fila.
            'name' => 'Cuenta dada de baja',
            'telefono' => null,
            // La contraseña también se va. Sobrevivía al cierre, y un hash
            // guardado es un dato personal más que no hace falta conservar
            // para nada: la cuenta ya no se puede abrir.
            'password' => null,
            'activo' => false,
            'baja_solicitada_en' => now(),
            'remember_token' => null,
        ])->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')
            ->with('mensaje', 'Cerramos tu cuenta. Gracias por haber estado.');
    }
}
