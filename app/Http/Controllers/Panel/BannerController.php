<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ImagenesWeb;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Las campañas de la portada, administrables.
 *
 * Lo pidió el cliente: los proveedores le mandan piezas nuevas cada temporada
 * y hasta ahora ponerlas era copiar archivos al servidor.
 *
 * Lo que sube se convierte a WebP en tres anchos, siempre. El diseñador manda
 * JPG de 3000 px y sin esto la portada volvería a pesar lo que pesa la de
 * ellos: 59 MB, de los cuales 40 son las campañas.
 */
class BannerController extends Controller
{
    public function __construct(private readonly ImagenesWeb $imagenes) {}

    public function index(): View
    {
        return view('panel.banners.index', [
            'banners' => Banner::orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'imagen' => ['required', 'image', 'mimes:webp,jpg,jpeg,png', 'max:8192'],
            'alt' => ['required', 'string', 'max:150'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'imagen.required' => 'Elige la imagen de la campaña.',
            'imagen.image' => 'Sube una imagen (WebP, JPG o PNG).',
            'imagen.max' => 'La imagen no puede pesar más de 8 MB.',
            'alt.required' => 'Escribe de qué es la campaña: es lo que lee quien no ve la imagen.',
        ]);

        // El nombre sale del archivo, con la fecha detrás: dos campañas de la
        // misma marca en meses distintos no se pisan.
        $base = Str::slug(pathinfo(
            $request->file('imagen')->getClientOriginalName(),
            PATHINFO_FILENAME
        )).'-'.now()->format('Ymd-His');

        try {
            $this->imagenes->guardarBanner($request->file('imagen'), $base);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['imagen' => $e->getMessage()]);
        }

        // De primera, no de última.
        //
        // `10` fijo metía cada campaña nueva detrás de las que ya estaban, y
        // el aviso «Subimos la campaña» no decía dónde había quedado: quien
        // acaba de subir una promoción quiere verla arriba.
        //
        // Se hace sitio corriendo a las demás, en vez de restarle uno al
        // mínimo. Restar reventaba con un 500 —la columna es
        // `unsignedSmallInteger` y con la primera campaña en 0 el cálculo daba
        // -1—, y como el formulario de alta no manda `orden`, fallaba SIEMPRE,
        // dejando además en disco los tres .webp que ya se habían escrito.
        // Recortar a 0 tampoco vale: empata con la primera y, a igualdad, gana
        // la más antigua.
        if (! isset($datos['orden'])) {
            Banner::query()->increment('orden');
        }

        Banner::create([
            'archivo' => $base,
            'alt' => $datos['alt'],
            'orden' => $datos['orden'] ?? 0,
            'activo' => true,
        ]);

        return redirect()->route('panel.banners')->with('mensaje', 'Subimos la campaña.');
    }

    /** El texto, el orden y si se muestra o no. */
    public function actualizar(Request $request, Banner $banner): RedirectResponse
    {
        $datos = $request->validate([
            'alt' => ['required', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $banner->update([
            'alt' => $datos['alt'],
            'orden' => $datos['orden'],
            // Apagar una campaña sin borrarla: la de diciembre vuelve el año
            // que viene y su archivo ya está subido.
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('panel.banners')->with('mensaje', 'Actualizamos la campaña.');
    }

    public function borrar(Banner $banner): RedirectResponse
    {
        // Primero la fila y después los archivos: si el borrado del disco
        // falla, no queda una fila apuntando a una imagen que ya no está.
        $archivo = $banner->archivo;
        $banner->delete();

        $this->imagenes->borrarBanner($archivo);

        return redirect()->route('panel.banners')->with('mensaje', 'Borramos la campaña.');
    }
}
