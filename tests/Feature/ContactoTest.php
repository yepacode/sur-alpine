<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Mail\MensajeContacto;
use App\Models\Configuracion;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * «Contáctenos»: la página, su formulario y la bandeja del panel.
 *
 * Lo que se protege por encima de todo es que un mensaje no se pierda. En su
 * sitio actual el formulario sólo manda un correo; si ese correo rebota, nadie
 * se entera de que alguien escribió.
 */
class ContactoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Configuracion::poner('correos_cotizacion', 'ventas@suralpine.co', 'correo');
    }

    private function admin(): User
    {
        return User::forceCreate([
            'name' => 'Administradora', 'email' => 'admin@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);
    }

    public function test_la_pagina_trae_los_datos_de_la_empresa(): void
    {
        $this->get(route('contacto'))
            ->assertOk()
            ->assertSee('Contáctenos')
            ->assertSee('Información de contacto')
            ->assertSee('Número de contacto')
            ->assertSee('Horarios de atención')
            ->assertSee('Lunes a viernes de 8:00 a.m. a 6:00 p.m.')
            ->assertSee('Oficinas')
            ->assertSee('Av. Caracas #19-15 sur')
            ->assertSee('Parqueadero vigilado.');
    }

    public function test_el_formulario_guarda_el_mensaje_y_avisa_al_equipo(): void
    {
        Mail::fake();

        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), [
                'nombre' => 'Pedro Ramírez',
                'email' => 'pedro@ejemplo.co',
                'mensaje' => 'Necesito pastillas de freno para un Aveo 2012.',
            ])
            ->assertRedirect(route('contacto'))
            ->assertSessionHas('mensaje_enviado');

        $mensaje = Mensaje::firstWhere('email', 'pedro@ejemplo.co');

        $this->assertNotNull($mensaje);
        $this->assertNotNull($mensaje->correo_enviado_en);
        $this->assertNull($mensaje->error_envio);

        Mail::assertSent(MensajeContacto::class, fn ($correo) => $correo->hasTo('ventas@suralpine.co'));
    }

    public function test_si_el_correo_falla_el_mensaje_no_se_pierde(): void
    {
        // Es el caso que su formulario no cubre: el correo es lo único que hay,
        // y si no sale, el mensaje desapareció.
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('Buzón lleno'));

        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), [
                'nombre' => 'Ana',
                'email' => 'ana@ejemplo.co',
                'mensaje' => 'Quiero saber si tienen amortiguadores para Spark.',
            ])
            // A la persona se le confirma igual: su mensaje sí quedó guardado.
            ->assertSessionHas('mensaje_enviado');

        $mensaje = Mensaje::firstWhere('email', 'ana@ejemplo.co');

        $this->assertNotNull($mensaje);
        $this->assertNull($mensaje->correo_enviado_en);
        $this->assertStringContainsString('Buzón lleno', $mensaje->error_envio);
    }

    public function test_no_se_aceptan_mensajes_incompletos(): void
    {
        Mail::fake();

        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), ['nombre' => '', 'email' => 'no-es-correo', 'mensaje' => 'corto'])
            ->assertSessionHasErrors(['nombre', 'email', 'mensaje']);

        $this->assertSame(0, Mensaje::count());
    }

    public function test_la_trampa_descarta_al_robot(): void
    {
        Mail::fake();

        $this->from(route('contacto'))
            ->post(route('contacto.enviar'), [
                'nombre' => 'Robot', 'email' => 'robot@ejemplo.co',
                'mensaje' => 'Compre seguidores baratos ahora mismo.',
                'sitio_web' => 'http://spam.example',
            ])
            ->assertSessionHas('mensaje_enviado');

        $this->assertSame(0, Mensaje::count());
        Mail::assertNothingSent();
    }

    public function test_la_bandeja_del_panel_lista_marca_y_reenvia(): void
    {
        Mail::fake();

        $mensaje = Mensaje::create([
            'nombre' => 'Luisa', 'email' => 'luisa@ejemplo.co',
            'mensaje' => 'Buenas, ¿hacen envíos a Villavicencio?',
            'error_envio' => 'El servidor no respondió',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('panel.mensajes'))
            ->assertOk()
            ->assertSee('luisa@ejemplo.co')
            ->assertSee('¿hacen envíos a Villavicencio?', false)
            ->assertSee('El correo no salió');

        $this->actingAs($admin)->post(route('panel.mensajes.atender', $mensaje))->assertRedirect();
        $this->assertNotNull($mensaje->fresh()->atendido_en);

        // Y al volver a pulsarlo, vuelve a quedar pendiente.
        $this->actingAs($admin)->post(route('panel.mensajes.atender', $mensaje))->assertRedirect();
        $this->assertNull($mensaje->fresh()->atendido_en);

        $this->actingAs($admin)->post(route('panel.mensajes.reenviar', $mensaje))->assertRedirect();
        $this->assertNotNull($mensaje->fresh()->correo_enviado_en);
        $this->assertNull($mensaje->fresh()->error_envio);
    }

    public function test_la_bandeja_no_es_publica(): void
    {
        $this->get(route('panel.mensajes'))->assertRedirect(route('acceso'));

        $cliente = User::forceCreate([
            'name' => 'Cliente', 'email' => 'cliente@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $this->actingAs($cliente)->get(route('panel.mensajes'))->assertForbidden();
    }

    public function test_el_mensaje_de_un_desconocido_no_inyecta_html(): void
    {
        Mail::fake();

        Mensaje::create([
            'nombre' => '<b>Robot</b>',
            'email' => 'x@ejemplo.co',
            'mensaje' => '<script>alert(1)</script> hola',
        ]);

        $html = $this->actingAs($this->admin())->get(route('panel.mensajes'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>Robot</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
