<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Cotizacion;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\Cotizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * «Mis cotizaciones».
 *
 * Faltaba entero: el cliente enviaba su solicitud y del lado de él
 * desaparecía. Aquí se cubre que la vea, que no vea la de otro, y que
 * «volver a pedir lo mismo» diga la verdad cuando una pieza ya no está.
 */
class MisCotizacionesTest extends TestCase
{
    use RefreshDatabase;

    private Producto $pastillas;

    private Producto $filtro;

    protected function setUp(): void
    {
        parent::setUp();

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);

        $categoria = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);

        $this->pastillas = $this->crearProducto($categoria, 'Pastillas Freno Delanteras', $vehiculo);
        $this->filtro = $this->crearProducto($categoria, 'Filtro Aceite', $vehiculo);
    }

    private function crearProducto(Categoria $categoria, string $parte, Vehiculo $vehiculo): Producto
    {
        $tipo = TipoParte::firstOrCreate(
            ['slug' => Str::slug($parte)],
            ['categoria_id' => $categoria->id, 'nombre' => $parte]
        );

        return Producto::create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_parte_id' => $tipo->id,
            'nombre' => $parte.' '.$vehiculo->nombre_completo,
            'slug' => Str::slug($parte.' '.$vehiculo->slug),
            'publicado' => true,
        ]);
    }

    private function cliente(array $atributos = []): User
    {
        return User::forceCreate($atributos + [
            'name' => 'Julián Mecánico',
            'email' => 'julian@taller.co',
            'telefono' => '3134223861',
            'password' => 'secreto123',
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);
    }

    private function cotizacionDe(User $usuario, array $productos = null): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'consecutivo' => Cotizacion::siguienteConsecutivo(),
            'user_id' => $usuario->id,
            'nombre' => 'Julián',
            'apellidos' => 'Mecánico',
            'telefono' => '3134223861',
            'email' => $usuario->email,
            'notas' => 'Urgente para el sábado',
            'correo_enviado_en' => now(),
        ]);

        foreach ($productos ?? [$this->pastillas, $this->filtro] as $producto) {
            $cotizacion->items()->create([
                'producto_id' => $producto->id,
                'vehiculo_id' => $producto->vehiculo_id,
                'producto_nombre' => $producto->nombre,
                'vehiculo_nombre' => 'CHEVROLET AVEO 1600 (2006-2013)',
                'cantidad' => 2,
            ]);
        }

        return $cotizacion;
    }

    public function test_el_listado_trae_sus_cotizaciones(): void
    {
        $usuario = $this->cliente();
        $cotizacion = $this->cotizacionDe($usuario);

        $this->actingAs($usuario)->get(route('cuenta.cotizaciones'))
            ->assertOk()
            ->assertSee($cotizacion->consecutivo)
            ->assertSee('2 repuestos');
    }

    public function test_el_detalle_muestra_los_repuestos_y_los_datos(): void
    {
        $usuario = $this->cliente();
        $cotizacion = $this->cotizacionDe($usuario);

        $this->actingAs($usuario)->get(route('cuenta.cotizacion', $cotizacion))
            ->assertOk()
            ->assertSee($cotizacion->consecutivo)
            ->assertSee('Pastillas Freno Delanteras')
            ->assertSee('CHEVROLET AVEO 1600 (2006-2013)')
            ->assertSee('Urgente para el sábado');
    }

    /** Ni la lista de otro, ni su detalle, ni repitiéndola. */
    public function test_la_cotizacion_de_otro_no_se_ve_ni_se_repite(): void
    {
        $ajena = $this->cotizacionDe($this->cliente(['email' => 'otro@taller.co']));
        $usuario = $this->cliente();

        $this->actingAs($usuario)->get(route('cuenta.cotizacion', $ajena))->assertNotFound();
        $this->actingAs($usuario)->post(route('cuenta.cotizacion.repetir', $ajena))->assertNotFound();

        $this->actingAs($usuario)->get(route('cuenta.cotizaciones'))
            ->assertOk()
            ->assertDontSee($ajena->consecutivo);
    }

    public function test_sin_sesion_no_hay_historial(): void
    {
        $this->get(route('cuenta.cotizaciones'))->assertRedirect(route('acceso'));
    }

    public function test_volver_a_pedir_lo_mismo_llena_el_carrito(): void
    {
        $usuario = $this->cliente();
        $cotizacion = $this->cotizacionDe($usuario);

        $this->actingAs($usuario)->post(route('cuenta.cotizacion.repetir', $cotizacion))
            ->assertRedirect(route('cotizacion.ver'));

        $cotizador = app(Cotizador::class);

        $this->assertSame(4, $cotizador->totalItems(), 'Dos repuestos, dos unidades cada uno.');
    }

    /**
     * Lo que no se puede hacer en silencio: si una pieza se despublicó, la
     * lista vieja se sigue leyendo completa —los nombres están guardados en
     * la solicitud— pero al repetir no se agrega. Hay que decirlo.
     */
    public function test_si_una_pieza_ya_no_esta_se_avisa_cuantas_faltaron(): void
    {
        $usuario = $this->cliente();
        $cotizacion = $this->cotizacionDe($usuario);

        $this->filtro->update(['publicado' => false]);

        $this->actingAs($usuario)->post(route('cuenta.cotizacion.repetir', $cotizacion))
            ->assertRedirect(route('cotizacion.ver'))
            ->assertSessionHas('mensaje', fn (string $m) => str_contains($m, 'Agregamos 1')
                && str_contains($m, 'Uno ya no está disponible'));

        $this->assertSame(2, app(Cotizador::class)->totalItems());

        // Y el nombre sigue leyéndose en el historial: se guardó ese día.
        $this->actingAs($usuario)->get(route('cuenta.cotizacion', $cotizacion))
            ->assertOk()
            ->assertSee('Filtro Aceite');
    }

    public function test_si_no_queda_ninguna_pieza_no_se_manda_a_un_carrito_vacio(): void
    {
        $usuario = $this->cliente();
        $cotizacion = $this->cotizacionDe($usuario);

        Producto::query()->update(['publicado' => false]);

        $this->actingAs($usuario)->post(route('cuenta.cotizacion.repetir', $cotizacion))
            ->assertRedirect(route('cuenta.cotizacion', $cotizacion))
            ->assertSessionHas('mensaje', fn (string $m) => str_contains($m, 'Ninguno'));

        $this->assertSame(0, app(Cotizador::class)->totalItems());
    }

    /** Desde «Mi cuenta» se tiene que poder llegar. */
    public function test_mi_cuenta_enlaza_el_historial(): void
    {
        $this->actingAs($this->cliente())->get(route('cuenta'))
            ->assertOk()
            ->assertSee(route('cuenta.cotizaciones'));
    }
}
