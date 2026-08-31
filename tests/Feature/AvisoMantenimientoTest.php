<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Mail\AvisoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * El recordatorio de mantenimiento que sale a buscar a la persona.
 *
 * Antes el aviso sólo existía en pantalla: quien no entraba, no se enteraba.
 * Lo que se cubre aquí es sobre todo lo que NO debe pasar —escribirle todos
 * los días a la misma persona, escribirle a quien cerró su cuenta, o inventar
 * una fecha para lo que va por kilometraje.
 */
class AvisoMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Fecha fija: con «dentro de seis meses» calculado sobre hoy, las
        // pruebas de fin de mes fallarían solas tres días al mes.
        $this->travelTo('2026-06-15 09:00:00');
    }

    private function cliente(array $atributos = []): User
    {
        static $n = 0;
        $n++;

        return User::forceCreate($atributos + [
            'name' => 'Julián Mecánico',
            'email' => "julian{$n}@taller.co",
            'telefono' => '3134223861',
            'password' => 'secreto123',
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);
    }

    private function mantenimiento(User $usuario, array $atributos = []): Mantenimiento
    {
        return $usuario->mantenimientos()->create($atributos + [
            'placa' => 'ABC123',
            'kilometraje' => 82000,
            'tipo' => 'Cambio de aceite',
            'fecha' => today()->subMonths(6),
            'periodicidad_tipo' => 'meses',
            'periodicidad_valor' => 6,
        ]);
    }

    public function test_avisa_lo_que_esta_por_vencer(): void
    {
        // Vence en tres días: dentro de la ventana de siete.
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(6)->addDays(3)]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertSent(AvisoMantenimiento::class, fn ($correo) => $correo->hasTo($usuario->email));
    }

    public function test_avisa_lo_vencido(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8)]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertSent(AvisoMantenimiento::class, fn ($correo) => $correo->vencidos->count() === 1);
    }

    public function test_lo_que_falta_mucho_no_dispara_nada(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($usuario->mantenimientos()->first()->aviso_enviado_en);
    }

    /**
     * El cierre que sostiene todo: el comando corre cada mañana. Sin la marca
     * de «ya avisé», el mismo cambio de aceite llegaría todos los días.
     */
    public function test_no_se_avisa_dos_veces_lo_mismo(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8)]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();
        $this->artisan('mantenimientos:avisar')->assertSuccessful();
        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertSentCount(1);
        $this->assertNotNull($usuario->mantenimientos()->first()->aviso_enviado_en);
    }

    /**
     * Un correo por persona, no uno por mantenimiento: quien lleva tres carros
     * recibiría tres correos la misma mañana y al tercero los manda a no
     * deseados —y con ellos, el de su cotización.
     */
    public function test_un_solo_correo_aunque_le_toquen_varios(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8), 'tipo' => 'Aceite']);
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(9), 'tipo' => 'Frenos', 'placa' => 'XYZ789']);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(AvisoMantenimiento::class, fn ($correo) => $correo->vencidos->count() === 2);
    }

    /** Quien pidió que lo borráramos no puede seguir recibiendo correos. */
    public function test_a_una_cuenta_desactivada_no_se_le_escribe(): void
    {
        $usuario = $this->cliente(['activo' => false]);
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8)]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertNothingSent();
    }

    /**
     * Lo que va por kilometraje no tiene fecha y no se la inventamos: no
     * dispara el correo por sí solo.
     */
    public function test_el_kilometraje_solo_no_dispara_correo(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, [
            'periodicidad_tipo' => 'kilometraje',
            'periodicidad_valor' => 20000,
            'fecha' => today()->subYears(2),
        ]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertNothingSent();
    }

    /** Pero si ya le estamos escribiendo por otra cosa, se los mencionamos. */
    public function test_el_kilometraje_viaja_de_acompanante(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8)]);
        $this->mantenimiento($usuario, [
            'tipo' => 'Pastillas',
            'periodicidad_tipo' => 'kilometraje',
            'periodicidad_valor' => 20000,
        ]);

        $this->artisan('mantenimientos:avisar')->assertSuccessful();

        Mail::assertSent(AvisoMantenimiento::class, fn ($correo) => $correo->porKilometraje->count() === 1);

        // Y NO quedan marcados: como nunca disparan el correo solos, marcarlos
        // los sacaría de la próxima mención sin que nadie los haya atendido.
        $this->assertNull(
            $usuario->mantenimientos()->where('tipo', 'Pastillas')->first()->aviso_enviado_en
        );
    }

    public function test_en_seco_no_envia_ni_marca(): void
    {
        $usuario = $this->cliente();
        $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8)]);

        $this->artisan('mantenimientos:avisar', ['--seco' => true])->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($usuario->mantenimientos()->first()->aviso_enviado_en);
    }

    /** Sin nada que avisar, el comando no falla ni escribe. */
    public function test_sin_pendientes_termina_bien(): void
    {
        $this->artisan('mantenimientos:avisar')
            ->expectsOutputToContain('No hay mantenimientos por avisar.')
            ->assertSuccessful();
    }

    /** El correo se arma sin reventar, con y sin bloque de kilometraje. */
    public function test_el_correo_se_arma(): void
    {
        $usuario = $this->cliente();
        $vencido = $this->mantenimiento($usuario, ['fecha' => today()->subMonths(8)]);
        $porKm = $this->mantenimiento($usuario, [
            'tipo' => 'Pastillas',
            'periodicidad_tipo' => 'kilometraje',
            'periodicidad_valor' => 20000,
        ]);

        $conKm = new AvisoMantenimiento($usuario, collect([$vencido]), collect(), collect([$porKm]));
        $sinKm = new AvisoMantenimiento($usuario, collect([$vencido]), collect(), collect());

        $this->assertStringContainsString('Cambio de aceite', (string) $conKm->render());
        $this->assertStringContainsString('Pastillas', (string) $conKm->render());
        $this->assertStringNotContainsString('Pastillas', (string) $sinKm->render());
    }
}
