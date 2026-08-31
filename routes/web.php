<?php

use App\Http\Controllers\AccesoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\AccesoSocialController;
use App\Http\Controllers\ClaveController;
use App\Http\Controllers\CorreoController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\Panel\CatalogoController as PanelCatalogoController;
use App\Http\Controllers\Panel\ConfiguracionController;
use App\Http\Controllers\Panel\ImportacionController;
use App\Http\Controllers\Panel\SolicitudController;
use App\Http\Controllers\Panel\TableroController;
use App\Http\Controllers\Panel\UsuarioController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogoController::class, 'inicio'])->name('inicio');

// Servido por la aplicación y no como archivo estático, para que la URL del
// sitemap salga de APP_URL y no haya que acordarse de cambiarla al desplegar.
/*
 * Las rutas de maquina, fuera de la sesion.
 *
 * `SESSION_DRIVER=database` abre una fila por visita ANONIMA: dos `select`
 * y un `insert` por peticion. En un sitemap eso es TODO el trabajo de base
 * de datos de la ruta -la respuesta sale de cache y aun asi abre una fila-
 * y encima le pega una `Set-Cookie` a un archivo XML, que es justo lo que
 * impide que un proxy lo guarde.
 *
 * Un rastreo completo son unas 29.600 peticiones sin cookie: otras tantas
 * filas y unas 89.000 consultas de sesion POR rastreador, y `robots.txt`
 * declara cinco. Aqui no hay formularios ni carrito: no hay nada que
 * guardar. Es el mismo tratamiento que ya tenia `/vehiculos.json`.
 */
Route::withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
])->group(function () {
    Route::get('/robots.txt', fn () => response()
        ->view('robots')
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        // Un día de caché en CDN/navegador: los rastreadores lo consultan de
        // sobra sin exigir el archivo fresco a cada visita.
        ->header('Cache-Control', 'public, max-age=86400'))->name('robots');

    // G · Convención https://llmstxt.org. Mapa del sitio para modelos de
    // lenguaje: qué somos, qué páginas hay, qué no ofrecemos.
    Route::get('/llms.txt', function () {
        $categorias = \App\Models\Categoria::orderBy('nombre')->get();

        return response()
            ->view('llms', [
                'categorias' => $categorias,
                // Calculados, no escritos a mano: «44 años» y «~29.000 referencias»
                // estaban en el texto, y en 2027 este archivo iba a seguir diciendo
                // 44 mientras un modelo lo repetía como dato de la empresa.
                'anios' => now()->year - 1982,
                'referencias' => \App\Models\Producto::publicados()->count(),
                'marcas' => \App\Models\Marca::count(),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    })->name('llms');

    // El mapa del sitio: sin él Google sólo descubre el catálogo saltando de
    // enlace en enlace desde diez categorías, y son 29.272 fichas.
    Route::get('/sitemap.xml', [SitemapController::class, 'indice'])->name('sitemap');
    Route::get('/sitemap-{nombre}.xml', [SitemapController::class, 'mapa'])
        ->where('nombre', '[a-z0-9-]+')
        ->name('sitemap.mapa');
});

Route::view('/quienes-somos', 'paginas.quienes-somos')->name('quienes-somos');
Route::view('/contactenos', 'paginas.contacto')->name('contacto');
Route::view('/mantenimientos', 'paginas.mantenimientos')->name('mantenimientos');
Route::view('/politica-datos', 'paginas.politica-datos')->name('politica-datos');
Route::view('/terminos', 'paginas.terminos')->name('terminos');

// El «Suscríbete al newsletter» del pie. Sin sesión y en todas las
// páginas: el límite por minuto no es opcional.
// El formulario de «Contáctenos». Como el del newsletter: sin sesión,
// en una página pública, con trampa y con límite por minuto.
Route::post('/contactenos', [ContactoController::class, 'enviar'])
    ->middleware('throttle:5,1')->name('contacto.enviar');

Route::post('/suscribirme', [SuscripcionController::class, 'guardar'])
    ->middleware('throttle:6,1')->name('suscripcion');

// La baja del boletín. Firmada y sin caducidad: es el enlace que va al pie de
// cada correo y tiene que seguir sirviendo dentro de dos años. Sin firma,
// cualquiera daría de baja el correo de otro escribiéndolo en la URL.
Route::get('/baja-newsletter/{correo}', [SuscripcionController::class, 'baja'])
    ->middleware(['signed', 'throttle:10,1'])->name('suscripcion.baja');

// «Actualízate con Nosotros». En su WordPress cada nota vive en una URL con la
// fecha y la hora dentro; aquí es `/noticias/{slug}`, que se puede dictar.
Route::get('/noticias', [NotaController::class, 'index'])->name('noticias');
Route::get('/noticias/{nota}', [NotaController::class, 'ver'])->middleware('slug')->name('nota');

Route::get('/repuestos', [CatalogoController::class, 'catalogo'])->name('catalogo');
Route::get('/repuestos/{categoria}', [CatalogoController::class, 'categoria'])
    ->middleware('slug')->name('categoria');
// El tipo de parte se resuelve DENTRO de su categoría (ver el controlador).
//
// Cuatro slugs existen dos veces —«axial-direccion», «terminal-direccion» y los
// dos retenes de rueda están en Dirección y también en Suspensión—. Sin esto,
// el binding resolvía siempre la fila de Dirección y el guardián del
// controlador mataba con 404 las cuatro URLs de Suspensión… que el sitemap
// publicaba igual. Eran 457 repuestos sin página de aterrizaje y cuatro
// errores de cobertura en Search Console, justo en las consultas de más
// intención («terminal de dirección aveo»).
// Sin el middleware `slug`: aquí la corrección la hace el controlador para
// los DOS segmentos de una vez. Con el middleware puesto salía un 301 hacia
// otro 301 —primero la categoría, después la pieza—, y una cadena de saltos
// es peor que uno solo para quien llega y para quien rastrea.
Route::get('/repuestos/{categoria}/{tipoParte}', [CatalogoController::class, 'tipoParte'])
    ->name('tipo-parte');

// `slug`: MySQL resuelve el slug sin distinguir mayúsculas, así que
// `/repuesto/ACEITE-12-1300-RENAULT` respondía 200 con un canonical que
// apuntaba a sí mismo. Eran copias indexables e ilimitadas de las 29.272
// fichas, cada una declarándose la original —la puerta exacta que le sirve a
// quien está suplantando a Sur Alpine—.
Route::get('/repuesto/{producto}', [CatalogoController::class, 'producto'])
    ->middleware('slug')->name('producto');

// El árbol de vehículos es igual para todo el mundo y no toca la sesión, así
// que sale de ella: una respuesta marcada `public` que arrastra la cookie de
// sesión es justo lo que un proxy compartido no debería guardar.
//
// `setEtag()` sólo escribe la cabecera; sin `cache.headers` nadie compara el
// `If-None-Match` que manda el navegador y el árbol viajaba entero cada vez.
Route::get('/vehiculos.json', [VehiculoController::class, 'arbol'])
    ->middleware('cache.headers:etag')
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->name('vehiculos.arbol');

Route::get('/sugerencias', [VehiculoController::class, 'sugerencias'])->name('sugerencias');
Route::post('/mi-vehiculo', [VehiculoController::class, 'guardar'])->name('vehiculo.guardar');
Route::post('/mi-vehiculo/quitar', [VehiculoController::class, 'olvidar'])->name('vehiculo.olvidar');

Route::controller(CotizacionController::class)->group(function () {
    Route::get('/mi-cotizacion', 'ver')->name('cotizacion.ver');
    Route::get('/cotizacion-enviada', 'enviada')->name('cotizacion.enviada');

    Route::post('/mi-cotizacion/agregar/{producto}', 'agregar')->name('cotizacion.agregar');
    Route::post('/mi-cotizacion/actualizar/{producto}', 'actualizar')->name('cotizacion.actualizar');
    Route::post('/mi-cotizacion/quitar/{producto}', 'quitar')->name('cotizacion.quitar');
    Route::post('/mi-cotizacion/quitar-vehiculo/{vehiculo}', 'quitarVehiculo')->name('cotizacion.quitar-vehiculo');
    Route::post('/mi-cotizacion/vaciar', 'vaciar')->name('cotizacion.vaciar');

    // Un límite honesto: cinco solicitudes por minuto es más de lo que hace
    // una persona y menos de lo que intenta un robot.
    Route::post('/mi-cotizacion/enviar', 'enviar')
        ->middleware('throttle:5,1')
        ->name('cotizacion.enviar');
});

Route::middleware('guest')->group(function () {
    Route::get('/acceso', [AccesoController::class, 'formulario'])->name('acceso');
    Route::post('/acceso', [AccesoController::class, 'entrar'])->middleware('throttle:20,1')->name('entrar');

    // Entrar con Facebook o con Google. Las rutas existen siempre; el
    // controlador da 404 si el proveedor no está configurado, y la vista
    // no pinta el botón. Así nunca hay un botón que lleve a un error.
    Route::get('/acceso/{proveedor}', [AccesoSocialController::class, 'redirigir'])
        ->whereIn('proveedor', AccesoSocialController::PROVEEDORES)
        ->middleware('throttle:20,1')->name('acceso.social');
    Route::get('/acceso/{proveedor}/volver', [AccesoSocialController::class, 'volver'])
        ->whereIn('proveedor', AccesoSocialController::PROVEEDORES)
        ->middleware('throttle:20,1')->name('acceso.social.volver');

    // Olvidé mi contraseña. Va dentro de `guest` porque quien ya entró no
    // lo necesita: cambiar la clave con la sesión abierta es otra cosa.
    Route::get('/clave-olvidada', [ClaveController::class, 'pedir'])->name('clave.pedir');
    Route::post('/clave-olvidada', [ClaveController::class, 'enviar'])
        ->middleware('throttle:10,1')->name('clave.enviar');
    Route::get('/clave-nueva/{token}', [ClaveController::class, 'formulario'])->name('clave.formulario');
    Route::post('/clave-nueva', [ClaveController::class, 'restablecer'])
        ->middleware('throttle:10,1')->name('clave.restablecer');

    Route::get('/registro', [RegistroController::class, 'formulario'])->name('registro');

    // Tres cuentas por minuto: una persona no crea más, un robot sí lo intenta.
    Route::post('/registro', [RegistroController::class, 'crear'])
        ->middleware('throttle:3,1')
        ->name('registro.crear');
});

/*
 * M3 · El área del cliente: sus vehículos y su historial de mantenimiento.
 *
 * Pide sesión y nada más: «cliente» es el escalón más bajo de la escalera, así
 * que el equipo interno también entra —a lo suyo, no a lo de otro.
 */
Route::middleware('auth')->group(function () {
    Route::get('/mi-cuenta', [CuentaController::class, 'inicio'])->name('cuenta');

    Route::post('/mi-cuenta/vehiculos', [CuentaController::class, 'guardarVehiculo'])->name('cuenta.vehiculo.guardar');
    Route::post('/mi-cuenta/vehiculos/{vehiculo}/quitar', [CuentaController::class, 'quitarVehiculo'])->name('cuenta.vehiculo.quitar');

    // El perfil de un carro guardado: su ficha, su placa y lo que se le
    // ha hecho. Va despues de `/quitar` para que esa ruta no se resuelva
    // como un vehiculo de slug «quitar».
    Route::get('/mi-cuenta/vehiculos/{vehiculo}', [CuentaController::class, 'verVehiculo'])->name('cuenta.vehiculo');
    Route::post('/mi-cuenta/vehiculos/{vehiculo}', [CuentaController::class, 'actualizarVehiculo'])->name('cuenta.vehiculo.actualizar');

    // Confirmar el correo. Se OFRECE, no se exige: no hay ninguna ruta
    // detrás del middleware `verified`. Exigirla dejaría fuera de un golpe
    // a todas las cuentas que ya existen.
    Route::get('/correo/verificar/{id}/{hash}', [CorreoController::class, 'verificar'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/correo/verificar', [CorreoController::class, 'reenviar'])
        ->middleware('throttle:6,1')->name('verificacion.reenviar');

    // Habeas Data · el titular puede llevarse lo que tenemos suyo.
    Route::get('/mi-cuenta/descargar-mis-datos', [CuentaController::class, 'descargarDatos'])
        ->middleware('throttle:6,1')->name('cuenta.descargar');

    // Sus datos: nombre, teléfono, correo y contraseña.
    Route::get('/mi-cuenta/datos', [CuentaController::class, 'datos'])->name('cuenta.datos');
    Route::post('/mi-cuenta/datos', [CuentaController::class, 'guardarDatos'])->name('cuenta.datos.guardar');
    Route::post('/mi-cuenta/clave', [CuentaController::class, 'guardarClave'])->name('cuenta.clave');

    // Sus cotizaciones. Faltaban: el cliente enviaba una solicitud y del
    // lado de él desaparecía.
    Route::get('/mi-cuenta/cotizaciones', [CuentaController::class, 'cotizaciones'])->name('cuenta.cotizaciones');
    Route::get('/mi-cuenta/cotizaciones/{cotizacion}', [CuentaController::class, 'verCotizacion'])->name('cuenta.cotizacion');
    Route::post('/mi-cuenta/cotizaciones/{cotizacion}/repetir', [CuentaController::class, 'repetirCotizacion'])->name('cuenta.cotizacion.repetir');

    // Habeas Data · el titular puede cerrar su cuenta desde el sitio.
    Route::post('/mi-cuenta/dar-de-baja', [CuentaController::class, 'darDeBaja'])->name('cuenta.baja');

    Route::get('/mi-cuenta/mantenimientos', [CuentaController::class, 'mantenimientos'])->name('cuenta.mantenimientos');
    Route::post('/mi-cuenta/mantenimientos', [CuentaController::class, 'guardarMantenimiento'])->name('cuenta.mantenimientos.guardar');
    Route::post('/mi-cuenta/mantenimientos/{mantenimiento}', [CuentaController::class, 'actualizarMantenimiento'])->name('cuenta.mantenimientos.actualizar');
    Route::post('/mi-cuenta/mantenimientos/{mantenimiento}/borrar', [CuentaController::class, 'borrarMantenimiento'])->name('cuenta.mantenimientos.borrar');
});

Route::post('/salir', [AccesoController::class, 'salir'])->middleware('auth')->name('salir');

// Panel interno. Con dos roles ya no hay secciones intermedias: o se entra
// al panel completo o no se entra.
Route::prefix('panel')->name('panel.')->middleware(['auth', 'rol:admin'])->group(function () {
    Route::get('/', TableroController::class)->name('tablero');

    Route::controller(SolicitudController::class)->group(function () {
        Route::get('/solicitudes', 'index')->name('solicitudes');
        Route::get('/solicitudes/exportar', 'exportar')->name('solicitudes.exportar');
        Route::get('/solicitudes/{solicitud}', 'show')->name('solicitudes.ver');
        Route::post('/solicitudes/{solicitud}/reenviar', 'reenviar')->name('solicitudes.reenviar');
    });

    Route::controller(PanelCatalogoController::class)->group(function () {
        Route::get('/catalogo', 'index')->name('catalogo');
        Route::get('/catalogo/nuevo', 'crear')->name('catalogo.crear');
        Route::post('/catalogo/vehiculo', 'guardarVehiculo')->name('catalogo.guardar-vehiculo');

        // Corregir los datos de un vehículo que ya existe. Va por su propia
        // ruta porque `POST /catalogo/vehiculo/{vehiculo}` ya es la matriz
        // de piezas.
        Route::get('/catalogo/vehiculo/{vehiculo}/datos', 'editarDatos')->name('catalogo.datos');
        Route::post('/catalogo/vehiculo/{vehiculo}/datos', 'guardarVehiculo')->name('catalogo.editar-datos');
        Route::get('/catalogo/vehiculo/{vehiculo}', 'editar')->name('catalogo.editar');
        Route::post('/catalogo/vehiculo/{vehiculo}', 'guardarMatriz')->name('catalogo.matriz');
        Route::get('/catalogo/pieza/{producto}', 'editarProducto')->name('catalogo.producto');
        Route::post('/catalogo/pieza/{producto}', 'guardarProducto')->name('catalogo.guardar-producto');
    });

    Route::controller(ImportacionController::class)->group(function () {
        Route::get('/catalogo-importar', 'formulario')->name('catalogo.importar');
        Route::get('/catalogo-importar/plantilla', 'plantilla')->name('catalogo.plantilla');
        Route::post('/catalogo-importar', 'previsualizar')->name('catalogo.previsualizar');
        Route::post('/catalogo-importar/confirmar', 'confirmar')->name('catalogo.confirmar');
    });

    // «Actualízate con Nosotros»: las notas del blog, para que el equipo
    // publique sin depender de nosotros. `crear` va antes que `{nota}`, si
    // no la ruta de alta se resolvería como una nota de slug «nueva».
    Route::controller(\App\Http\Controllers\Panel\NotaController::class)->group(function () {
        Route::get('/notas', 'index')->name('notas');
        Route::get('/notas/nueva', 'crear')->name('notas.crear');
        Route::post('/notas', 'guardar')->name('notas.guardar');
        Route::get('/notas/{nota}', 'editar')->name('notas.editar');
        Route::post('/notas/{nota}', 'guardar')->name('notas.actualizar');
        Route::post('/notas/{nota}/borrar', 'borrar')->name('notas.borrar');
    });

    // La bandeja de «Contáctenos». Se puede marcar como atendido y
    // reenviar el correo si no salió la primera vez.
    Route::get('/mensajes', [\App\Http\Controllers\Panel\MensajeController::class, 'index'])->name('mensajes');
    Route::post('/mensajes/{mensaje}/atender', [\App\Http\Controllers\Panel\MensajeController::class, 'atender'])->name('mensajes.atender');
    Route::post('/mensajes/{mensaje}/reenviar', [\App\Http\Controllers\Panel\MensajeController::class, 'reenviar'])->name('mensajes.reenviar');

    // Los correos del newsletter del pie. Sólo listar y exportar: no
    // hay alta manual a propósito, porque un correo que el equipo
    // escriba a mano no tiene consentimiento de nadie.
    Route::get('/suscriptores', [\App\Http\Controllers\Panel\SuscriptorController::class, 'index'])
        ->name('suscriptores');
    Route::get('/suscriptores/exportar', [\App\Http\Controllers\Panel\SuscriptorController::class, 'exportar'])
        ->name('suscriptores.exportar');
    Route::post('/suscriptores/{suscriptor}/baja', [\App\Http\Controllers\Panel\SuscriptorController::class, 'darDeBaja'])
        ->name('suscriptores.baja');

    // Las campañas de la portada: subir, ordenar, apagar y borrar.
    Route::controller(\App\Http\Controllers\Panel\BannerController::class)->group(function () {
        Route::get('/banners', 'index')->name('banners');
        Route::post('/banners', 'guardar')->name('banners.guardar');
        Route::post('/banners/{banner}', 'actualizar')->name('banners.actualizar');
        Route::post('/banners/{banner}/borrar', 'borrar')->name('banners.borrar');
    });

    // F1 · Categorías: nombre, orden y foto editables desde el panel.
    Route::controller(\App\Http\Controllers\Panel\CategoriaController::class)->group(function () {
        Route::get('/categorias', 'index')->name('categorias');
        Route::get('/categorias/{categoria}', 'editar')->name('categorias.editar');
        Route::post('/categorias/{categoria}', 'guardar')->name('categorias.guardar');
    });

    // F · Configuración de página: un solo panel con hero, buscador,
    // cabecera, cotización, contacto, quiénes somos, mantenimientos y
    // política. Adentro de cada sección: textos, botones y SEO completo.
    Route::controller(\App\Http\Controllers\Panel\ConfiguracionPaginaController::class)->group(function () {
        Route::get('/pagina', 'index')->name('pagina');
        Route::post('/pagina', 'guardar')->name('pagina.guardar');
    });

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios');
    Route::post('/usuarios', [UsuarioController::class, 'guardar'])->name('usuarios.guardar');

    // Sin esta ruta el panel sólo podía crear usuarios: no había forma de
    // desactivar a un empleado que se fue.
    Route::post('/usuarios/{usuario}', [UsuarioController::class, 'guardar'])->name('usuarios.actualizar');

    Route::get('/configuracion', [ConfiguracionController::class, 'editar'])->name('configuracion');
    Route::post('/configuracion', [ConfiguracionController::class, 'guardar'])->name('configuracion.guardar');
    Route::post('/configuracion/probar', [ConfiguracionController::class, 'probarCorreo'])->name('configuracion.probar');
});
