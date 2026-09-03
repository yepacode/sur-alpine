<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Nota;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use App\Services\Cotizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Lo que quedaba sin una sola prueba, por orden de riesgo.
 *
 * No es cobertura por el número: son las ramas donde un cambio silencioso
 * hace daño y nadie se entera. La más clara es el límite de peticiones de los
 * dos únicos formularios que aceptan datos sin sesión: se podía borrar
 * `->middleware('throttle:5,1')` de `routes/web.php` y la suite entera seguía
 * verde, porque las dos clases que los prueban desactivan ese middleware en su
 * `setUp`.
 */
class CoberturaPendienteTest extends TestCase
{
    use RefreshDatabase;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);
        $categoria = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $tipo = TipoParte::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Pastillas', 'slug' => 'pastillas',
        ]);

        $this->producto = Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Pastillas AVEO', 'slug' => 'pastillas-aveo', 'publicado' => true,
        ]);
    }

    // ── El límite de los dos formularios públicos ───────────────────────────

    /**
     * `/contactenos` acepta datos de cualquiera sin sesión. Sin límite, un
     * robot llena la bandeja del mostrador en una tarde.
     */
    public function test_el_formulario_de_contacto_tiene_limite(): void
    {
        $datos = [
            'nombre' => 'Julián', 'email' => 'julian@taller.co',
            'telefono' => '3134223861', 'mensaje' => 'Necesito pastillas.', 'acepta' => 1,
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contacto.enviar'), $datos)->assertRedirect();
        }

        $this->post(route('contacto.enviar'), $datos)->assertStatus(429);
    }

    /** Y el del boletín, que está en el pie de TODAS las páginas. */
    public function test_el_formulario_del_boletin_tiene_limite(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('suscripcion'), ['correo' => "alguien{$i}@ejemplo.co"])
                ->assertRedirect();
        }

        $this->post(route('suscripcion'), ['correo' => 'uno.mas@ejemplo.co'])
            ->assertStatus(429);
    }

    // ── El carrito: editar cantidades ───────────────────────────────────────

    /** Poner cero borra la línea: es cómo se quita algo sin buscar el botón. */
    public function test_cantidad_cero_quita_la_linea(): void
    {
        session()->put('vehiculo_activo', $this->producto->vehiculo_id);
        $this->post(route('cotizacion.agregar', $this->producto), ['cantidad' => 3]);
        $this->assertSame(3, app(Cotizador::class)->totalItems());

        $this->post(route('cotizacion.actualizar', $this->producto), ['cantidad' => 0]);

        $this->assertSame(0, app(Cotizador::class)->totalItems());
    }

    /**
     * Una cantidad imposible se RECHAZA con un aviso, no se recorta callando.
     *
     * Recortar 100000 a 99 dejaba a alguien convencido de que pidió cien mil
     * unidades. Y el 0, que sí es una orden válida —«quítalo»—, no puede ser
     * la misma respuesta que un -5, que es un error de dedo. La cantidad se
     * queda como estaba y el formulario dice por qué.
     */
    public static function cantidadesImposibles(): array
    {
        return [
            'negativa' => [-5],
            'desmedida' => [100000],
            'con letras' => ['dos'],
        ];
    }

    #[DataProvider('cantidadesImposibles')]
    public function test_una_cantidad_imposible_se_rechaza_sin_tocar_el_carrito(mixed $cantidad): void
    {
        session()->put('vehiculo_activo', $this->producto->vehiculo_id);
        $this->post(route('cotizacion.agregar', $this->producto));

        $this->post(route('cotizacion.actualizar', $this->producto), ['cantidad' => $cantidad])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(1, app(Cotizador::class)->totalItems());
    }

    public function test_vaciar_deja_el_carrito_en_cero(): void
    {
        session()->put('vehiculo_activo', $this->producto->vehiculo_id);
        $this->post(route('cotizacion.agregar', $this->producto), ['cantidad' => 4]);

        $this->post(route('cotizacion.vaciar'))->assertRedirect();

        $this->assertSame(0, app(Cotizador::class)->totalItems());
    }

    // ── Rutas destructivas ──────────────────────────────────────────────────

    /** Borrar una noticia: sólo el equipo, y se lleva la nota. */
    public function test_borrar_una_noticia_es_cosa_del_equipo(): void
    {
        $nota = Nota::create([
            'titulo' => 'Kit de distribución', 'slug' => 'kit-de-distribucion',
            'categoria' => 'Mantenimiento', 'resumen' => 'Qué es', 'cuerpo' => 'Texto',
            'publicada' => true, 'publicada_en' => now()->subDay(),
        ]);

        $this->actingAs($this->usuario(Rol::Cliente))
            ->post(route('panel.notas.borrar', $nota))->assertForbidden();

        $this->assertNotNull($nota->fresh());

        $this->entrarComo($this->usuario(Rol::Admin))
            ->post(route('panel.notas.borrar', $nota))->assertRedirect();

        $this->assertNull($nota->fresh());
    }

    /** Quitar un carro de la cuenta: sólo el dueño, y sólo el suyo. */
    public function test_quitar_un_carro_es_cosa_del_dueno(): void
    {
        $vehiculo = Vehiculo::first();
        $ana = $this->usuario(Rol::Cliente, ['email' => 'ana@taller.co']);
        $beto = $this->usuario(Rol::Cliente, ['email' => 'beto@taller.co']);

        $ana->vehiculosGuardados()->attach($vehiculo->id, ['placa' => 'ABC123']);

        // Beto no puede quitarle el carro a Ana.
        $this->actingAs($beto)->post(route('cuenta.vehiculo.quitar', $vehiculo))->assertRedirect();
        $this->assertSame(1, $ana->vehiculosGuardados()->count());

        $this->entrarComo($ana)->post(route('cuenta.vehiculo.quitar', $vehiculo))->assertRedirect();
        $this->assertSame(0, $ana->vehiculosGuardados()->count());
    }

    // ── Editar los datos de un vehículo del catálogo ────────────────────────

    /**
     * Corregir un vehículo recalcula su slug —que está en URLs indexadas y en
     * el sitemap— y tira las cachés del catálogo. Cero pruebas hasta ahora.
     */
    public function test_corregir_un_vehiculo_recalcula_su_url(): void
    {
        $vehiculo = Vehiculo::first();

        $this->actingAs($this->usuario(Rol::Admin))
            ->post(route('panel.catalogo.editar-datos', $vehiculo), [
                'marca' => 'CHEVROLET',
                'modelo' => 'AVEO GTI',
                'cilindraje' => '1600',
                'anio_inicio' => 2006,
                'anio_fin' => 2013,
            ])->assertRedirect();

        $vehiculo->refresh();

        $this->assertStringContainsString('aveo-gti', $vehiculo->slug);
        $this->assertSame('CHEVROLET AVEO GTI 1600 (2006-2013)', $vehiculo->nombre_completo);
    }
}
