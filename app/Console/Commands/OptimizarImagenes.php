<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Convierte los originales del cliente a WebP en varios tamaños.
 *
 * Los banners que enviaron pesan de 4 a 7 MB cada uno; así es como la portada
 * del sitio actual llega a 59 MB. Bien servidos, los mismos siete banners caben
 * en menos de lo que hoy pesa uno solo.
 */
class OptimizarImagenes extends Command
{
    protected $signature = 'imagenes:optimizar
        {--origen=marca : Carpeta dentro de storage/app}
        {--grupo= : Fuerza el grupo de destino en vez de deducirlo del nombre}
        {--filtro=* : Sólo los archivos que casen con este patrón, p. ej. "Banner*"}
        {--calidad=80 : Calidad WebP, de 1 a 100}';

    protected $description = 'Convierte las imágenes de marca a WebP en los tamaños que usa el sitio';

    /**
     * Cada grupo se sirve a un ancho distinto: no tiene sentido un icono de
     * 1600 px. Los anchos salen de lo que mide la caja en pantalla, no de
     * números redondos: la tarjeta de categoría se pinta a 223 px y el banner,
     * dentro de su contenedor de 1248, nunca necesita los 1600 salvo en
     * pantallas de mucha densidad.
     */
    private const ANCHOS = [
        'banners' => [1600, 1280, 900],
        'categorias' => [640, 480],
        'logo' => [280],
        'fondo' => [1200],
        'promo' => [900, 520],
        'proveedores' => [190],
    ];

    public function handle(): int
    {
        $origen = storage_path('app/'.$this->option('origen'));

        if (! is_dir($origen)) {
            $this->error("No encuentro la carpeta {$origen}");

            return self::FAILURE;
        }

        $calidad = (int) $this->option('calidad');
        $pesoOriginal = 0;
        $pesoFinal = 0;
        $generadas = 0;

        $filtros = (array) $this->option('filtro');

        foreach (glob($origen.'/*.{jpg,jpeg,png,JPG,PNG}', GLOB_BRACE) as $archivo) {
            // Sin filtro se procesa todo; con filtro, sólo lo que case. Sirve
            // para reprocesar los banners sin tocar el resto de la carpeta.
            if ($filtros && ! $this->casa(basename($archivo), $filtros)) {
                continue;
            }

            $grupo = $this->option('grupo') ?: $this->clasificar(basename($archivo));

            if (! isset(self::ANCHOS[$grupo])) {
                $this->warn("  Grupo desconocido «{$grupo}» para ".basename($archivo));

                continue;
            }

            $destino = public_path('img/'.$grupo);

            if (! is_dir($destino)) {
                mkdir($destino, 0755, true);
            }

            $imagen = $this->abrir($archivo);

            if (! $imagen) {
                $this->warn('  No pude leer '.basename($archivo));

                continue;
            }

            $pesoOriginal += filesize($archivo);
            $nombre = $this->nombreLimpio(basename($archivo));

            foreach (self::ANCHOS[$grupo] as $ancho) {
                $sufijo = count(self::ANCHOS[$grupo]) > 1 ? "-{$ancho}" : '';
                $salida = "{$destino}/{$nombre}{$sufijo}.webp";

                $redimensionada = $this->redimensionar($imagen, $ancho);
                imagewebp($redimensionada, $salida, $calidad);
                imagedestroy($redimensionada);

                $pesoFinal += filesize($salida);
                $generadas++;
            }

            imagedestroy($imagen);
            $this->line('  ✓ '.$nombre);
        }

        $this->newLine();
        $this->info(sprintf(
            '%d archivos generados. %s → %s (%d%% menos).',
            $generadas,
            $this->enMB($pesoOriginal),
            $this->enMB($pesoFinal),
            $pesoOriginal > 0 ? round(100 - ($pesoFinal / $pesoOriginal * 100)) : 0
        ));

        return self::SUCCESS;
    }

    /** @param array<int, string> $patrones */
    private function casa(string $archivo, array $patrones): bool
    {
        foreach ($patrones as $patron) {
            if (fnmatch($patron, $archivo, FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }

    private function clasificar(string $archivo): string
    {
        $nombre = mb_strtolower($archivo);

        return match (true) {
            Str::startsWith($nombre, 'banner') => 'banners',
            Str::contains($nombre, 'fondo') => 'fondo',
            Str::contains($nombre, 'senor') => 'promo',
            Str::contains($nombre, 'logo') => 'logo',
            default => 'categorias',
        };
    }

    /** "REFIREGERACION .jpg" → "refiregeracion" */
    private function nombreLimpio(string $archivo): string
    {
        return Str::slug(pathinfo($archivo, PATHINFO_FILENAME));
    }

    private function abrir(string $ruta): \GdImage|false
    {
        return match (mb_strtolower(pathinfo($ruta, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($ruta),
            'png' => @imagecreatefrompng($ruta),
            default => false,
        };
    }

    private function redimensionar(\GdImage $original, int $ancho): \GdImage
    {
        $anchoOriginal = imagesx($original);
        $altoOriginal = imagesy($original);

        // Nunca se agranda: escalar hacia arriba sólo suma peso y borrosidad.
        $ancho = min($ancho, $anchoOriginal);
        $alto = (int) round($altoOriginal * ($ancho / $anchoOriginal));

        $nueva = imagecreatetruecolor($ancho, $alto);
        imagealphablending($nueva, false);
        imagesavealpha($nueva, true);
        imagecopyresampled($nueva, $original, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);

        return $nueva;
    }

    private function enMB(int $bytes): string
    {
        return number_format($bytes / 1048576, 1, ',', '.').' MB';
    }
}
