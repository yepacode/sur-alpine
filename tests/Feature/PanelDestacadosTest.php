<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel: elegir los productos destacados a mano.
 *
 * Peticion del cliente el 7-sep: poder marcar cuales salen en el carrusel de
 * la portada y darles su propio orden. Antes era 100% automatico (por veces
 * cotizado) y no habia manera de empujar una promo o retirar una pieza vieja.
 *
 * Con destacados manuales seleccionados, la portada muestra esos. Sin ninguno,
 * cae al automatico de siempre: el carrusel nunca sale vacio.
 */
class PanelDestacadosTest extends TestCase
{
    use RefreshDatabase;

    private Producto $a;

    private Producto $b;

    private Producto $c;

    protected function setUp(): void
    {
        parent::setUp();

        $m = Marca::create(['nombre' => 'KIA', 'slug' => 'kia']);
        $mo = Modelo::create(['marca_id' => $m->id, 'nombre' => 'RIO', 'slug' => 'rio']);
        $v = Vehiculo::create([
            'modelo_id' => $mo->id, 'cilindraje' => '1500',
            'slug' => 'kia-rio-1500', 'anio_inicio' => 2002, 'anio_fin' => 2005,
        ]);
        $cat = Categoria::create(['nombre' => 'Motor', 'slug' => 'motor']);
        $tps = [
            'a' => TipoParte::create(['categoria_id' => $cat->id, 'nombre' => 'Filtro A', 'slug' => 'filtro-a']),
            'b' => TipoParte::create(['categoria_id' => $cat->id, 'nombre' => 'Filtro B', 'slug' => 'filtro-b']),
            'c' => TipoParte::create(['categoria_id' => $cat->id, 'nombre' => 'Filtro C', 'slug' => 'filtro-c']),
        ];

        $crear = fn (string $letra, string $nombre, string $slug) => Producto::create([
            'vehiculo_id' => $v->id, 'tipo_parte_id' => $tps[$letra]->id,
            'nombre' => $nombre, 'slug' => $slug, 'publicado' => true,
        ]);

        $this->a = $crear('a', 'Filtro A RIO 1500 KIA', 'filtro-a-rio-1500-kia');
        $this->b = $crear('b', 'Filtro B RIO 1500 KIA', 'filtro-b-rio-1500-kia');
        $this->c = $crear('c', 'Filtro C RIO 1500 KIA', 'filtro-c-rio-1500-kia');
    }

    private ?\App\Models\User $admin = null;

    private function admin(): \App\Models\User
    {
        return $this->admin ??= $this->usuario(Rol::Admin);
    }

    public function test_la_pantalla_se_abre_para_un_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(route('panel.destacados'))
            ->assertOk()
            ->assertSee('Productos destacados')
            ->assertSee('Todavía no hay destacados elegidos a mano', false);
    }

    public function test_solo_un_admin_puede_entrar(): void
    {
        $cliente = $this->usuario(Rol::Cliente);

        $this->actingAs($cliente)
            ->get(route('panel.destacados'))
            ->assertForbidden();
    }

    public function test_agregar_marca_el_producto_al_final(): void
    {
        $this->actingAs($this->admin())
            ->post(route('panel.destacados.agregar'), ['producto_id' => $this->a->id])
            ->assertRedirect(route('panel.destacados'))
            ->assertSessionHas('mensaje');

        $this->assertSame(1, $this->a->fresh()->destacado_orden);

        $this->actingAs($this->admin())
            ->post(route('panel.destacados.agregar'), ['producto_id' => $this->b->id]);

        $this->assertSame(2, $this->b->fresh()->destacado_orden,
            'El segundo agregado va al final, no al principio.');
    }

    public function test_agregar_dos_veces_no_lo_manda_al_final(): void
    {
        $this->actingAs($this->admin())
            ->post(route('panel.destacados.agregar'), ['producto_id' => $this->a->id]);
        $this->actingAs($this->admin())
            ->post(route('panel.destacados.agregar'), ['producto_id' => $this->b->id]);

        // Volver a agregar `a` no debe cambiar su orden ni empujarlo.
        $this->actingAs($this->admin())
            ->post(route('panel.destacados.agregar'), ['producto_id' => $this->a->id]);

        $this->assertSame(1, $this->a->fresh()->destacado_orden);
        $this->assertSame(2, $this->b->fresh()->destacado_orden);
    }

    public function test_quitar_pone_el_orden_a_null(): void
    {
        $this->a->update(['destacado_orden' => 1]);

        $this->actingAs($this->admin())
            ->post(route('panel.destacados.quitar', $this->a))
            ->assertRedirect(route('panel.destacados'));

        $this->assertNull($this->a->fresh()->destacado_orden);
    }

    public function test_guardar_orden_actualiza_los_numeros(): void
    {
        $this->a->update(['destacado_orden' => 1]);
        $this->b->update(['destacado_orden' => 2]);
        $this->c->update(['destacado_orden' => 3]);

        // El admin escribe: A=3, B=1, C=2 en la tabla.
        $this->actingAs($this->admin())
            ->post(route('panel.destacados.orden'), [
                'orden' => [
                    $this->a->id => 3,
                    $this->b->id => 1,
                    $this->c->id => 2,
                ],
            ])
            ->assertRedirect(route('panel.destacados'));

        $this->assertSame(3, $this->a->fresh()->destacado_orden);
        $this->assertSame(1, $this->b->fresh()->destacado_orden);
        $this->assertSame(2, $this->c->fresh()->destacado_orden);
    }

    public function test_guardar_orden_ignora_ids_que_no_son_destacados(): void
    {
        $this->a->update(['destacado_orden' => 1]);
        // `b` NO esta destacado; no debe entrar aunque venga en el form.

        $this->actingAs($this->admin())
            ->post(route('panel.destacados.orden'), [
                'orden' => [
                    $this->b->id => 5,
                    $this->a->id => 7,
                ],
            ])
            ->assertRedirect(route('panel.destacados'));

        $this->assertNull($this->b->fresh()->destacado_orden);
        $this->assertSame(7, $this->a->fresh()->destacado_orden);
    }

    public function test_la_portada_muestra_los_destacados_a_mano_en_su_orden(): void
    {
        // c va primero, a segundo. b no esta destacado.
        $this->c->update(['destacado_orden' => 1]);
        $this->a->update(['destacado_orden' => 2]);

        $html = $this->get('/')->assertOk()->getContent();

        $posC = strpos($html, 'Filtro C RIO 1500 KIA');
        $posA = strpos($html, 'Filtro A RIO 1500 KIA');
        $posB = strpos($html, 'Filtro B RIO 1500 KIA');

        $this->assertNotFalse($posC, 'El primero manual debe salir.');
        $this->assertNotFalse($posA, 'El segundo manual debe salir.');
        $this->assertLessThan($posA, $posC, 'C debe aparecer antes que A.');

        // b no esta destacado: no debe salir cuando hay manuales.
        $this->assertFalse($posB, 'Un producto no destacado no debe colarse.');
    }

    public function test_sin_destacados_manuales_cae_al_automatico(): void
    {
        // Ninguno tiene `destacado_orden`. El carrusel cae al automatico.
        $html = $this->get('/')->assertOk()->getContent();

        // Al menos uno de los tres tiene que salir por el fallback.
        $this->assertTrue(
            str_contains($html, 'Filtro A') || str_contains($html, 'Filtro B') || str_contains($html, 'Filtro C'),
            'Sin destacados manuales, el sitio cae al automatico por veces cotizado.'
        );
    }

    public function test_buscador_devuelve_solo_no_destacados(): void
    {
        $this->a->update(['destacado_orden' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('panel.destacados', ['q' => 'Filtro']))
            ->assertOk()
            ->getContent();

        // b y c deben salir en resultados; a NO (ya esta destacado).
        $this->assertStringContainsString('Filtro B RIO 1500 KIA', $html);
        $this->assertStringContainsString('Filtro C RIO 1500 KIA', $html);
        // `a` sale en la lista de arriba («Lo que sale ahora») pero NO en resultados.
        $this->assertSame(1, substr_count($html, 'Filtro A RIO 1500 KIA<'),
            'El destacado ya activo no debe aparecer también en resultados del buscador.');
    }
}
