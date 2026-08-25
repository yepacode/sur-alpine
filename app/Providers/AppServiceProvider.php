<?php

namespace App\Providers;

use App\Models\Categoria;
use App\Services\Contacto;
use App\Services\Cotizador;
use App\Services\VehiculoActivo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
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
    }

    public function boot(): void
    {
        // Formato colombiano: 29.272, no 29,272.
        Blade::directive('numero', fn (string $expresion) => "<?php echo number_format($expresion, 0, ',', '.'); ?>");

        // El pie de página lista las categorías en todas las vistas. Cacheadas,
        // porque cambian sólo cuando el equipo importa un catálogo nuevo.
        View::composer('layouts.app', function ($view) {
            $view->with('categoriasMenu', Cache::remember(
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
            $view->with('vehiculoActivo', app(VehiculoActivo::class)->get());
            $view->with('itemsCotizacion', app(Cotizador::class)->totalItems());
            $view->with('contacto', app(Contacto::class));
        });
    }
}
