<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Suscriptor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * El pie, sus páginas legales y el newsletter.
 *
 * El pie es lo único que se pinta en todas las páginas del sitio: si se rompe,
 * se rompe entero. Y el formulario del newsletter es el único que acepta datos
 * de cualquiera sin sesión detrás, así que sus cierres van cubiertos uno a uno.
 */
class PieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El límite de la ruta es real y se comparte entre pruebas: sin esto,
        // la sexta suscripción de la clase fallaría por un motivo que no es el
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    public function test_el_pie_trae_sus_cinco_columnas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Menú')
            ->assertSee('Enlaces de interés')
            ->assertSee('Legales')
            ->assertSee('Nuestras redes sociales')
            ->assertSee('Suscríbete al newsletter')
            ->assertSee('Todos los derechos reservados');
    }

    public function test_el_pie_enlaza_las_dos_paginas_legales(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('politica-datos'))
            ->assertSee(route('terminos'));

        $this->get(route('terminos'))
            ->assertOk()
            ->assertSee('Términos y condiciones de uso')
            ->assertSee('1.5. Ley Aplicable y Jurisdicción');
    }

    public function test_el_pie_enlaza_las_redes_reales_de_la_empresa(): void
    {
        // Salían vacías mientras nadie las escribiera en el panel, y la columna
        // quedaba con el título y ningún enlace debajo.
        $this->get('/')
            ->assertOk()
            ->assertSee('https://www.facebook.com/Importadorasuralpinesa', false)
            ->assertSee('https://www.instagram.com/importadorasuralpine', false);
    }

    public function test_el_newsletter_guarda_el_correo(): void
    {
        $this->from('/')
            ->post(route('suscripcion'), ['correo' => 'Cliente@Ejemplo.CO'])
            ->assertRedirect('/')
            ->assertSessionHas('suscrito');

        // En minúsculas: si no, «Cliente@…» y «cliente@…» entran dos veces.
        $this->assertDatabaseHas('suscriptores', ['correo' => 'cliente@ejemplo.co']);
    }

    public function test_el_mismo_correo_no_entra_dos_veces(): void
    {
        foreach (range(1, 3) as $ignorado) {
            $this->from('/')->post(route('suscripcion'), ['correo' => 'repetido@ejemplo.co']);
        }

        $this->assertSame(1, Suscriptor::where('correo', 'repetido@ejemplo.co')->count());
    }

    public function test_volver_a_suscribirse_no_revive_una_baja(): void
    {
        Suscriptor::create(['correo' => 'debaja@ejemplo.co', 'baja_en' => now()->subMonth()]);

        $this->from('/')->post(route('suscripcion'), ['correo' => 'debaja@ejemplo.co']);

        // Quien pidió la baja decidió; un formulario público no puede
        // deshacerlo por él sólo porque alguien reescriba el correo.
        $this->assertNotNull(Suscriptor::firstWhere('correo', 'debaja@ejemplo.co')->baja_en);
    }

    public function test_la_trampa_descarta_al_robot_sin_decirselo(): void
    {
        $this->from('/')
            ->post(route('suscripcion'), [
                'correo' => 'robot@ejemplo.co',
                'sitio_web' => 'http://spam.example',
            ])
            // Se le responde lo mismo que a una persona: si viera un error,
            // probaría otra cosa.
            ->assertRedirect('/')
            ->assertSessionHas('suscrito');

        $this->assertDatabaseMissing('suscriptores', ['correo' => 'robot@ejemplo.co']);
    }

    public function test_un_correo_invalido_no_entra(): void
    {
        $this->from('/')
            ->post(route('suscripcion'), ['correo' => 'esto-no-es-un-correo'])
            ->assertSessionHasErrors('correo');

        $this->assertSame(0, Suscriptor::count());
    }

    public function test_los_suscriptores_solo_los_ve_el_equipo(): void
    {
        $this->get(route('panel.suscriptores'))->assertRedirect(route('acceso'));

        $cliente = User::forceCreate([
            'name' => 'Cliente', 'email' => 'cliente@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Cliente, 'activo' => true,
        ]);
        $this->actingAs($cliente)->get(route('panel.suscriptores'))->assertForbidden();

        // `flushSession()` entre los dos: la sesión quedó atada a la
        // contraseña del primero (middleware `AuthenticateSession`) y
        // reusarla con otra cuenta lo saca. Un navegador no puede cambiar
        // de identidad sin cerrar sesión; esto es sólo el atajo de las
        // pruebas, y aquí se paga.
        $this->flushSession();

        $admin = User::forceCreate([
            'name' => 'Administradora', 'email' => 'admin@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);
        Suscriptor::create(['correo' => 'alguien@ejemplo.co']);

        $this->actingAs($admin)->get(route('panel.suscriptores'))
            ->assertOk()
            ->assertSee('alguien@ejemplo.co');
    }

    public function test_el_csv_sale_con_bom_para_que_excel_lea_las_tildes(): void
    {
        $admin = User::forceCreate([
            'name' => 'Administradora', 'email' => 'admin@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);
        Suscriptor::create(['correo' => 'alguien@ejemplo.co', 'origen' => 'Bogotá']);

        $csv = $this->actingAs($admin)->get(route('panel.suscriptores.exportar'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $csv);
        $this->assertStringContainsString('alguien@ejemplo.co', $csv);
    }

    public function test_la_cabecera_pinta_el_buscador_dos_veces_sin_repetir_ids(): void
    {
        // En la portada el buscador está en su sección y dentro del modal que
        // abre «Agregar vehículo». Si los dos usaran los mismos `id`, cada
        // etiqueta apuntaría al formulario de atrás.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="hero-marca"'));
        $this->assertSame(1, substr_count($html, 'id="modal-marca"'));
        $this->assertStringContainsString('aria-labelledby="modal-titulo"', $html);
    }
}
