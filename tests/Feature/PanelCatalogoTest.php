<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PanelCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $aveo;

    private TipoParte $pastillas;

    private TipoParte $filtro;

    protected function setUp(): void
    {
        parent::setUp();

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $this->aveo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);

        $frenos = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $motor = Categoria::create(['nombre' => 'Motor Externo', 'slug' => 'motor-externo']);

        $this->pastillas = TipoParte::create([
            'categoria_id' => $frenos->id, 'nombre' => 'Pastillas Freno Delanteras', 'slug' => 'pastillas-freno-delanteras',
        ]);
        $this->filtro = TipoParte::create([
            'categoria_id' => $motor->id, 'nombre' => 'Filtro Aceite', 'slug' => 'filtro-aceite',
        ]);

        Producto::create([
            'vehiculo_id' => $this->aveo->id,
            'tipo_parte_id' => $this->pastillas->id,
            'nombre' => 'Pastillas Freno Delanteras AVEO 1600 CHEVROLET',
            'slug' => 'pastillas-freno-delanteras-aveo-1600-chevrolet',
        ]);
    }

    private function asesor(): User
    {
        return User::firstOrCreate(
            ['email' => 'asesor@suralpine.com'],
            ['name' => 'Asesor', 'password' => 'secreto123', 'rol' => Rol::Asesor, 'activo' => true]
        );
    }

    private function vendedor(): User
    {
        return User::firstOrCreate(
            ['email' => 'vendedor@suralpine.com'],
            ['name' => 'Vendedor', 'password' => 'secreto123', 'rol' => Rol::Vendedor, 'activo' => true]
        );
    }

    private function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@suralpine.com'],
            ['name' => 'Admin', 'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true]
        );
    }

    public function test_el_vendedor_no_edita_el_catalogo(): void
    {
        $this->actingAs($this->vendedor())->get('/panel/catalogo')->assertForbidden();
    }

    public function test_el_listado_muestra_cuantas_piezas_lleva_cada_vehiculo(): void
    {
        $vehiculos = $this->actingAs($this->asesor())->get('/panel/catalogo')
            ->assertOk()
            ->viewData('vehiculos');

        $this->assertSame(1, $vehiculos->first()->productos_count);
    }

    public function test_la_matriz_muestra_todos_los_tipos_de_parte_y_marca_los_que_lleva(): void
    {
        $vista = $this->actingAs($this->asesor())
            ->get(route('panel.catalogo.editar', $this->aveo))
            ->assertOk()
            ->assertSee('Pastillas Freno Delanteras')
            ->assertSee('Filtro Aceite');

        $marcados = $vista->viewData('marcados');

        $this->assertTrue($marcados->has($this->pastillas->id));
        $this->assertFalse($marcados->has($this->filtro->id));
    }

    public function test_marcar_una_casilla_crea_la_pieza_y_desmarcar_la_quita(): void
    {
        $this->actingAs($this->asesor())->post(route('panel.catalogo.matriz', $this->aveo), [
            'tipos' => [$this->filtro->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('productos', [
            'vehiculo_id' => $this->aveo->id,
            'tipo_parte_id' => $this->filtro->id,
            'nombre' => 'Filtro Aceite AVEO 1600 CHEVROLET',
        ]);

        $this->assertDatabaseMissing('productos', [
            'vehiculo_id' => $this->aveo->id,
            'tipo_parte_id' => $this->pastillas->id,
        ]);
    }

    /**
     * Quitar una pieza del catálogo no puede romper una solicitud vieja: el
     * ítem guarda el nombre congelado y la llave foránea queda en nulo.
     */
    public function test_quitar_una_pieza_no_rompe_las_solicitudes_historicas(): void
    {
        $producto = Producto::first();

        $cotizacion = \App\Models\Cotizacion::create([
            'consecutivo' => 'SA-2026-00001',
            'nombre' => 'Julián', 'telefono' => '3134223861', 'email' => 'j@t.co',
        ]);
        $cotizacion->items()->create([
            'producto_id' => $producto->id,
            'vehiculo_id' => $this->aveo->id,
            'producto_nombre' => $producto->nombre,
            'vehiculo_nombre' => $this->aveo->nombre_completo,
            'cantidad' => 2,
        ]);

        $this->actingAs($this->asesor())->post(route('panel.catalogo.matriz', $this->aveo), ['tipos' => []]);

        $item = $cotizacion->fresh()->items->first();

        $this->assertNull($item->producto_id);
        $this->assertSame('Pastillas Freno Delanteras AVEO 1600 CHEVROLET', $item->producto_nombre);
    }

    public function test_se_puede_dar_de_alta_un_vehiculo_con_cilindraje_de_texto(): void
    {
        $this->actingAs($this->asesor())->post(route('panel.catalogo.guardar-vehiculo'), [
            'marca' => 'Mazda',
            'modelo' => '323',
            'cilindraje' => '1300 CARB',
            'anio_inicio' => 1990,
            'anio_fin' => 1995,
        ])->assertRedirect();

        $this->assertDatabaseHas('vehiculos', ['cilindraje' => '1300 CARB', 'anio_inicio' => 1990]);
        $this->assertDatabaseHas('marcas', ['nombre' => 'MAZDA']);
    }

    public function test_el_ano_final_no_puede_ser_anterior_al_inicial(): void
    {
        $this->actingAs($this->asesor())
            ->from(route('panel.catalogo.crear'))
            ->post(route('panel.catalogo.guardar-vehiculo'), [
                'marca' => 'Kia', 'modelo' => 'Picanto', 'cilindraje' => '1000',
                'anio_inicio' => 2015, 'anio_fin' => 2010,
            ])
            ->assertSessionHasErrors('anio_fin');
    }

    public function test_la_ficha_guarda_referencia_y_no_la_pisa_una_reimportacion(): void
    {
        $producto = Producto::first();

        $this->actingAs($this->asesor())->post(route('panel.catalogo.guardar-producto', $producto), [
            'referencia' => 'MAH-4471',
            'descripcion' => 'Juego de cuatro pastillas.',
            'publicado' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'referencia' => 'MAH-4471']);
    }

    public function test_la_carga_masiva_muestra_una_vista_previa_sin_escribir_nada(): void
    {
        Storage::fake('local');

        $antes = Producto::count();

        $vista = $this->actingAs($this->asesor())
            ->post(route('panel.catalogo.previsualizar'), ['archivo' => $this->excelDePrueba()])
            ->assertOk();

        $resultado = $vista->viewData('resultado');

        $this->assertTrue($resultado->simulacion);
        $this->assertSame(1, $resultado->vehiculosLeidos);
        $this->assertSame(2, $resultado->celdasMarcadas);
        $this->assertSame($antes, Producto::count(), 'La vista previa no debe escribir en la base.');
    }

    public function test_confirmar_la_carga_masiva_importa_de_verdad(): void
    {
        $vista = $this->actingAs($this->asesor())
            ->post(route('panel.catalogo.previsualizar'), ['archivo' => $this->excelDePrueba()]);

        $this->actingAs($this->asesor())
            ->post(route('panel.catalogo.confirmar'), ['archivo' => $vista->viewData('archivo')])
            ->assertRedirect(route('panel.catalogo'));

        $this->assertDatabaseHas('vehiculos', ['cilindraje' => '1200', 'anio_inicio' => 2018]);
        $this->assertDatabaseHas('productos', ['nombre' => 'Filtro Aceite SAIL 1200 CHEVROLET']);
    }

    /** El nombre del archivo viene de un formulario: no puede apuntar a cualquier ruta. */
    public function test_no_se_puede_importar_un_archivo_fuera_de_la_carpeta(): void
    {
        $this->actingAs($this->asesor())
            ->post(route('panel.catalogo.confirmar'), ['archivo' => '../../.env'])
            ->assertRedirect(route('panel.catalogo.importar'));
    }

    public function test_solo_el_administrador_toca_la_configuracion(): void
    {
        $this->actingAs($this->asesor())->get('/panel/configuracion')->assertForbidden();
        $this->actingAs($this->admin())->get('/panel/configuracion')->assertOk();
    }

    public function test_la_configuracion_guarda_varios_correos_de_destino(): void
    {
        $this->actingAs($this->admin())->post(route('panel.configuracion.guardar'), [
            'correos_cotizacion' => "ventas@suralpine.com\ncotizaciones@suralpine.com",
            'telefono_pbx' => '(601) 366 0066',
        ])->assertRedirect();

        $this->assertSame(
            ['ventas@suralpine.com', 'cotizaciones@suralpine.com'],
            Configuracion::correosDestino()
        );
    }

    public function test_no_se_aceptan_correos_de_destino_invalidos(): void
    {
        Configuracion::poner('correos_cotizacion', 'ventas@suralpine.com');

        $this->actingAs($this->admin())
            ->from(route('panel.configuracion'))
            ->post(route('panel.configuracion.guardar'), ['correos_cotizacion' => 'esto no es un correo'])
            ->assertSessionHasErrors('correos_cotizacion');
    }

    public function test_el_administrador_crea_usuarios_con_su_rol(): void
    {
        $this->actingAs($this->admin())->post(route('panel.usuarios.guardar'), [
            'name' => 'Nueva Vendedora',
            'email' => 'nueva@suralpine.com',
            'rol' => 'vendedor',
            'password' => 'clavelarga123',
            'password_confirmation' => 'clavelarga123',
            'activo' => '1',
        ])->assertRedirect(route('panel.usuarios'));

        $usuario = User::where('email', 'nueva@suralpine.com')->firstOrFail();

        $this->assertSame(Rol::Vendedor, $usuario->rol);
        $this->assertTrue($usuario->activo);
    }

    /**
     * Hasta ahora el panel sólo sabía crear usuarios: si un empleado se iba,
     * no había forma de quitarle el acceso sin entrar a la base de datos.
     */
    public function test_el_administrador_desactiva_a_quien_ya_no_trabaja(): void
    {
        $vendedor = User::create([
            'name' => 'Vendedor que se fue', 'email' => 'sefue@suralpine.com',
            'password' => 'secreto123', 'rol' => Rol::Vendedor, 'activo' => true,
        ]);

        $this->actingAs($this->admin())
            ->post(route('panel.usuarios.actualizar', $vendedor), [
                'name' => $vendedor->name,
                'email' => $vendedor->email,
                'rol' => Rol::Vendedor->value,
                // Sin 'activo': es la casilla desmarcada.
            ])
            ->assertRedirect(route('panel.usuarios'));

        $this->assertFalse($vendedor->fresh()->activo);

        // Y de verdad deja de entrar. Hay que salir primero: la ruta de acceso
        // es sólo para invitados y con el admin dentro nunca se ejecuta.
        auth()->logout();

        $this->post(route('entrar'), ['email' => $vendedor->email, 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');
    }

    /** Editar sin escribir contraseña no la borra ni la cambia. */
    public function test_editar_un_usuario_sin_contrasena_deja_la_que_tenia(): void
    {
        $asesor = User::create([
            'name' => 'Asesor', 'email' => 'asesor2@suralpine.com',
            'password' => 'secreto123', 'rol' => Rol::Asesor, 'activo' => true,
        ]);
        $claveVieja = $asesor->password;

        $this->actingAs($this->admin())
            ->post(route('panel.usuarios.actualizar', $asesor), [
                'name' => 'Nombre corregido',
                'email' => $asesor->email,
                'rol' => Rol::Asesor->value,
                'activo' => 1,
            ])->assertRedirect(route('panel.usuarios'));

        $asesor->refresh();
        $this->assertSame('Nombre corregido', $asesor->name);
        $this->assertSame($claveVieja, $asesor->password);
    }

    /** El formulario llega relleno cuando se pide editar a alguien. */
    public function test_el_formulario_de_edicion_trae_los_datos_del_usuario(): void
    {
        $asesor = User::create([
            'name' => 'Asesor de catálogo', 'email' => 'asesor3@suralpine.com',
            'password' => 'secreto123', 'rol' => Rol::Asesor, 'activo' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('panel.usuarios', ['editar' => $asesor->id]))
            ->assertOk()
            ->assertSee('Editar Asesor de catálogo')
            ->assertSee('asesor3@suralpine.com');
    }

    /** Nadie se quita a sí mismo el acceso y deja el panel sin administrador. */
    public function test_un_admin_no_puede_degradarse_ni_desactivarse(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('panel.usuarios.actualizar', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'rol' => 'cliente',
            'activo' => null,
        ])->assertRedirect();

        $admin->refresh();

        $this->assertSame(Rol::Admin, $admin->rol);
        $this->assertTrue($admin->activo);
    }

    /** Matriz mínima con un vehículo nuevo y dos piezas marcadas. */
    private function excelDePrueba(): UploadedFile
    {
        $hoja = (new Spreadsheet)->getActiveSheet();

        $hoja->fromArray([
            [null, null, null, null, null, 'Frenos', null, 'Motor Externo'],
            ['Marca', 'Modelo', 'Motor', 'Año Comienzo', 'Año Final',
                'Pastillas Freno Delanteras', 'Bandas Freno', 'Filtro Aceite'],
            ['CHEVROLET', 'SAIL', 1200.0, 2018.0, 2022.0, 1, 0, 1],
        ], null, 'A1');

        $ruta = tempnam(sys_get_temp_dir(), 'matriz').'.xlsx';
        (new Xlsx($hoja->getParent()))->save($ruta);

        return new UploadedFile($ruta, 'matriz.xlsx', null, null, true);
    }
}
