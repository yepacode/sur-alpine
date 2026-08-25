<?php

use App\Http\Controllers\AccesoController;
use App\Http\Controllers\CatalogoController;
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
Route::get('/robots.txt', fn () => response()
    ->view('robots')
    ->header('Content-Type', 'text/plain; charset=UTF-8'))->name('robots');

// G · Convención https://llmstxt.org. Mapa del sitio para modelos de
// lenguaje: qué somos, qué páginas hay, qué no ofrecemos.
Route::get('/llms.txt', function () {
    $categorias = \App\Models\Categoria::orderBy('nombre')->get();
    return response()
        ->view('llms', ['categorias' => $categorias])
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('llms');

// El mapa del sitio: sin él Google sólo descubre el catálogo saltando de
// enlace en enlace desde diez categorías, y son 29.272 fichas.
Route::get('/sitemap.xml', [SitemapController::class, 'indice'])->name('sitemap');
Route::get('/sitemap-{nombre}.xml', [SitemapController::class, 'mapa'])
    ->where('nombre', '[a-z0-9-]+')
    ->name('sitemap.mapa');

Route::view('/quienes-somos', 'paginas.quienes-somos')->name('quienes-somos');
Route::view('/contactenos', 'paginas.contacto')->name('contacto');
Route::view('/mantenimientos', 'paginas.mantenimientos')->name('mantenimientos');
Route::view('/politica-datos', 'paginas.politica-datos')->name('politica-datos');

Route::get('/repuestos', [CatalogoController::class, 'catalogo'])->name('catalogo');
Route::get('/repuestos/{categoria}', [CatalogoController::class, 'categoria'])->name('categoria');
Route::get('/repuestos/{categoria}/{tipoParte}', [CatalogoController::class, 'tipoParte'])->name('tipo-parte');

Route::get('/repuesto/{producto}', [CatalogoController::class, 'producto'])->name('producto');

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

    // Habeas Data · el titular puede cerrar su cuenta desde el sitio.
    Route::post('/mi-cuenta/dar-de-baja', [CuentaController::class, 'darDeBaja'])->name('cuenta.baja');

    Route::get('/mi-cuenta/mantenimientos', [CuentaController::class, 'mantenimientos'])->name('cuenta.mantenimientos');
    Route::post('/mi-cuenta/mantenimientos', [CuentaController::class, 'guardarMantenimiento'])->name('cuenta.mantenimientos.guardar');
    Route::post('/mi-cuenta/mantenimientos/{mantenimiento}', [CuentaController::class, 'actualizarMantenimiento'])->name('cuenta.mantenimientos.actualizar');
    Route::post('/mi-cuenta/mantenimientos/{mantenimiento}/borrar', [CuentaController::class, 'borrarMantenimiento'])->name('cuenta.mantenimientos.borrar');
});

Route::post('/salir', [AccesoController::class, 'salir'])->middleware('auth')->name('salir');

// Panel interno. Los roles son una escalera, así que cada sección pide su mínimo.
Route::prefix('panel')->name('panel.')->middleware(['auth', 'rol:vendedor'])->group(function () {
    Route::get('/', TableroController::class)->name('tablero');

    Route::controller(SolicitudController::class)->group(function () {
        Route::get('/solicitudes', 'index')->name('solicitudes');
        Route::get('/solicitudes/exportar', 'exportar')->name('solicitudes.exportar');
        Route::get('/solicitudes/{solicitud}', 'show')->name('solicitudes.ver');
        Route::post('/solicitudes/{solicitud}/reenviar', 'reenviar')->name('solicitudes.reenviar');
    });

    // El catálogo lo edita el asesor; el vendedor sólo consulta solicitudes.
    Route::middleware('rol:asesor')->group(function () {
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
    });

    Route::middleware('rol:admin')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios');
        Route::post('/usuarios', [UsuarioController::class, 'guardar'])->name('usuarios.guardar');

        // Sin esta ruta el panel sólo podía crear usuarios: no había forma de
        // desactivar a un empleado que se fue.
        Route::post('/usuarios/{usuario}', [UsuarioController::class, 'guardar'])->name('usuarios.actualizar');

        Route::get('/configuracion', [ConfiguracionController::class, 'editar'])->name('configuracion');
        Route::post('/configuracion', [ConfiguracionController::class, 'guardar'])->name('configuracion.guardar');
        Route::post('/configuracion/probar', [ConfiguracionController::class, 'probarCorreo'])->name('configuracion.probar');
    });
});
