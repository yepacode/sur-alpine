<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * «Actualízate con Nosotros»: las notas del blog.
 *
 * Lo que se protege aquí es, sobre todo, lo que en el sitio actual está roto:
 * que la tarjeta lleve de verdad a su nota (allá la del kit apunta a `#`), que
 * un borrador no se asome, y que lo que escriba un asesor no pueda inyectar
 * HTML en la página.
 */
class NotasTest extends TestCase
{
    use RefreshDatabase;

    private function nota(array $atributos = []): Nota
    {
        return Nota::create($atributos + [
            'titulo' => 'Cada cuánto se cambia el kit',
            'slug' => Nota::slugUnico($atributos['titulo'] ?? 'Cada cuánto se cambia el kit'),
            'resumen' => 'Un arranque corto para la tarjeta de la portada.',
            'cuerpo' => "Primer párrafo.\n## Un subtítulo\n- Punto uno\n- Punto dos\nCierre.",
            'categoria' => 'Tips',
            'publicada' => true,
            'publicada_en' => now()->subDay(),
        ]);
    }

    private function admin(): User
    {
        return User::forceCreate([
            'name' => 'Administradora', 'email' => 'admin@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Admin, 'activo' => true,
        ]);
    }

    public function test_la_portada_muestra_las_notas_y_enlaza_a_cada_una(): void
    {
        $nota = $this->nota();

        // El defecto que se está corrigiendo del sitio actual: allá la tarjeta
        // del kit de distribución apunta a «#» y no se llega al artículo.
        $this->get('/')
            ->assertOk()
            ->assertSee('Actualízate con Nosotros')
            ->assertSee($nota->titulo)
            ->assertSee(route('nota', $nota));
    }

    public function test_la_portada_solo_trae_las_cuatro_mas_recientes(): void
    {
        foreach (range(1, 6) as $i) {
            $this->nota(['titulo' => "Nota número {$i}", 'publicada_en' => now()->subDays(10 - $i)]);
        }

        $respuesta = $this->get('/')->assertOk();

        // Las cuatro últimas entran; las dos primeras se quedan en /noticias.
        $respuesta->assertSee('Nota número 6')->assertSee('Nota número 3');
        $respuesta->assertDontSee('Nota número 2');
    }

    public function test_sin_notas_la_seccion_no_se_pinta(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Actualízate con Nosotros');
    }

    public function test_el_listado_y_la_ficha_cargan(): void
    {
        $nota = $this->nota();

        $this->get(route('noticias'))->assertOk()->assertSee($nota->titulo);
        $this->get(route('nota', $nota))
            ->assertOk()
            ->assertSee($nota->titulo)
            ->assertSee('Un subtítulo')
            ->assertSee('Punto uno')
            ->assertSee('Cierre.');
    }

    public function test_un_borrador_no_se_ve_en_ninguna_parte(): void
    {
        $nota = $this->nota(['publicada' => false]);

        $this->get('/')->assertOk()->assertDontSee($nota->titulo);
        $this->get(route('noticias'))->assertOk()->assertDontSee($nota->titulo);
        $this->get(route('nota', $nota))->assertNotFound();
    }

    public function test_una_nota_programada_espera_su_fecha(): void
    {
        $nota = $this->nota(['publicada_en' => now()->addWeek()]);

        $this->get(route('noticias'))->assertOk()->assertDontSee($nota->titulo);
        $this->get(route('nota', $nota))->assertNotFound();
    }

    public function test_el_cuerpo_no_puede_inyectar_html(): void
    {
        $nota = $this->nota([
            'titulo' => 'Nota con truco',
            'cuerpo' => '<script>alert(1)</script> texto normal',
            'resumen' => '<img src=x onerror=alert(1)>',
        ]);

        $html = $this->get(route('nota', $nota))->assertOk()->getContent();

        // Lo que se comprueba es que ninguna de las dos cargas llegue entera
        // al HTML. Dos intentos anteriores no servían: buscar «onerror=alert(1)»
        // suelto da falso positivo contra `&lt;img … &gt;`, que ya es inofensivo,
        // y buscar «<script» choca con los JSON-LD legítimos del layout.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('texto normal', $html);
    }

    public function test_las_notas_entran_al_sitemap(): void
    {
        $nota = $this->nota();

        $this->get('/sitemap-secciones.xml')
            ->assertOk()
            ->assertSee(route('noticias'), false)
            ->assertSee(route('nota', $nota), false);
    }

    public function test_ninguna_nota_habla_de_precios(): void
    {
        $nota = $this->nota();

        // La regla del sitio entero: aquí no se habla de dinero. Vale también
        // para lo que el equipo escriba en el blog.
        $html = $this->get(route('nota', $nota))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\$\s?[\d.,]+/', $html);
    }

    public function test_el_equipo_publica_una_nota_desde_el_panel(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('panel.notas.guardar'), [
                'titulo' => 'Cómo revisar los frenos',
                'resumen' => 'Tres cosas que puedes mirar tú mismo antes de venir al taller.',
                'cuerpo' => "Primer párrafo.\n- Una viñeta",
                'categoria' => 'Tips',
                'publicada' => '1',
                'imagen' => UploadedFile::fake()->image('frenos.jpg', 1024, 573),
            ])
            ->assertRedirect(route('panel.notas'));

        $nota = Nota::firstWhere('titulo', 'Cómo revisar los frenos');

        $this->assertNotNull($nota);
        $this->assertSame('como-revisar-los-frenos', $nota->slug);
        // El sufijo `-1024` es el que habilita el srcset; si se pierde, el
        // celular baja la foto grande en una tarjeta de 330 px.
        //
        // Y `.webp`, no `.jpg`: esta prueba afirmaba `-1024.jpg`, que era
        // justo el fallo. El accesor `imagen_srcset` exige `.webp`, así que
        // con un JPG el srcset quedaba en null sin que nada avisara. Ahora el
        // servidor reencoda y la prueba lo comprueba de verdad.
        $this->assertStringEndsWith('-1024.webp', $nota->imagen);
        $this->assertNotNull($nota->imagen_srcset);

        $this->get(route('nota', $nota))->assertOk()->assertSee('Una viñeta');
    }

    public function test_editar_el_titulo_no_cambia_la_direccion(): void
    {
        $nota = $this->nota();
        $slug = $nota->slug;

        // La URL ya circula por WhatsApp y está en el sitemap: cambiarla al
        // corregir una tilde del título rompería los enlaces de meses.
        $this->actingAs($this->admin())
            ->post(route('panel.notas.actualizar', $nota), [
                'titulo' => 'Cada cuánto se cambia el kit de distribución',
                'resumen' => $nota->resumen,
                'cuerpo' => $nota->cuerpo,
                'categoria' => $nota->categoria,
                'publicada' => '1',
            ])
            ->assertRedirect(route('panel.notas'));

        $this->assertSame($slug, $nota->fresh()->slug);
        $this->assertSame('Cada cuánto se cambia el kit de distribución', $nota->fresh()->titulo);
    }

    public function test_dos_notas_con_el_mismo_titulo_no_chocan(): void
    {
        $this->nota(['titulo' => 'Consejos de viaje']);

        $this->actingAs($this->admin())->post(route('panel.notas.guardar'), [
            'titulo' => 'Consejos de viaje',
            'resumen' => 'Otro arranque.',
            'cuerpo' => 'Texto.',
            'categoria' => 'Tips',
            'publicada' => '1',
        ])->assertRedirect();

        $this->assertSame(2, Nota::where('titulo', 'Consejos de viaje')->count());
        $this->assertSame(2, Nota::whereIn('slug', ['consejos-de-viaje', 'consejos-de-viaje-2'])->count());
    }

    public function test_el_panel_de_notas_pide_sesion_y_rol(): void
    {
        $this->get(route('panel.notas'))->assertRedirect(route('acceso'));

        $cliente = User::forceCreate([
            'name' => 'Cliente', 'email' => 'cliente@x.co', 'telefono' => '300',
            'password' => 'secreto123', 'rol' => Rol::Cliente, 'activo' => true,
        ]);

        $this->actingAs($cliente)->get(route('panel.notas'))->assertForbidden();
    }

    public function test_el_cuerpo_agrupa_las_vinetas_seguidas(): void
    {
        $nota = $this->nota();
        $bloques = $nota->bloques();

        $this->assertSame(['parrafo', 'titulo', 'lista', 'parrafo'], array_column($bloques, 'tipo'));
        $this->assertSame(['Punto uno', 'Punto dos'], $bloques[2]['puntos']);
    }
}
