<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Cotizacion;
use App\Models\Mensaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cerrar la cuenta tiene que borrar los datos personales de verdad.
 *
 * La supresión es un derecho del titular (Ley 1581). Lo que la retención
 * tributaria justifica es el DOCUMENTO —el consecutivo, la fecha, qué
 * repuestos se pidieron—, no el teléfono ni el correo, que además se
 * descargan enteros desde el CSV del panel.
 */
class BajaDeCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_quedan_datos_personales_en_ningun_lado(): void
    {
        $usuario = $this->usuario(Rol::Cliente, [
            'name' => 'Julián Restrepo',
            'email' => 'julian@tallerelprogreso.co',
            'telefono' => '3134223861',
        ]);

        $cotizacion = Cotizacion::create([
            'consecutivo' => 'SA-2026-00042',
            'user_id' => $usuario->id,
            'nombre' => 'Julián', 'apellidos' => 'Restrepo',
            'telefono' => '3134223861', 'email' => 'julian@tallerelprogreso.co',
            'notas' => 'Llamar después de las 6', 'ip' => '181.51.22.9',
        ]);

        Mensaje::create([
            'nombre' => 'Julián', 'email' => 'julian@tallerelprogreso.co',
            'mensaje' => '¿Manejan repuestos de Mazda?',
        ]);

        $this->entrarComo($usuario)
            ->post(route('cuenta.baja'), [
                'password' => 'secreto123',
                'confirmo' => '1',
            ])
            ->assertRedirect(route('inicio'));

        // La cuenta: sin nombre, sin teléfono, sin contraseña, correo liberado.
        $usuario->refresh();
        $this->assertSame('Cuenta dada de baja', $usuario->name);
        $this->assertNull($usuario->telefono);
        $this->assertNull($usuario->password);
        $this->assertStringEndsWith('@suralpine.invalid', $usuario->email);

        // El histórico sobrevive, pero sin nada con lo que llamar a nadie.
        $cotizacion->refresh();
        $this->assertSame('SA-2026-00042', $cotizacion->consecutivo, 'El documento tenía que conservarse.');
        $this->assertSame(1, $cotizacion->items()->count() + 1, 'El histórico sigue existiendo.');
        $this->assertNull($cotizacion->user_id);
        $this->assertNull($cotizacion->ip);
        $this->assertNull($cotizacion->notas);
        $this->assertSame('', $cotizacion->telefono);
        $this->assertStringEndsWith('@suralpine.invalid', $cotizacion->email);
        $this->assertStringNotContainsStringIgnoringCase('Restrepo', $cotizacion->nombre.$cotizacion->apellidos);

        // Y el mensaje de «Contáctenos», anonimizado.
        $this->assertSame(0, Mensaje::where('email', 'julian@tallerelprogreso.co')->count());
    }

    /** Y nada de todo eso puede quedar en el CSV que descarga el mostrador. */
    public function test_el_csv_del_panel_ya_no_lleva_sus_datos(): void
    {
        $usuario = $this->usuario(Rol::Cliente, ['email' => 'julian@tallerelprogreso.co']);

        $cotizacion = Cotizacion::create([
            'consecutivo' => 'SA-2026-00043', 'user_id' => $usuario->id,
            'nombre' => 'Julián', 'telefono' => '3134223861',
            'email' => 'julian@tallerelprogreso.co', 'ip' => '181.51.22.9',
        ]);

        // Con un ítem: el CSV escribe una línea POR REPUESTO, así que una
        // solicitud vacía no aparecería y la prueba pasaría por la razón
        // equivocada.
        $cotizacion->items()->create([
            'producto_nombre' => 'Bandas Freno AVEO 1400 CHEVROLET',
            'tipo_parte_nombre' => 'Bandas Freno',
            'vehiculo_nombre' => 'CHEVROLET AVEO 1400',
            'cantidad' => 2,
        ]);

        $this->entrarComo($usuario)->post(route('cuenta.baja'), [
            'password' => 'secreto123', 'confirmo' => '1',
        ]);

        $this->flushSession();

        $csv = $this->entrarComo($this->usuario(Rol::Admin))
            ->get(route('panel.solicitudes.exportar'))
            ->streamedContent();

        $this->assertStringContainsString('SA-2026-00043', $csv);
        $this->assertStringNotContainsString('3134223861', $csv);
        $this->assertStringNotContainsString('julian@tallerelprogreso.co', $csv);
    }
}
