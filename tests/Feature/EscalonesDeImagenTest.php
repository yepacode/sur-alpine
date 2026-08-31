<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Categoria;
use App\Services\ImagenesWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada `srcset` tiene que ofrecer un archivo que exista, y uno chico de verdad.
 *
 * Los dos defectos que cubre esto son de factura de datos, no de velocidad
 * percibida —y este cliente atiende a mecánicos con datos móviles—:
 *
 *   · el banner ofrecía 900w como candidato más pequeño para un hueco de
 *     358 px CSS. Es el elemento LCP de la portada en un teléfono.
 *   · las tarjetas de categoría ofrecían 480w para huecos de 250 px, diez
 *     veces por página.
 *
 * Y comprueba lo otro, que ya pasó una vez: que los anchos declarados y los
 * archivos en disco no se separen. Un `srcset` que apunta a un archivo que no
 * existe no falla: sirve la imagen equivocada o ninguna.
 */
class EscalonesDeImagenTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_archivos_del_banner_existen_en_todos_sus_anchos(): void
    {
        $banner = Banner::visibles()->first();

        $this->assertNotNull($banner, 'Sin campañas no hay nada que comprobar.');

        foreach (ImagenesWeb::ANCHOS_BANNER as $ancho) {
            $this->assertFileExists(
                public_path("img/banners/{$banner->archivo}-{$ancho}.webp"),
                "Falta el ancho {$ancho} del banner: el `srcset` lo ofrece y no está."
            );
        }
    }

    public function test_el_banner_ofrece_un_candidato_para_un_telefono(): void
    {
        $this->assertContains(400, ImagenesWeb::ANCHOS_BANNER);
        $this->assertContains(750, ImagenesWeb::ANCHOS_BANNER);

        $srcset = Banner::visibles()->first()->paraElCarrusel()['srcset'];

        $this->assertStringContainsString('-400.webp 400w', $srcset);
        $this->assertStringContainsString('-750.webp 750w', $srcset);
    }

    public function test_las_categorias_tambien(): void
    {
        // La foto se pone a mano: en el banco de pruebas las categorías nacen
        // sin ella, y lo que se comprueba aquí es el `srcset`, no el seeder.
        $categoria = Categoria::firstOrCreate(
            ['slug' => 'frenos'],
            ['nombre' => 'Frenos']
        );

        $categoria->forceFill(['imagen' => '/img/categorias/frenos-640.webp'])->save();

        $this->assertStringContainsString('320w', (string) $categoria->imagen_srcset);
        $this->assertStringContainsString('640w', (string) $categoria->imagen_srcset);

        // Y los archivos de fábrica están de verdad en disco, en los tres
        // anchos: un `srcset` que apunta a un archivo que no existe no falla,
        // sirve la imagen equivocada o ninguna.
        foreach (Categoria::ANCHOS as $ancho) {
            $this->assertFileExists(
                public_path("img/categorias/frenos-{$ancho}.webp"),
                "Falta el ancho {$ancho} de la foto de categoría."
            );
        }
    }
}
