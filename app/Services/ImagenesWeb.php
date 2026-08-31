<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Convierte lo que suba el cliente a WebP en varios anchos.
 *
 * Esto no es un lujo: en su sitio actual las diez campañas bajan siempre a
 * tamaño completo y se llevan 40 de los 59 MB de la portada. El diseñador que
 * manda las piezas las manda en JPG de 3000 px, y lo que se sube por el panel
 * no puede depender de que alguien se acuerde de comprimirlas antes.
 *
 * Usa GD, que ya viene con PHP. No hace falta imagick ni una librería más.
 */
class ImagenesWeb
{
    /** Los tres anchos que pide el `srcset` del carrusel. */
    /**
     * Los anchos del banner.
     *
     * El más chico era 900, y el hueco en un teléfono de 390 px mide 358 px
     * CSS: se bajaban 44 KB para pintar 358 px, y encima es el elemento LCP de
     * la portada en móvil —donde cada KB vale el triple—. El de 750 cubre
     * exactamente una pantalla así a doble densidad, y el de 400 la sencilla.
     */
    public const ANCHOS_BANNER = [400, 750, 900, 1280, 1600];

    private const CALIDAD = 82;

    /**
     * Guarda un banner en sus tres anchos y devuelve el nombre base.
     *
     * @throws \RuntimeException si el archivo no se puede leer como imagen
     */
    public function guardarBanner(UploadedFile $archivo, string $base): string
    {
        $original = $this->abrir($archivo->getRealPath());

        if ($original === false) {
            throw new \RuntimeException('No pudimos leer esa imagen.');
        }

        $carpeta = public_path('img/banners');

        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        foreach (self::ANCHOS_BANNER as $ancho) {
            $version = $this->redimensionar($original, $ancho);
            imagewebp($version, $carpeta.'/'.$base.'-'.$ancho.'.webp', self::CALIDAD);
            imagedestroy($version);
        }

        imagedestroy($original);

        return $base;
    }

    /**
     * Una imagen editable del sitio, en los anchos que pida esa pieza.
     *
     * Devuelve el nombre base sin `-{ancho}.webp`, que es lo que guarda el
     * panel y lo que la vista usa para armar su `srcset`.
     *
     * @param  list<int>  $anchos
     *
     * @throws \RuntimeException si el archivo no se puede leer como imagen
     */
    public function guardarEditable(UploadedFile $archivo, string $clave, array $anchos): string
    {
        $original = $this->abrir($archivo->getRealPath());

        if ($original === false) {
            throw new \RuntimeException('No pudimos leer esa imagen.');
        }

        $carpeta = public_path('img/editables');

        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        // La fecha en el nombre evita dos cosas: que el navegador siga
        // mostrando la foto vieja desde su caché, y que subir una nueva pise
        // la anterior mientras alguien la está viendo.
        $base = str_replace('.', '-', $clave).'-'.now()->format('Ymd-His');

        foreach ($anchos as $ancho) {
            $version = $this->redimensionar($original, $ancho);
            imagewebp($version, $carpeta.'/'.$base.'-'.$ancho.'.webp', self::CALIDAD);
            imagedestroy($version);
        }

        imagedestroy($original);

        return '/img/editables/'.$base;
    }

    /**
     * Una foto que sube el panel al disco público (notas, categorías).
     *
     * Devuelve la ruta `/storage/…` de la versión grande, que es la que se
     * guarda en base.
     *
     * Reencodar aquí no es sólo por peso; cierra dos cosas:
     *
     * · La extensión dejaba de ser del contenido y pasaba a ser la del NOMBRE
     *   que mandaba quien subía. `mimes:` valida por contenido, así que un PNG
     *   legítimo llamado `algo.html` pasaba la validación y quedaba guardado
     *   como `.html` dentro de `public/storage`, servido como HTML desde el
     *   propio dominio. Ahora el nombre y el formato los pone el servidor.
     *
     * · Los accesores `imagen_srcset` de Nota y Categoría exigen que el
     *   archivo termine en `-1024.webp` / `-640.webp`. Subir un JPG dejaba el
     *   `srcset` en null sin avisar, y aun subiendo WebP la versión chica no
     *   la generaba nadie: el `srcset` apuntaba a un archivo inexistente.
     *
     * @param  list<int>  $anchos  del más chico al más grande
     *
     * @throws \RuntimeException si el archivo no se puede leer como imagen
     */
    public function guardarEnDisco(UploadedFile $archivo, string $carpeta, string $base, array $anchos): string
    {
        $original = $this->abrir($archivo->getRealPath());

        if ($original === false) {
            throw new \RuntimeException('No pudimos leer esa imagen.');
        }

        $destino = storage_path('app/public/'.$carpeta);

        if (! is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        foreach ($anchos as $ancho) {
            $version = $this->redimensionar($original, $ancho);
            imagewebp($version, $destino.'/'.$base.'-'.$ancho.'.webp', self::CALIDAD);
            imagedestroy($version);
        }

        imagedestroy($original);

        return '/storage/'.$carpeta.'/'.$base.'-'.end($anchos).'.webp';
    }

    /**
     * Borra todas las versiones de una imagen editable.
     *
     * Recibe el nombre base (`/img/editables/algo-20260829-120000`) y barre
     * cualquier ancho que exista, sin tener que saber cuáles se generaron.
     */
    public function borrarEditable(string $base): void
    {
        if (! str_starts_with($base, '/img/editables/')) {
            return;
        }

        foreach (glob(public_path(ltrim($base, '/').'-*.webp')) ?: [] as $ruta) {
            @unlink($ruta);
        }
    }

    /** Borra las tres versiones. */
    public function borrarBanner(string $base): void
    {
        foreach (self::ANCHOS_BANNER as $ancho) {
            $ruta = public_path('img/banners/'.$base.'-'.$ancho.'.webp');

            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
    }

    /**
     * Abre la imagen mirando el CONTENIDO, nunca la extensión del nombre.
     *
     * Con la extensión pasaban dos cosas malas a la vez: una foto legítima
     * llamada `algo.html` se rechazaba, y —peor— ese mismo nombre era el que
     * antes decidía cómo se guardaba el archivo en el servidor. Aquí manda
     * `getimagesize()`, que lee los primeros bytes.
     */
    /** Tope de píxeles antes de decodificar. 40 MP es una foto de sobra. */
    private const MAXIMO_PIXELES = 40_000_000;

    private function abrir(string $ruta): \GdImage|false
    {
        $info = @getimagesize($ruta);

        // El tope se comprueba ANTES de abrir, con los datos de la cabecera.
        //
        // `max:4096` limita el archivo a 4 MB, pero un JPEG muy comprimido de
        // 4 MB puede traer 100 megapíxeles dentro, y GD necesita unos cuatro
        // bytes por píxel para tenerlo en memoria: 400 MB para una foto que
        // vamos a dejar en 1.600 px de ancho. En un alojamiento compartido eso
        // no da un error, tumba el proceso. Es una bomba de descompresión, y
        // sale gratis pararla mirando la cabecera.
        if (($info[0] ?? 0) * ($info[1] ?? 0) > self::MAXIMO_PIXELES) {
            throw new \RuntimeException(
                'Esa imagen es enorme ('.number_format($info[0]).'×'.number_format($info[1])
                .' px). Redúcela antes de subirla: con 2.000 px de ancho sobra.'
            );
        }

        return match ($info[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
            default => false,
        };
    }

    private function redimensionar(\GdImage $original, int $ancho): \GdImage
    {
        $anchoOriginal = imagesx($original);
        $altoOriginal = imagesy($original);

        // Nunca se agranda: escalar hacia arriba sólo suma peso y borrosidad.
        $ancho = min($ancho, $anchoOriginal);

        // Y nunca menos de 1 px de alto: una imagen muy apaisada —20.000 × 1—
        // daba `round(0,08) = 0`, e `imagecreatetruecolor($ancho, 0)` no
        // devuelve un lienzo, devuelve un error.
        $alto = max(1, (int) round($altoOriginal * ($ancho / $anchoOriginal)));

        $nueva = imagecreatetruecolor($ancho, $alto);
        imagealphablending($nueva, false);
        imagesavealpha($nueva, true);
        imagecopyresampled($nueva, $original, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);

        return $nueva;
    }
}
