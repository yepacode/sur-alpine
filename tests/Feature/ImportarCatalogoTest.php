<?php

namespace Tests\Feature;

use App\Models\Modelo;
use App\Models\Producto;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportarCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archivo = $this->crearMatriz();
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        parent::tearDown();
    }

    /**
     * Matriz reducida con las tres trampas reales del archivo del cliente:
     * un tipo de parte que vive en dos categorías, un modelo con dos
     * generaciones, y un modelo cuyo nombre es un número.
     */
    private function crearMatriz(bool $fiatConAmortiguador = true): string
    {
        $hoja = (new Spreadsheet)->getActiveSheet();

        $hoja->fromArray([
            // Fila 1: categorías, sólo en la primera columna de cada grupo.
            [null, null, null, null, null, 'Direccion', null, 'Suspension', null],
            // Fila 2: tipos de parte.
            ['Marca', 'Modelo', 'Motor', 'Año Comienzo', 'Año Final',
                'Axial Direccion', 'Terminal Direccion', 'Axial Direccion', 'Amortiguador'],
            // Vehículos.
            ['CHEVROLET', 'OPTRA', 1800.0, 2004.0, 2006.0, 1, 1, 1, 0],
            ['CHEVROLET', 'OPTRA', 1800.0, 2007.0, 2013.0, 1, 0, 1, 1],
            ['FIAT', 147.0, '1300 CARB', 1980.0, 1985.0, 1, 0, 0, $fiatConAmortiguador ? 1 : 0],
        ], null, 'A1');

        $ruta = tempnam(sys_get_temp_dir(), 'matriz').'.xlsx';
        (new Xlsx($hoja->getParent()))->save($ruta);

        return $ruta;
    }

    private function importar(): void
    {
        $this->artisan('catalogo:importar', ['--archivo' => $this->archivo])
            ->assertSuccessful();
    }

    public function test_cada_celda_marcada_se_convierte_en_un_producto(): void
    {
        $this->importar();

        // 3 + 3 + 2 celdas en 1.
        $this->assertSame(8, Producto::count());
        $this->assertSame(3, Vehiculo::count());
    }

    public function test_no_se_pierde_ningun_producto_por_colision_de_slug(): void
    {
        $this->importar();

        $this->assertSame(
            Producto::count(),
            Producto::distinct('slug')->count('slug'),
            'Hay slugs repetidos: el upsert estaría pisando productos distintos.'
        );
    }

    public function test_una_parte_en_dos_categorias_genera_dos_productos_distintos(): void
    {
        $this->importar();

        $axiales = Producto::where('nombre', 'like', 'Axial Direccion OPTRA%')
            ->whereHas('vehiculo', fn ($q) => $q->where('anio_inicio', 2004))
            ->pluck('slug');

        $this->assertCount(2, $axiales);
        $this->assertTrue($axiales->contains(fn ($s) => str_contains($s, 'direccion')));
        $this->assertTrue($axiales->contains(fn ($s) => str_contains($s, 'suspension')));
    }

    public function test_dos_generaciones_del_mismo_modelo_no_se_pisan(): void
    {
        $this->importar();

        $generaciones = Vehiculo::whereHas('modelo', fn ($q) => $q->where('nombre', 'OPTRA'))
            ->orderBy('anio_inicio')
            ->pluck('anio_fin', 'anio_inicio');

        $this->assertSame([2004 => 2006, 2007 => 2013], $generaciones->all());

        $slugs = Producto::where('nombre', 'like', 'Terminal Direccion OPTRA%')->pluck('slug');
        $this->assertCount(1, $slugs);
        $this->assertStringContainsString('2004', $slugs->first());
    }

    public function test_los_modelos_numericos_no_llegan_como_decimales(): void
    {
        $this->importar();

        $this->assertTrue(Modelo::where('nombre', '147')->exists());
        $this->assertFalse(Modelo::where('nombre', 'like', '%.0')->exists());
    }

    public function test_el_cilindraje_conserva_el_texto(): void
    {
        $this->importar();

        $this->assertTrue(Vehiculo::where('cilindraje', '1300 CARB')->exists());
    }

    /**
     * Si el Excel deja de marcar una celda, la pieza vieja se queda en el
     * catálogo: retirarla es decisión del negocio. Lo que sí hace el importador
     * es avisar, para que nadie se entere por un cliente que no la encuentra.
     */
    public function test_avisa_de_las_piezas_que_ya_no_vienen_en_el_excel(): void
    {
        $importador = app(\App\Services\ImportadorCatalogo::class);

        $importador->importar($this->archivo);
        $this->assertSame(8, Producto::count());

        // `updated_at` guarda segundos enteros y las dos importaciones caen en
        // el mismo: en la vida real una toma minutos, aquí hay que separarlas.
        $this->travel(1)->seconds();

        // El mismo archivo, pero el FIAT deja de llevar amortiguador.
        $recortado = $this->crearMatriz(fiatConAmortiguador: false);
        $resultado = $importador->importar($recortado);
        @unlink($recortado);

        $this->assertSame(1, $resultado->sobrantes, 'No detectó la pieza que dejó de venir en el Excel.');
        $this->assertSame(['Amortiguador 147 1300 CARB FIAT'], $resultado->muestraSobrantes);

        // Y no la borró: sigue ahí para que alguien decida qué hacer con ella.
        $this->assertSame(8, Producto::count());
    }

    public function test_reimportar_no_duplica_ni_pierde_nada(): void
    {
        $this->importar();
        $primera = Producto::pluck('slug')->sort()->values();

        $this->importar();

        $this->assertSame(8, Producto::count());
        $this->assertSame($primera->all(), Producto::pluck('slug')->sort()->values()->all());
    }

    public function test_una_reimportacion_no_borra_lo_que_cargo_el_equipo(): void
    {
        $this->importar();

        Producto::query()->first()->update([
            'referencia' => 'MAH-4471',
            'imagen' => 'productos/axial.webp',
        ]);

        $this->importar();

        $this->assertDatabaseHas('productos', [
            'referencia' => 'MAH-4471',
            'imagen' => 'productos/axial.webp',
        ]);
    }
}
