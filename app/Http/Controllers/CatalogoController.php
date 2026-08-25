<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Services\Cotizador;
use App\Services\ImportadorCatalogo;
use App\Services\VehiculoActivo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogoController extends Controller
{
    private const POR_PAGINA = 24;

    public function __construct(private readonly VehiculoActivo $vehiculoActivo) {}

    public function inicio(): View
    {
        return view('inicio', [
            // Sólo las que el cliente exhibe: las que tienen foto. Una tarjeta
            // vacía en la portada se ve peor que una categoría de menos.
            'categorias' => $this->categoriasDePortada(),
            'marcas' => Marca::query()->orderBy('nombre')->get(),
            'totalProductos' => $this->totalCatalogo(),
            'banners' => $this->banners(),
            'destacados' => $this->destacados(),
            'proveedores' => $this->proveedores(),
        ]);
    }

    /**
     * Las tarjetas de la portada con su conteo de piezas.
     *
     * El conteo es una subconsulta por categoría sobre 29.272 productos: es la
     * consulta más cara del sitio y el resultado sólo cambia cuando el equipo
     * toca el catálogo, así que se guarda. La llave incluye el vehículo porque
     * los contadores dependen de él.
     */
    private function categoriasDePortada(): \Illuminate\Support\Collection
    {
        $id = $this->vehiculoActivo->id();

        return Cache::remember('inicio.categorias.'.ImportadorCatalogo::version().'.'.$id, 3600, fn () => Categoria::query()
            ->whereNotNull('imagen')
            ->withCount(['productos' => $this->filtroVehiculo()])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get());
    }

    /**
     * El carrusel de "Productos Destacados". Mientras no haya un criterio del
     * negocio, se muestran piezas de rotación alta de vehículos populares.
     *
     * El azar se congela diez minutos. Sin eso, `ORDER BY RAND()` escanea la
     * tabla entera en cada visita —el 60 % del tiempo de la portada— y, peor,
     * la lista cambia bajo los pies del visitante cada vez que agrega algo.
     */
    private function destacados(): \Illuminate\Support\Collection
    {
        $vehiculo = $this->vehiculoActivo->get();

        return Cache::remember(
            'inicio.destacados.'.ImportadorCatalogo::version().'.'.($vehiculo?->id ?? ''),
            600,
            fn () => Producto::publicados()
                ->with(['vehiculo.modelo.marca', 'tipoParte.categoria'])
                ->when($vehiculo, fn ($q) => $q->where('vehiculo_id', $vehiculo->id))
                ->inRandomOrder()
                ->limit(10)
                ->get()
        );
    }

    /**
     * Los logos de proveedores que el cliente exhibe como marcas destacadas.
     *
     * El nombre sale del archivo, así que se limpia: `hQ.png` se anunciaba
     * literalmente como «hQ», y los que van en mayúsculas muchos lectores de
     * pantalla los deletrean letra por letra.
     */
    private const NOMBRES_PROVEEDOR = [
        'hq' => 'HQ',
        'magnetti' => 'Magnetti Marelli',
        'npr' => 'NPR',
        'ngk' => 'NGK',
        'bwb' => 'BWB',
        'mac' => 'MAC',
    ];

    private function proveedores(): array
    {
        return Cache::remember('inicio.proveedores', 3600, fn () => collect(glob(public_path('img/proveedores/*.png')))
            ->map(function ($ruta) {
                $archivo = pathinfo($ruta, PATHINFO_FILENAME);

                return [
                    'src' => '/img/proveedores/'.basename($ruta),
                    'nombre' => self::NOMBRES_PROVEEDOR[mb_strtolower($archivo)] ?? Str::title($archivo),
                ];
            })
            ->sortBy('nombre')
            ->values()
            ->all());
    }

    /**
     * Las campañas que envió el cliente, ya convertidas a WebP en dos anchos.
     * Se leen del disco para que subir una nueva sea copiar un archivo.
     */
    private function banners(): array
    {
        return Cache::remember('inicio.banners', 3600, function () {
            $rotulos = [
                'espirales' => 'Espirales Imal: mayor resistencia y duración',
                'gabriel' => 'Amortiguadores Gabriel: las mejores piezas de suspensión',
                'mac' => 'Baterías MAC',
                'bwb' => 'Frenos BWB',
                'incolbest' => 'Frenos Incolbest',
                'aceite' => 'Aceites y lubricantes',
                'sitio-oficial' => 'Importadora Sur Alpine: único sitio web oficial',
            ];

            return collect(glob(public_path('img/banners/*-1600.webp')))
                ->map(function (string $ruta) use ($rotulos) {
                    $base = str_replace('-1600.webp', '', basename($ruta));
                    $clave = collect($rotulos)->keys()->first(fn ($k) => str_contains($base, $k));

                    return [
                        'src' => '/img/banners/'.$base.'-1600.webp',
                        // El escalón que faltaba: la caja mide 1248 px, así que
                        // entre 900 y 1600 el navegador saltaba al grande.
                        'medio' => '/img/banners/'.$base.'-1280.webp',
                        'chico' => '/img/banners/'.$base.'-900.webp',
                        'alt' => $rotulos[$clave] ?? 'Novedades Sur Alpine',
                        'orden' => $clave === 'sitio-oficial' ? 0 : 1,
                    ];
                })
                ->sortBy('orden')
                ->values()
                ->all();
        });
    }

    /**
     * Listado general. A diferencia del sitio anterior, el catálogo se ve
     * completo sin obligar a elegir vehículo: el selector filtra, no cobra peaje.
     */
    public function catalogo(Request $request): View
    {
        return $this->listado($request, titulo: 'Todos los repuestos');
    }

    public function categoria(Request $request, Categoria $categoria): View
    {
        return $this->listado($request, titulo: $categoria->nombre, categoria: $categoria);
    }

    public function tipoParte(Request $request, Categoria $categoria, TipoParte $tipoParte): View
    {
        abort_unless($tipoParte->categoria_id === $categoria->id, 404);

        return $this->listado(
            $request,
            titulo: $tipoParte->nombre,
            categoria: $categoria,
            tipoParte: $tipoParte
        );
    }

    public function producto(Producto $producto): View
    {
        $producto->load(['tipoParte.categoria', 'vehiculo.modelo.marca']);

        // Lo que un mecánico busca después: las otras piezas del mismo sistema
        // para ese mismo carro.
        $relacionados = Producto::publicados()
            ->where('vehiculo_id', $producto->vehiculo_id)
            ->whereHas('tipoParte', fn ($q) => $q->where('categoria_id', $producto->tipoParte->categoria_id))
            ->whereKeyNot($producto->getKey())
            ->with('tipoParte.categoria')
            ->orderBy('nombre')
            ->limit(8)
            ->get();

        return view('producto', [
            'producto' => $producto,
            'relacionados' => $relacionados,
            'enCotizacion' => app(Cotizador::class)->tiene($producto->id),
        ]);
    }

    private function listado(
        Request $request,
        string $titulo,
        ?Categoria $categoria = null,
        ?TipoParte $tipoParte = null,
    ): View {
        $consulta = Producto::publicados()
            ->with(['tipoParte.categoria', 'vehiculo.modelo.marca'])
            ->paraVehiculo($this->vehiculoActivo->id())
            ->buscar($request->query('q'));

        if ($tipoParte) {
            $consulta->where('tipo_parte_id', $tipoParte->id);
        } elseif ($categoria) {
            $consulta->whereHas('tipoParte', fn ($q) => $q->where('categoria_id', $categoria->id));
        }

        $consulta = match ($request->query('orden')) {
            'z-a' => $consulta->orderByDesc('nombre'),
            'recientes' => $consulta->orderByDesc('id'),
            default => $consulta->orderBy('nombre'),
        };

        // Un solo conteo alimenta el encabezado, el filtro lateral y la
        // paginación. En el sitio anterior había tres cifras distintas para el
        // mismo listado, y ninguna coincidía con lo que se veía en pantalla.
        $productos = $consulta->paginate(self::POR_PAGINA)->withQueryString();

        return view('catalogo', [
            'titulo' => $titulo,
            'productos' => $productos,
            'categoria' => $categoria,
            'tipoParte' => $tipoParte,
            'categorias' => Categoria::query()
                ->withCount(['productos' => $this->filtroVehiculo()])
                ->orderBy('nombre')
                ->get(),
            'tiposParte' => $categoria
                ? TipoParte::where('categoria_id', $categoria->id)
                    ->withCount(['productos' => $this->filtroVehiculo()])
                    ->orderBy('nombre')
                    ->get()
                : collect(),
        ]);
    }

    /**
     * El mismo filtro que ve el listado se aplica a los contadores del menú.
     * Si no, el filtro lateral promete 21 repuestos y la grilla muestra 3.
     */
    private function filtroVehiculo(): callable
    {
        $id = $this->vehiculoActivo->id();

        return fn (Builder $query) => $query->publicados()->paraVehiculo($id);
    }

    private function totalCatalogo(): int
    {
        $id = $this->vehiculoActivo->id();

        return $id
            ? Producto::publicados()->paraVehiculo($id)->count()
            : Cache::remember('catalogo.total', 3600, fn () => Producto::publicados()->count());
    }
}
