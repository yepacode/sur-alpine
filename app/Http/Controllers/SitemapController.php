<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Nota;
use App\Models\Producto;
use App\Models\SeoPagina;
use App\Models\TipoParte;
use App\Services\ImportadorCatalogo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * El mapa del sitio.
 *
 * Son 29.272 piezas y la portada sólo enlaza diez categorías: sin esto Google
 * tiene que descubrir el catálogo entero saltando de enlace en enlace, y el
 * fondo del árbol no lo alcanza nunca.
 *
 * Va partido en dos porque el límite de un sitemap son 50.000 URL y 50 MB: uno
 * con las páginas fijas y las categorías, y otro con las fichas de repuesto.
 */
class SitemapController extends Controller
{
    private const POR_PAGINA = 20000;

    /** El índice: la única URL que hay que declarar en robots.txt. */
    public function indice(): Response
    {
        // El conteo cacheado por versión del catálogo: sin esto, cada uno de
        // los 30 hits concurrentes del estrés re-ejecutaba `count()` sobre
        // 29 K productos (50-116 ms por request).
        $paginas = $this->paginasDeProductos();

        $mapas = ['secciones'];

        for ($i = 1; $i <= $paginas; $i++) {
            $mapas[] = "productos-{$i}";
        }

        $xml = view('sitemap.indice', [
            'mapas' => collect($mapas)->map(fn ($nombre) => route('sitemap.mapa', $nombre))->all(),
        ])->render();

        return $this->xml($xml);
    }

    /**
     * Cuantos trozos de productos hay.
     *
     * Cacheado por version del catalogo: sin esto cada peticion del indice
     * re-ejecutaba un `count()` sobre 29 K filas, y ahora ademas lo consulta
     * cada peticion de un trozo para rechazar los que no existen.
     */
    private function paginasDeProductos(): int
    {
        return Cache::remember(
            'sitemap.paginas.'.ImportadorCatalogo::version(),
            3600,
            // `canonicos()` igual que en el trozo: si no, el ultimo podia
            // salir anunciado y venir vacio.
            fn () => (int) ceil(max(1, Producto::publicados()->canonicos()->count()) / self::POR_PAGINA),
        );
    }

    public function mapa(string $nombre): Response
    {
        // Caché versionada por contador de catálogo: si el equipo importa un
        // Excel nuevo, `ImportadorCatalogo::olvidarCaches()` sube la versión
        // y todo el sitemap se regenera sin borrar llaves a mano.
        $urls = Cache::remember(
            "sitemap.{$nombre}.".ImportadorCatalogo::version(),
            3600,
            fn () => $this->urlesDe($nombre),
        );

        abort_if($urls === null, 404);

        return $this->xml(view('sitemap.mapa', ['urls' => $urls])->render());
    }

    /** @return array<int, array{loc: string, prioridad: string, frecuencia: string}>|null */
    private function urlesDe(string $nombre): ?array
    {
        if ($nombre === 'secciones') {
            return $this->secciones();
        }

        if (! preg_match('/^productos-(\d+)$/', $nombre, $coincidencias)) {
            return null;
        }

        // Acotado contra el numero real de trozos.
        //
        // Sin esto, `/sitemap-productos-0.xml` devolvia un duplicado exacto
        // del 1 —`forPage(0)` y `forPage(1)` dan lo mismo— y `-3`, `-99` o
        // cualquier numero devolvian un `urlset` vacio con 200. Son sitemaps
        // inventables desde fuera: URLs indexables que no anuncia nadie y que
        // le dicen a Google que el sitio publica mapas vacios.
        $pagina = (int) $coincidencias[1];

        if ($pagina < 1 || $pagina > $this->paginasDeProductos()) {
            return null;
        }

        return $this->productos($pagina);
    }

    /** @return array<int, array{loc: string, prioridad: string, frecuencia: string}> */
    private function secciones(): array
    {
        // Los defaults del sitemap por sección. El panel «Configuración de
        // página» puede sobreescribir cualquiera de estos por su fila en
        // `seo_paginas`, o excluir la página del sitemap con `sitemap_incluir=0`.
        $defaults = [
            'inicio'         => ['prioridad' => '1.0', 'frecuencia' => 'weekly'],
            'catalogo'       => ['prioridad' => '0.9', 'frecuencia' => 'weekly'],
            'quienes-somos'  => ['prioridad' => '0.5', 'frecuencia' => 'yearly'],
            'contacto'       => ['prioridad' => '0.6', 'frecuencia' => 'yearly'],
            'mantenimientos' => ['prioridad' => '0.5', 'frecuencia' => 'yearly'],
            'noticias'       => ['prioridad' => '0.6', 'frecuencia' => 'weekly'],
            // Las legales pesan poco pero se indexan: son las que Google mira
            // para creer que detrás hay una empresa y no una tienda fantasma
            // —que es justo el problema que este cliente tiene con las copias.
            'terminos'       => ['prioridad' => '0.2', 'frecuencia' => 'yearly'],
            'politica-datos' => ['prioridad' => '0.2', 'frecuencia' => 'yearly'],
        ];

        // Un solo SELECT contra `seo_paginas`, keyBy ruta, para que la lista
        // decida en O(1) sin ir a base fila por fila.
        $seo = SeoPagina::query()->get()->keyBy('ruta');

        $urls = [];
        foreach ($defaults as $ruta => $por) {
            $override = $seo->get($ruta);
            // La portada no se puede sacar del sitemap. La casilla del panel
            // existe para excluir una página secundaria, pero apagar la raíz
            // del sitio es lo único que nadie quiere hacer nunca y nadie
            // notaría hasta perder el tráfico. (Pasó aquí: la fila de `inicio`
            // apareció con la casilla apagada y la portada llevaba tiempo
            // fuera del sitemap.)
            if ($ruta !== 'inicio' && $override && ! $override->sitemap_incluir) {
                continue;
            }
            $urls[] = [
                'loc' => route($ruta),
                'prioridad' => $override?->sitemap_prioridad !== null
                    ? number_format((float) $override->sitemap_prioridad, 1)
                    : $por['prioridad'],
                'frecuencia' => $override?->sitemap_frecuencia ?: $por['frecuencia'],
            ];
        }

        // Con `lastmod`. De los tres datos que puede llevar una entrada,
        // `priority` y `changefreq` los ignora Google y `lastmod` si lo usa
        // para decidir cuando vuelve. Estas paginas cambian en cada
        // importacion del catalogo y ese dato ya estaba guardado: salian con
        // las dos que no sirven y sin la que si.
        // Sólo las que tienen algo, igual que los tipos de parte veinte líneas
        // más abajo: una categoría vacía es una página que responde 200 y dice
        // «0 repuestos». El mismo 404 disfrazado que el catálogo se molesta en
        // tapar para `?page=99999`, servido esta vez por nosotros.
        foreach (Categoria::whereHas('tiposParte.productos')->orderBy('nombre')->get() as $categoria) {
            $urls[] = [
                'loc' => route('categoria', $categoria),
                'modificado' => $categoria->updated_at?->toAtomString(),
                'prioridad' => '0.8',
                'frecuencia' => 'weekly',
            ];
        }

        // Los tipos de parte son la entrada natural desde una búsqueda como
        // "pastillas de freno aveo": son 290 páginas y valen su sitio aquí.
        // Las notas del blog. Son pocas, y son las paginas por las que este
        // negocio puede aparecer en busquedas que no son de repuesto («cada
        // cuanto se cambia el kit de distribucion»), asi que entran todas.
        foreach (Nota::query()->visibles()->recientes()->get() as $nota) {
            $urls[] = [
                'loc' => route('nota', $nota),
                'modificado' => $nota->updated_at?->toAtomString(),
                'prioridad' => '0.6',
                'frecuencia' => 'monthly',
            ];
        }

        // Sólo los que tienen piezas.
        //
        // Tres columnas del Excel del cliente no las marca ningún vehículo, y
        // el sitemap las ofrecía igual: páginas que responden 200, están
        // vacías y le dicen al visitante «0 referencias en catálogo». Es el
        // mismo 404 disfrazado que el catálogo se molestó en tapar para
        // `?page=99999`, servido esta vez por nosotros mismos.
        foreach (TipoParte::has('productos')->with('categoria')->orderBy('nombre')->get() as $tipo) {
            // La cara secundaria de un tipo duplicado no entra: su canonical
            // ya apunta a la otra, y ofrecerla igual es contradecirse.
            if (! $tipo->esPrincipal()) {
                continue;
            }

            $urls[] = [
                'loc' => route('tipo-parte', [$tipo->categoria, $tipo]),
                'modificado' => $tipo->updated_at?->toAtomString(),
                'prioridad' => '0.7',
                'frecuencia' => 'monthly',
            ];
        }

        return $urls;
    }

    /** @return array<int, array{loc: string, prioridad: string, frecuencia: string}> */
    private function productos(int $pagina): array
    {
        // `slug` y `updated_at`, no el modelo entero: son 29.272 filas y
        // hidratarlas costaría más que armar el XML.
        // `canonicos()`: de cada par de fichas gemelas entra sólo la que
        // manda. Publicar las dos es pedirle a Google que elija —y elige él—.
        return Producto::publicados()
            ->canonicos()
            ->orderBy('id')
            ->forPage($pagina, self::POR_PAGINA)
            ->get(['slug', 'updated_at'])
            ->map(fn (Producto $producto) => [
                'loc' => route('producto', $producto->slug),
                'modificado' => $producto->updated_at?->toAtomString(),
                'prioridad' => '0.6',
                'frecuencia' => 'monthly',
            ])
            ->all();
    }

    private function xml(string $contenido): Response
    {
        return response($contenido, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
