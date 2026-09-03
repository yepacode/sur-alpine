<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use App\Services\Cotizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sin carro, no se agrega, y el modal del vehículo es obligatorio.
 *
 * Peticion literal del cliente, repetida dos veces: «si o si necesitamos el
 * vehiculo, si o si al filtro». Los rastreadores (Googlebot, Bingbot,
 * WhatsApp…) se exentan para que Google siga indexando el catalogo y las
 * vistas previas compartidas por WhatsApp no salgan como una captura del
 * modal. La logica del user agent está en `es_rastreador()`.
 */
class ExigeVehiculoTest extends TestCase
{
    use RefreshDatabase;

    private Producto $pieza;

    protected function setUp(): void
    {
        parent::setUp();

        $m = Marca::create(['nombre' => 'KIA', 'slug' => 'kia']);
        $mo = Modelo::create(['marca_id' => $m->id, 'nombre' => 'RIO', 'slug' => 'rio']);
        $v = Vehiculo::create([
            'modelo_id' => $mo->id, 'cilindraje' => '1500',
            'slug' => 'kia-rio-1500', 'anio_inicio' => 2002, 'anio_fin' => 2005,
        ]);
        $cat = Categoria::create(['nombre' => 'Refrigeración', 'slug' => 'refrigeracion']);
        $tp = TipoParte::create(['categoria_id' => $cat->id, 'nombre' => 'Radiador', 'slug' => 'radiador']);

        $this->pieza = Producto::create([
            'vehiculo_id' => $v->id, 'tipo_parte_id' => $tp->id,
            'nombre' => 'Radiador RIO 1500 KIA', 'slug' => 'radiador-rio-1500-kia',
            'publicado' => true,
        ]);
    }

    public function test_agregar_sin_vehiculo_por_json_devuelve_409(): void
    {
        $this->postJson(route('cotizacion.agregar', $this->pieza))
            ->assertStatus(409)
            ->assertJsonPath('requiereVehiculo', true);

        $this->assertSame(0, app(Cotizador::class)->totalItems(),
            'El carrito no puede tener nada agregado sin haber elegido carro.');
    }

    public function test_agregar_sin_vehiculo_sin_js_marca_la_sesion(): void
    {
        $this->from(route('inicio'))
            ->post(route('cotizacion.agregar', $this->pieza))
            ->assertRedirect(route('inicio'))
            ->assertSessionHas('exige-vehiculo', true);

        $this->assertSame(0, app(Cotizador::class)->totalItems());
    }

    public function test_con_vehiculo_agregar_funciona_normal(): void
    {
        session()->put('vehiculo_activo', $this->pieza->vehiculo_id);

        $this->postJson(route('cotizacion.agregar', $this->pieza))
            ->assertOk()
            ->assertJson(['total' => 1]);
    }

    /**
     * Cataloge y ficha disparan el modal obligatorio para personas.
     *
     * Se mira que el JS del disparador este presente. Es una senal fragil pero
     * suficiente para que un cambio accidental que quite el disparador salga
     * fallando aqui en vez de en produccion.
     */
    public function test_catalogo_dispara_el_modal_obligatorio_para_personas(): void
    {
        $this->get('/repuestos')
            ->assertOk()
            ->assertSee("dispatch('abrir-buscador-obligatorio')", false);
    }

    public function test_ficha_dispara_el_modal_obligatorio_para_personas(): void
    {
        $this->get('/repuesto/'.$this->pieza->slug)
            ->assertOk()
            ->assertSee("dispatch('abrir-buscador-obligatorio')", false);
    }

    /**
     * Con vehiculo elegido, ni el catalogo ni la ficha vuelven a insistir.
     * Ya se contesto a la pregunta.
     */
    public function test_con_vehiculo_no_se_dispara_el_modal(): void
    {
        session()->put('vehiculo_activo', $this->pieza->vehiculo_id);

        $this->get('/repuestos')
            ->assertOk()
            ->assertDontSee("dispatch('abrir-buscador-obligatorio')", false);

        $this->get('/repuesto/'.$this->pieza->slug)
            ->assertOk()
            ->assertDontSee("dispatch('abrir-buscador-obligatorio')", false);
    }

    /**
     * Googlebot ve el catalogo y la ficha sin traba.
     *
     * Sin esta exencion, el sitio queda sin indexar y las vistas previas de
     * WhatsApp salen como una captura del modal.
     */
    public function test_googlebot_ve_el_catalogo_como_siempre(): void
    {
        $ua = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

        $this->withHeaders(['User-Agent' => $ua])
            ->get('/repuestos')
            ->assertOk()
            ->assertDontSee("dispatch('abrir-buscador-obligatorio')", false);

        $this->withHeaders(['User-Agent' => $ua])
            ->get('/repuesto/'.$this->pieza->slug)
            ->assertOk()
            ->assertDontSee("dispatch('abrir-buscador-obligatorio')", false);
    }

    public function test_whatsapp_ve_la_ficha_para_generar_vista_previa(): void
    {
        $this->withHeaders(['User-Agent' => 'WhatsApp/2.23'])
            ->get('/repuesto/'.$this->pieza->slug)
            ->assertOk()
            ->assertDontSee("dispatch('abrir-buscador-obligatorio')", false);
    }
}
