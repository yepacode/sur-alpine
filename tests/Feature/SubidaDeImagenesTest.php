<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Lo que sube el panel al disco público.
 *
 * Esto existe por un agujero real: el nombre del archivo guardado salía de
 * `getClientOriginalExtension()`, que es la extensión del NOMBRE que manda
 * quien sube, no la del contenido. La regla `mimes:` valida por contenido, así
 * que un PNG legítimo llamado `payload.html` pasaba la validación y quedaba
 * guardado como `.html` dentro de `public/storage`, servido como HTML desde el
 * propio dominio. Eso es XSS almacenada y alojamiento de phishing en la casa
 * del cliente —que además ya tiene páginas suplantándolo—.
 *
 * De paso cubre el fallo callado que salió con él: los accesores
 * `imagen_srcset` exigen `.webp`, así que subir un JPG dejaba el `srcset` en
 * null sin avisar, y la versión chica no la generaba nadie.
 *
 * OJO: estas pruebas escriben en `storage/app/public` DE VERDAD. No sirve
 * `Storage::fake()`: la conversión la hace GD escribiendo al sistema de
 * archivos, sin pasar por la fachada `Storage`, así que con el disco falso las
 * comprobaciones miraban una carpeta vacía y pasaban sin probar nada. Se
 * limpia en `tearDown`, y sólo lo que la propia prueba creó.
 */
class SubidaDeImagenesTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> rutas absolutas creadas por esta prueba */
    private array $creados = [];

    protected function tearDown(): void
    {
        foreach ($this->creados as $ruta) {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::firstWhere(['email' => 'admin@suralpine.com']) ?? User::forceCreate(['email' => 'admin@suralpine.com'] + ['name' => 'Administradora', 'telefono' => '300',
             'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true]);
    }

    /**
     * Los archivos de esa carpeta cuyo nombre empiece por el prefijo, y de
     * paso quedan anotados para borrarlos al final.
     *
     * @return list<string> nombres, no rutas
     */
    private function archivos(string $carpeta, string $prefijo): array
    {
        $encontrados = glob(storage_path("app/public/{$carpeta}/{$prefijo}*")) ?: [];

        foreach ($encontrados as $ruta) {
            $this->creados[] = $ruta;
        }

        return array_map('basename', $encontrados);
    }

    /**
     * Un archivo con CONTENIDO de imagen real y NOMBRE engañoso.
     *
     * `UploadedFile::fake()->image('payload.html')` no sirve para esto: el
     * doble de Laravel deduce el mime del NOMBRE, así que la validación lo
     * rechaza por `text/html` y la prueba pasaría sin haber ejercido el
     * ataque. En una petición de verdad Laravel huele el CONTENIDO, y por eso
     * un PNG llamado `.html` sí atravesaba `mimes:`. Con `$test = true` y el
     * mime en null, Symfony hace justo eso: mirar el archivo.
     */
    private function imagenLlamada(string $nombre): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'aud').'.png';

        $lienzo = imagecreatetruecolor(900, 600);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 20, 100, 220));
        imagepng($lienzo, $ruta);
        imagedestroy($lienzo);

        $this->creados[] = $ruta;

        return new UploadedFile($ruta, $nombre, null, null, true);
    }

    private function categoria(): Categoria
    {
        // Un slug propio de la prueba: así no se confunde con la categoría
        // real «frenos» ni se borran sus fotos al limpiar.
        return Categoria::create(['nombre' => 'Prueba subida', 'slug' => 'aud-subida']);
    }

    public function test_un_nombre_con_extension_html_no_deja_un_html_en_el_servidor(): void
    {
        $categoria = $this->categoria();

        // Una imagen VÁLIDA —pasa `mimes:png`— con nombre malicioso.
        $this->actingAs($this->admin())->post(route('panel.categorias.guardar', $categoria), [
            'nombre' => 'Prueba subida',
            'imagen' => $this->imagenLlamada('payload.html'),
        ])->assertRedirect(route('panel.categorias'));

        $archivos = $this->archivos('categorias', 'aud-subida');

        $this->assertNotEmpty($archivos, 'La foto tenía que guardarse: sin esto la prueba no probaría nada.');

        foreach ($archivos as $archivo) {
            $this->assertStringEndsWith('.webp', $archivo, "Quedó «{$archivo}» servible en el dominio.");
        }

        $this->assertStringEndsWith('.webp', $categoria->fresh()->imagen);
    }

    public function test_lo_mismo_en_las_notas(): void
    {
        $this->actingAs($this->admin())->post(route('panel.notas.guardar'), [
            'titulo' => 'AUD subida de imagen',
            'categoria' => 'Mantenimiento',
            'resumen' => 'Qué es y cuándo se cambia.',
            'cuerpo' => 'Texto de la nota.',
            'imagen' => $this->imagenLlamada('payload.html'),
        ])->assertRedirect();

        $archivos = $this->archivos('notas', 'aud-subida-de-imagen');

        $this->assertNotEmpty($archivos, 'La foto tenía que guardarse.');

        foreach ($archivos as $archivo) {
            $this->assertStringEndsWith('.webp', $archivo, "Quedó «{$archivo}» servible en el dominio.");
        }
    }

    /**
     * Laravel bloquea por su cuenta los nombres `.php` —y sólo esos—.
     *
     * Vale la pena dejarlo escrito porque explica el agujero: la red del
     * framework atrapa `.php`, `.phtml` y compañía, pero no `.html`, que es
     * igual de ejecutable en el navegador y era justo por donde se colaba.
     */
    public function test_el_framework_ya_rechaza_los_nombres_php(): void
    {
        $categoria = $this->categoria();

        $this->actingAs($this->admin())->post(route('panel.categorias.guardar', $categoria), [
            'nombre' => 'Prueba subida',
            'imagen' => $this->imagenLlamada('payload.php'),
        ])->assertSessionHasErrors('imagen');

        $this->assertSame([], $this->archivos('categorias', 'aud-subida'));
    }

    /**
     * Y el `srcset` deja de ser una promesa: se guardan las dos versiones y
     * el accesor las encuentra.
     */
    public function test_se_generan_las_dos_versiones_y_el_srcset_apunta_a_archivos_reales(): void
    {
        $categoria = $this->categoria();

        $this->actingAs($this->admin())->post(route('panel.categorias.guardar', $categoria), [
            'nombre' => 'Prueba subida',
            // Un JPG: antes esto dejaba `-640.jpg` y el srcset en null.
            'imagen' => UploadedFile::fake()->image('foto.jpg', 1200, 800),
        ])->assertRedirect();

        $categoria->refresh();

        $this->assertSame('/storage/categorias/aud-subida-640.webp', $categoria->imagen);
        $this->assertNotNull($categoria->imagen_srcset, 'El srcset no puede quedar en null.');
        $this->assertStringContainsString('aud-subida-480.webp 480w', $categoria->imagen_srcset);

        // Y las dos rutas del srcset existen de verdad, que es lo que fallaba.
        $archivos = $this->archivos('categorias', 'aud-subida');

        $this->assertContains('aud-subida-480.webp', $archivos);
        $this->assertContains('aud-subida-640.webp', $archivos);
    }

    /**
     * Una imagen muy apaisada no puede pedir un lienzo de altura cero.
     *
     * 20.000 × 1 daba `round(0,08) = 0`, e `imagecreatetruecolor($ancho, 0)`
     * no devuelve lienzo: devuelve un error.
     */
    public function test_una_imagen_apaisada_no_revienta(): void
    {
        $categoria = $this->categoria();

        $this->actingAs($this->admin())->post(route('panel.categorias.guardar', $categoria), [
            'nombre' => 'Prueba subida',
            'imagen' => UploadedFile::fake()->image('franja.png', 4000, 2),
        ])->assertRedirect(route('panel.categorias'));

        $archivos = $this->archivos('categorias', 'aud-subida');

        $this->assertNotEmpty($archivos);

        foreach ($archivos as $archivo) {
            [$ancho, $alto] = getimagesize(storage_path("app/public/categorias/{$archivo}"));

            $this->assertGreaterThan(0, $alto, "«{$archivo}» quedó con altura cero.");
            $this->assertGreaterThan(0, $ancho);
        }
    }

    /**
     * Una bomba de descompresión se rechaza mirando la cabecera, sin abrirla.
     *
     * `max:4096` limita el ARCHIVO a 4 MB, pero un JPEG muy comprimido de 4 MB
     * puede traer cien megapíxeles dentro, y GD necesita unos cuatro bytes por
     * píxel: 400 MB de memoria para una foto que vamos a dejar en 1.600 px. En
     * un alojamiento compartido eso no da error, tumba el proceso.
     */
    public function test_una_imagen_de_demasiados_pixeles_se_rechaza_sin_abrirla(): void
    {
        $servicio = app(\App\Services\ImagenesWeb::class);

        $ruta = tempnam(sys_get_temp_dir(), 'bomba').'.png';
        $this->creados[] = $ruta;

        // Una imagen enorme pero de un solo color: pesa poco y ocuparía
        // muchísimo al descomprimirse. Es justo la forma del ataque.
        $lienzo = imagecreatetruecolor(7000, 7000);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 250, 250, 250));
        imagepng($lienzo, $ruta, 9);
        imagedestroy($lienzo);

        $this->assertLessThan(
            4 * 1024 * 1024,
            filesize($ruta),
            'Tiene que pesar poco: si no, la validación de tamaño ya la pararía y esto no probaría nada.'
        );

        $this->expectException(\RuntimeException::class);

        $servicio->guardarEnDisco(
            new UploadedFile($ruta, 'bomba.png', null, null, true),
            'categorias',
            'aud-bomba',
            [640]
        );
    }


    public function test_un_archivo_que_no_es_imagen_sigue_sin_pasar(): void
    {
        $categoria = $this->categoria();

        $this->actingAs($this->admin())->post(route('panel.categorias.guardar', $categoria), [
            'nombre' => 'Prueba subida',
            'imagen' => UploadedFile::fake()->create('virus.php', 20),
        ])->assertSessionHasErrors('imagen');

        $this->assertSame([], $this->archivos('categorias', 'aud-subida'));
    }
}
