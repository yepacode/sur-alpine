<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Mail\ConfirmacionCotizacion;
use App\Mail\SolicitudCotizacion;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CotizadorTest extends TestCase
{
    use RefreshDatabase;

    private Producto $pastillasAveo;

    private Producto $filtroAveo;

    private Producto $bandasLogan;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $aveo = $this->crearVehiculo('CHEVROLET', 'AVEO', '1600', 2006, 2013);
        $logan = $this->crearVehiculo('RENAULT', 'LOGAN', '1600', 2010, 2015);

        $frenos = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $motor = Categoria::create(['nombre' => 'Motor Externo', 'slug' => 'motor-externo']);

        $this->pastillasAveo = $this->crearProducto($frenos, 'Pastillas Freno Delanteras', $aveo);
        $this->filtroAveo = $this->crearProducto($motor, 'Filtro Aceite', $aveo);
        $this->bandasLogan = $this->crearProducto($frenos, 'Bandas Freno', $logan);

        Configuracion::poner('correos_cotizacion', 'cotizaciones@suralpine.com');
    }

    private function crearVehiculo(string $marca, string $modelo, string $cc, int $desde, int $hasta): Vehiculo
    {
        $m = Marca::firstOrCreate(['slug' => Str::slug($marca)], ['nombre' => $marca]);
        $mo = Modelo::firstOrCreate(['marca_id' => $m->id, 'slug' => Str::slug($modelo)], ['nombre' => $modelo]);

        return Vehiculo::create([
            'modelo_id' => $mo->id, 'cilindraje' => $cc,
            'anio_inicio' => $desde, 'anio_fin' => $hasta,
            'slug' => Str::slug("{$marca}-{$modelo}-{$cc}-{$desde}"),
        ]);
    }

    private function crearProducto(Categoria $categoria, string $parte, Vehiculo $vehiculo): Producto
    {
        $tipo = TipoParte::firstOrCreate(
            ['categoria_id' => $categoria->id, 'slug' => Str::slug($parte)],
            ['nombre' => $parte]
        );

        $nombre = $parte.' '.strtoupper($vehiculo->modelo->nombre);

        return Producto::create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_parte_id' => $tipo->id,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
        ]);
    }

    private function agregar(Producto $producto, int $cantidad = 1): void
    {
        $this->post(route('cotizacion.agregar', $producto), ['cantidad' => $cantidad]);
    }

    /** El que cotiza. Sus datos son los mismos de `datosContacto()`. */
    private function cliente(): User
    {
        return User::firstWhere(['email' => 'julian@taller.co']) ?? User::forceCreate(['email' => 'julian@taller.co'] + [
                'name' => 'Julián Restrepo',
                'telefono' => '3134223861',
                'password' => 'secreto123',
                'rol' => Rol::Cliente,
                'activo' => true,
            ]);
    }

    private function datosContacto(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Julián',
            'apellidos' => 'Restrepo',
            'telefono' => '3134223861',
            'email' => 'julian@taller.co',
            'acepta' => '1',
        ], $extra);
    }

    // ── Enviar exige sesión ─────────────────────────────────────────────────

    /**
     * Lo pidió el cliente: una solicitud sin cuenta detrás deja al mostrador
     * llamando a un número suelto, sin historial ni forma de retomar.
     */
    public function test_sin_sesion_no_se_envia_y_no_se_pierde_lo_agregado(): void
    {
        $this->agregar($this->pastillasAveo, 2);

        $this->post(route('cotizacion.enviar'), $this->datosContacto())
            ->assertRedirect(route('acceso'));

        $this->assertSame(0, Cotizacion::count());
        Mail::assertNothingSent();

        // Y lo que traía sigue ahí: es la diferencia entre volver e irse.
        $this->get(route('cotizacion.ver'))->assertOk()->assertSee('Pastillas Freno Delanteras');
    }

    /** Al entrar cae otra vez en su cotización, no en la portada. */
    public function test_tras_iniciar_sesion_vuelve_a_su_cotizacion(): void
    {
        $this->agregar($this->pastillasAveo);
        $cliente = $this->cliente();

        $this->post(route('cotizacion.enviar'), $this->datosContacto())
            ->assertRedirect(route('acceso'));

        $this->post(route('entrar'), ['email' => $cliente->email, 'password' => 'secreto123'])
            ->assertRedirect(route('cotizacion.ver'));
    }

    /** Sin sesión el formulario ni se pinta: se ofrece entrar o registrarse. */
    public function test_al_visitante_se_le_ofrece_entrar_en_vez_del_formulario(): void
    {
        $this->agregar($this->pastillasAveo);

        $this->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee('Para enviarla, entra a tu cuenta')
            ->assertSee(route('registro'))
            ->assertDontSee('quién llamamos', false);
    }

    /**
     * El otro punto del documento del cliente: quien ya entró no vuelve a
     * escribir su nombre, su teléfono y su correo en cada cotización.
     */
    public function test_con_sesion_el_formulario_llega_con_sus_datos(): void
    {
        $this->agregar($this->pastillasAveo);

        $html = $this->actingAs($this->cliente())->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee('quién llamamos', false)
            ->getContent();

        $this->assertStringContainsString('value="Julián"', $html);
        $this->assertStringContainsString('value="Restrepo"', $html);
        $this->assertStringContainsString('value="3134223861"', $html);
        $this->assertStringContainsString('value="julian@taller.co"', $html);
    }

    /**
     * Si la validación devuelve el formulario, manda lo que la persona
     * escribió y no lo que tiene guardado: si no, corregir un teléfono sólo
     * para esta solicitud sería imposible.
     */
    public function test_lo_escrito_no_lo_pisa_lo_guardado(): void
    {
        $this->agregar($this->pastillasAveo);
        $cliente = $this->cliente();

        $this->actingAs($cliente)
            ->from(route('cotizacion.ver'))
            ->post(route('cotizacion.enviar'), $this->datosContacto([
                'telefono' => '3009998877',
                'acepta' => '',
            ]))
            ->assertRedirect(route('cotizacion.ver'))
            ->assertSessionHasErrors('acepta');

        $this->actingAs($cliente)
            ->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee('value="3009998877"', false);
    }

    /**
     * El defecto crítico del sitio anterior: el botón de la ficha agregaba
     * siempre el producto 18853, sin importar cuál estuviera viendo el cliente.
     */
    public function test_agregar_desde_la_ficha_agrega_ese_producto_y_no_otro(): void
    {
        $this->agregar($this->filtroAveo);

        $this->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee($this->filtroAveo->nombre)
            ->assertDontSee($this->pastillasAveo->nombre)
            ->assertDontSee($this->bandasLogan->nombre);
    }

    public function test_agregar_dos_veces_suma_la_cantidad_sin_duplicar_la_linea(): void
    {
        $this->agregar($this->filtroAveo, 2);
        $this->agregar($this->filtroAveo, 3);

        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $items = Cotizacion::first()->items;
        $this->assertCount(1, $items);
        $this->assertSame(5, $items->first()->cantidad);
    }

    /**
     * Desde el carrusel de la portada se agrega sin recargar. Recargar devolvía
     * al visitante al inicio de la página, siete pantallas por encima de donde
     * estaba mirando.
     */
    public function test_agregar_sin_recargar_responde_el_total_en_json(): void
    {
        $this->postJson(route('cotizacion.agregar', $this->filtroAveo), ['cantidad' => 2])
            ->assertOk()
            ->assertJson(['total' => 2])
            ->assertJsonPath('mensaje', "Agregamos «{$this->filtroAveo->nombre}» a tu cotización.");

        $this->postJson(route('cotizacion.agregar', $this->pastillasAveo))
            ->assertOk()
            ->assertJson(['total' => 3]);
    }

    /**
     * Borrar una solicitud no puede reciclar su número: el consecutivo es lo
     * que el cliente tiene a mano cuando lo llaman, y dos solicitudes distintas
     * con el mismo número dejan al asesor sin saber cuál es cuál.
     */
    public function test_el_consecutivo_no_se_reutiliza_al_borrar(): void
    {
        $this->agregar($this->filtroAveo);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $this->agregar($this->pastillasAveo);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $this->assertSame(
            ['SA-'.now()->year.'-00001', 'SA-'.now()->year.'-00002'],
            Cotizacion::orderBy('id')->pluck('consecutivo')->all()
        );

        // Se borra la PRIMERA y se manda otra. Contando filas, la nueva salía
        // con el 00002 —el mismo que ya tenía una solicitud viva— y el índice
        // único reventaba en la cara del cliente.
        Cotizacion::orderBy('id')->first()->delete();

        $this->agregar($this->bandasLogan);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $this->assertSame(
            ['SA-'.now()->year.'-00002', 'SA-'.now()->year.'-00003'],
            Cotizacion::orderBy('id')->pluck('consecutivo')->all()
        );
    }

    /** Sin JavaScript el mismo botón tiene que seguir funcionando a la antigua. */
    public function test_agregar_sin_javascript_sigue_redirigiendo(): void
    {
        $this->from(route('inicio'))
            ->post(route('cotizacion.agregar', $this->filtroAveo))
            ->assertRedirect(route('inicio'))
            ->assertSessionHas('mensaje');
    }

    public function test_se_pueden_cotizar_varios_vehiculos_en_una_solicitud(): void
    {
        $this->agregar($this->pastillasAveo);
        $this->agregar($this->bandasLogan);

        $this->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee('CHEVROLET AVEO 1600 (2006-2013)')
            ->assertSee('RENAULT LOGAN 1600 (2010-2015)');
    }

    public function test_cambiar_de_vehiculo_no_borra_lo_ya_agregado(): void
    {
        $this->agregar($this->pastillasAveo);

        $this->post(route('vehiculo.guardar'), ['vehiculo_id' => $this->bandasLogan->vehiculo_id]);
        $this->agregar($this->bandasLogan);

        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $this->assertCount(2, Cotizacion::first()->items);
    }

    public function test_quitar_un_vehiculo_deja_intactos_los_demas(): void
    {
        $this->agregar($this->pastillasAveo);
        $this->agregar($this->filtroAveo);
        $this->agregar($this->bandasLogan);

        $this->post(route('cotizacion.quitar-vehiculo', $this->pastillasAveo->vehiculo));

        $this->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee($this->bandasLogan->nombre)
            ->assertDontSee($this->pastillasAveo->nombre)
            ->assertDontSee($this->filtroAveo->nombre);
    }

    public function test_la_cotizacion_no_muestra_precios(): void
    {
        $this->agregar($this->pastillasAveo);

        $contenido = $this->get(route('cotizacion.ver'))->assertOk()->getContent();

        // Un `$` suelto es Alpine; lo prohibido es una cifra de dinero.
        $this->assertDoesNotMatchRegularExpression('/\$\s?[\d.,]+/', $contenido);
        $this->assertStringNotContainsString('COP', $contenido);
    }

    public function test_enviar_guarda_la_solicitud_y_manda_los_correos(): void
    {
        $this->agregar($this->pastillasAveo, 2);
        $this->agregar($this->bandasLogan);

        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto(['notas' => 'Urgente']))
            ->assertRedirect(route('cotizacion.enviada'));

        $cotizacion = Cotizacion::with('items')->firstOrFail();

        $this->assertSame('Julián', $cotizacion->nombre);
        $this->assertSame('Urgente', $cotizacion->notas);
        $this->assertMatchesRegularExpression('/^SA-\d{4}-\d{5}$/', $cotizacion->consecutivo);
        $this->assertCount(2, $cotizacion->items);
        $this->assertNotNull($cotizacion->correo_enviado_en);

        // Encolados, no enviados en la petición: el cliente no tiene que
        // esperar a que conteste el servidor de correo para ver su confirmación.
        Mail::assertSent(SolicitudCotizacion::class, fn ($m) => $m->hasTo('cotizaciones@suralpine.com'));
        Mail::assertSent(ConfirmacionCotizacion::class, fn ($m) => $m->hasTo('julian@taller.co'));
    }

    public function test_el_item_recuerda_el_vehiculo_con_el_que_se_pidio(): void
    {
        $this->agregar($this->bandasLogan);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $item = Cotizacion::first()->items->first();

        $this->assertSame('RENAULT LOGAN 1600 (2010-2015)', $item->vehiculo_nombre);
        $this->assertSame($this->bandasLogan->nombre, $item->producto_nombre);
    }

    public function test_enviar_vacia_el_carrito(): void
    {
        $this->agregar($this->pastillasAveo);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $this->get(route('cotizacion.ver'))
            ->assertOk()
            ->assertSee('Todavía no has agregado repuestos');
    }

    public function test_sin_autorizacion_de_datos_no_se_envia(): void
    {
        $this->agregar($this->pastillasAveo);

        $this->actingAs($this->cliente())
            ->from(route('cotizacion.ver'))
            ->post(route('cotizacion.enviar'), $this->datosContacto(['acepta' => null]))
            ->assertSessionHasErrors('acepta');

        $this->assertSame(0, Cotizacion::count());
        Mail::assertNothingSent();
    }

    public function test_el_campo_trampa_descarta_los_envios_automaticos(): void
    {
        $this->agregar($this->pastillasAveo);

        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto(['sitio_web' => 'http://spam.example']))
            ->assertRedirect(route('cotizacion.enviada'));

        $this->assertSame(0, Cotizacion::count());
        Mail::assertNothingSent();
    }

    /**
     * Si el correo no sale, la solicitud no se pierde: queda marcada para que
     * el panel la muestre y alguien la reenvíe.
     */
    public function test_si_el_correo_falla_la_solicitud_queda_registrada_con_el_error(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Buzón lleno'));

        $this->agregar($this->pastillasAveo);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto());

        $cotizacion = Cotizacion::firstOrFail();

        $this->assertNull($cotizacion->correo_enviado_en);
        $this->assertStringContainsString('Buzón lleno', $cotizacion->error_envio);
        $this->assertCount(1, $cotizacion->items);
        $this->assertTrue(Cotizacion::sinEnviar()->exists());
    }

    /**
     * `Mail::fake()` no arma la plantilla, así que un correo roto pasa todas
     * las demás pruebas sin que nadie lo note. Este lo renderiza de verdad.
     */
    public function test_los_correos_se_arman_sin_reventar(): void
    {
        $this->agregar($this->pastillasAveo, 2);
        $this->agregar($this->bandasLogan);
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto(['notas' => 'Urgente']));

        $cotizacion = Cotizacion::with('items')->firstOrFail();

        $paraElEquipo = (new SolicitudCotizacion($cotizacion))->render();
        $this->assertStringContainsString($cotizacion->consecutivo, $paraElEquipo);
        $this->assertStringContainsString('CHEVROLET AVEO 1600 (2006-2013)', $paraElEquipo);
        $this->assertStringContainsString('RENAULT LOGAN 1600 (2010-2015)', $paraElEquipo);
        $this->assertStringContainsString('Urgente', $paraElEquipo);

        $paraElCliente = (new ConfirmacionCotizacion($cotizacion))->render();
        $this->assertStringContainsString($cotizacion->consecutivo, $paraElCliente);
        $this->assertStringContainsString('3134223861', $paraElCliente);

        // Ni un precio se escapa al correo.
        $this->assertDoesNotMatchRegularExpression('/\$\s?[\d.,]+/', $paraElEquipo);
        $this->assertDoesNotMatchRegularExpression('/\$\s?[\d.,]+/', $paraElCliente);
    }

    public function test_no_se_envia_una_cotizacion_vacia(): void
    {
        $this->actingAs($this->cliente())->post(route('cotizacion.enviar'), $this->datosContacto())
            ->assertRedirect(route('cotizacion.ver'));

        $this->assertSame(0, Cotizacion::count());
    }
}
