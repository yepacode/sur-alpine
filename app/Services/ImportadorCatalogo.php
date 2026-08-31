<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa el catálogo desde el "Formato Importación Suralpine".
 *
 * El archivo es una matriz de compatibilidad, no una lista de productos:
 *
 *      fila 1  →  categorías, en celdas combinadas sobre sus columnas
 *      fila 2  →  nombre de cada tipo de parte
 *      col A-E →  Marca | Modelo | Motor | Año Comienzo | Año Final
 *      col F+  →  1 si ese vehículo lleva esa parte, 0 si no
 *
 * Cada 1 se convierte en una fila de `productos`. Es idempotente: volver a
 * correrlo actualiza lo que cambió y no duplica nada.
 */
class ImportadorCatalogo
{
    /** Columnas fijas antes de que empiecen los tipos de parte. */
    private const COLUMNAS_VEHICULO = 5;

    private const LOTE = 500;

    private ResultadoImportacion $resultado;

    /** Slugs de tipo de parte que aparecen en más de una categoría. */
    private array $partesAmbiguas = [];

    /** "marca|modelo|cilindraje" que existe con más de un rango de años. */
    private array $vehiculosConGeneraciones = [];

    public function importar(string $ruta, ?string $hoja = null, bool $simular = false): ResultadoImportacion
    {
        $this->resultado = new ResultadoImportacion(simulacion: $simular);
        $this->partesAmbiguas = [];
        $this->vehiculosConGeneraciones = [];

        $filas = $this->leerHoja($ruta, $hoja);

        if (count($filas) < 3) {
            $this->resultado->errores[] = 'La hoja no tiene la estructura esperada: faltan las dos filas de encabezado.';

            return $this->resultado;
        }

        // Dos pasadas de reconocimiento antes de escribir nada. Sin esto, los
        // nombres de parte repetidos en dos categorías y los modelos con dos
        // generaciones producen slugs iguales, y el upsert pisa filas en
        // silencio: el catálogo queda incompleto sin que nadie se entere.
        $this->detectarPartesAmbiguas($filas[0], $filas[1]);
        $this->detectarGeneraciones(array_slice($filas, 2));

        $this->resultado->partesAmbiguas = count($this->partesAmbiguas);
        $this->resultado->modelosConGeneraciones = count($this->vehiculosConGeneraciones);

        $tiposParte = $this->mapearTiposParte($filas[0], $filas[1], $simular);
        $productosAntes = $simular ? 0 : Producto::count();

        // Marca de agua para saber después qué filas NO tocó esta importación:
        // el `upsert` refresca `updated_at` de todo lo que viene en el Excel,
        // así que lo que quede con fecha anterior es lo que ya no aparece.
        //
        // `updated_at` guarda segundos enteros: dos importaciones dentro del
        // mismo segundo no se distinguirían. Con 29.272 piezas eso no pasa —una
        // corrida son minutos— y el peor caso es un aviso de menos, nunca un
        // borrado de más.
        $comienzo = now();
        $pendientes = [];

        foreach (array_slice($filas, 2) as $numero => $fila) {
            if (blank($fila[0] ?? null)) {
                continue;
            }

            $vehiculo = $this->resolverVehiculo($fila, $numero + 3, $simular);

            if (! $vehiculo) {
                continue;
            }

            $this->resultado->vehiculosLeidos++;
            $partesDelVehiculo = 0;

            foreach ($tiposParte as $columna => $tipo) {
                if (! $this->celdaMarcada($fila[$columna] ?? null)) {
                    continue;
                }

                $this->resultado->celdasMarcadas++;
                $partesDelVehiculo++;

                if (! $simular) {
                    $pendientes[] = $this->armarProducto($vehiculo, $tipo);
                }
            }

            $this->resultado->vehiculos[] = [
                'marca' => $this->limpiar($fila[0]),
                'modelo' => $this->limpiar($fila[1] ?? null),
                'cilindraje' => $this->limpiar($fila[2] ?? null),
                'anios' => $this->limpiar($fila[3] ?? null).'-'.$this->limpiar($fila[4] ?? null),
                'partes' => $partesDelVehiculo,
            ];

            if (! $simular && count($pendientes) >= self::LOTE) {
                $this->guardarLote($pendientes);
                $pendientes = [];
            }
        }

        if (! $simular) {
            if ($pendientes) {
                $this->guardarLote($pendientes);
            }

            $this->contarSobrantes($comienzo);
            $this->resultado->productosEnBase = Producto::count();
            $this->resultado->productosCreados = $this->resultado->productosEnBase - $productosAntes;
            $this->resultado->productosActualizados = $this->resultado->celdasMarcadas - $this->resultado->productosCreados;

            $this->olvidarCaches();
        }

        return $this->resultado;
    }

    public const CLAVE_VERSION = 'catalogo.version';

    /**
     * Lo que quedó en el catálogo y ya no viene en el Excel.
     *
     * No se borra nada: retirar piezas de golpe es una decisión del negocio, y
     * una celda desmarcada por error dejaría al cliente sin repuestos que sí
     * vende. Aquí sólo se cuentan y se muestran para que alguien decida.
     */
    private function contarSobrantes(\Illuminate\Support\Carbon $comienzo): void
    {
        $sobrantes = Producto::where('updated_at', '<', $comienzo);

        $this->resultado->sobrantes = (clone $sobrantes)->count();
        $this->resultado->muestraSobrantes = (clone $sobrantes)
            ->orderBy('nombre')
            ->limit(15)
            ->pluck('nombre')
            ->all();
    }

    public static function olvidarCaches(): void
    {
        ArbolVehiculos::olvidar();
        Cache::forget('catalogo.total');
        Cache::forget('menu.categorias');

        // Los contadores de la portada se guardan por vehículo, así que son
        // hasta 225 llaves distintas y no hay forma de borrarlas una por una.
        // Subir la versión las deja huérfanas y expiran solas.
        // `Cache::increment` devuelve 1 sobre una llave ausente —que es
        // «truthy»—, así que la rama `?:` que había aquí como red de
        // seguridad no se ejecutaba nunca y la versión se quedaba en 1.
        Cache::forever(self::CLAVE_VERSION, self::version() + 1);
    }

    /** El número que cuelga de las llaves de caché que dependen del catálogo. */
    public static function version(): int
    {
        return (int) Cache::rememberForever(self::CLAVE_VERSION, fn () => 1);
    }

    /** @return array<int, array<int, mixed>> */
    private function leerHoja(string $ruta, ?string $hoja): array
    {
        if (! is_file($ruta)) {
            $this->resultado->errores[] = "No encuentro el archivo: {$ruta}";

            return [];
        }

        $lector = IOFactory::createReaderForFile($ruta);
        $lector->setReadDataOnly(true);

        $libro = $lector->load($ruta);
        $pagina = $hoja ? $libro->getSheetByName($hoja) : $libro->getSheet(0);

        if (! $pagina) {
            $this->resultado->errores[] = "La hoja «{$hoja}» no existe. Disponibles: ".implode(', ', $libro->getSheetNames());

            return [];
        }

        return $pagina->toArray(null, true, false, false);
    }

    /**
     * Un mismo nombre de parte puede vivir en dos categorías: "Axial Direccion"
     * está en Dirección y también en Suspensión. Son productos distintos y
     * necesitan URLs distintas.
     */
    private function detectarPartesAmbiguas(array $filaCategorias, array $filaPartes): void
    {
        $categoriaActual = null;
        $vistos = [];

        for ($i = self::COLUMNAS_VEHICULO; $i < count($filaPartes); $i++) {
            if (filled($filaCategorias[$i] ?? null)) {
                $categoriaActual = $this->limpiar($filaCategorias[$i]);
            }

            $parte = $this->limpiar($filaPartes[$i] ?? null);

            if (blank($parte) || blank($categoriaActual)) {
                continue;
            }

            $vistos[Str::slug($parte)][Str::slug($categoriaActual)] = true;
        }

        foreach ($vistos as $slug => $categorias) {
            if (count($categorias) > 1) {
                $this->partesAmbiguas[$slug] = true;
            }
        }
    }

    /**
     * El Chevrolet Optra 1800 existe como 2004-2006 y como 2007-2013. Son dos
     * carros distintos que comparten marca, modelo y cilindraje.
     */
    private function detectarGeneraciones(array $filas): void
    {
        $vistos = [];

        foreach ($filas as $fila) {
            if (blank($fila[0] ?? null)) {
                continue;
            }

            $clave = $this->claveModelo($fila);
            $vistos[$clave] = ($vistos[$clave] ?? 0) + 1;
        }

        foreach ($vistos as $clave => $veces) {
            if ($veces > 1) {
                $this->vehiculosConGeneraciones[$clave] = true;
            }
        }
    }

    private function claveModelo(array $fila): string
    {
        return implode('|', [
            $this->limpiar($fila[0] ?? null),
            $this->limpiar($fila[1] ?? null),
            $this->limpiar($fila[2] ?? null),
        ]);
    }

    /** @return array<int, TipoParte> */
    private function mapearTiposParte(array $filaCategorias, array $filaPartes, bool $simular): array
    {
        $tipos = [];
        $categoriaActual = null;
        $orden = 0;

        for ($i = self::COLUMNAS_VEHICULO; $i < count($filaPartes); $i++) {
            if (filled($filaCategorias[$i] ?? null)) {
                $categoriaActual = $this->limpiar($filaCategorias[$i]);
            }

            $nombreParte = $this->limpiar($filaPartes[$i] ?? null);

            if (blank($nombreParte) || blank($categoriaActual)) {
                continue;
            }

            $tipos[$i] = $this->resolverTipoParte($categoriaActual, $nombreParte, ++$orden, $simular);
        }

        return array_filter($tipos);
    }

    private function resolverTipoParte(string $categoria, string $parte, int $orden, bool $simular): ?TipoParte
    {
        if ($simular) {
            return new TipoParte(['nombre' => $parte, 'slug' => Str::slug($parte)]);
        }

        $modeloCategoria = Categoria::firstOrCreate(
            ['slug' => Str::slug($categoria)],
            ['nombre' => $categoria]
        );

        if ($modeloCategoria->wasRecentlyCreated) {
            $this->resultado->categoriasNuevas++;
        }

        $tipo = TipoParte::firstOrCreate(
            ['categoria_id' => $modeloCategoria->id, 'slug' => Str::slug($parte)],
            ['nombre' => $parte, 'orden' => $orden]
        );

        if ($tipo->wasRecentlyCreated) {
            $this->resultado->tiposParteNuevos++;
        }

        $tipo->setRelation('categoria', $modeloCategoria);

        return $tipo;
    }

    private function resolverVehiculo(array $fila, int $linea, bool $simular): ?Vehiculo
    {
        $nombreMarca = $this->limpiar($fila[0] ?? null);
        $nombreModelo = $this->limpiar($fila[1] ?? null);
        $cilindraje = $this->limpiar($fila[2] ?? null);
        $anioInicio = $this->aEntero($fila[3] ?? null);
        $anioFin = $this->aEntero($fila[4] ?? null);

        if (blank($nombreMarca) || blank($nombreModelo) || blank($cilindraje)) {
            $this->resultado->errores[] = "Fila {$linea}: falta marca, modelo o cilindraje.";

            return null;
        }

        if (! $anioInicio || ! $anioFin || $anioFin < $anioInicio) {
            $this->resultado->errores[] = "Fila {$linea}: rango de años inválido ({$anioInicio}-{$anioFin}) en {$nombreMarca} {$nombreModelo}.";

            return null;
        }

        $slug = Str::slug(implode('-', [$nombreMarca, $nombreModelo, $cilindraje, $anioInicio, $anioFin]));

        if ($simular) {
            return new Vehiculo([
                'cilindraje' => $cilindraje,
                'anio_inicio' => $anioInicio,
                'anio_fin' => $anioFin,
                'slug' => $slug,
            ]);
        }

        $marca = Marca::firstOrCreate(['slug' => Str::slug($nombreMarca)], ['nombre' => $nombreMarca]);

        if ($marca->wasRecentlyCreated) {
            $this->resultado->marcasNuevas++;
        }

        $modelo = Modelo::firstOrCreate(
            ['marca_id' => $marca->id, 'slug' => Str::slug($nombreModelo)],
            ['nombre' => $nombreModelo]
        );

        if ($modelo->wasRecentlyCreated) {
            $this->resultado->modelosNuevos++;
        }

        // El rango de años hace parte de la identidad: el Optra 1800 existe
        // como 2004-2006 y como 2007-2013, y son dos vehículos distintos.
        $vehiculo = Vehiculo::updateOrCreate(
            ['modelo_id' => $modelo->id, 'cilindraje' => $cilindraje, 'anio_inicio' => $anioInicio],
            ['anio_fin' => $anioFin, 'slug' => $slug]
        );

        if ($vehiculo->wasRecentlyCreated) {
            $this->resultado->vehiculosNuevos++;
        }

        $vehiculo->setRelation('modelo', $modelo->setRelation('marca', $marca));

        return $vehiculo;
    }

    /**
     * Nombre y slug replican el formato que el cliente ya usa:
     * "Pastillas Freno Delanteras AVEO 1600 CHEVROLET".
     * Mantenerlo facilita el mapa de redirecciones desde el sitio actual.
     */
    private function armarProducto(Vehiculo $vehiculo, TipoParte $tipo): array
    {
        $modelo = $vehiculo->modelo;
        $marca = $modelo->marca;

        $nombre = sprintf('%s %s %s %s', $tipo->nombre, $modelo->nombre, $vehiculo->cilindraje, $marca->nombre);

        // El slug arranca igual que el título y sólo se desambigua si hace falta.
        $partes = [$nombre];

        if (isset($this->partesAmbiguas[$tipo->slug])) {
            $partes[] = $tipo->categoria->slug;
        }

        $claveModelo = implode('|', [$marca->nombre, $modelo->nombre, $vehiculo->cilindraje]);

        if (isset($this->vehiculosConGeneraciones[$claveModelo])) {
            $partes[] = $vehiculo->anio_inicio;
        }

        return [
            'vehiculo_id' => $vehiculo->id,
            'tipo_parte_id' => $tipo->id,
            'nombre' => $nombre,
            'slug' => Str::slug(implode('-', $partes)),
            'publicado' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function guardarLote(array $productos): void
    {
        DB::transaction(function () use ($productos) {
            // No se tocan `referencia`, `imagen` ni `descripcion`: son datos que
            // el equipo carga desde el panel y una reimportación no debe borrarlos.
            Producto::upsert($productos, ['vehiculo_id', 'tipo_parte_id'], ['nombre', 'slug', 'updated_at']);
        });
    }

    private function celdaMarcada(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(trim((string) $valor), ['1', '1.0', 'x', 'X', 'si', 'SI', 'sí', 'SÍ'], true);
    }

    /**
     * Excel entrega los números como float: el modelo Fiat 147 llega como 147.0
     * y el cilindraje 1000 como 1000.0. Sin esto el catálogo queda con "Fiat 147.0".
     */
    private function limpiar(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_float($valor) && floor($valor) === $valor) {
            return (string) (int) $valor;
        }

        if (is_numeric($valor) && str_ends_with((string) $valor, '.0')) {
            return substr((string) $valor, 0, -2);
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $valor));
    }

    private function aEntero(mixed $valor): ?int
    {
        $limpio = $this->limpiar($valor);

        return is_numeric($limpio) ? (int) $limpio : null;
    }
}
