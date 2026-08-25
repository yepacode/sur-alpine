<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    private Producto $pastillas;

    private Producto $filtro;

    private Categoria $frenos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frenos = Categoria::create(['nombre' => 'Frenos', 'slug' => 'frenos']);
        $motor = Categoria::create(['nombre' => 'Motor Externo', 'slug' => 'motor-externo']);

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);

        $this->pastillas = $this->crearProducto($this->frenos, 'Pastillas Freno Delanteras', $vehiculo);
        $this->filtro = $this->crearProducto($motor, 'Filtro Aceite', $vehiculo);
    }

    private function crearProducto(Categoria $categoria, string $parte, Vehiculo $vehiculo): Producto
    {
        $tipo = TipoParte::create([
            'categoria_id' => $categoria->id,
            'nombre' => $parte,
            'slug' => Str::slug($parte),
        ]);

        $nombre = "{$parte} AVEO 1600 CHEVROLET";

        return Producto::create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_parte_id' => $tipo->id,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
        ]);
    }

    public function test_la_portada_carga(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Categorías de autopartes')
            ->assertSee('historial de mantenimiento')
            ->assertSee('Visítanos en Restrepo');
    }

    /**
     * B7 · La portada muestra TODAS las categorías —también las que aún no
     * tienen foto. Antes se filtraba `whereNotNull('imagen')` y eso escondía
     * Carrocería y Transmisión, 2.234 piezas sin puerta de entrada visible.
     * La que no tiene foto cae en un tratamiento tipográfico (ver la vista).
     */
    public function test_la_portada_muestra_todas_las_categorias_con_o_sin_foto(): void
    {
        $this->frenos->update(['imagen' => '/img/categorias/frenos.webp']);
        // La otra categoría del setUp («Motor Externo») queda sin `imagen`.

        $categorias = $this->get('/')->assertOk()->viewData('categorias');

        $this->assertCount(2, $categorias);
        $this->assertContains('Frenos', $categorias->pluck('nombre')->all());
        $this->assertContains('Motor Externo', $categorias->pluck('nombre')->all());
    }

    /**
     * El sitio anterior mostraba cero productos hasta que el visitante elegía
     * un vehículo, y abría un modal que tapaba la pantalla. Esto lo impide.
     */
    public function test_el_catalogo_se_ve_completo_sin_elegir_vehiculo(): void
    {
        $this->get('/repuestos')
            ->assertOk()
            ->assertSee($this->pastillas->nombre)
            ->assertSee($this->filtro->nombre);
    }

    public function test_la_categoria_muestra_solo_sus_productos(): void
    {
        $this->get('/repuestos/frenos')
            ->assertOk()
            ->assertSee($this->pastillas->nombre)
            ->assertDontSee($this->filtro->nombre);
    }

    public function test_el_tipo_de_parte_filtra_dentro_de_su_categoria(): void
    {
        $this->get('/repuestos/frenos/pastillas-freno-delanteras')
            ->assertOk()
            ->assertSee($this->pastillas->nombre)
            ->assertDontSee($this->filtro->nombre);
    }

    public function test_un_tipo_de_parte_de_otra_categoria_da_404(): void
    {
        $this->get('/repuestos/frenos/filtro-aceite')->assertNotFound();
    }

    public function test_la_busqueda_encuentra_por_nombre(): void
    {
        $this->get('/repuestos?q=pastillas')
            ->assertOk()
            ->assertSee($this->pastillas->nombre)
            ->assertDontSee($this->filtro->nombre);
    }

    public function test_la_busqueda_sin_resultados_ofrece_salida(): void
    {
        $this->get('/repuestos?q=turbocompresor')
            ->assertOk()
            ->assertSee('No encontramos repuestos con esa búsqueda');
    }

    /**
     * En el sitio anterior la ficha de producto agregaba siempre el mismo
     * repuesto ajeno. Aquí la ficha sólo puede hablar de su propio producto.
     */
    public function test_la_ficha_muestra_el_producto_que_corresponde(): void
    {
        $this->get('/repuesto/'.$this->pastillas->slug)
            ->assertOk()
            ->assertSee($this->pastillas->nombre)
            ->assertSee('CHEVROLET AVEO 1600 (2006-2013)')
            ->assertDontSee($this->filtro->nombre);
    }

    /**
     * Un `$` suelto no sirve como prueba: Alpine usa `$refs` y `$dispatch`.
     * Lo que no puede aparecer es una cifra de dinero.
     */
    public function test_la_ficha_no_publica_ningun_precio(): void
    {
        $contenido = $this->get('/repuesto/'.$this->pastillas->slug)->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\$\s?[\d.,]+/', $contenido);
        $this->assertStringNotContainsString('COP', $contenido);
    }

    /**
     * Decisión del cliente: el sitio ni siquiera nombra el tema. Mencionar que
     * "los precios cambian a diario" invita justo la pregunta que no se quiere
     * abrir en la web; el valor lo trata el asesor por teléfono.
     */
    public function test_ninguna_pagina_publica_habla_de_precios(): void
    {
        $this->frenos->update(['imagen' => '/img/categorias/frenos.webp']);

        $rutas = [
            '/',
            '/repuestos',
            '/repuestos/frenos',
            '/repuesto/'.$this->pastillas->slug,
            '/mi-cotizacion',
            '/mantenimientos',
        ];

        foreach ($rutas as $ruta) {
            $contenido = $this->get($ruta)->assertOk()->getContent();

            $this->assertDoesNotMatchRegularExpression(
                '/precio/i', $contenido, "«{$ruta}» menciona precios."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\$\s?[\d.,]+/', $contenido, "«{$ruta}» muestra una cifra de dinero."
            );
        }
    }

    /**
     * C6 · Una pieza despublicada no se ve por su URL directa: 404 tanto en la
     * ficha como al intentar agregarla al carrito, aunque el enlace se conozca.
     */
    public function test_una_pieza_despublicada_da_404_en_ficha_y_en_agregar(): void
    {
        $this->pastillas->update(['publicado' => false]);

        $this->get('/repuesto/'.$this->pastillas->slug)->assertNotFound();

        $this->post('/mi-cotizacion/agregar/'.$this->pastillas->id, ['cantidad' => 1])
            ->assertNotFound();
    }
}
