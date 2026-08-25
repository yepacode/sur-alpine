<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Contenido;
use App\Models\SeoPagina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F · «Configuración de página» — el panel unificado, y su rebote sobre
 * el sitio público. Los textos y el SEO viven en tablas clave-valor; si
 * el asesor los toca, se ven en el HTML sin tocar código.
 */
class ConfiguracionPaginaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);
    }

    public function test_el_panel_muestra_las_secciones_del_sitio(): void
    {
        $this->actingAs($this->admin())
            ->get(route('panel.pagina'))
            ->assertOk()
            ->assertSee('Hero de la portada')
            ->assertSee('Buscador de vehículo')
            ->assertSee('Cabecera y menú')
            ->assertSee('SEO de la página');
    }

    public function test_las_claves_conocidas_se_registran_al_primer_acceso(): void
    {
        // Antes del acceso al panel: la tabla está vacía. Después: hay filas
        // para todas las claves conocidas. Así el asesor entra y ve el
        // catálogo listo para editar sin pasar una tarea previa.
        $this->assertSame(0, Contenido::count());

        $this->actingAs($this->admin())->get(route('panel.pagina'))->assertOk();

        $this->assertGreaterThan(0, Contenido::count());
        $this->assertTrue(Contenido::where('clave', 'inicio.hero.titulo')->exists());
    }

    public function test_al_guardar_un_texto_ese_texto_reemplaza_al_original_en_la_portada(): void
    {
        $admin = $this->admin();
        // Primer refresco: sincroniza las claves.
        $this->actingAs($admin)->get(route('panel.pagina'));

        $bajada = Contenido::firstWhere('clave', 'inicio.hero.bajada');
        $this->assertNotNull($bajada);

        // El asesor cambia la bajada.
        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'textos' => [$bajada->id => 'Dinos qué carro tienes y te llamamos hoy.'],
        ])->assertRedirect();

        $this->assertSame(
            'Dinos qué carro tienes y te llamamos hoy.',
            $bajada->fresh()->valor,
        );

        // Y aparece en la portada.
        $this->get('/')->assertSee('Dinos qué carro tienes y te llamamos hoy.', false);
    }

    public function test_al_guardar_seo_los_meta_de_la_portada_los_llevan(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $seo = SeoPagina::firstWhere('ruta', 'inicio');
        $this->assertNotNull($seo);

        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'seo' => [$seo->id => [
                'titulo' => 'Repuestos AVEO Bogotá',
                'descripcion' => 'Autopartes para carros Chevrolet en Bogotá.',
                'palabras_clave' => 'repuestos aveo, autopartes bogota',
                'canonical' => 'https://suralpine.com/',
                'og_titulo' => 'OG Título',
                'og_descripcion' => 'OG Descripción',
                'og_imagen' => 'https://suralpine.com/img/og.webp',
                'og_imagen_alt' => 'Sur Alpine',
                'og_tipo' => 'website',
                'twitter_card' => 'summary_large_image',
                'twitter_titulo' => 'TW Título',
                'twitter_descripcion' => 'TW Descripción',
                'twitter_imagen' => 'https://suralpine.com/img/tw.webp',
                'indexable' => '1',
                'seguir_enlaces' => '1',
                'json_ld_extra' => '{"@context":"https://schema.org","@type":"Thing"}',
            ]],
        ])->assertRedirect();

        // Cierro la sesión (para no ver la barra del panel en la portada).
        auth()->logout();
        $html = $this->get('/')->assertOk()->getContent();

        // Todo esto viene del panel, no del blade original.
        $this->assertStringContainsString('<title>Repuestos AVEO Bogotá</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Autopartes para carros Chevrolet en Bogotá.', $html);
        $this->assertStringContainsString('<meta name="keywords" content="repuestos aveo, autopartes bogota"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://suralpine.com/"', $html);
        $this->assertStringContainsString('<meta property="og:title" content="OG Título"', $html);
        $this->assertStringContainsString('<meta property="og:image" content="https://suralpine.com/img/og.webp"', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image"', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="https://suralpine.com/img/tw.webp"', $html);
        $this->assertStringContainsString('<meta name="robots" content="index,follow', $html);
        $this->assertStringContainsString('"@context":"https://schema.org","@type":"Thing"', $html);
    }

    public function test_desactivar_indexable_marca_noindex_en_los_meta(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('panel.pagina'));

        $seo = SeoPagina::firstWhere('ruta', 'inicio');

        // Sin el hidden input, `checkbox` no marcado no llega — pero el
        // controlador ya lo maneja con `(bool)` sobre el valor puesto.
        $this->actingAs($admin)->post(route('panel.pagina.guardar'), [
            'seo' => [$seo->id => ['indexable' => '0', 'seguir_enlaces' => '0']],
        ])->assertRedirect();

        auth()->logout();
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow', $html);
    }
}
