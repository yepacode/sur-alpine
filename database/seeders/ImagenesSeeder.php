<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

/**
 * Nombres, fotos y orden de las categorías tal como los muestra el sitio actual.
 *
 * El Excel del cliente trae los nombres sin tildes —"Direccion", "Baterias"—
 * porque es una hoja de cálculo interna. De cara al público van bien escritos,
 * y con el nombre comercial: "Caja" es "Caja de Cambios".
 *
 * El importador busca por slug, así que reimportar el catálogo no pisa esto.
 */
class ImagenesSeeder extends Seeder
{
    /** slug => [nombre público, archivo de foto, orden en la portada] */
    private const CATEGORIAS = [
        'partes-electricas' => ['Partes Eléctricas', 'partes-electricas', 1],
        'refrigeracion' => ['Refrigeración', 'refrigeracion', 2],
        'caja' => ['Caja de Cambios', 'caja', 3],
        'sensores-motor' => ['Sensores de Motor', 'sensores-motor', 4],
        'baterias' => ['Baterías', 'baterias', 5],
        'direccion' => ['Dirección', 'direccion', 6],
        'motor-externo' => ['Motor Externo', 'motor-externo', 7],
        'motor-interno' => ['Motor Interno', 'motor-interno', 8],
        'suspension' => ['Suspensión', 'suspension', 9],
        'frenos' => ['Frenos', 'frenos', 10],

        // Existen en el catálogo pero el cliente no las exhibe en la portada,
        // porque no tiene foto de ellas.
        'carroceria' => ['Carrocería', null, 90],
        'transmision' => ['Transmisión', null, 91],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $slug => [$nombre, $archivo, $orden]) {
            $categoria = Categoria::where('slug', $slug)->first();

            if (! $categoria) {
                $this->command?->warn("  No existe la categoría {$slug}");

                continue;
            }

            $imagen = null;

            if ($archivo) {
                // Se guarda el ancho grande; el chico se deduce de este nombre
                // en `Categoria::imagenSrcset`. La tarjeta se pinta a 223 px,
                // así que servir siempre 640 era ocho veces lo necesario.
                $ruta = "/img/categorias/{$archivo}-640.webp";

                if (is_file(public_path($ruta))) {
                    $imagen = $ruta;
                } else {
                    $this->command?->warn("  Falta {$ruta}, corre antes imagenes:optimizar");
                }
            }

            $categoria->update([
                'nombre' => $nombre,
                'imagen' => $imagen,
                'orden' => $orden,
            ]);
        }
    }
}
