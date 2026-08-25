<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Contenido;
use App\Models\SeoPagina;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * F · «Configuración de página» — un solo panel, secciones adentro.
 *
 * En vez de tres pestañas separadas (textos, SEO, imágenes) el asesor entra
 * a UNA página y ve el sitio por bloques: Hero, Buscador, Cómo funciona,
 * Cotización, Contacto, Cabecera. Cada bloque contiene, en la misma
 * tarjeta, todo lo que se puede tocar de esa sección: los textos y
 * botones que salen ahí, la imagen o video si lo lleva, y el SEO de la
 * página a la que pertenece si aplica.
 */
class ConfiguracionPaginaController extends Controller
{
    /**
     * Mapa de secciones. Cada sección define:
     *   · textos:  claves editables (con su rótulo, tipo y texto original)
     *   · seo:     nombre de ruta cuya `title/description/og:image` toca
     *              esta sección (opcional).
     *
     * @return array<string, array{titulo: string, subtitulo: string, textos: list<array{clave:string,rotulo:string,tipo:string,valor:string}>, seo?: array{ruta:string, etiqueta:string}}>
     */
    public function secciones(): array
    {
        return [
            'hero' => [
                'titulo' => 'Hero de la portada',
                'subtitulo' => 'La primera pantalla que ve un visitante al entrar.',
                'textos' => [
                    ['clave' => 'inicio.hero.chip', 'rotulo' => 'Marbete rojo', 'tipo' => 'texto',
                     'valor' => '• 44 AÑOS · ÚNICO SITIO OFICIAL'],
                    ['clave' => 'inicio.hero.titulo', 'rotulo' => 'Titular', 'tipo' => 'parrafo',
                     'valor' => 'La pieza exacta de tu carro'],
                    ['clave' => 'inicio.hero.bajada', 'rotulo' => 'Bajada', 'tipo' => 'parrafo',
                     'valor' => 'Dinos qué carro tienes y te mostramos sólo lo que le sirve.'],
                ],
                'seo' => ['ruta' => 'inicio', 'etiqueta' => 'Portada'],
            ],
            'buscador' => [
                'titulo' => 'Buscador de vehículo',
                'subtitulo' => 'La tarjeta blanca que aparece dentro del hero.',
                'textos' => [
                    ['clave' => 'buscador.titulo', 'rotulo' => 'Rótulo', 'tipo' => 'texto',
                     'valor' => 'BUSCA POR TU VEHÍCULO'],
                    ['clave' => 'buscador.subtitulo', 'rotulo' => 'Frase de apoyo', 'tipo' => 'texto',
                     'valor' => 'y te mostramos sólo lo que le sirve'],
                    ['clave' => 'buscador.boton', 'rotulo' => 'Botón principal', 'tipo' => 'boton',
                     'valor' => 'Buscar'],
                ],
            ],
            'como' => [
                'titulo' => 'Cómo funciona',
                'subtitulo' => 'Las cuatro tarjetas debajo del hero.',
                'textos' => [
                    ['clave' => 'inicio.como.titulo', 'rotulo' => 'Titular', 'tipo' => 'texto', 'valor' => 'Cómo funciona'],
                    ['clave' => 'inicio.como.bajada', 'rotulo' => 'Bajada', 'tipo' => 'parrafo',
                     'valor' => 'Elige tu vehículo, arma tu lista de repuestos y envíanosla. Un asesor te contacta.'],
                ],
            ],
            'cabecera' => [
                'titulo' => 'Cabecera y menú',
                'subtitulo' => 'La barra que está siempre arriba.',
                'textos' => [
                    ['clave' => 'menu.catalogo', 'rotulo' => 'Botón Catálogo', 'tipo' => 'boton', 'valor' => 'Catálogo'],
                    ['clave' => 'menu.mantenimientos', 'rotulo' => 'Enlace mantenimientos', 'tipo' => 'texto',
                     'valor' => 'Mantenimientos'],
                    ['clave' => 'menu.visitanos', 'rotulo' => 'Enlace visítanos', 'tipo' => 'texto',
                     'valor' => 'Visitanos en Restrepo'],
                    ['clave' => 'menu.sobre', 'rotulo' => 'Enlace sobre nosotros', 'tipo' => 'texto',
                     'valor' => 'Sobre nosotros'],
                    ['clave' => 'menu.cotizar', 'rotulo' => 'Botón «Mi cotización»', 'tipo' => 'boton',
                     'valor' => 'Mi cotización'],
                ],
            ],
            'cotizacion' => [
                'titulo' => 'Formulario de cotización',
                'subtitulo' => 'La página «Mi cotización» y su acuse de recibo.',
                'textos' => [
                    ['clave' => 'cotizacion.titulo', 'rotulo' => 'Título de la página', 'tipo' => 'texto',
                     'valor' => 'Tu cotización'],
                    ['clave' => 'cotizacion.boton', 'rotulo' => 'Botón enviar', 'tipo' => 'boton',
                     'valor' => 'Enviar cotización'],
                    ['clave' => 'cotizacion.gracias', 'rotulo' => 'Mensaje de gracias', 'tipo' => 'parrafo',
                     'valor' => 'Gracias por escribirnos. Un asesor te llamará pronto.'],
                ],
            ],
            'acceso' => [
                'titulo' => 'Iniciar sesión / Registro',
                'subtitulo' => 'Las dos páginas del área del cliente.',
                'textos' => [
                    ['clave' => 'acceso.entrar.boton', 'rotulo' => 'Botón entrar', 'tipo' => 'boton', 'valor' => 'Entrar'],
                    ['clave' => 'registro.crear.boton', 'rotulo' => 'Botón crear cuenta', 'tipo' => 'boton',
                     'valor' => 'Crear mi cuenta'],
                ],
                'seo' => ['ruta' => 'acceso', 'etiqueta' => 'Iniciar sesión'],
            ],
            'contacto' => [
                'titulo' => 'Contacto y ubicación',
                'subtitulo' => 'La sección con el video del local y el mapa.',
                'textos' => [
                    ['clave' => 'contacto.mapa.boton', 'rotulo' => 'Botón «Cómo llegar»', 'tipo' => 'boton',
                     'valor' => 'Cómo llegar'],
                    ['clave' => 'contacto.mapa.enlace', 'rotulo' => 'Enlace externo', 'tipo' => 'texto',
                     'valor' => 'Abrir en Google Maps'],
                ],
                'seo' => ['ruta' => 'contacto', 'etiqueta' => 'Visítanos en el Restrepo'],
            ],
            'quienes' => [
                'titulo' => 'Quiénes somos',
                'subtitulo' => 'La página con la historia del negocio y las preguntas frecuentes.',
                'textos' => [],
                'seo' => ['ruta' => 'quienes-somos', 'etiqueta' => 'Quiénes somos'],
            ],
            'mantenimientos' => [
                'titulo' => 'Mantenimientos',
                'subtitulo' => 'La página que invita a llevar el historial del vehículo.',
                'textos' => [],
                'seo' => ['ruta' => 'mantenimientos', 'etiqueta' => 'Recordatorios de mantenimiento'],
            ],
            'catalogo' => [
                'titulo' => 'Catálogo (listado general)',
                'subtitulo' => 'La página con todas las piezas y el filtro lateral.',
                'textos' => [],
                'seo' => ['ruta' => 'catalogo', 'etiqueta' => 'Catálogo'],
            ],
            'politica' => [
                'titulo' => 'Política de datos',
                'subtitulo' => 'La página legal enlazada desde el pie.',
                'textos' => [],
                'seo' => ['ruta' => 'politica-datos', 'etiqueta' => 'Política de tratamiento de datos'],
            ],
        ];
    }

    /** Crea las filas de textos y SEO conocidas la primera vez que se ven. */
    private function sincronizar(): void
    {
        foreach ($this->secciones() as $slug => $s) {
            foreach ($s['textos'] as $t) {
                Contenido::firstOrCreate(
                    ['clave' => $t['clave']],
                    ['grupo' => $s['titulo'], 'rotulo' => $t['rotulo'], 'tipo' => $t['tipo'],
                     'valor' => $t['valor'], 'valor_ejemplo' => $t['valor']],
                );
            }
            if (isset($s['seo'])) {
                SeoPagina::firstOrCreate(
                    ['ruta' => $s['seo']['ruta']],
                    ['etiqueta' => $s['seo']['etiqueta']],
                );
            }
        }
    }

    public function index(): View
    {
        $this->sincronizar();

        // Diccionarios ya listos para pintar cada tarjeta sin tocar la base
        // varias veces.
        $textos = Contenido::query()->get()->keyBy('clave');
        $seo = SeoPagina::query()->get()->keyBy('ruta');

        return view('panel.pagina.index', [
            'secciones' => $this->secciones(),
            'textos' => $textos,
            'seo' => $seo,
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        // Textos: un input por cada fila de Contenido. Un solo POST guarda
        // toda la página del panel.
        foreach ((array) $request->input('textos', []) as $id => $valor) {
            $fila = Contenido::find((int) $id);
            if (! $fila) continue;
            $fila->update(['valor' => is_string($valor) ? trim($valor) : null]);
        }

        // SEO profesional. Todo llega por el mismo POST; cada string vacío
        // se guarda como null. Las casillas usan un `<input type="hidden">`
        // antes de cada checkbox para que el navegador mande 0 cuando no
        // están marcadas.
        $limpio = static fn ($v): ?string => is_string($v) && trim($v) !== '' ? trim($v) : null;
        $numero = static fn ($v): ?int => is_numeric($v) ? (int) $v : null;

        foreach ((array) $request->input('seo', []) as $id => $c) {
            $fila = SeoPagina::find((int) $id);
            if (! $fila || ! is_array($c)) continue;

            // Hreflang: viene como pares [lang, href]. Se filtran los
            // parciales para no guardar entradas rotas.
            $hreflang = null;
            if (isset($c['hreflang']) && is_array($c['hreflang'])) {
                $hreflang = collect($c['hreflang'])
                    ->map(fn ($h) => is_array($h) ? [
                        'lang' => $limpio($h['lang'] ?? null),
                        'href' => $limpio($h['href'] ?? null),
                    ] : null)
                    ->filter(fn ($h) => $h && $h['lang'] && $h['href'])
                    ->values()
                    ->all();
                $hreflang = $hreflang ?: null;
            }

            $fila->update([
                // Básico
                'slug' => $limpio($c['slug'] ?? null),
                'titulo' => $limpio($c['titulo'] ?? null),
                'titulo_h1' => $limpio($c['titulo_h1'] ?? null),
                'descripcion' => $limpio($c['descripcion'] ?? null),
                'palabras_clave' => $limpio($c['palabras_clave'] ?? null),
                'focus_keyword' => $limpio($c['focus_keyword'] ?? null),
                'canonical' => $limpio($c['canonical'] ?? null),

                // OG
                'og_titulo' => $limpio($c['og_titulo'] ?? null),
                'og_descripcion' => $limpio($c['og_descripcion'] ?? null),
                'og_imagen' => $limpio($c['og_imagen'] ?? null),
                'og_imagen_alt' => $limpio($c['og_imagen_alt'] ?? null),
                'og_tipo' => $limpio($c['og_tipo'] ?? null) ?? 'website',
                'og_locale' => $limpio($c['og_locale'] ?? null) ?? 'es_CO',
                'og_locale_alternate' => $limpio($c['og_locale_alternate'] ?? null),
                'og_imagen_ancho' => $numero($c['og_imagen_ancho'] ?? null),
                'og_imagen_alto' => $numero($c['og_imagen_alto'] ?? null),

                // Twitter
                'twitter_card' => $limpio($c['twitter_card'] ?? null) ?? 'summary_large_image',
                'twitter_titulo' => $limpio($c['twitter_titulo'] ?? null),
                'twitter_descripcion' => $limpio($c['twitter_descripcion'] ?? null),
                'twitter_imagen' => $limpio($c['twitter_imagen'] ?? null),
                'twitter_sitio' => $limpio($c['twitter_sitio'] ?? null),
                'twitter_creador' => $limpio($c['twitter_creador'] ?? null),

                // Robots
                'indexable' => (bool) ($c['indexable'] ?? false),
                'seguir_enlaces' => (bool) ($c['seguir_enlaces'] ?? false),
                'max_snippet' => $numero($c['max_snippet'] ?? null),
                'max_image_preview' => $limpio($c['max_image_preview'] ?? null) ?? 'large',
                'max_video_preview' => $numero($c['max_video_preview'] ?? null),
                'noarchive' => (bool) ($c['noarchive'] ?? false),
                'nosnippet' => (bool) ($c['nosnippet'] ?? false),
                'noimageindex' => (bool) ($c['noimageindex'] ?? false),
                'notranslate' => (bool) ($c['notranslate'] ?? false),

                // Article
                'article_publicado_en' => $limpio($c['article_publicado_en'] ?? null),
                'article_modificado_en' => $limpio($c['article_modificado_en'] ?? null),
                'article_seccion' => $limpio($c['article_seccion'] ?? null),
                'article_etiquetas' => $limpio($c['article_etiquetas'] ?? null),
                'article_autor' => $limpio($c['article_autor'] ?? null),

                // Hreflang y paginación
                'hreflang' => $hreflang,
                'rel_prev' => $limpio($c['rel_prev'] ?? null),
                'rel_next' => $limpio($c['rel_next'] ?? null),

                // Sitemap
                'sitemap_incluir' => (bool) ($c['sitemap_incluir'] ?? false),
                'sitemap_frecuencia' => $limpio($c['sitemap_frecuencia'] ?? null) ?? 'weekly',
                'sitemap_prioridad' => is_numeric($c['sitemap_prioridad'] ?? null)
                    ? max(0.0, min(1.0, (float) $c['sitemap_prioridad']))
                    : 0.5,

                // Avanzado
                'json_ld_extra' => $limpio($c['json_ld_extra'] ?? null),
                'schema_tipo' => $limpio($c['schema_tipo'] ?? null),
                'head_extra' => $limpio($c['head_extra'] ?? null),
            ]);
        }

        return back()->with('mensaje', 'Configuración de página actualizada.');
    }
}
