<?php

namespace App\Services;

use App\Models\Vehiculo;
use Illuminate\Support\Facades\Cache;

/**
 * El árbol marca → modelo → cilindraje → año que alimenta el selector.
 *
 * Son 224 vehículos: entero pesa unos pocos KB. En vez de pedirle al servidor
 * un paso cada vez —cuatro viajes de más de un segundo, como en el sitio
 * anterior— se manda una sola vez y el navegador arma la cascada al instante.
 */
class ArbolVehiculos
{
    public const CLAVE_CACHE = 'vehiculos.arbol';

    /**
     * Lista plana y compacta. Las llaves son cortas a propósito: multiplicadas
     * por 224 filas, los nombres largos serían la mitad del peso del archivo.
     *
     * @return array<int, array{i:int, s:string, ma:string, mo:string, c:string, d:int, h:int}>
     */
    public function paraSelector(): array
    {
        return Cache::rememberForever(self::CLAVE_CACHE, function () {
            return Vehiculo::query()
                ->where('activo', true)
                ->with('modelo.marca')
                ->get()
                ->sortBy([
                    fn ($v) => $v->modelo->marca->nombre,
                    fn ($v) => $v->modelo->nombre,
                    fn ($v) => $this->ordenNatural($v->cilindraje),
                    fn ($v) => $v->anio_inicio,
                ])
                ->map(fn (Vehiculo $v) => [
                    'i' => $v->id,
                    's' => $v->slug,
                    'ma' => $v->modelo->marca->nombre,
                    'mo' => $v->modelo->nombre,
                    'c' => $v->cilindraje,
                    'd' => $v->anio_inicio,
                    'h' => $v->anio_fin,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * "1600" antes de "1700 DIESEL", y ambos antes de "2500 DIESEL".
     * Ordenar como texto pondría "1000" después de "800".
     */
    private function ordenNatural(string $cilindraje): int
    {
        return (int) preg_replace('/\D/', '', $cilindraje);
    }

    public static function olvidar(): void
    {
        Cache::forget(self::CLAVE_CACHE);
    }
}
