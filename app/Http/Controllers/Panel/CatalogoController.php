<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use App\Services\ImagenesWeb;
use App\Services\ImportadorCatalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogoController extends Controller
{
    public function __construct(private readonly ImagenesWeb $imagenes) {}

    public function index(Request $request): View
    {
        return view('panel.catalogo.index', [
            // El `select` va antes del `withCount`: al revés borra el subquery
            // del conteo y la columna de piezas sale en blanco.
            'vehiculos' => Vehiculo::query()
                ->select('vehiculos.*')
                ->join('modelos', 'modelos.id', '=', 'vehiculos.modelo_id')
                ->join('marcas', 'marcas.id', '=', 'modelos.marca_id')
                ->with('modelo.marca')
                ->withCount('productos')
                ->when($request->query('marca'), fn ($q, $marca) => $q->where('marcas.slug', $marca))
                ->when(is_string($q = $request->query('q')) ? $q : '', fn ($q, $termino) => $q
                    ->where(fn ($sub) => $sub
                        ->where('modelos.nombre', 'like', "%{$termino}%")
                        ->orWhere('marcas.nombre', 'like', "%{$termino}%")))
                ->orderBy('marcas.nombre')
                ->orderBy('modelos.nombre')
                ->orderBy('vehiculos.cilindraje')
                ->orderBy('vehiculos.anio_inicio')
                ->paginate(30)
                ->withQueryString(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'filtros' => $request->only(['q', 'marca']),
            'totales' => [
                'vehiculos' => Vehiculo::count(),
                'productos' => Producto::count(),
                'tiposParte' => TipoParte::count(),
            ],
        ]);
    }

    /**
     * El editor de matriz. Nadie va a editar 29.272 fichas una por una: lo que
     * el equipo necesita es abrir un carro y marcar con casillas las piezas que
     * lleva, agrupadas por sistema — igual que en su Excel.
     */
    public function editar(Vehiculo $vehiculo): View
    {
        $vehiculo->load('modelo.marca');

        return view('panel.catalogo.matriz', [
            'vehiculo' => $vehiculo,
            'categorias' => Categoria::with(['tiposParte' => fn ($q) => $q->orderBy('nombre')])
                ->orderBy('nombre')
                ->get(),
            // La pieza entera y no sólo su id: hace falta el slug para poder
            // enlazar a su ficha desde la casilla marcada. Las claves siguen
            // siendo el `tipo_parte_id`, que es lo que usa la vista.
            'marcados' => Producto::where('vehiculo_id', $vehiculo->id)
                ->get()
                ->keyBy('tipo_parte_id'),
        ]);
    }

    public function guardarMatriz(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $datos = $request->validate([
            'tipos' => ['array'],
            'tipos.*' => ['integer', 'exists:tipos_parte,id'],
        ]);

        $elegidos = collect($datos['tipos'] ?? [])->map(fn ($id) => (int) $id);
        $actualesPorTipo = Producto::where('vehiculo_id', $vehiculo->id)->get()->keyBy('tipo_parte_id');

        $desmarcadas = $actualesPorTipo->reject(fn ($_, $tipoId) => $elegidos->contains($tipoId));

        // Un desmarcado por error borraba en silencio referencias, imágenes y
        // descripciones que el equipo había cargado a mano —trabajo que no se
        // recupera reencendiendo la casilla, porque `armar()` crea otra pieza
        // en blanco. Cuando la desmarcada tiene datos propios, se pide una
        // confirmación explícita en el segundo paso.
        $conDatos = $desmarcadas->filter(fn ($p) => filled($p->referencia) || filled($p->imagen) || filled($p->descripcion));

        if ($conDatos->isNotEmpty() && ! $request->boolean('confirmar_retiro')) {
            return back()->withInput()->with('confirmar_retiro', [
                'vehiculo' => $vehiculo->nombre_completo,
                'piezas' => $conDatos->values()->map(fn ($p) => [
                    'nombre' => $p->nombre,
                    'referencia' => $p->referencia,
                    'tiene_imagen' => filled($p->imagen),
                    'tiene_descripcion' => filled($p->descripcion),
                ])->all(),
            ]);
        }

        // El plan se RECALCULA dentro de la transacción.
        //
        // Lo de arriba se leyó fuera para poder pedir la confirmación de
        // retiro, así que es una foto vieja. Si dos personas del equipo
        // guardan el mismo vehículo con pocos segundos de diferencia, la
        // segunda intenta crear una pieza que la primera acaba de crear y
        // revienta con «Duplicate entry» —perdiendo su guardado entero—, o
        // peor: quedan mezcladas las piezas de los dos formularios, que no es
        // ni lo que pidió uno ni lo que pidió el otro.
        //
        // Volver a leer aquí, con la transacción ya abierta, hace que el plan
        // se calcule sobre lo que hay de verdad. Lo que decide sigue siendo lo
        // que la persona marcó; lo que cambia es contra qué se compara.
        // Lo que de verdad pasó, para que el aviso no cuente el plan viejo.
        [$creadas, $retiradas] = DB::transaction(function () use ($vehiculo, $elegidos) {
            $ahora = Producto::where('vehiculo_id', $vehiculo->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('tipo_parte_id');

            $quitar = $ahora->reject(fn ($_, $tipoId) => $elegidos->contains($tipoId))->pluck('id');

            if ($quitar->isNotEmpty()) {
                // Las solicitudes históricas no se rompen: el ítem guarda el
                // nombre congelado y la llave foránea queda en nulo.
                Producto::whereIn('id', $quitar)->delete();
            }

            $crear = $elegidos->reject(fn ($id) => $ahora->has($id));

            foreach (TipoParte::with('categoria')->whereIn('id', $crear)->get() as $tipo) {
                Producto::create($this->armar($vehiculo, $tipo));
            }

            return [$crear->count(), $quitar->count()];
        });

        ImportadorCatalogo::olvidarCaches();

        return back()->with('mensaje', sprintf(
            'Guardado: %d %s, %d %s en %s.',
            $creadas,
            plural($creadas, 'pieza agregada', 'piezas agregadas'),
            $retiradas,
            plural($retiradas, 'retirada', 'retiradas'),
            $vehiculo->nombre_completo
        ));
    }

    public function crear(): View
    {
        return view('panel.catalogo.vehiculo', [
            'vehiculo' => new Vehiculo(['anio_inicio' => now()->year - 10, 'anio_fin' => now()->year]),
            'marcas' => Marca::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Corregir los datos de un vehículo que ya existe. Hasta ahora sólo se
     * podían crear: un rango de años mal tecleado no había forma de arreglarlo
     * desde el panel.
     */
    public function editarDatos(Vehiculo $vehiculo): View
    {
        return view('panel.catalogo.vehiculo', [
            'vehiculo' => $vehiculo->load('modelo.marca'),
            'marcas' => Marca::orderBy('nombre')->get(),
        ]);
    }

    public function guardarVehiculo(Request $request, ?Vehiculo $vehiculo = null): RedirectResponse
    {
        $datos = $request->validate([
            'marca' => ['required', 'string', 'max:60'],
            'modelo' => ['required', 'string', 'max:60'],
            'cilindraje' => ['required', 'string', 'max:40'],
            'anio_inicio' => ['required', 'integer', 'min:1950', 'max:'.(now()->year + 2)],
            'anio_fin' => ['required', 'integer', 'gte:anio_inicio', 'max:'.(now()->year + 2)],
        ], [
            'anio_fin.gte' => 'El año final no puede ser anterior al inicial.',
        ]);

        $marca = Marca::firstOrCreate(['slug' => Str::slug($datos['marca'])], ['nombre' => mb_strtoupper($datos['marca'])]);
        $modelo = Modelo::firstOrCreate(
            ['marca_id' => $marca->id, 'slug' => Str::slug($datos['modelo'])],
            ['nombre' => mb_strtoupper($datos['modelo'])]
        );

        $slug = Str::slug(implode('-', [
            $marca->nombre, $modelo->nombre, $datos['cilindraje'], $datos['anio_inicio'], $datos['anio_fin'],
        ]));

        $identidad = [
            'modelo_id' => $modelo->id,
            'cilindraje' => $datos['cilindraje'],
            'anio_inicio' => $datos['anio_inicio'],
        ];

        // Marca, modelo, cilindraje y año inicial son la identidad del vehículo:
        // si al editar se cambian a los de otro que ya existe, se avisa en vez
        // de dejar que reviente el índice único.
        // Se comprueba la identidad Y el slug.
        //
        // Con sólo la identidad, el aviso amable no llegaba nunca cuando el
        // choque era de slug: en el catálogo real conviven cilindrajes como
        // «1600 M.N», «1600 M.V» y «1300 CARB», y teclear «1600 MN» donde ya
        // existe «1600 M.N» produce el mismo slug —y un 500 en la cara de
        // quien está corrigiendo un dato—.
        $choque = Vehiculo::where(fn ($q) => $q->where($identidad)->orWhere('slug', $slug))
            ->when($vehiculo, fn ($q) => $q->whereKeyNot($vehiculo->id))
            ->first();

        if ($choque) {
            return back()->withInput()->withErrors([
                'cilindraje' => "Ya existe {$choque->nombre_completo}. Edita ese en vez de duplicarlo.",
            ]);
        }

        if ($vehiculo) {
            $vehiculo->update($identidad + ['anio_fin' => $datos['anio_fin'], 'slug' => $slug]);

            $this->renombrarPiezasDe($vehiculo);
        } else {
            $vehiculo = Vehiculo::create($identidad + ['anio_fin' => $datos['anio_fin'], 'slug' => $slug]);
        }

        ImportadorCatalogo::olvidarCaches();

        return redirect()
            ->route('panel.catalogo.editar', $vehiculo)
            ->with('mensaje', $vehiculo->wasRecentlyCreated
                ? 'Vehículo guardado. Marca ahora las piezas que lleva.'
                : 'Vehículo actualizado.');
    }

    /**
     * Rehace el nombre de las piezas de un vehículo que se acaba de corregir.
     *
     * El nombre se compone con marca, modelo y cilindraje —«Pastillas Freno
     * Delanteras AVEO 1600 CHEVROLET»—, así que al corregir el cilindraje de
     * 1600 a 1800 el catálogo, el buscador y el correo al mostrador seguían
     * diciendo 1600 debajo de un carro que ya se llamaba 1800.
     *
     * El SLUG no se toca, y es a propósito: está en URLs que Google ya indexó
     * y en el sitemap, y además puede llevar desambiguaciones que sólo conoce
     * el importador (la categoría cuando dos partes se llaman igual, el año
     * cuando hay dos generaciones). Cambiar lo que se lee y dejar quieto lo
     * que enlaza es lo correcto en los dos frentes.
     */
    private function renombrarPiezasDe(Vehiculo $vehiculo): void
    {
        $vehiculo->loadMissing('modelo.marca');

        $modelo = $vehiculo->modelo;
        $marca = $modelo->marca;

        $vehiculo->productos()->with('tipoParte')->chunkById(200, function ($productos) use ($modelo, $marca, $vehiculo) {
            foreach ($productos as $producto) {
                $producto->update([
                    'nombre' => sprintf(
                        '%s %s %s %s',
                        $producto->tipoParte->nombre,
                        $modelo->nombre,
                        $vehiculo->cilindraje,
                        $marca->nombre
                    ),
                ]);
            }
        });
    }

    /** Ficha individual: referencia, foto y descripción son datos del equipo. */
    public function editarProducto(Producto $producto): View
    {
        return view('panel.catalogo.producto', [
            'producto' => $producto->load(['vehiculo.modelo.marca', 'tipoParte.categoria']),
        ]);
    }

    public function guardarProducto(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'referencia' => ['nullable', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'publicado' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:webp,jpg,jpeg,png', 'max:4096'],
        ], [
            'imagen.image' => 'Sube una imagen (WebP, JPG o PNG).',
            'imagen.max' => 'La imagen no puede pesar más de 4 MB.',
        ]);

        if ($request->hasFile('imagen')) {
            // Por el mismo camino que las demás fotos del panel. Ésta era la
            // única que guardaba los BYTES ORIGINALES: sin `mimes:` aceptaba
            // GIF y BMP que las otras rechazan, sin reencodar se servía con
            // todos sus metadatos —el EXIF con la ubicación si la foto la tomó
            // un asesor con el celular—, sin tope de dimensiones un JPEG de
            // 6.000 px iba entero a un móvil, y el nombre nunca cumplía la
            // convención `-{ancho}.webp` que exige el `srcset`.
            try {
                $datos['imagen'] = $this->imagenes->guardarEnDisco(
                    $request->file('imagen'), 'productos', $producto->slug, [520, 1024]
                );
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['imagen' => $e->getMessage()]);
            }
        }

        // Sólo invalidamos la caché de la portada si cambió lo que la portada
        // ve: la casilla «publicado» o la foto. Editar sólo la descripción o
        // la referencia no toca contadores ni destacados, y no vale la pena
        // tirar las 225 llaves versionadas por eso.
        $cambioVisibilidad = $producto->publicado !== $request->boolean('publicado');
        $cambioImagen = isset($datos['imagen']);

        $producto->update([
            'referencia' => $datos['referencia'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'publicado' => $request->boolean('publicado'),
            'imagen' => $datos['imagen'] ?? $producto->imagen,
        ]);

        if ($cambioVisibilidad || $cambioImagen) {
            ImportadorCatalogo::olvidarCaches();
        }

        return back()->with('mensaje', 'Ficha actualizada.');
    }

    private function armar(Vehiculo $vehiculo, TipoParte $tipo): array
    {
        $modelo = $vehiculo->modelo;
        $marca = $modelo->marca;
        $nombre = sprintf('%s %s %s %s', $tipo->nombre, $modelo->nombre, $vehiculo->cilindraje, $marca->nombre);

        // Mismo criterio que el importador: se desambigua sólo si hace falta.
        $ambigua = TipoParte::where('slug', $tipo->slug)->count() > 1;
        $generaciones = Vehiculo::where('modelo_id', $modelo->id)
            ->where('cilindraje', $vehiculo->cilindraje)
            ->count() > 1;

        $partes = [$nombre];

        if ($ambigua) {
            $partes[] = $tipo->categoria->slug;
        }

        if ($generaciones) {
            $partes[] = $vehiculo->anio_inicio;
        }

        return [
            'vehiculo_id' => $vehiculo->id,
            'tipo_parte_id' => $tipo->id,
            'nombre' => $nombre,
            'slug' => Str::slug(implode('-', $partes)),
            'publicado' => true,
        ];
    }
}
