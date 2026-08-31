<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Mensaje;
use App\Models\Suscriptor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un byte de latin1 no puede tumbar el sitio.
 *
 * La regla `string` de Laravel no comprueba la codificación, así que un «café»
 * pegado desde un sistema viejo atravesaba la validación y moría en el
 * `INSERT` con un 500 público. Y el peor caso no necesitaba ni un formulario
 * mal llenado: bastaba con llegar desde un enlace cuya URL trajera el byte,
 * porque el boletín guarda de dónde vino —y ese formulario está en el pie de
 * todas las páginas—.
 */
class TextoMalFormadoTest extends TestCase
{
    use RefreshDatabase;

    /** Un byte suelto de latin1: la «é» de «café» en ISO-8859-1. */
    private const BYTE_MALO = "caf\xE9";

    public function test_el_formulario_de_contacto_no_revienta(): void
    {
        $this->post(route('contacto.enviar'), [
            'nombre' => 'Jos'."\xE9".' Mu'."\xF1".'oz',
            'email' => 'jose@ejemplo.test',
            'mensaje' => 'Necesito un filtro para mi '.self::BYTE_MALO.', ¿lo manejan?',
        ])->assertRedirect();

        $mensaje = Mensaje::latest('id')->first();

        $this->assertNotNull($mensaje, 'El mensaje ni siquiera se guardó.');
        $this->assertTrue(mb_check_encoding($mensaje->nombre, 'UTF-8'));
        $this->assertTrue(mb_check_encoding($mensaje->mensaje, 'UTF-8'));
    }

    /** Y el del boletín tampoco, ni por el formulario ni por el `Referer`. */
    public function test_el_boletin_aguanta_una_url_de_origen_mal_formada(): void
    {
        $this->withServerVariables(['HTTP_REFERER' => 'http://localhost/buscar?q='.self::BYTE_MALO])
            ->post(route('suscripcion'), ['correo' => 'ana@ejemplo.test'])
            ->assertRedirect();

        $suscriptor = Suscriptor::where('correo', 'ana@ejemplo.test')->first();

        $this->assertNotNull($suscriptor);
        $this->assertTrue(mb_check_encoding((string) $suscriptor->origen, 'UTF-8'));
    }

    /** Lo que de verdad duele: perder una cotización ya armada. */
    public function test_enviar_la_cotizacion_con_un_nombre_mal_formado(): void
    {
        $usuario = $this->usuario(Rol::Cliente);

        $marca = \App\Models\Marca::create(['nombre' => 'RENAULT', 'slug' => 'renault']);
        $modelo = \App\Models\Modelo::create(['marca_id' => $marca->id, 'nombre' => '12', 'slug' => '12']);
        $vehiculo = \App\Models\Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1300',
            'slug' => 'renault-12-1300', 'anio_inicio' => 1980, 'anio_fin' => 1990,
        ]);
        $categoria = \App\Models\Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $tipo = \App\Models\TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Bujía', 'slug' => 'bujia',
        ]);
        $producto = \App\Models\Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Bujía 12 1300 RENAULT', 'slug' => 'bujia-12-1300-renault',
            'publicado' => true,
        ]);

        $this->entrarComo($usuario);
        $this->post(route('cotizacion.agregar', $producto));

        $this->post(route('cotizacion.enviar'), [
            'nombre' => 'Jos'."\xE9",
            'telefono' => '3134223861',
            'email' => 'jose@ejemplo.test',
            'notas' => 'Para mi '.self::BYTE_MALO,
            'acepta' => '1',
        ])->assertRedirect(route('cotizacion.enviada'));

        $cotizacion = \App\Models\Cotizacion::latest('id')->first();

        $this->assertNotNull($cotizacion, 'La solicitud se perdió.');
        $this->assertTrue(mb_check_encoding($cotizacion->nombre, 'UTF-8'));
    }

    /** Y lo bueno sigue pasando entero: tildes, ñ y emoji. */
    public function test_no_se_estropea_el_texto_correcto(): void
    {
        $this->post(route('contacto.enviar'), [
            'nombre' => 'Begoña Ñuñez',
            'email' => 'bego@ejemplo.test',
            'mensaje' => 'Necesito pastillas para el Peugeot 😀 — urgente, «hoy».',
        ])->assertRedirect();

        $mensaje = Mensaje::latest('id')->first();

        $this->assertSame('Begoña Ñuñez', $mensaje->nombre);
        $this->assertStringContainsString('😀', $mensaje->mensaje);
        $this->assertStringContainsString('«hoy»', $mensaje->mensaje);
    }
}
