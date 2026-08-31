<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El telón de entrada.
 *
 * Es adorno, pero un adorno que se pinta encima de todo y bloquea el scroll:
 * si algún día se queda pegado, se lleva el sitio entero por delante. Lo que
 * se cubre aquí no es que se vea bonito —eso se mira— sino que las tres
 * salidas sigan ahí: que no aparezca solo, que se pueda quitar y que no se
 * meta en el panel.
 */
class CargadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_telon_llega_en_las_paginas_publicas(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="cargador"', $html);
        $this->assertStringContainsString("sessionStorage.getItem('telon')", $html);
    }

    /**
     * La clase que lo enciende la pone JavaScript. Si el HTML llegara ya con
     * `cargando` puesto, quien tenga JavaScript apagado se quedaría mirando
     * una pantalla negra para siempre.
     */
    public function test_el_html_no_llega_con_el_telon_encendido(): void
    {
        $html = $this->get('/')->getContent();

        // Una sola: la segunda ya cubría a la primera —era un subconjunto
        // estricto— y dos aserciones donde basta una hacen creer que se está
        // comprobando más de lo que se comprueba.
        $this->assertStringNotContainsString('class="cargando', $html);
    }

    /** El panel es herramienta de trabajo: se abre veinte veces al día. */
    public function test_el_panel_no_lleva_telon(): void
    {
        $admin = User::forceCreate([
            'name' => 'Administrador', 'email' => 'admin@suralpine.com',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);

        $html = $this->actingAs($admin)->get('/panel')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="cargador"', $html);
    }

    /** Lo que ve un lector de pantalla del telón: nada. */
    public function test_el_telon_esta_fuera_del_arbol_de_accesibilidad(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression(
            '/<div id="cargador"[^>]*aria-hidden="true"/',
            $html
        );
    }
}
