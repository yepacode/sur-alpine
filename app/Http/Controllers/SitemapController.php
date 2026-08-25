<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\TipoParte;
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
        $paginas = (int) ceil(max(1, Producto::publicados()->count()) / self::POR_PAGINA);

        $mapas = ['secciones'];

        for ($i = 1; $i <= $paginas; $i++) {
            $mapas[] = "productos-{$i}";
        }

        $xml = view('sitemap.indice', [
            'mapas' => collect($mapas)->map(fn ($nombre) => route('sitemap.mapa', $nombre))->all(),
        ])->render();

        return $this->xml($xml);
    }

    public function mapa(string $nombre): Response
    {
        $urls = Cache::remember("sitemap.{$nombre}", 3600, fn () => $this->urlesDe($nombre));

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

        return $this->productos((int) $coincidencias[1]);
    }

    /** @return array<int, array{loc: string, prioridad: string, frecuencia: string}> */
    private function secciones(): array
    {
        $urls = [
            ['loc' => route('inicio'), 'prioridad' => '1.0', 'frecuencia' => 'weekly'],
            ['loc' => route('catalogo'), 'prioridad' => '0.9', 'frecuencia' => 'weekly'],
            ['loc' => route('quienes-somos'), 'prioridad' => '0.5', 'frecuencia' => 'yearly'],
            ['loc' => route('contacto'), 'prioridad' => '0.6', 'frecuencia' => 'yearly'],
            ['loc' => route('mantenimientos'), 'prioridad' => '0.5', 'frecuencia' => 'yearly'],
        ];

        foreach (Categoria::orderBy('nombre')->get() as $categoria) {
            $urls[] = ['loc' => route('categoria', $categoria), 'prioridad' => '0.8', 'frecuencia' => 'weekly'];
        }

        // Los tipos de parte son la entrada natural desde una búsqueda como
        // "pastillas de freno aveo": son 290 páginas y valen su sitio aquí.
        foreach (TipoParte::with('categoria')->orderBy('nombre')->get() as $tipo) {
            $urls[] = [
                'loc' => route('tipo-parte', [$tipo->categoria, $tipo]),
                'prioridad' => '0.7',
                'frecuencia' => 'monthly',
            ];
        }

        return $urls;
    }

    /** @return array<int, array{loc: string, prioridad: string, frecuencia: string}> */
    private function productos(int $pagina): array
    {
        return Producto::publicados()
            ->orderBy('id')
            ->forPage($pagina, self::POR_PAGINA)
            ->pluck('slug')
            ->map(fn (string $slug) => [
                'loc' => route('producto', $slug),
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
