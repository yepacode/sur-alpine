<?php

namespace App\Providers;

use App\Models\Categoria;
use App\Services\Contacto;
use App\Services\Cotizador;
use App\Services\VehiculoActivo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola instancia por petición: así el vehículo activo se resuelve
        // con una consulta, no con una por cada vista que lo necesite.
        $this->app->scoped(VehiculoActivo::class);
        $this->app->scoped(Contacto::class);

        // Helpers globales (F · CRUD de textos y SEO editables). Se cargan
        // aquí y no en `composer.json` para no depender de `dump-autoload`.
        require_once base_path('app/helpers.php');
    }

    public function boot(): void
    {
        // Todas las URLs que el sitio genera salen de `APP_URL`, no de la
        // cabecera `Host` de quien pregunta.
        //
        // Sin esto, cualquiera que pusiera un proxy apuntando aquí recibía
        // páginas cuyo `canonical`, `og:url` y `@id` del schema decían que el
        // original es SU dominio. A este cliente, que tiene sitios
        // suplantándolo, le estábamos entregando la única señal con la que
        // Google decide cuál de dos copias es la buena. Y bastaba un GET con
        // `Host:` falso al sitemap para que la caché sirviera durante una hora
        // un sitemap lleno de URLs ajenas.
        //
        // En pruebas no: allí el host lo pone el propio banco de pruebas.
        if (! $this->app->environment('testing') && $raiz = config('app.url')) {
            URL::forceRootUrl($raiz);

            if (str_starts_with($raiz, 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Paginador propio en español: `resources/views/vendor/pagination/tailwind.blade.php`.
        Paginator::useTailwind();

        // Formato colombiano: 29.272, no 29,272.
        Blade::directive('numero', fn (string $expresion) => "<?php echo number_format($expresion, 0, ',', '.'); ?>");

        // El pie de página lista las categorías en todas las vistas. Cacheadas,
        // porque cambian sólo cuando el equipo importa un catálogo nuevo.
        // Las categorías del desplegable «Productos» de la cabecera. Cacheadas,
        // porque cambian sólo cuando el equipo importa un catálogo nuevo.
        //
        // Antes esto se compartía también con `layouts.app`, para que el pie
        // las listara. El pie dejó de hacerlo —el suyo tampoco las lista, y
        // ahora la puerta de entrada de las categorías sin foto es ese mismo
        // desplegable—, así que la variable sobraba.
        View::composer('components.cabecera', function ($view) {
            $view->with('categoriasCabecera', Cache::remember(
                'menu.categorias',
                3600,
                fn () => Categoria::query()->orderBy('nombre')->get()
            ));
        });

        // El vehículo activo se consulta desde el encabezado, la barra lateral
        // y las fichas, así que se comparte con todas las vistas. Los datos de
        // contacto van por el mismo camino: los edita el administrador desde el
        // panel y tienen que reflejarse en cabecera, pie y fichas por igual.
        View::composer('*', function ($view) {
            $activo = app(VehiculoActivo::class);
            $view->with('vehiculoActivo', $activo->get());
            $view->with('vehiculoActivoAnio', $activo->anio());
            $view->with('itemsCotizacion', app(Cotizador::class)->totalItems());
            $view->with('contacto', app(Contacto::class));
        });
    }
}
