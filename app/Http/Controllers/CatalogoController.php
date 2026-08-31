<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Nota;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Services\Cotizador;
use App\Services\ImportadorCatalogo;
use App\Services\VehiculoActivo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogoController extends Controller
{
    private const POR_PAGINA = 24;

    /*
     * Las que el cliente exhibe en la portada, en el orden que él fijó.
     *
     * Son diez, contadas sobre su sitio en producción: hasta ahora aquí salían
     * ocho, y las dos que quedaban fuera eran Frenos y Suspensión —justo las
     * dos categorías más grandes del catálogo.
     */
    private const CATEGORIAS_EN_PORTADA = 10;

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
            'notas' => $this->notas(),
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

        // Diez, no las doce: el cliente eligió cuáles exhibir y en qué orden,
        // y ese orden vive en la columna `orden` (ver ImagenesSeeder). Las
        // demás siguen accesibles desde el catálogo y el menú.
        //
        // El límite entra en la llave de caché: sin él, subirlo de ocho a diez
        // no cambiaba nada hasta que la caché expirara sola una hora después.
        return Cache::remember('inicio.categorias.'.ImportadorCatalogo::version().'.'.self::CATEGORIAS_EN_PORTADA.'.'.$id, 3600, fn () => Categoria::query()
            ->withCount(['productos' => $this->filtroVehiculo()])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->limit(self::CATEGORIAS_EN_PORTADA)
            ->get());
    }

    /**
     * Las cuatro notas de «Actualízate con Nosotros».
     *
     * Cuatro porque son las que caben en la fila del original; si el cliente
     * publica una quinta, entra la nueva y sale la más vieja. El listado
     * completo vive en `/noticias`.
     */
    private function notas(): \Illuminate\Support\Collection
    {
        return Nota::query()->visibles()->recientes()->limit(4)->get();
    }

    /**
     * El carrusel de "Productos Destacados": las piezas que más han pedido.
     *
     * El criterio lo fijó el cliente —«lo ideal es que sean los productos más
     * cotizados»—, así que se ordena por cuántas veces ha entrado cada pieza en
     * una solicitud. Las que nadie ha pedido todavía completan la fila en orden
     * estable, para que el carrusel nunca salga vacío al arrancar.
     */
    private function destacados(): \Illuminate\Support\Collection
    {
        $vehiculo = $this->vehiculoActivo->get();

        return Cache::remember(
            'inicio.destacados.'.ImportadorCatalogo::version().'.'.($vehiculo?->id ?? ''),
            600,
            fn () => Producto::publicados()
                ->with(['vehiculo.modelo.marca', 'tipoParte.categoria'])
                ->withCount('itemsCotizados as veces_cotizado')
                ->when($vehiculo, fn ($q) => $q->where('vehiculo_id', $vehiculo->id))
                ->orderByDesc('veces_cotizado')
                ->orderBy('id')
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
     * Las campañas de la portada.
     *
     * Antes salían de un `glob()` sobre el disco y los textos alternativos
     * estaban escritos aquí. Ahora vienen de la tabla `banners`, que el
     * cliente administra desde el panel: puede ordenarlas, apagar una por
     * temporada sin borrarla, y ponerle el texto que lea un lector de
     * pantalla.
     */
    private function banners(): array
    {
        return Cache::remember(
            'inicio.banners',
            3600,
            fn () => Banner::visibles()->get()->map->paraElCarrusel()->all()
        );
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

    /**
     * El listado de un tipo de parte dentro de una categoría.
     *
     * El slug llega como texto y se busca DENTRO de la categoría, en vez de
     * dejar que Laravel resuelva el modelo por su cuenta. Cuatro slugs existen
     * dos veces —«axial-direccion», «terminal-direccion» y los dos retenes de
     * rueda están en Dirección y también en Suspensión—: el binding implícito
     * devolvía siempre la fila de Dirección y las cuatro URLs de Suspensión
     * morían con 404… mientras el sitemap las seguía publicando. Eran 457
     * repuestos sin página de aterrizaje, justo en las búsquedas de más
     * intención («terminal de dirección aveo»).
     */
    public function tipoParte(Request $request, Categoria $categoria, string $tipoParte): View|RedirectResponse
    {
        // El slug se resuelve a mano —dentro de su categoría, porque cuatro se
        // repiten entre dos—, así que este segmento no es un parámetro de ruta
        // y el middleware `slug` no lo ve. La corrección de mayúsculas hay que
        // hacerla aquí: sin ella, `/repuestos/FRENOS/BANDAS-FRENO` se quedaba
        // a medias, con la categoría ya corregida y la pieza no.
        $pedido = $tipoParte;

        $tipoParte = $categoria->tiposParte()
            ->where(fn ($q) => $q->where('slug', $pedido)
                ->orWhereRaw('lower(slug) = ?', [mb_strtolower($pedido)]))
            ->firstOrFail();

        // Se comparan los dos segmentos a la vez para redirigir UNA sola vez.
        // `originalParameter` y no `route('categoria')`: lo segundo devuelve el
        // MODELO ya resuelto, así que la comparación era siempre distinta y la
        // URL buena se redirigía a sí misma. Bucle infinito en el navegador.
        if ($pedido !== $tipoParte->slug
            || $request->route()->originalParameter('categoria') !== $categoria->slug) {
            return redirect()->to(
                route('tipo-parte', [$categoria, $tipoParte])
                .($request->getQueryString() ? '?'.$request->getQueryString() : ''),
                301
            );
        }

        return $this->listado(
            $request,
            titulo: $tipoParte->nombre,
            categoria: $categoria,
            tipoParte: $tipoParte
        );
    }

    public function producto(Producto $producto): View
    {
        // Una pieza despublicada no se ve por la URL pública, ni siquiera pegando
        // el enlace directo. Antes respondía 200 tal cual, y quedaba en el
        // sitemap la primera hora tras despublicar.
        abort_unless($producto->publicado, 404);

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
            ->buscar(is_string($q = $request->query('q')) ? $q : '');

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
        // Los enlaces del paginador llevan `orden` y `q`, y nada más.
        //
        // `withQueryString()` los llenaba con TODA la cadena de consulta, así
        // que `/repuestos?utm_source=facebook` generaba mil doscientos enlaces
        // rastreables con el `utm_source` dentro. Cada uno es una dirección
        // distinta del mismo catálogo, y multiplicarlas es exactamente lo que
        // le sirve a quien está suplantando a Sur Alpine.
        $productos = $consulta->paginate(self::POR_PAGINA)
            ->appends(array_filter($request->only(['orden', 'q']), 'is_scalar'));

        // `?page=99999` respondía 200 con cero productos: un 404 disfrazado,
        // que Google cuenta como página de baja calidad. Con 3.822 URLs
        // paginadas en el catálogo, eso es mucho ruido.
        abort_if($productos->currentPage() > max(1, $productos->lastPage()), 404);

        // Los conteos del filtro lateral son la consulta más cara del sitio
        // —una subconsulta correlacionada por categoría sobre 29.272 productos,
        // 340-530 ms medidos— y sólo cambia cuando el equipo toca el catálogo.
        // La misma estrategia que la portada, con la misma versión de caché.
        $id = $this->vehiculoActivo->id() ?? 0;

        // Con una búsqueda activa NO se muestran los contadores del filtro
        // lateral.
        //
        // Decían 2.877 mientras la grilla mostraba 239: dos cifras que se
        // contradicen en la misma pantalla. Meter el término en el conteo
        // arreglaba el número pero metía la búsqueda de texto completo dentro
        // de una subconsulta correlacionada sobre 29.272 productos, y eso son
        // cientos de milisegundos por página que además no se puede cachear
        // (una llave por término escrito). Un número que no se ve es mejor que
        // un número que miente y más barato que uno que cuesta medio segundo.
        $buscando = filled($q);

        // La descripción para Google, armada con lo que ya está en la mano.
        //
        // El catálogo, las 12 categorías y los 290 tipos de parte emitían
        // LITERALMENTE la misma frase: 303 páginas indistinguibles entre sí y
        // ninguna que responda a la búsqueda que la trajo.
        $cuantos = $productos->total();
        // Con cero resultados no se emite descripción propia: decirle a
        // Google y al visitante «0 referencias en catálogo» es peor que el
        // texto genérico del sitio.
        $descripcion = ($tipoParte || $categoria) && $cuantos > 0
            ? sprintf(
                '%s: %s en catálogo para vehículos livianos. Pide tu cotización a Importadora Sur Alpine, Bogotá.',
                $titulo,
                $cuantos === 1 ? '1 referencia' : number_format($cuantos, 0, ',', '.').' referencias'
            )
            : null;

        return view('catalogo', [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'productos' => $productos,
            'categoria' => $categoria,
            'tipoParte' => $tipoParte,
            'contarFiltros' => ! $buscando,
            'categorias' => Cache::remember(
                'catalogo.categorias.'.ImportadorCatalogo::version().'.'.$id,
                3600,
                fn () => Categoria::query()
                    ->withCount(['productos' => $this->filtroVehiculo()])
                    ->orderBy('nombre')
                    ->get()
            ),
            'tiposParte' => $categoria
                ? Cache::remember(
                    'catalogo.tipos.'.ImportadorCatalogo::version().'.'.$categoria->id.'.'.$id,
                    3600,
                    fn () => TipoParte::where('categoria_id', $categoria->id)
                        ->withCount(['productos' => $this->filtroVehiculo()])
                        ->orderBy('nombre')
                        ->get()
                )
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
