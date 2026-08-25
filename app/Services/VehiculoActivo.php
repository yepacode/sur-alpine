<?php

namespace App\Services;

use App\Models\Vehiculo;

/**
 * El vehículo que el visitante tiene seleccionado.
 *
 * Vive en la sesión y filtra el catálogo. A diferencia del sitio anterior no
 * es obligatorio: sin vehículo el catálogo se ve completo.
 */
class VehiculoActivo
{
    private const LLAVE = 'vehiculo_activo';

    /**
     * La memoria cuelga de la petición, no del servicio. Guardarla en el
     * servicio lo haría devolver un vehículo viejo si el contenedor sobrevive
     * a la petición, como pasa bajo Octane o entre llamadas de una prueba.
     */
    private const MEMORIA = 'vehiculo_activo.resuelto';

    /**
     * Pasa por `get()` a propósito: así el filtro del catálogo nunca usa un id
     * que ya no existe. Devolverlo crudo dejaría el catálogo en cero sin que
     * nadie entienda por qué.
     */
    public function id(): ?int
    {
        return $this->get()?->id;
    }

    public function get(): ?Vehiculo
    {
        $peticion = request();

        if ($peticion->attributes->has(self::MEMORIA)) {
            return $peticion->attributes->get(self::MEMORIA);
        }

        $id = $peticion->hasSession() ? $peticion->session()->get(self::LLAVE) : null;
        $vehiculo = $id ? Vehiculo::with('modelo.marca')->find($id) : null;

        // Si el vehículo desapareció en una reimportación, la sesión se limpia
        // sola en vez de dejar al visitante filtrando contra la nada.
        if ($id && ! $vehiculo) {
            $peticion->session()->forget(self::LLAVE);
        }

        $peticion->attributes->set(self::MEMORIA, $vehiculo);

        return $vehiculo;
    }

    public function guardar(Vehiculo $vehiculo): void
    {
        request()->session()->put(self::LLAVE, $vehiculo->id);
        request()->attributes->set(self::MEMORIA, $vehiculo);
    }

    public function olvidar(): void
    {
        request()->session()->forget(self::LLAVE);
        request()->attributes->set(self::MEMORIA, null);
    }

    public function hay(): bool
    {
        return $this->get() !== null;
    }
}
