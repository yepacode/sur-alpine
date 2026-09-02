<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Contenido;
use App\Services\ImagenesWeb;
use App\Models\SeoPagina;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * F · «Textos e imágenes» — un solo panel, secciones adentro.
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
    public function __construct(private readonly ImagenesWeb $imagenes) {}


    /**
     * Mapa de secciones. Cada sección define:
     *   · textos:  claves editables (con su rótulo, tipo y texto original)
     *   · seo:     nombre de ruta cuya `title/description/og:image` toca
     *              esta sección (opcional).
     *
     * @return array<string, array{titulo: string, subtitulo: string, textos: list<array{clave:string,rotulo:string,tipo:string,valor:string}>, seo?: array{ruta:string, etiqueta:string}}>
     */
    /**
     * Los tipos de schema que el panel ofrece.
     *
     * Vive aquí, y no sólo en el blade, porque el servidor tiene que validar
     * contra la misma lista: un `<select>` cerrado en pantalla no impide nada
     * a quien manda el POST a mano.
     *
     * @var list<string>
     */
    public const TIPOS_DE_SCHEMA = [
        'WebPage', 'AboutPage', 'ContactPage', 'CollectionPage', 'Product',
        'Article', 'BlogPosting', 'FAQPage', 'HowTo', 'LocalBusiness',
        'AutoPartsStore', 'Organization', 'Event', 'Recipe',
    ];

    public function secciones(): array
    {
        return [
            // El hero se retiró de la portada a pedido del cliente, así que sus
            // tres textos ya no se pintan en ningún lado. Se quitan también de
            // aquí: un campo que se guarda y no cambia nada en la web es peor
            // que no tenerlo, porque el asesor cree que editó algo.
            'buscador' => [
                'titulo' => 'Buscador de vehículo',
                'subtitulo' => 'La tarjeta blanca que abre la portada.',
                'seo' => ['ruta' => 'inicio', 'etiqueta' => 'Portada'],
                'textos' => [
                    // El h1 de la portada. Va oculto para lectores de pantalla y
                    // buscadores, y era el único texto de la página que el dueño
                    // no podía tocar —siendo la señal más fuerte que tiene Google
                    // para separarlos de las páginas que los suplantan—.
                    ['clave' => 'inicio.h1', 'rotulo' => 'Título de la página para Google', 'tipo' => 'texto',
                     'valor' => 'Importadora Sur Alpine · Repuestos y autopartes en Bogotá'],
                    ['clave' => 'buscador.titulo', 'rotulo' => 'Rótulo', 'tipo' => 'texto',
                     'valor' => 'Busca por tu vehículo'],
                    ['clave' => 'buscador.subtitulo', 'rotulo' => 'Frase de apoyo', 'tipo' => 'texto',
                     'valor' => 'y te mostramos sólo lo que le sirve'],
                    ['clave' => 'buscador.boton', 'rotulo' => 'Botón principal', 'tipo' => 'boton',
                     'valor' => 'Buscar'],
                ],
            ],
            // Los tres títulos azules con la línea roja que separan la portada.
            // Van juntos porque son la misma pieza repetida: si el cliente le
            // cambia el nombre a una sección, aquí los ve los tres a la vez.
            'portada' => [
                'titulo' => 'Títulos de la portada',
                'subtitulo' => 'Los rótulos azules con la línea roja debajo.',
                'textos' => [
                    ['clave' => 'inicio.categorias.titulo', 'rotulo' => 'Sobre la rejilla de fotos', 'tipo' => 'texto',
                     'valor' => 'Categorías Autopartes'],
                    ['clave' => 'inicio.servicios.titulo', 'rotulo' => 'Sobre las dos tarjetas', 'tipo' => 'texto',
                     'valor' => 'Nuestros Servicios'],
                    ['clave' => 'inicio.destacados.titulo', 'rotulo' => 'Sobre el carrusel de piezas', 'tipo' => 'texto',
                     'valor' => 'Productos Destacados'],
                    ['clave' => 'inicio.notas.titulo', 'rotulo' => 'Sobre las noticias', 'tipo' => 'texto',
                     'valor' => 'Actualízate con Nosotros'],
                ],
            ],
            // Los tres bloques con ícono rojo que van debajo del buscador.
            //
            // AVISO PARA EL CLIENTE: en su sitio actual los tres repiten el
            // MISMO párrafo («Nuestro equipo cuenta con amplia experiencia…»).
            // Lo copiamos tal cual porque el encargo era que se viera igual,
            // pero se nota que fue un descuido al armar la página. Ahora que
            // son editables, aquí se corrige en un minuto.
            'respaldos' => [
                'titulo' => 'Los tres respaldos',
                'subtitulo' => 'Los bloques con ícono rojo debajo del buscador.',
                'textos' => [
                    ['clave' => 'respaldo.1.titulo', 'rotulo' => 'Primero · título', 'tipo' => 'texto',
                     'valor' => 'Asesoría Especializada'],
                    ['clave' => 'respaldo.1.texto', 'rotulo' => 'Primero · texto', 'tipo' => 'parrafo',
                     'valor' => 'Nuestro equipo cuenta con amplia experiencia en el sector automotriz para brindarte la mejor orientación.'],
                    ['clave' => 'respaldo.2.titulo', 'rotulo' => 'Segundo · título', 'tipo' => 'texto',
                     'valor' => 'Variedad de Marcas'],
                    ['clave' => 'respaldo.2.texto', 'rotulo' => 'Segundo · texto', 'tipo' => 'parrafo',
                     'valor' => 'Nuestro equipo cuenta con amplia experiencia en el sector automotriz para brindarte la mejor orientación.'],
                    ['clave' => 'respaldo.3.titulo', 'rotulo' => 'Tercero · título', 'tipo' => 'texto',
                     'valor' => 'Respaldo y Garantía'],
                    ['clave' => 'respaldo.3.texto', 'rotulo' => 'Tercero · texto', 'tipo' => 'parrafo',
                     'valor' => 'Nuestro equipo cuenta con amplia experiencia en el sector automotriz para brindarte la mejor orientación.'],
                ],
            ],
            'servicios' => [
                'titulo' => 'Nuestros Servicios · tarjeta roja',
                'subtitulo' => 'La tarjeta del historial de mantenimientos. La otra es el video de envíos.',
                'textos' => [
                    ['clave' => 'servicios.historial.titulo', 'rotulo' => 'Título', 'tipo' => 'texto',
                     'valor' => 'Historial de mantenimientos'],
                    ['clave' => 'servicios.historial.texto', 'rotulo' => 'Texto', 'tipo' => 'parrafo',
                     'valor' => 'Regístrate en nuestra página web y lleva el seguimiento de todos los servicios y mantenimientos del vehículo.'],
                    ['clave' => 'servicios.historial.boton', 'rotulo' => 'Botón (sin sesión)', 'tipo' => 'boton',
                     'valor' => 'Registrar ahora'],
                    ['clave' => 'servicios.historial.boton_dentro', 'rotulo' => 'Botón (con sesión)', 'tipo' => 'boton',
                     'valor' => 'Ver mi historial'],
                    ['clave' => 'servicios.historial.imagen', 'rotulo' => 'Foto de la tarjeta', 'tipo' => 'imagen',
                     'anchos' => [520, 900], 'valor' => '/img/promo/senor'],
                ],
            ],
            'ubicacion' => [
                'titulo' => '¿Dónde estamos ubicados?',
                'subtitulo' => 'El bloque con el video del local. La dirección y los teléfonos salen de «Datos y correos».',
                'textos' => [
                    ['clave' => 'ubicacion.titulo', 'rotulo' => 'Título', 'tipo' => 'texto',
                     'valor' => '¿Dónde estamos ubicados?'],
                    ['clave' => 'ubicacion.texto', 'rotulo' => 'Texto', 'tipo' => 'parrafo',
                     'valor' => 'Importadora Sur Alpine cuenta con un único punto de atención en Bogotá, con un equipo de asesores expertos que te ayudarán a encontrar la pieza exacta que necesita tu vehículo. Nuestra ubicación estratégica te permite llegar fácilmente y acceder rápidamente a soluciones confiables y de calidad.'],
                    ['clave' => 'marcas.titulo', 'rotulo' => 'Título de «Marcas destacadas»', 'tipo' => 'texto',
                     'valor' => 'Marcas destacadas'],
                    ['clave' => 'ubicacion.mapa', 'rotulo' => 'Imagen del mapa', 'tipo' => 'imagen',
                     'anchos' => [220, 440], 'valor' => '/img/mapa/mapa-restrepo'],
                ],
            ],
            // El pie sale en TODAS las páginas y no tenía una sola clave.
            'pie' => [
                'titulo' => 'Pie de página',
                'subtitulo' => 'Los títulos de las columnas, el newsletter y la línea de derechos.',
                'textos' => [
                    ['clave' => 'pie.menu', 'rotulo' => 'Columna 1 · título', 'tipo' => 'texto', 'valor' => 'Menú'],
                    ['clave' => 'pie.enlaces', 'rotulo' => 'Columna 2 · título', 'tipo' => 'texto', 'valor' => 'Enlaces de interés'],
                    ['clave' => 'pie.legales', 'rotulo' => 'Columna 3 · título', 'tipo' => 'texto', 'valor' => 'Legales'],
                    ['clave' => 'pie.redes', 'rotulo' => 'Columna 4 · título', 'tipo' => 'texto', 'valor' => 'Nuestras redes sociales'],
                    ['clave' => 'pie.newsletter.boton', 'rotulo' => 'Botón del newsletter', 'tipo' => 'boton',
                     'valor' => 'Suscríbete al newsletter'],
                    ['clave' => 'pie.newsletter.gracias', 'rotulo' => 'Al suscribirse', 'tipo' => 'parrafo',
                     'valor' => 'Listo, quedaste suscrito. Te escribiremos cuando haya algo que valga la pena.'],
                    ['clave' => 'pie.derechos', 'rotulo' => 'Línea de derechos', 'tipo' => 'texto',
                     'valor' => 'Todos los derechos reservados'],
                ],
            ],
            'cabecera' => [
                'titulo' => 'Cabecera y menú',
                'subtitulo' => 'La barra que está siempre arriba.',
                'textos' => [
                    ['clave' => 'menu.sobre', 'rotulo' => 'Barra azul · primer enlace', 'tipo' => 'texto',
                     'valor' => '¿Quiénes somos?'],
                    ['clave' => 'menu.contacto', 'rotulo' => 'Barra azul · segundo enlace', 'tipo' => 'texto',
                     'valor' => 'Contáctanos'],
                    ['clave' => 'menu.catalogo', 'rotulo' => 'Abre las categorías', 'tipo' => 'texto', 'valor' => 'Productos'],
                    ['clave' => 'menu.vehiculo', 'rotulo' => 'Elegir vehículo', 'tipo' => 'texto',
                     'valor' => 'Agregar vehículo'],
                    ['clave' => 'menu.perfil', 'rotulo' => 'Acceso a la cuenta', 'tipo' => 'texto', 'valor' => 'Mi perfil'],
                    ['clave' => 'menu.cotizar', 'rotulo' => 'Ícono «Mi cotización»', 'tipo' => 'texto',
                     'valor' => 'Mi cotización'],
                    ['clave' => 'menu.mantenimientos', 'rotulo' => 'Menú móvil · mantenimientos', 'tipo' => 'texto',
                     'valor' => 'Mantenimientos'],
                ],
            ],
            'cotizacion' => [
                'titulo' => 'Formulario de cotización',
                'subtitulo' => 'La página «Mi cotización» y su acuse de recibo.',
                'textos' => [
                    ['clave' => 'cotizacion.titulo', 'rotulo' => 'Título de la página', 'tipo' => 'texto',
                     'valor' => 'Mi cotización'],
                    ['clave' => 'cotizacion.boton', 'rotulo' => 'Botón enviar', 'tipo' => 'boton',
                     'valor' => 'Enviar mi solicitud'],
                    ['clave' => 'cotizacion.gracias', 'rotulo' => 'Mensaje de gracias', 'tipo' => 'parrafo',
                     'valor' => 'Recibimos tu solicitud'],
                ],
            ],
            'acceso' => [
                'titulo' => 'Iniciar sesión / Registro',
                'subtitulo' => 'Las dos páginas del área del cliente.',
                'textos' => [
                    ['clave' => 'acceso.entrar.boton', 'rotulo' => 'Botón entrar', 'tipo' => 'boton', 'valor' => 'Iniciar sesión'],
                    ['clave' => 'registro.crear.boton', 'rotulo' => 'Botón crear cuenta', 'tipo' => 'boton',
                     'valor' => 'Crear mi cuenta'],
                    ['clave' => 'acceso.imagen', 'rotulo' => 'Foto del costado', 'tipo' => 'imagen',
                     'anchos' => [480, 700], 'valor' => '/img/acceso/acceso-motor'],
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
                    ['clave' => 'contacto.horario.semana', 'rotulo' => 'Horario · entre semana', 'tipo' => 'texto',
                     'valor' => 'Lunes a viernes de 8:00 a.m. a 6:00 p.m.'],
                    ['clave' => 'contacto.horario.sabado', 'rotulo' => 'Horario · sábados', 'tipo' => 'texto',
                     'valor' => 'Sábados de 8:00 a.m. a 4:00 p.m.'],
                    ['clave' => 'contacto.horario.festivo', 'rotulo' => 'Horario · festivos', 'tipo' => 'texto',
                     'valor' => 'Festivos de 9:00 a.m. a 1:00 p.m.'],
                    ['clave' => 'contacto.oficinas.nota', 'rotulo' => 'Nota bajo «Oficinas»', 'tipo' => 'parrafo',
                     'valor' => 'Parqueadero vigilado.'],
                    ['clave' => 'contacto.imagen', 'rotulo' => 'Foto de la cabecera', 'tipo' => 'imagen',
                     'anchos' => [1024, 1600], 'valor' => '/img/cabeceras/banner-contactenos'],
                    ['clave' => 'contacto.local', 'rotulo' => 'Foto del local', 'tipo' => 'imagen',
                     'anchos' => [520, 1040], 'valor' => '/img/fotos/local-contactenos'],
                ],
                'seo' => ['ruta' => 'contacto', 'etiqueta' => 'Visítanos en el Restrepo'],
            ],
            'quienes' => [
                'titulo' => 'Quiénes somos',
                'subtitulo' => 'El texto de la página y la foto de su cabecera.',
                'textos' => [
                    ['clave' => 'quienes.imagen', 'rotulo' => 'Foto de la cabecera', 'tipo' => 'imagen',
                     'anchos' => [1024, 1600], 'valor' => '/img/cabeceras/banner-quienes-somos'],
                    // El mismo párrafo que pinta la vista, palabra por palabra.
                    //
                    // Declararlo VACÍO fue lo que hizo desaparecer la historia
                    // de la empresa de «Quiénes somos»: el panel escribía esa
                    // cadena vacía en la base y el sitio la respetaba.
                    //
                    // La vista intercala la dirección del panel; aquí va la de
                    // ahora, porque esto sólo alimenta la casilla mientras
                    // nadie edite. Lo que se publica lo sigue armando la vista.
                    ['clave' => 'quienes.texto', 'rotulo' => 'El párrafo de la empresa', 'tipo' => 'parrafo',
                     'valor' => 'Importadora Sur Alpine es una compañía fundada en el año 1982 con sede en la '
                        .app(\App\Services\Contacto::class)->direccion().'. En su metodología siempre está presente '
                        .'trabajar con esfuerzo, dedicación y responsabilidad, y fue gracias a esto que la compañía '
                        .'está en constante transformación e innovación en sus procesos. Siempre buscando el mejor '
                        .'servicio y calidad para sus clientes, entendiendo y creando nuevas líneas de negocio; un '
                        .'ejemplo es el servicio a domicilio, puesto que movilizarse en la ciudad es cada vez más '
                        .'difícil y toma más tiempo. También expandiéndose a nivel nacional, llegando a diferentes '
                        .'municipios con repuestos de alta calidad.'],
                    ['clave' => 'quienes.aviso.titulo', 'rotulo' => 'Aviso de sitios falsos · título', 'tipo' => 'texto',
                     'valor' => 'Cuidado con los sitios falsos'],
                    ['clave' => 'quienes.aviso.texto', 'rotulo' => 'Aviso de sitios falsos · texto', 'tipo' => 'parrafo',
                     'valor' => 'Circulan páginas que usan nuestro nombre y nuestras fotos. Si tienes dudas, llámanos directamente:'],
                ],
                'seo' => ['ruta' => 'quienes-somos', 'etiqueta' => 'Quiénes somos'],
            ],
            'mantenimientos' => [
                'titulo' => 'Mantenimientos',
                'subtitulo' => 'La página que invita a llevar el historial del vehículo.',
                'textos' => [
                    ['clave' => 'mant.rotulo', 'rotulo' => 'Rótulo rojo de arriba', 'tipo' => 'texto',
                     'valor' => 'Para mecánicos'],
                    ['clave' => 'mant.titulo', 'rotulo' => 'Título', 'tipo' => 'texto',
                     'valor' => 'Historial de mantenimientos'],
                    ['clave' => 'mant.texto', 'rotulo' => 'Bajada', 'tipo' => 'parrafo',
                     'valor' => 'Registra qué le hiciste a tu carro y cuándo. Nosotros calculamos cuándo toca el próximo cambio y te avisamos.'],
                    ['clave' => 'mant.paso1.titulo', 'rotulo' => 'Paso 1 · título', 'tipo' => 'texto',
                     'valor' => 'Registra tu vehículo'],
                    ['clave' => 'mant.paso1.texto', 'rotulo' => 'Paso 1 · texto', 'tipo' => 'parrafo',
                     'valor' => 'Placa, marca, modelo y cilindraje. Puedes tener varios.'],
                    ['clave' => 'mant.paso2.titulo', 'rotulo' => 'Paso 2 · título', 'tipo' => 'texto',
                     'valor' => 'Anota cada servicio'],
                    ['clave' => 'mant.paso2.texto', 'rotulo' => 'Paso 2 · texto', 'tipo' => 'parrafo',
                     'valor' => 'Kilometraje, fecha, qué se cambió y tus notas.'],
                    ['clave' => 'mant.paso3.titulo', 'rotulo' => 'Paso 3 · título', 'tipo' => 'texto',
                     'valor' => 'Te avisamos'],
                    ['clave' => 'mant.paso3.texto', 'rotulo' => 'Paso 3 · texto', 'tipo' => 'parrafo',
                     'valor' => 'Según los kilómetros o el tiempo que definas para cada mantenimiento.'],
                ],
                'seo' => ['ruta' => 'mantenimientos', 'etiqueta' => 'Recordatorios de mantenimiento'],
            ],
            'catalogo' => [
                'titulo' => 'Catálogo (listado general)',
                'subtitulo' => 'Los rótulos del filtro lateral y lo que se lee cuando una búsqueda no encuentra nada.',
                'textos' => [
                    ['clave' => 'catalogo.filtro.vehiculo', 'rotulo' => 'Rótulo «Tu vehículo»', 'tipo' => 'texto',
                     'valor' => 'Tu vehículo'],
                    ['clave' => 'catalogo.filtro.parte', 'rotulo' => 'Rótulo de la lista de categorías', 'tipo' => 'texto',
                     'valor' => 'Categorías'],
                    ['clave' => 'catalogo.vacio.titulo', 'rotulo' => 'Sin resultados · título', 'tipo' => 'texto',
                     'valor' => 'No encontramos repuestos con esa búsqueda'],
                    ['clave' => 'catalogo.vacio.texto', 'rotulo' => 'Sin resultados · sugerencia', 'tipo' => 'parrafo',
                     'valor' => 'Prueba con el nombre de la pieza, por ejemplo «pastillas freno» o «filtro aceite».'],
                ],
                'seo' => ['ruta' => 'catalogo', 'etiqueta' => 'Catálogo'],
            ],
            'noticias' => [
                'titulo' => 'Noticias y novedades',
                'subtitulo' => 'El listado del blog. Las notas se escriben en «Noticias».',
                'textos' => [
                    ['clave' => 'noticias.texto', 'rotulo' => 'Bajada bajo el título', 'tipo' => 'parrafo',
                     'valor' => 'Consejos, tips y novedades sobre el cuidado de tu carro, escritos por el equipo que atiende el mostrador.'],
                    ['clave' => 'noticias.vacio', 'rotulo' => 'Cuando no hay notas', 'tipo' => 'texto',
                     'valor' => 'Todavía no hay notas publicadas.'],
                ],
                'seo' => ['ruta' => 'noticias', 'etiqueta' => 'Noticias y novedades'],
            ],
            'politica' => [
                'titulo' => 'Política de datos',
                // El cuerpo NO se edita aquí a propósito: es un documento
                // legal que redacta el abogado, y una caja de texto invita a
                // retocarlo de a pedazos. Lo que sí cambia cada vez que lo
                // reemplazan es la versión y desde cuándo rige.
                'subtitulo' => 'El documento completo, la versión y la fecha. '
                    .'Subir la versión hace que todo el mundo vuelva a aceptar la política.',
                'textos' => [
                    ['clave' => 'politica.version', 'rotulo' => 'Versión del documento', 'tipo' => 'texto',
                     'valor' => (string) config('habeas.version')],
                    ['clave' => 'politica.vigencia', 'rotulo' => 'Vigente desde (aaaa-mm-dd)', 'tipo' => 'texto',
                     'valor' => (string) config('habeas.vigente_desde')],
                    // El cuerpo, editable. Estaba clavado en el código: para
                    // cambiarle una coma a un documento que redacta un abogado
                    // había que llamarnos. Vacío, se usa el texto de fábrica.
                    ['clave' => 'politica.cuerpo', 'rotulo' => 'Texto completo de la política', 'tipo' => 'documento',
                     'valor' => ''],
                ],
                'seo' => ['ruta' => 'politica-datos', 'etiqueta' => 'Política de tratamiento de datos'],
            ],
            'terminos' => [
                'titulo' => 'Términos y condiciones',
                'subtitulo' => 'La otra página legal del pie, con su documento completo.',
                'textos' => [
                    ['clave' => 'terminos.vigencia', 'rotulo' => 'Última actualización (aaaa-mm-dd)', 'tipo' => 'texto',
                     'valor' => (string) config('habeas.vigente_desde')],
                    ['clave' => 'terminos.cuerpo', 'rotulo' => 'Texto completo de los términos', 'tipo' => 'documento',
                     'valor' => ''],
                ],
                'seo' => ['ruta' => 'terminos', 'etiqueta' => 'Términos y condiciones'],
            ],
        ];
    }

    /**
     * Qué anchos necesita cada imagen editable.
     *
     * Vive con la definición de las secciones y no en la base: es una decisión
     * de maquetación —cuánto mide esa foto en pantalla— y no algo que el
     * cliente deba elegir.
     *
     * @return array<string, list<int>>
     */
    /**
     * El id de cada fila de imagen contra el nombre que el cliente ve.
     *
     * Sirve para que un error de validación diga «La foto de la cabecera» en
     * vez de «imagenes.44»: ese número es la llave primaria de `contenido` y
     * no aparece en ninguna parte de la pantalla.
     *
     * @return array<int, string>
     */
    private function rotulosDeImagen(): array
    {
        $porClave = collect($this->secciones())
            ->flatMap(fn ($seccion) => collect($seccion['textos'])
                ->filter(fn ($t) => $t['tipo'] === 'imagen')
                ->mapWithKeys(fn ($t) => [$t['clave'] => mb_strtolower($t['rotulo'])]))
            ->all();

        return Contenido::query()
            ->whereIn('clave', array_keys($porClave))
            ->pluck('clave', 'id')
            ->map(fn ($clave) => $porClave[$clave])
            ->all();
    }

    private function anchosPorClave(): array
    {
        $mapa = [];

        foreach ($this->secciones() as $s) {
            foreach ($s['textos'] as $t) {
                if (($t['tipo'] ?? null) === 'imagen') {
                    $mapa[$t['clave']] = $t['anchos'] ?? [900, 1600];
                }
            }
        }

        return $mapa;
    }

    /** Crea las filas de textos y SEO conocidas la primera vez que se ven. */
    /**
     * Crea las filas que falten para los campos declarados en `secciones()`.
     *
     * Dos consultas de lectura y, casi siempre, ninguna de escritura.
     *
     * Antes era un `firstOrCreate` por campo en CADA carga de la pantalla: 73
     * selects a `contenidos` más 9 a `seo_paginas`, o sea 82 consultas
     * evitables en la única página del panel que pasaba de once. Y todas para
     * descubrir lo mismo: que ya estaban todas.
     */
    private function sincronizar(): void
    {
        $secciones = $this->secciones();

        $yaEstan = Contenido::query()->pluck('clave')->flip();

        $faltan = [];

        foreach ($secciones as $s) {
            foreach ($s['textos'] as $t) {
                if ($yaEstan->has($t['clave'])) {
                    continue;
                }

                $faltan[] = [
                    'clave' => $t['clave'],
                    'grupo' => $s['titulo'],
                    'rotulo' => $t['rotulo'],
                    'tipo' => $t['tipo'],
                    // `valor` nace en NULO, y es importante: significa «nadie ha
                    // tocado esto, manda lo que pinta la vista».
                    //
                    // Antes se escribía aquí el texto declarado más abajo en
                    // `secciones()`, y eso convertía esa declaración en el
                    // contenido REAL del sitio en cuanto alguien abría la
                    // pantalla por primera vez. Si no coincidía con el texto de
                    // la vista —y cinco no coincidían— la web cambiaba sola sin
                    // que el dueño tocara nada. Con `quienes.texto`, declarado
                    // como cadena vacía, la historia de la empresa desaparecía
                    // de «Quiénes somos».
                    //
                    // El texto declarado se guarda sólo en `valor_ejemplo`, que
                    // es lo que la casilla del panel enseña mientras nadie
                    // edite. Una sola fuente de verdad: la vista.
                    'valor' => null,
                    'valor_ejemplo' => $t['valor'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($faltan !== []) {
            // `insertOrIgnore` y no `insert`: dos administradores abriendo la
            // pantalla a la vez la primera vez chocarían con la clave única.
            // `insertOrIgnore` no dispara el evento `saved`, así que la caché
            // del mapa hay que olvidarla a mano.
            Contenido::query()->insertOrIgnore($faltan);
            \Illuminate\Support\Facades\Cache::forget(Contenido::LLAVE_CACHE);
        }

        $rutasQueHay = SeoPagina::query()->pluck('ruta')->flip();

        $seoFaltantes = collect($secciones)
            ->filter(fn ($s) => isset($s['seo']) && ! $rutasQueHay->has($s['seo']['ruta']))
            ->map(fn ($s) => [
                'ruta' => $s['seo']['ruta'],
                'etiqueta' => $s['seo']['etiqueta'],
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->unique('ruta')
            ->values()
            ->all();

        if ($seoFaltantes !== []) {
            SeoPagina::query()->insertOrIgnore($seoFaltantes);
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
        // TODAS las imágenes se validan ANTES de escribir un solo texto.
        //
        // Antes se validaba una por una dentro del bucle que ya venía después
        // de guardar los 66 textos, así que subir un PDF en el campo de foto
        // dejaba al cliente con el recuadro rojo «No pudimos guardar todo» —y
        // con los textos YA cambiados en la web—. Veía un error, asumía que no
        // había pasado nada, y la portada había cambiado.
        //
        // El rótulo humano va en `attributes` para que el mensaje diga «La
        // foto de la cabecera tiene que ser...» y no «imagenes.44», que es el
        // id de una fila de la base y no le dice cuál de las diez falló.
        $archivos = array_filter(
            (array) $request->file('imagenes', []),
            fn ($archivo) => $archivo && $archivo->isValid()
        );

        if ($archivos !== []) {
            $rotulos = $this->rotulosDeImagen();

            $request->validate(
                collect($archivos)->mapWithKeys(fn ($_, $id) => [
                    "imagenes.{$id}" => ['image', 'mimes:webp,jpg,jpeg,png', 'max:8192'],
                ])->all(),
                collect($archivos)->flatMap(fn ($_, $id) => [
                    "imagenes.{$id}.image" => 'Sube una imagen (WebP, JPG o PNG).',
                    "imagenes.{$id}.max" => 'La imagen no puede pesar más de 8 MB.',
                ])->all(),
                collect($archivos)->mapWithKeys(fn ($_, $id) => [
                    "imagenes.{$id}" => $rotulos[(int) $id] ?? 'la imagen',
                ])->all()
            );
        }

        // La longitud, ANTES de escribir nada.
        //
        // `contenidos.valor` es un TEXT de 65.535 bytes y MySQL va en modo
        // estricto: un documento legal más largo reventaba con «Data too long»
        // a mitad del bucle, dejando escritos los campos anteriores y sin
        // escribir los siguientes. El administrador veía una pantalla de error
        // y daba por hecho que no se había guardado nada, mientras los
        // términos y condiciones ya habían desaparecido del sitio.
        //
        // Antes esto no podía pasar porque ningún campo pasaba de 500
        // caracteres; lo abrió la caja del documento legal, que no tiene tope.
        $request->validate(
            collect((array) $request->input('textos', []))
                ->mapWithKeys(fn ($_, $id) => ["textos.{$id}" => ['nullable', 'string', 'max:60000']])
                ->all(),
            [],
            collect((array) $request->input('textos', []))
                ->mapWithKeys(fn ($_, $id) => ["textos.{$id}" => 'el texto'])
                ->all()
        );

        // Textos: un input por cada fila de Contenido. Un solo POST guarda
        // toda la página del panel.
        //
        // El vacío se guarda como cadena vacía, NO como `null`: son cosas
        // distintas y `contenido()` las trata distinto —`null` significa
        // «nunca se tocó, usa el original» y `''` significa «el cliente borró
        // esto a propósito»—. El middleware `ConvertEmptyStringsToNull` ya
        // convirtió el `''` del formulario en `null` antes de llegar aquí, así
        // que sin esta línea borrar un texto no lo borraba: se guardaba `null`
        // y la web seguía mostrando el original. El formulario manda las 66
        // claves siempre, así que un `null` aquí sólo puede ser un campo que
        // alguien dejó en blanco.
        foreach ((array) $request->input('textos', []) as $id => $valor) {
            $fila = Contenido::find((int) $id);

            if (! $fila) {
                continue;
            }

            $nuevo = is_string($valor) ? trim($valor) : '';

            // Si devuelve EXACTAMENTE el texto de fábrica, se deja en nulo.
            //
            // «Nulo» significa «nadie ha tocado esto», y eso importa porque
            // varios textos tienen un valor por defecto que se CALCULA: el
            // horario se arma desde «Datos y correos» y el párrafo de la
            // empresa intercala su dirección. En cuanto la fila deja de estar
            // en nulo, ese cálculo se congela: el dueño cambia la dirección en
            // el panel y «Quiénes somos» sigue diciendo la vieja.
            //
            // Sin esto, abrir la pantalla y guardar sin tocar nada pasaba 69
            // filas de «sin definir» a «definido» de un plumazo, y el sitio se
            // quedaba clavado en la foto de ese momento.
            // Ojo con el vacío: sólo se deja en nulo cuando coincide con un
            // texto de fábrica que EXISTE. Si no hubiera texto de fábrica,
            // vaciar la casilla y no haberla tocado nunca serían la misma
            // cosa, y el sitio volvería a enseñar lo que el dueño quiso quitar.
            $deFabrica = (string) $fila->valor_ejemplo;

            // Un documento legal vacío significa «usa el que trae el sitio»,
            // no «déjalo en blanco»: así lo lee su vista, que cae al texto de
            // fábrica cuando no hay nada escrito. Para un rótulo o un botón,
            // en cambio, vaciar la casilla es una orden: «que esto no salga».
            $sinTocar = $fila->tipo === 'documento'
                ? $nuevo === '' || $nuevo === $deFabrica
                : ($deFabrica !== '' && $nuevo === $deFabrica);

            $fila->update(['valor' => $sinTocar ? null : $nuevo]);
        }

        // Las imágenes viajan aparte porque son archivos. Sólo se tocan las
        // filas donde de verdad subieron algo: el formulario manda las diez a
        // la vez, y un `<input type="file"> ` vacío no puede borrar la foto
        // que ya estaba.
        $anchos = $this->anchosPorClave();

        foreach ($archivos as $id => $archivo) {
            $fila = Contenido::find((int) $id);

            if (! $fila) {
                continue;
            }

            $anterior = $fila->valor;

            try {
                $fila->update([
                    'valor' => $this->imagenes->guardarEditable(
                        $archivo,
                        $fila->clave,
                        $anchos[$fila->clave] ?? [900, 1600]
                    ),
                ]);

                // La anterior se borra DESPUÉS de que la nueva quedó guardada.
                // Sin esto `public/img/editables` crecía sin límite: cada
                // cambio de foto dejaba la vieja ahí para siempre. Sólo se
                // toca lo que vive en esa carpeta —las de fábrica, que están
                // en `img/cabeceras` o `img/fotos`, no se tocan nunca—.
                if ($anterior && str_starts_with($anterior, '/img/editables/')) {
                    $this->imagenes->borrarEditable($anterior);
                }
            } catch (\RuntimeException $e) {
                return back()->withErrors(["imagenes.{$id}" => $e->getMessage()]);
            }
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
                'titulo' => $limpio($c['titulo'] ?? null),
                'descripcion' => $limpio($c['descripcion'] ?? null),
                'palabras_clave' => $limpio($c['palabras_clave'] ?? null),
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
                //
                // Lo que NO viene en el formulario se deja como estaba, en vez
                // de caer en el valor por defecto. Para la portada el blade no
                // pinta la casilla —va siempre, y la muestra desactivada—, asi
                // que no se posteaba nada y cada guardado la marcaba
                // `sitemap_incluir = false`: la raiz del sitio quedaba apagada
                // en silencio y solo la salvaba un guardian del
                // SitemapController. Lo mismo aplastaba las prioridades de
                // fabrica —portada 1.0, catalogo 0.9, legales 0.2— dejandolas
                // todas en 0.5 sin que nadie tocara ese campo.
                'sitemap_incluir' => array_key_exists('sitemap_incluir', $c)
                    ? (bool) $c['sitemap_incluir']
                    : $fila->sitemap_incluir,
                'sitemap_frecuencia' => $limpio($c['sitemap_frecuencia'] ?? null) ?? $fila->sitemap_frecuencia,
                'sitemap_prioridad' => is_numeric($c['sitemap_prioridad'] ?? null)
                    ? max(0.0, min(1.0, (float) $c['sitemap_prioridad']))
                    : $fila->sitemap_prioridad,

                // Avanzado
                'json_ld_extra' => $limpio($c['json_ld_extra'] ?? null),
                // Contra la MISMA lista que pinta el desplegable: en pantalla
                // es un `<select>` cerrado, pero por POST llegaba cualquier
                // texto de hasta 40 caracteres directo al `@type` del JSON-LD.
                // `</script><svg onload=alert(1)>` mide exactamente 30.
                'schema_tipo' => in_array($limpio($c['schema_tipo'] ?? null), self::TIPOS_DE_SCHEMA, true)
                    ? $limpio($c['schema_tipo'])
                    : null,
                'head_extra' => $limpio($c['head_extra'] ?? null),
            ]);
        }

        return back()->with('mensaje', 'Textos e imágenes: cambios guardados.');
    }
}
