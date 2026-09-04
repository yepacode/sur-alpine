<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use App\Services\VehiculoActivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * El nombre del vehículo dice el año elegido, no el rango entero.
 *
 * El cliente eligió 1976 y el sitio decía «FIAT 128 1500 (1976-1982)». Le daba
 * miedo pensar que se le mostraban piezas de 1977 hasta 1982 sin haberlas
 * pedido. En realidad el rango es del vehículo en la base —hay un registro que
 * cubre esos siete años— y el filtro solo comprueba que el año elegido caiga
 * dentro. Para el visitante, si eligió 1976, todo debe decir 1976.
 *
 * El `nombre_completo` (con rango) se conserva para lo que va al correo, al
 * schema.org y al panel: ahí sí importa el rango.
 */
class NombreConAnioElegidoTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $fiat;

    private Producto $pieza;

    protected function setUp(): void
    {
        parent::setUp();

        $m = Marca::create(['nombre' => 'FIAT', 'slug' => 'fiat']);
        $mo = Modelo::create(['marca_id' => $m->id, 'nombre' => '128', 'slug' => '128']);
        $this->fiat = Vehiculo::create([
            'modelo_id' => $mo->id, 'cilindraje' => '1500',
            'slug' => 'fiat-128-1500', 'anio_inicio' => 1976, 'anio_fin' => 1982,
        ]);
        $c = Categoria::create(['nombre' => 'Motor', 'slug' => 'motor']);
        $tp = TipoParte::create(['categoria_id' => $c->id, 'nombre' => 'Filtro', 'slug' => 'filtro']);
        $this->pieza = Producto::create([
            'vehiculo_id' => $this->fiat->id, 'tipo_parte_id' => $tp->id,
            'nombre' => 'Filtro 128 1500 FIAT', 'slug' => 'filtro-128-1500-fiat',
            'publicado' => true,
        ]);
    }

    public function test_con_anio_elegido_el_nombre_sale_con_ese_anio(): void
    {
        $this->assertSame('FIAT 128 1500 (1976)', $this->fiat->nombreParaVisitante(1976));
        $this->assertSame('FIAT 128 1500 (1980)', $this->fiat->nombreParaVisitante(1980));
        $this->assertSame('FIAT 128 1500 (1982)', $this->fiat->nombreParaVisitante(1982));
    }

    public function test_sin_anio_elegido_cae_al_rango_completo(): void
    {
        $this->assertSame('FIAT 128 1500 (1976-1982)', $this->fiat->nombreParaVisitante());
        $this->assertSame('FIAT 128 1500 (1976-1982)', $this->fiat->nombreParaVisitante(null));
    }

    public function test_un_anio_fuera_de_rango_tambien_cae_al_rango(): void
    {
        // Alguien que juega con la URL o toca la sesion: preferimos ensenar
        // el rango completo antes que un ano que no cuadra con el vehiculo.
        $this->assertSame('FIAT 128 1500 (1976-1982)', $this->fiat->nombreParaVisitante(1970));
        $this->assertSame('FIAT 128 1500 (1976-1982)', $this->fiat->nombreParaVisitante(2000));
    }

    public function test_el_nombre_completo_conserva_el_rango(): void
    {
        // No queremos tocar `nombre_completo`, que se usa en correos, en el
        // panel y en JSON-LD. Ahi si importa saber el rango entero.
        $this->assertSame('FIAT 128 1500 (1976-1982)', $this->fiat->nombre_completo);
    }

    public function test_el_servicio_guarda_y_devuelve_el_anio(): void
    {
        $this->withSession([
            'vehiculo_activo' => $this->fiat->id,
            'vehiculo_activo_anio' => 1976,
        ])->get('/');

        $this->assertSame(1976, app(VehiculoActivo::class)->anio());
        $this->assertSame($this->fiat->id, app(VehiculoActivo::class)->id());
    }

    public function test_un_anio_fuera_de_rango_no_se_guarda(): void
    {
        $this->post(route('vehiculo.guardar'), [
            'vehiculo_id' => $this->fiat->id,
            'anio' => 1970,
        ]);

        // Reencendemos la sesion en una nueva peticion para leerla ya guardada.
        $this->get('/')->assertOk();

        $this->assertNull(app(VehiculoActivo::class)->anio(),
            'Un ano fuera del rango del vehiculo se descarta; olvidarlo evita mostrar rotulos incoherentes.');
    }

    public function test_olvidar_borra_tambien_el_anio(): void
    {
        $this->post(route('vehiculo.guardar'), [
            'vehiculo_id' => $this->fiat->id,
            'anio' => 1976,
        ]);

        $this->post(route('vehiculo.olvidar'));

        $this->get('/')->assertOk();

        $this->assertNull(app(VehiculoActivo::class)->anio());
        $this->assertNull(app(VehiculoActivo::class)->id());
    }

    public function test_la_h1_del_catalogo_dice_el_anio_elegido(): void
    {
        session()->put('vehiculo_activo', $this->fiat->id);
        session()->put('vehiculo_activo_anio', 1976);

        $this->get('/repuestos')
            ->assertOk()
            ->assertSee('FIAT 128 1500 (1976)')
            ->assertDontSee('(1976-1982)');
    }

    public function test_el_boton_guardar_del_buscador_manda_tambien_el_anio(): void
    {
        $this->post(route('vehiculo.guardar'), [
            'vehiculo_id' => $this->fiat->id,
            'anio' => 1978,
        ])->assertRedirect(route('catalogo'));

        $this->assertSame(1978, app(VehiculoActivo::class)->anio());
    }

    /**
     * El bloque «Mi cotizacion» agrupaba por `nombre_completo` (con rango).
     *
     * Captura del cliente: eligio 1994 para un MAZDA 323 1300 CARB, la cabecera
     * decia «(1994)» y adentro el bloque de la cotizacion decia «(1986-1998)».
     * Confusion garantizada. Ahora se agrupa por el nombre CON el ano que la
     * persona eligio al agregar cada pieza.
     */
    public function test_el_carrito_agrupa_por_vehiculo_con_el_anio(): void
    {
        $this->post(route('vehiculo.guardar'), [
            'vehiculo_id' => $this->fiat->id,
            'anio' => 1978,
        ]);

        $this->post(route('cotizacion.agregar', $this->pieza), ['cantidad' => 1]);

        $this->get('/mi-cotizacion')
            ->assertOk()
            ->assertSee('FIAT 128 1500 (1978)')
            ->assertDontSee('FIAT 128 1500 (1976-1982)');
    }
}
