<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use App\Services\ImportadorCatalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogoController extends Controller
{
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
                ->when($request->query('q'), fn ($q, $termino) => $q
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
            'marcados' => Producto::where('vehiculo_id', $vehiculo->id)
                ->pluck('tipo_parte_id')
                ->flip(),
        ]);
    }

    public function guardarMatriz(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $datos = $request->validate([
            'tipos' => ['array'],
            'tipos.*' => ['integer', 'exists:tipos_parte,id'],
        ]);

        $elegidos = collect($datos['tipos'] ?? [])->map(fn ($id) => (int) $id);
        $actuales = Producto::where('vehiculo_id', $vehiculo->id)->pluck('id', 'tipo_parte_id');

        $porCrear = $elegidos->reject(fn ($id) => $actuales->has($id));
        $porQuitar = $actuales->reject(fn ($_, $tipoId) => $elegidos->contains($tipoId));

        DB::transaction(function () use ($vehiculo, $porCrear, $porQuitar) {
            if ($porQuitar->isNotEmpty()) {
                // Las solicitudes históricas no se rompen: el ítem guarda el
                // nombre congelado y la llave foránea queda en nulo.
                Producto::whereIn('id', $porQuitar->values())->delete();
            }

            foreach (TipoParte::with('categoria')->whereIn('id', $porCrear)->get() as $tipo) {
                Producto::create($this->armar($vehiculo, $tipo));
            }
        });

        ImportadorCatalogo::olvidarCaches();

        return back()->with('mensaje', sprintf(
            'Guardado: %d %s y %d %s en %s.',
            $porCrear->count(),
            Str::plural('pieza agregada', $porCrear->count()),
            $porQuitar->count(),
            Str::plural('quitada', $porQuitar->count()),
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
        $choque = Vehiculo::where($identidad)->when($vehiculo, fn ($q) => $q->whereKeyNot($vehiculo->id))->first();

        if ($choque) {
            return back()->withInput()->withErrors([
                'cilindraje' => "Ya existe {$choque->nombre_completo}. Edita ese en vez de duplicarlo.",
            ]);
        }

        if ($vehiculo) {
            $vehiculo->update($identidad + ['anio_fin' => $datos['anio_fin'], 'slug' => $slug]);
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
            'imagen' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = '/storage/'.$request->file('imagen')->store('productos', 'public');
        }

        $producto->update([
            'referencia' => $datos['referencia'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'publicado' => $request->boolean('publicado'),
            'imagen' => $datos['imagen'] ?? $producto->imagen,
        ]);

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
