<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * «Textos e imágenes»: que lo que se guarda sea lo que se ve.
 *
 * Dos defectos del mismo tipo, y son los peores que puede tener un panel:
 * campos que se guardan sin cambiar nada, y errores que mienten sobre lo que
 * pasó. El cliente pierde la confianza en la herramienta y vuelve a llamar por
 * teléfono para cada cambio, que es justo lo que este panel venía a evitar.
 */
class PanelTextosEImagenesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): \App\Models\User
    {
        return $this->usuario(Rol::Admin);
    }

    private function fila(string $clave, string $valor = 'Texto original'): Contenido
    {
        return Contenido::create([
            'clave' => $clave, 'grupo' => 'contacto', 'rotulo' => 'Prueba',
            'tipo' => 'texto', 'valor' => $valor,
        ]);
    }

    /**
     * Borrar un texto lo borra de verdad.
     *
     * El middleware `ConvertEmptyStringsToNull` convierte el '' del formulario
     * en `null` antes de llegar al controlador, y `null` significa «nunca lo
     * tocaron, usa el original». Resultado: el cliente borraba «Parqueadero
     * vigilado», salía «guardado», y la web seguía diciendo lo mismo.
     */
    public function test_vaciar_un_texto_lo_vacia(): void
    {
        $fila = $this->fila('contacto.oficinas.nota');

        $this->entrarComo($this->admin())
            ->post(route('panel.pagina.guardar'), ['textos' => [$fila->id => '']])
            ->assertRedirect();

        $this->assertSame('', $fila->fresh()->valor);
        $this->assertSame('', contenido('contacto.oficinas.nota', 'Parqueadero vigilado.'));
    }

    /** Y escribir algo sigue funcionando, claro. */
    public function test_escribir_un_texto_lo_cambia(): void
    {
        $fila = $this->fila('contacto.mapa.boton');

        $this->entrarComo($this->admin())
            ->post(route('panel.pagina.guardar'), ['textos' => [$fila->id => '  Cómo llegar  ']]);

        $this->assertSame('Cómo llegar', $fila->fresh()->valor);
    }

    /**
     * Una imagen inválida NO puede dejar los textos ya cambiados.
     *
     * Pasaba: los 66 textos se escribían antes de validar las imágenes, así
     * que subir un PDF en el campo de foto mostraba «No pudimos guardar todo»
     * con la portada YA cambiada. El cliente veía un error, asumía que no
     * había pasado nada, y la web era otra.
     */
    public function test_una_imagen_invalida_no_deja_los_textos_a_medias(): void
    {
        $texto = $this->fila('contacto.mapa.boton', 'Antes');
        $imagen = $this->fila('contacto.foto', '/img/fotos/algo-1600.webp');

        $this->entrarComo($this->admin())
            ->post(route('panel.pagina.guardar'), [
                'textos' => [$texto->id => 'Después'],
                'imagenes' => [$imagen->id => UploadedFile::fake()->create('manual.pdf', 40, 'application/pdf')],
            ])
            ->assertSessionHasErrors('imagenes.'.$imagen->id);

        $this->assertSame('Antes', $texto->fresh()->valor, 'El texto se guardó pese al error.');
    }

    /** Y el error nombra el campo como se llama en pantalla, no por su id. */
    public function test_el_error_de_imagen_nombra_el_campo_en_castellano(): void
    {
        $imagen = $this->fila('contacto.foto', '/img/fotos/algo-1600.webp');

        $this->entrarComo($this->admin())
            ->post(route('panel.pagina.guardar'), [
                'imagenes' => [$imagen->id => UploadedFile::fake()->create('manual.pdf', 40, 'application/pdf')],
            ]);

        $mensaje = session('errors')->first('imagenes.'.$imagen->id);

        $this->assertStringNotContainsString((string) $imagen->id, $mensaje);
        $this->assertStringNotContainsString('field', $mensaje);
    }
}
