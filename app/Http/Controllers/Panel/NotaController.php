<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Nota;
use App\Services\ImagenesWeb;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Las notas de «Actualízate con Nosotros», editables desde el panel.
 *
 * Hoy esas cuatro entradas viven en el WordPress viejo y sólo se tocan desde
 * allá. Aquí las escribe el asesor: título, foto, arranque y cuerpo en texto
 * plano —«## » para un subtítulo, «- » para una viñeta— y nada más. Sin editor
 * enriquecido y sin HTML: lo que se guarda es texto, y la vista lo pinta
 * escapado. Es lo que impide que una nota inyecte un script en el sitio.
 */
class NotaController extends Controller
{
    public function __construct(private readonly ImagenesWeb $imagenes) {}

    public function index(): View
    {
        return view('panel.notas.index', [
            'notas' => Nota::query()->recientes()->paginate(20),
        ]);
    }

    public function crear(): View
    {
        return view('panel.notas.editar', ['nota' => new Nota(['publicada' => true])]);
    }

    public function editar(Nota $nota): View
    {
        return view('panel.notas.editar', ['nota' => $nota]);
    }

    public function guardar(Request $request, ?Nota $nota = null): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'resumen' => ['required', 'string', 'max:400'],
            'cuerpo' => ['required', 'string', 'max:20000'],
            'categoria' => ['required', 'string', 'max:60'],
            'publicada' => ['nullable', 'boolean'],
            'publicada_en' => ['nullable', 'date'],
            'imagen' => ['nullable', 'image', 'mimes:webp,jpg,jpeg,png', 'max:4096'],
        ], [
            'resumen.max' => 'El arranque no puede pasar de 400 caracteres: en la tarjeta sólo se ven tres líneas.',
            'imagen.image' => 'Sube una imagen (WebP, JPG o PNG).',
            'imagen.max' => 'La imagen no puede pesar más de 4 MB.',
        ]);

        $nota ??= new Nota;

        // El slug se calcula la primera vez y después no se toca: está en
        // enlaces que el equipo ya pasó por WhatsApp y en el sitemap. Cambiar
        // el título de una nota publicada no debe romper su URL.
        if (! $nota->exists) {
            $nota->slug = Nota::slugUnico($datos['titulo']);
        }

        if ($request->hasFile('imagen')) {
            // El nombre y el formato los pone el servidor, nunca el archivo
            // que llega: la extensión del cliente permitía guardar un PNG
            // válido con nombre `.html` dentro de `public/storage`. Y el
            // sufijo `-1024` es lo que le dice al modelo que puede armar el
            // `srcset`; sin la versión de 520 apuntaba a un archivo que no
            // existía.
            try {
                $datos['imagen'] = $this->imagenes->guardarEnDisco(
                    $request->file('imagen'), 'notas', $nota->slug, [520, 1024]
                );
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['imagen' => $e->getMessage()]);
            }
        }

        $nota->fill([
            'titulo' => $datos['titulo'],
            'resumen' => $datos['resumen'],
            'cuerpo' => $datos['cuerpo'],
            'categoria' => $datos['categoria'],
            'publicada' => $request->boolean('publicada'),
            'publicada_en' => $datos['publicada_en'] ?? $nota->publicada_en ?? now(),
            'imagen' => $datos['imagen'] ?? $nota->imagen,
        ])->save();

        return redirect()->route('panel.notas')->with('mensaje', 'Nota guardada.');
    }

    public function borrar(Nota $nota): RedirectResponse
    {
        $nota->delete();

        return redirect()->route('panel.notas')->with('mensaje', 'Nota eliminada.');
    }
}
