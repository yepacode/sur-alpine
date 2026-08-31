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
        return User::forceCreate($atributos + [
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

    /**
     * Corregir, y no sólo borrar. La ruta existía y estaba probada, pero
     * ninguna vista la usaba: quien se equivocaba en el kilometraje tenía que
     * borrar el registro y escribirlo todo otra vez.
     */
    /**
     * Corregir un registro y equivocarse no puede borrar lo que se escribio.
     *
     * Pasaba: el componente decidia entre `old()` y la base con «hay un $m?»,
     * asi que al fallar la validacion el formulario del nº 7 volvia a los
     * valores viejos, se quedaba cerrado, y el que se abria solo era el de
     * «anotar uno nuevo» —o sea que el aviso rojo señalaba al formulario
     * equivocado mientras el trabajo de la persona desaparecia—.
     */
    public function test_corregir_con_un_error_conserva_lo_escrito_y_reabre_ese_formulario(): void
    {
        $usuario = $this->cliente();
        $mantenimiento = $usuario->mantenimientos()->create([
            'placa' => 'ABC123', 'kilometraje' => 82000, 'tipo' => 'Cambio de aceite',
            'fecha' => today()->subMonth(), 'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $this->actingAs($usuario)
            ->from(route('cuenta.mantenimientos'))
            ->post(route('cuenta.mantenimientos.actualizar', $mantenimiento), [
                '_editando' => $mantenimiento->id,
                'placa' => 'ABC123',
                'tipo' => 'Cambio de aceite y filtro',
                'kilometraje' => 95000,
                // Una fecha futura: es lo que ya se hizo, no puede estar por venir.
                'fecha' => today()->addYear()->toDateString(),
                'periodicidad_tipo' => 'meses',
                'periodicidad_valor' => 6,
            ])
            ->assertSessionHasErrors('fecha');

        $vista = $this->actingAs($usuario)->get(route('cuenta.mantenimientos'))->assertOk();

        // Lo que la persona alcanzo a escribir sigue en pantalla...
        $vista->assertSee('Cambio de aceite y filtro')
            ->assertSee('95000');

        // ...y es ESE formulario el que queda abierto, no el de anotar uno nuevo.
        $vista->assertSee('x-data="{ editando: true }"', false);
    }

    public function test_el_historial_ofrece_corregir_cada_registro(): void
    {
        $usuario = $this->cliente();
        $mantenimiento = $usuario->mantenimientos()->create([
            'placa' => 'ABC123', 'kilometraje' => 82000, 'tipo' => 'Cambio de aceite',
            'fecha' => today()->subMonth(), 'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $this->actingAs($usuario)->get(route('cuenta.mantenimientos'))
            ->assertOk()
            ->assertSee(route('cuenta.mantenimientos.actualizar', $mantenimiento))
            ->assertSee('Corregir');
    }

    /** El formulario de corregir llega con lo que ya estaba escrito. */
    public function test_el_formulario_de_corregir_trae_los_datos(): void
    {
        $usuario = $this->cliente();
        $usuario->mantenimientos()->create([
            'placa' => 'XYZ789', 'kilometraje' => 91500, 'tipo' => 'Kit de distribución',
            'fecha' => today()->subMonths(3), 'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 12,
            'notas' => 'Lo hicieron en el taller de la 68',
        ]);

        $this->actingAs($usuario)->get(route('cuenta.mantenimientos'))
            ->assertOk()
            ->assertSee('value="XYZ789"', false)
            ->assertSee('value="91500"', false)
            ->assertSee('Lo hicieron en el taller de la 68');
    }

    // ── El perfil de un carro ───────────────────────────────────────────────

    public function test_el_perfil_del_carro_trae_su_ficha_y_sus_mantenimientos(): void
    {
        $usuario = $this->cliente();
        $vehiculo = $this->vehiculo();
        $usuario->vehiculosGuardados()->attach($vehiculo->id, [
            'placa' => 'ABC123', 'alias' => 'El del trabajo',
        ]);

        $usuario->mantenimientos()->create([
            'vehiculo_id' => $vehiculo->id, 'placa' => 'ABC123', 'kilometraje' => 82000,
            'tipo' => 'Cambio de aceite', 'fecha' => today()->subMonths(2),
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);

        $this->actingAs($usuario)->get(route('cuenta.vehiculo', $vehiculo))
            ->assertOk()
            ->assertSee('El del trabajo')
            ->assertSee('ABC123')
            ->assertSee('CHEVROLET')
            ->assertSee('Cambio de aceite');
    }

    /**
     * El mantenimiento anotado escribiendo la placa a mano, sin elegir el
     * carro de la lista, también es de este carro para quien lo mira.
     */
    public function test_el_perfil_recoge_lo_anotado_solo_con_la_placa(): void
    {
        $usuario = $this->cliente();
        $vehiculo = $this->vehiculo();
        $usuario->vehiculosGuardados()->attach($vehiculo->id, ['placa' => 'ABC123']);

        $usuario->mantenimientos()->create([
            'vehiculo_id' => null, 'placa' => 'ABC123', 'kilometraje' => 90000,
            'tipo' => 'Correa de repartición', 'fecha' => today()->subMonth(),
            'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 12,
        ]);

        $this->actingAs($usuario)->get(route('cuenta.vehiculo', $vehiculo))
            ->assertOk()
            ->assertSee('Correa de repartición');
    }

    /**
     * El límite que importa: el carro tiene que estar en SU cuenta, no sólo
     * existir en el catálogo. Si no, la placa y el historial de otro salen
     * cambiando el slug de la URL.
     */
    public function test_un_carro_que_no_guardo_no_tiene_perfil(): void
    {
        $usuario = $this->cliente();
        $vehiculo = $this->vehiculo();

        // Existe, pero es de otra persona.
        $otro = $this->cliente(['email' => 'otra@taller.co']);
        $otro->vehiculosGuardados()->attach($vehiculo->id, ['placa' => 'ZZZ999']);

        $this->actingAs($usuario)->get(route('cuenta.vehiculo', $vehiculo))->assertNotFound();
        $this->actingAs($usuario)->post(route('cuenta.vehiculo.actualizar', $vehiculo), [
            'placa' => 'HACK01',
        ])->assertNotFound();

        $this->assertSame('ZZZ999', $otro->vehiculosGuardados()->first()->pivot->placa);
    }

    public function test_se_corrige_la_placa_y_el_alias(): void
    {
        $usuario = $this->cliente();
        $vehiculo = $this->vehiculo();
        $usuario->vehiculosGuardados()->attach($vehiculo->id, [
            'placa' => 'ABC123', 'alias' => 'El viejo',
        ]);

        $this->actingAs($usuario)->post(route('cuenta.vehiculo.actualizar', $vehiculo), [
            'placa' => 'xyz789',
            'alias' => 'El de mi esposa',
        ])->assertRedirect(route('cuenta.vehiculo', $vehiculo));

        $pivote = $usuario->vehiculosGuardados()->first()->pivot;

        $this->assertSame('XYZ789', $pivote->placa, 'La placa se guarda en mayúsculas.');
        $this->assertSame('El de mi esposa', $pivote->alias);
    }

    /**
     * Aquí sí se puede borrar: este formulario manda los dos campos siempre,
     * así que dejar el alias en blanco es una orden y no un descuido. (En
     * «agregar vehículo» es al revés y por eso allá se filtran los vacíos.)
     */
    public function test_vaciar_el_alias_lo_borra(): void
    {
        $usuario = $this->cliente();
        $vehiculo = $this->vehiculo();
        $usuario->vehiculosGuardados()->attach($vehiculo->id, [
            'placa' => 'ABC123', 'alias' => 'El del trabajo',
        ]);

        $this->actingAs($usuario)->post(route('cuenta.vehiculo.actualizar', $vehiculo), [
            'placa' => 'ABC123',
            'alias' => '',
        ])->assertRedirect();

        $this->assertNull($usuario->vehiculosGuardados()->first()->pivot->alias);
    }

    /** Sin sesión no hay perfil que ver. */
    public function test_el_perfil_del_carro_pide_sesion(): void
    {
        $vehiculo = $this->vehiculo();

        $this->get(route('cuenta.vehiculo', $vehiculo))->assertRedirect(route('acceso'));
    }

    // ── Registro ────────────────────────────────────────────────────────────

    public function test_un_mecanico_puede_crear_su_cuenta(): void
    {
        // El registro no hace auto-login: la respuesta observable tiene que
        // ser IDÉNTICA a la de un correo duplicado, para no filtrar qué
        // emails ya están en el sistema.
        $this->post(route('registro.crear'), [
            'name' => 'Julián Mecánico',
            'telefono' => '313 422 3861',
            'email' => 'julian@taller.co',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'acepta' => 1,
        ])->assertRedirect(route('acceso'));

        $usuario = User::where('email', 'julian@taller.co')->firstOrFail();

        // Cliente, sí; sesión, no (aún tiene que iniciar sesión).
        $this->assertSame(Rol::Cliente, $usuario->rol);
        $this->assertGuest();
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
        // Con fecha congelada a mitad de mes: restar y volver a sumar meses no
        // es simétrico cerca del fin de mes —desde un día 29, 30 o 31 la
        // vuelta cae en otro día— y la prueba fallaba sola tres días al mes
        // por el andamio, no por lo que mide.
        $this->travelTo('2026-06-15 09:00:00');

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

    // ── Habeas Data ────────────────────────────────────────────────────────

    public function test_al_registrarse_queda_clavada_la_version_y_fecha_de_aceptacion(): void
    {
        // Antes no había forma de responder al oficial de datos con qué
        // versión aceptó cada persona: ahora queda en el usuario.
        $this->post(route('registro.crear'), [
            'name' => 'Ana', 'telefono' => '300', 'email' => 'ana@taller.co',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'acepta' => 1,
        ])->assertRedirect(route('acceso'));

        $ana = User::firstWhere('email', 'ana@taller.co');
        $this->assertNotNull($ana->acepto_en, 'Debe quedar la fecha de aceptación.');
        $this->assertSame((string) config('habeas.version'), $ana->politica_version);
    }

    /**
     * H3 · Seguridad: la enumeración de correos por diferencia de `Location`.
     * El registro con correo NUEVO y con correo DUPLICADO deben terminar en
     * la misma URL y con el mismo flash — así un atacante no distingue.
     */
    public function test_registro_duplicado_y_exitoso_dan_la_misma_respuesta(): void
    {
        $this->cliente(['email' => 'ya-existo@taller.co']);

        $exito = $this->post(route('registro.crear'), [
            'name' => 'Nuevo', 'telefono' => '300', 'email' => 'nuevo@taller.co',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'acepta' => 1,
        ]);

        $duplicado = $this->post(route('registro.crear'), [
            'name' => 'Otro', 'telefono' => '300', 'email' => 'ya-existo@taller.co',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'acepta' => 1,
        ]);

        $exito->assertRedirect(route('acceso'));
        $duplicado->assertRedirect(route('acceso'));
        $this->assertSame(
            $exito->headers->get('Location'),
            $duplicado->headers->get('Location'),
            'El destino tiene que ser IDÉNTICO para no filtrar qué correos existen.',
        );
    }

    public function test_el_cliente_puede_darse_de_baja_desde_mi_cuenta(): void
    {
        $cliente = $this->cliente();
        $vehiculo = $this->vehiculo();
        $cliente->vehiculosGuardados()->attach($vehiculo->id, ['placa' => 'XYZ 987']);

        $mant = new Mantenimiento([
            'placa' => 'XYZ 987', 'tipo' => 'Aceite', 'fecha' => now()->subMonth()->toDateString(),
            'kilometraje' => 12000, 'periodicidad_tipo' => 'meses', 'periodicidad_valor' => 6,
        ]);
        $mant->user_id = $cliente->id;
        $mant->calcularProximo()->save();

        $this->actingAs($cliente)
            ->post(route('cuenta.baja'), [
                'confirmo' => 1,
                'password' => 'secreto123',
            ])
            ->assertRedirect(route('inicio'));

        $cliente->refresh();
        $this->assertFalse($cliente->activo, 'La cuenta queda desactivada.');
        $this->assertNotNull($cliente->baja_solicitada_en, 'Queda registrada la fecha.');
        $this->assertSame(0, $cliente->mantenimientos()->count(), 'Sus mantenimientos se borran.');
        $this->assertSame(0, $cliente->vehiculosGuardados()->count(), 'Sus vehículos se desligan.');
        $this->assertGuest();
    }

    public function test_la_baja_pide_contrasena_actual(): void
    {
        // Sin contraseña, la baja no procede: si alguien deja abierta la
        // sesión en un teléfono ajeno, no se pierde la cuenta con un clic.
        $cliente = $this->cliente();

        $this->actingAs($cliente)
            ->post(route('cuenta.baja'), [
                'confirmo' => 1,
                'password' => 'esta-no-es-mi-clave',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($cliente->refresh()->activo, 'La cuenta sigue activa.');
    }

    public function test_una_cuenta_desactivada_pierde_la_sesion_al_siguiente_request(): void
    {
        // Si el admin desactiva a un empleado, la sesión abierta cae en el
        // próximo request: no queda un panel accesible en el navegador.
        $cliente = $this->cliente();
        $this->actingAs($cliente);

        $this->get(route('cuenta'))->assertOk();

        $cliente->forceFill(['activo' => false])->save();

        $this->get(route('cuenta'))
            ->assertRedirect(route('acceso'));
        $this->assertGuest();
    }

    public function test_la_pagina_de_politica_de_datos_esta_publicada(): void
    {
        $this->get('/politica-datos')
            ->assertOk()
            ->assertSee('Ley 1581', false)
            ->assertSee('Sur Alpine', false);
    }
}
