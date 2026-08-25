<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Mantenimiento;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M3 · El área del cliente: sus vehículos y su historial de mantenimiento.
 *
 * Es lo que el negocio llama «el módulo para mecánicos»: uno solo maneja tres
 * o cuatro carros y necesita saber a cuál le toca qué.
 */
class CuentaTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(array $atributos = []): User
    {
        return User::create($atributos + [
            'name' => 'Julián Mecánico',
            'email' => 'julian@taller.co',
            'telefono' => '313 422 3861',
            'password' => 'secreto123',
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);
    }

    private function vehiculo(): Vehiculo
    {
        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);

        return Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);
    }

    // ── Registro ────────────────────────────────────────────────────────────

    public function test_un_mecanico_puede_crear_su_cuenta(): void
    {
        $this->post(route('registro.crear'), [
            'name' => 'Julián Mecánico',
            'telefono' => '313 422 3861',
            'email' => 'julian@taller.co',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'acepta' => 1,
        ])->assertRedirect(route('cuenta'));

        $usuario = User::where('email', 'julian@taller.co')->firstOrFail();

        // Entra como cliente: nunca al panel, por mucho que cambie la URL.
        $this->assertSame(Rol::Cliente, $usuario->rol);
        $this->assertAuthenticatedAs($usuario);
        $this->get('/panel')->assertForbidden();
    }

    public function test_sin_autorizacion_de_datos_no_hay_cuenta(): void
    {
        $this->post(route('registro.crear'), [
            'name' => 'Julián', 'telefono' => '313 422 3861',
            'email' => 'julian@taller.co',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertSessionHasErrors('acepta');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_el_campo_trampa_descarta_los_registros_automaticos(): void
    {
        $this->post(route('registro.crear'), [
            'name' => 'Robot', 'telefono' => '000', 'email' => 'robot@spam.co',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'acepta' => 1, 'sitio_web' => 'http://spam.example',
        ])->assertRedirect(route('inicio'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_repetir_el_correo_no_enumera_cuentas_existentes(): void
    {
        // Antes el formulario respondía «ya hay una cuenta con ese correo», y
        // eso servía para averiguar qué correos existen en el sistema. Hoy
        // devuelve la misma pantalla que un registro exitoso: no se crea el
        // duplicado, y no se dice por qué.
        $this->cliente();

        $antes = \App\Models\User::count();

        $this->post(route('registro.crear'), [
            'name' => 'Otro', 'telefono' => '300', 'email' => 'julian@taller.co',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'acepta' => 1,
        ])
            ->assertRedirect(route('acceso'))
            ->assertSessionMissing('errors');

        $this->assertSame($antes, \App\Models\User::count());
    }

    // ── Vehículos guardados ─────────────────────────────────────────────────

    public function test_el_cliente_guarda_sus_carros_con_placa_y_alias(): void
    {
        $cliente = $this->cliente();
        $vehiculo = $this->vehiculo();

        $this->actingAs($cliente)->post(route('cuenta.vehiculo.guardar'), [
            'vehiculo_id' => $vehiculo->id,
            'placa' => 'abc123',
            'alias' => 'El de la empresa',
        ])->assertRedirect();

        $guardado = $cliente->vehiculosGuardados()->first();

        $this->assertSame($vehiculo->id, $guardado->id);
        $this->assertSame('ABC123', $guardado->pivot->placa, 'La placa se guarda en mayúsculas.');
        $this->assertSame('El de la empresa', $guardado->pivot->alias);
    }

    public function test_guardar_el_mismo_carro_dos_veces_no_lo_duplica(): void
    {
        $cliente = $this->cliente();
        $vehiculo = $this->vehiculo();

        foreach (['ABC123', 'XYZ789'] as $placa) {
            $this->actingAs($cliente)->post(route('cuenta.vehiculo.guardar'), [
                'vehiculo_id' => $vehiculo->id, 'placa' => $placa,
            ]);
        }

        $this->assertCount(1, $cliente->vehiculosGuardados()->get());
        $this->assertSame('XYZ789', $cliente->vehiculosGuardados()->first()->pivot->placa);
    }

    // ── Mantenimientos ──────────────────────────────────────────────────────

    public function test_el_proximo_se_calcula_por_meses(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)->post(route('cuenta.mantenimientos.guardar'), [
            'placa' => 'ABC123', 'tipo' => 'Cambio de aceite',
            'fecha' => '2026-01-15', 'kilometraje' => 48000,
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ])->assertRedirect(route('cuenta.mantenimientos'));

        $registro = $cliente->mantenimientos()->firstOrFail();

        $this->assertSame('2026-07-15', $registro->proximo_fecha->toDateString());
        $this->assertNull($registro->proximo_kilometraje);
    }

    public function test_el_proximo_se_calcula_por_kilometraje(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)->post(route('cuenta.mantenimientos.guardar'), [
            'placa' => 'ABC123', 'tipo' => 'Kit de distribución',
            'fecha' => today()->toDateString(), 'kilometraje' => 48000,
            'periodicidad_tipo' => 'kilometraje', 'periodicidad_valor' => 60000,
        ]);

        $registro = $cliente->mantenimientos()->firstOrFail();

        $this->assertSame(108000, $registro->proximo_kilometraje);
        $this->assertNull($registro->proximo_fecha, 'Por kilómetros no hay fecha que avisar.');
    }

    public function test_la_fecha_no_puede_ser_futura(): void
    {
        $this->actingAs($this->cliente())->post(route('cuenta.mantenimientos.guardar'), [
            'placa' => 'ABC123', 'tipo' => 'Cambio de aceite',
            'fecha' => today()->addWeek()->toDateString(), 'kilometraje' => 1000,
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ])->assertSessionHasErrors('fecha');
    }

    public function test_el_historial_se_filtra_por_placa(): void
    {
        $cliente = $this->cliente();

        foreach ([['ABC123', 'Sincronizacion'], ['ABC123', 'Empaque culata'], ['XYZ789', 'Rectificada discos']] as [$placa, $tipo]) {
            $this->actingAs($cliente)->post(route('cuenta.mantenimientos.guardar'), [
                'placa' => $placa, 'tipo' => $tipo,
                'fecha' => today()->toDateString(), 'kilometraje' => 1000,
                'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
            ]);
        }

        // El mensaje de «Anotamos …» del último guardado sobrevive a la
        // siguiente petición: se consume antes de mirar el listado.
        $this->actingAs($cliente)->get(route('cuenta'));

        $this->actingAs($cliente)->get(route('cuenta.mantenimientos', ['placa' => 'ABC123']))
            ->assertOk()
            ->assertSee('Sincronizacion')
            ->assertSee('Empaque culata')
            ->assertDontSee('Rectificada discos');
    }

    /**
     * Lo más importante del módulo: el historial de un mecánico no lo ve otro,
     * ni cambiando el número de la URL.
     */
    public function test_nadie_toca_el_historial_de_otro(): void
    {
        $julian = $this->cliente();
        $otro = $this->cliente(['email' => 'otro@taller.co', 'name' => 'Otro']);

        $delOtro = Mantenimiento::create([
            'user_id' => $otro->id, 'placa' => 'XYZ789', 'tipo' => 'Rectificada discos',
            'fecha' => today(), 'kilometraje' => 1000,
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $this->actingAs($julian)
            ->post(route('cuenta.mantenimientos.borrar', $delOtro))
            ->assertForbidden();

        $this->actingAs($julian)
            ->post(route('cuenta.mantenimientos.actualizar', $delOtro), [
                'placa' => 'HACK', 'tipo' => 'Robado', 'fecha' => today()->toDateString(),
                'kilometraje' => 1, 'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('mantenimientos', ['id' => $delOtro->id, 'tipo' => 'Rectificada discos']);

        // Y tampoco aparece en su listado.
        $this->actingAs($julian)->get(route('cuenta.mantenimientos'))->assertDontSee('Rectificada discos');
    }

    public function test_el_cliente_borra_su_propio_registro(): void
    {
        $cliente = $this->cliente();

        $suyo = Mantenimiento::create([
            'user_id' => $cliente->id, 'placa' => 'ABC123', 'tipo' => 'Sincronizacion',
            'fecha' => today(), 'kilometraje' => 1000,
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $this->actingAs($cliente)->post(route('cuenta.mantenimientos.borrar', $suyo))->assertRedirect();

        $this->assertDatabaseMissing('mantenimientos', ['id' => $suyo->id]);
    }

    public function test_el_area_del_cliente_pide_sesion(): void
    {
        $this->get(route('cuenta'))->assertRedirect(route('acceso'));
        $this->get(route('cuenta.mantenimientos'))->assertRedirect(route('acceso'));
    }

    /**
     * B5 · El mantenimiento que toca hoy no puede salir como vencido: la píldora
     * roja diciendo «Hoy» era el escenario que hacía dudar del sitio entero.
     */
    public function test_el_que_toca_hoy_no_sale_como_vencido(): void
    {
        $cliente = $this->cliente();

        $hoy = Mantenimiento::create([
            'user_id' => $cliente->id, 'placa' => 'ABC123', 'tipo' => 'Aceite',
            'fecha' => today()->subMonths(6), 'kilometraje' => 40000,
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ])->calcularProximo();
        $hoy->save();

        $this->assertSame(today()->toDateString(), $hoy->fresh()->proximo_fecha->toDateString());
        $this->assertSame('Hoy', $hoy->fresh()->aviso);
        $this->assertFalse($hoy->fresh()->vencido, 'Lo que toca hoy NO está vencido.');
    }

    /**
     * B6 · Los mantenimientos por kilometraje no tienen `proximo_fecha` y
     * antes se caían del listado de «Próximos» —el mecánico que lleva el carro
     * por kilómetros no veía nada en su tablero.
     */
    public function test_los_proximos_incluyen_los_de_kilometraje(): void
    {
        $cliente = $this->cliente();

        $km = Mantenimiento::create([
            'user_id' => $cliente->id, 'placa' => 'ABC123', 'tipo' => 'Kit de distribución',
            'fecha' => today(), 'kilometraje' => 48000,
            'periodicidad_tipo' => 'kilometraje', 'periodicidad_valor' => 60000,
        ])->calcularProximo();
        $km->save();

        $this->actingAs($cliente)->get(route('cuenta'))
            ->assertOk()
            ->assertSee('Kit de distribución');
    }

    /**
     * B9 · Reguardar el vehículo sólo para cambiar el alias no puede borrar
     * la placa: es el vínculo con el historial, que se filtra por placa.
     */
    public function test_reguardar_un_vehiculo_no_borra_la_placa(): void
    {
        $cliente = $this->cliente();
        $vehiculo = $this->vehiculo();

        $this->actingAs($cliente)->post(route('cuenta.vehiculo.guardar'), [
            'vehiculo_id' => $vehiculo->id, 'placa' => 'ABC123', 'alias' => 'El primero',
        ]);

        // Ahora sólo se cambia el alias, dejando el campo de placa vacío.
        $this->actingAs($cliente)->post(route('cuenta.vehiculo.guardar'), [
            'vehiculo_id' => $vehiculo->id, 'placa' => '', 'alias' => 'El de la empresa',
        ]);

        $guardado = $cliente->vehiculosGuardados()->first();
        $this->assertSame('ABC123', $guardado->pivot->placa, 'La placa tiene que sobrevivir.');
        $this->assertSame('El de la empresa', $guardado->pivot->alias);
    }
}
