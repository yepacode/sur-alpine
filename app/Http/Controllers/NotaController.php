<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * «Actualízate con Nosotros» — el listado y la ficha de cada nota.
 *
 * En el sitio actual estas entradas viven en URLs de WordPress con la fecha y
 * la hora incrustadas («/que-es-el-kit-de-distribucion/2023/05/25/15/12/00/…»).
 * Aquí son `/noticias` y `/noticias/{slug}`: se pueden dictar por teléfono, que
 * es como este negocio pasa los enlaces.
 */
class NotaController extends Controller
{
    private const POR_PAGINA = 12;

    public function index(): View
    {
        $notas = Nota::query()->visibles()->recientes()->paginate(self::POR_PAGINA);

        // El mismo guardián que el catálogo. Sin él, `/noticias?page=99`
        // respondía 200 con una lista vacía: un 404 disfrazado que Google
        // cuenta como página de baja calidad, y un espacio de rastreo que
        // crece solo. Hoy hay pocas notas y sólo hay una página, así que esto
        // está latente: se activa con la nota número trece.
        abort_if($notas->currentPage() > max(1, $notas->lastPage()), 404);

        return view('paginas.noticias', ['notas' => $notas]);
    }

    public function ver(Nota $nota): View
    {
        // El modelo se resuelve por slug sin filtrar por estado: una nota
        // despublicada tiene que dar 404, no mostrarse a quien conserve el
        // enlace. Igual que las piezas despublicadas del catálogo.
        if (! $nota->publicada || ($nota->publicada_en && $nota->publicada_en->isFuture())) {
            throw new NotFoundHttpException;
        }

        return view('paginas.nota', [
            'nota' => $nota,
            // Tres para el pie de la nota, nunca ella misma.
            'otras' => Nota::query()->visibles()->whereKeyNot($nota->id)->recientes()->limit(3)->get(),
        ]);
    }
}
