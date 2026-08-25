<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\ImportadorCatalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * F1 · Categorías editables desde el panel.
 *
 * Antes la portada mostraba «sin foto» en las categorías que no tenían
 * imagen en base, y no había forma de subirla sin tocar el seeder. Aquí el
 * asesor las edita cosa por cosa: nombre, orden y foto. El slug no se
 * cambia —está en URLs indexadas y en el sitemap—, así se protege.
 */
class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('panel.categorias.index', [
            'categorias' => Categoria::query()
                ->orderBy('orden')->orderBy('nombre')
                ->withCount('productos')
                ->get(),
        ]);
    }

    public function editar(Categoria $categoria): View
    {
        return view('panel.categorias.editar', [
            'categoria' => $categoria,
        ]);
    }

    public function guardar(Request $request, Categoria $categoria): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'imagen' => ['nullable', 'image', 'mimes:webp,jpg,jpeg,png', 'max:4096'],
        ], [
            'imagen.image' => 'Sube una imagen (WebP, JPG o PNG).',
            'imagen.max' => 'La imagen no puede pesar más de 4 MB.',
        ]);

        // La foto pesa: entra a `storage/app/public/categorias`, y el nombre
        // sale del propio archivo para que se reemplace al reeditar. Nota
        // para el diseñador: `-640.webp` en el nombre le dice al modelo que
        // genere el `srcset` automático.
        if ($request->hasFile('imagen')) {
            $extension = $request->file('imagen')->getClientOriginalExtension();
            $nombre = $categoria->slug.'-640.'.$extension;
            $ruta = $request->file('imagen')->storeAs('categorias', $nombre, 'public');
            $datos['imagen'] = '/storage/'.$ruta;
        }

        $categoria->update([
            'nombre' => $datos['nombre'],
            'orden' => $datos['orden'] ?? $categoria->orden,
            'imagen' => $datos['imagen'] ?? $categoria->imagen,
        ]);

        // La portada y el filtro lateral leen `catalogo.categorias.*` con
        // caché de una hora. Sin este bump, el cliente sube una foto y
        // sigue viendo «sin foto» hasta que expira sola.
        ImportadorCatalogo::olvidarCaches();

        return redirect()->route('panel.categorias')
            ->with('mensaje', "Actualizamos «{$categoria->nombre}».");
    }
}
