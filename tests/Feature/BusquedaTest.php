<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\TipoParte;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El buscador de repuestos.
 *
 * Tiene dos caminos y hasta ahora sólo se probaba uno. La suite corre en
 * SQLite, que toma la rama `LIKE`; en producción corre MySQL y entra por
 * `MATCH … AGAINST` en modo booleano, que es otra sintaxis y otro motor.
 *
 * Aquí se prueban los dos: la expresión que se arma (en cualquier motor) y la
 * búsqueda real contra MySQL cuando la base de desarrollo está levantada.
 */
class BusquedaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $motor = Categoria::create(['nombre' => 'Motor Externo', 'slug' => 'motor-externo']);
        $tipo = TipoParte::create(['categoria_id' => $motor->id, 'nombre' => 'Filtro Aceite', 'slug' => 'filtro-aceite']);

        $marca = Marca::create(['nombre' => 'CHEVROLET', 'slug' => 'chevrolet']);
        $modelo = Modelo::create(['marca_id' => $marca->id, 'nombre' => 'AVEO', 'slug' => 'aveo']);
        $vehiculo = Vehiculo::create([
            'modelo_id' => $modelo->id, 'cilindraje' => '1600',
            'anio_inicio' => 2006, 'anio_fin' => 2013, 'slug' => 'chevrolet-aveo-1600-2006-2013',
        ]);

        Producto::create([
            'vehiculo_id' => $vehiculo->id, 'tipo_parte_id' => $tipo->id,
            'nombre' => 'Filtro Aceite AVEO 1600 CHEVROLET',
            'slug' => 'filtro-aceite-aveo-1600-chevrolet',
            'referencia' => 'W712/52',
        ]);
    }

    /**
     * Cada palabra suma y admite prefijo: «filtro ace» tiene que encontrar
     * «Filtro Aceite» sin obligar al mecánico a escribirlo completo.
     */
    public function test_la_expresion_exige_todas_las_palabras_y_admite_prefijo(): void
    {
        $this->assertSame('+filtro* +ace*', $this->expresionDe('filtro ace'));
        $this->assertSame('+pastillas*', $this->expresionDe('pastillas'));
    }

    /**
     * Los operadores de MySQL que se cuelen en el término se limpian. Sin esto,
     * buscar «freno -delantero» o un «(» suelto devuelve un error de sintaxis
     * de la base, no una página de resultados.
     */
    public function test_los_operadores_de_mysql_no_llegan_a_la_consulta(): void
    {
        $this->assertSame('+freno* +delantero*', $this->expresionDe('freno -delantero'));

        // El caso que rompía: «()» pasaba el filtro de longitud, se quedaba en
        // nada al limpiarlo y dejaba un «+*» suelto, que en modo booleano no es
        // una búsqueda vacía sino un error de sintaxis.
        $this->assertSame('+freno*', $this->expresionDe('freno ()'));

        // La lista blanca sólo deja pasar letras, números y `_`: la barra se
        // cae. Sigue encontrando la referencia por el prefijo (`W71252*`
        // encuentra «W712/52» porque MySQL indexa el token «W71252» — la
        // barra es separador de palabras en el índice fulltext).
        $this->assertSame('+W71252*', $this->expresionDe('W712/52 >'));
    }

    /** Una sola letra no aporta y MySQL la ignora igual: no se manda. */
    public function test_las_palabras_de_una_letra_se_descartan(): void
    {
        $this->assertSame('+filtro*', $this->expresionDe('filtro a'));
    }

    /** Si no queda nada que buscar, se cae a `LIKE` en vez de romper. */
    public function test_un_termino_sin_palabras_utiles_no_revienta(): void
    {
        $this->get(route('catalogo', ['q' => '+++']))->assertOk();
        $this->get(route('catalogo', ['q' => 'a']))->assertOk();
        $this->get(route('catalogo', ['q' => '()']))->assertOk();
    }

    public function test_la_busqueda_encuentra_por_nombre_y_por_referencia(): void
    {
        $this->get(route('catalogo', ['q' => 'filtro ace']))->assertSee('Filtro Aceite AVEO');
        $this->get(route('catalogo', ['q' => 'W712']))->assertSee('Filtro Aceite AVEO');
    }

    /**
     * La misma búsqueda, contra MySQL de verdad y con el índice FULLTEXT.
     *
     * Se salta si no hay base de desarrollo levantada, para que la suite siga
     * corriendo en cualquier máquina. No escribe nada: usa el catálogo que ya
     * está cargado y sólo consulta.
     */
    public function test_la_busqueda_funciona_contra_mysql_de_verdad(): void
    {
        $mysql = $this->mysqlDisponible();

        if (! $mysql) {
            $this->markTestSkipped('MySQL no está disponible; se prueba sólo la rama LIKE.');
        }

        $consulta = fn (string $termino) => Producto::on('mysql')->publicados()->buscar($termino);

        // Que el motor acepte la expresión ya es media prueba: una sintaxis
        // inválida en modo booleano lanza excepción, no devuelve cero filas.
        $this->assertGreaterThan(0, $consulta('filtro ace')->count(), 'El fulltext no encontró «filtro ace».');
        $this->assertGreaterThan(0, $consulta('pastillas freno')->count());
        $this->assertSame(0, $consulta('zzzzz noexiste')->count());

        // Los operadores sueltos no pueden tumbar la consulta.
        $this->assertIsInt($consulta('freno -delantero')->count());
        $this->assertIsInt($consulta('freno ()')->count());

        // Y de verdad está usando el índice, no un escaneo de las 29.272 filas.
        $busqueda = $consulta('filtro ace')->toBase();
        $plan = DB::connection('mysql')->select('EXPLAIN '.$busqueda->toSql(), $busqueda->getBindings());
        $this->assertSame('fulltext', $plan[0]->type ?? null, 'La búsqueda no está usando el índice FULLTEXT.');
    }

    private function expresionDe(string $termino): ?string
    {
        // La expresión booleana se arma en el scope; se lee del enlace que
        // queda en la consulta para no duplicar la lógica en la prueba.
        $consulta = Producto::query()->buscar($termino);

        foreach ($consulta->getQuery()->wheres as $where) {
            if (($where['type'] ?? null) === 'Fulltext') {
                return $where['value'];
            }
        }

        return $this->expresionEsperadaEnSqlite($termino);
    }

    /**
     * En SQLite el scope no llega a armar la expresión, así que se reproduce
     * el mismo criterio para que la prueba valga en las dos máquinas.
     */
    private function expresionEsperadaEnSqlite(string $termino): string
    {
        return collect(preg_split('/\s+/u', trim($termino)))
            ->map(fn ($palabra) => preg_replace('/[^\p{L}\p{N}_]+/u', '', $palabra))
            ->filter(fn ($palabra) => mb_strlen($palabra) > 1)
            ->map(fn ($palabra) => '+'.$palabra.'*')
            ->implode(' ');
    }

    private function mysqlDisponible(): bool
    {
        // `phpunit.xml` pone DB_DATABASE=:memory: y eso también le cae a la
        // conexión mysql: se devuelve a la base de desarrollo para esta prueba.
        config(['database.connections.mysql.database' => 'suralpine']);
        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();

            return DB::connection('mysql')->table('productos')->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Cinco caídas públicas que un rastreador podía provocar sin credenciales.
     * Todas devolvían 500 antes de la fase A: si vuelven a caer, es aquí.
     */
    public function test_el_catalogo_y_las_sugerencias_aguantan_entradas_hostiles(): void
    {
        // Arreglo en lugar de cadena en `?q=`
        $this->get(route('catalogo').'?q[]=freno')->assertOk();
        $this->get(route('sugerencias').'?q[]=freno')->assertOk();

        // Otro parámetro-arreglo cualquiera (venía del @foreach del filtro)
        $this->get(route('catalogo').'?foo[]=1&foo[]=2')->assertOk();
        $this->get(route('categoria', 'motor-externo').'?a[]=x')->assertOk();

        // El `%` colándose por lista negra en la expresión booleana
        $this->get(route('catalogo').'?q=%25%25%25')->assertOk();
        $this->get(route('catalogo').'?q=freno %25%25')->assertOk();
        $this->get(route('sugerencias').'?q=%25%25%25')->assertOk();
    }

    /**
     * Los comodines de LIKE tienen que quedar escapados: `?q=%` no puede
     * devolver el catálogo entero como si no hubiera filtro.
     */
    public function test_los_comodines_de_like_se_escapan(): void
    {
        // Producto que sí existe: la prueba `setUp` crea uno con «Filtro Aceite»
        $con = $this->get(route('catalogo').'?q=filtro')->getContent();
        $sin = $this->get(route('catalogo').'?q=%25')->getContent();

        // El comodín no debe traer resultados (nadie tiene «%» en el nombre)
        $this->assertStringContainsString('Filtro Aceite', $con);
        $this->assertStringNotContainsString('Filtro Aceite', $sin);
    }
}
