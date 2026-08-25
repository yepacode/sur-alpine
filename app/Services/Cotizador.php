<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * El carrito de cotización.
 *
 * Nunca maneja precios: los precios de Sur Alpine cambian a diario y los da un
 * asesor por teléfono. Esto sólo arma la lista de lo que el cliente necesita.
 *
 * Los ítems guardan el vehículo con el que se agregaron, no el vehículo que
 * esté activo al enviar. Es lo que permite que un mecánico cotice tres o cuatro
 * carros distintos en una sola solicitud.
 */
class Cotizador
{
    public const LLAVE = 'cotizacion.items';

    private const MAXIMO_ITEMS = 200;

    private const MAXIMO_CANTIDAD = 99;

    private ?Collection $hidratados = null;

    public function agregar(Producto $producto, int $cantidad = 1): void
    {
        $items = $this->crudos();

        if (! isset($items[$producto->id]) && count($items) >= self::MAXIMO_ITEMS) {
            return;
        }

        $actual = $items[$producto->id]['c'] ?? 0;

        $items[$producto->id] = [
            'v' => $producto->vehiculo_id,
            'c' => min($actual + $cantidad, self::MAXIMO_CANTIDAD),
        ];

        $this->guardar($items);
    }

    public function actualizar(int $productoId, int $cantidad): void
    {
        $items = $this->crudos();

        if (! isset($items[$productoId])) {
            return;
        }

        if ($cantidad < 1) {
            unset($items[$productoId]);
        } else {
            $items[$productoId]['c'] = min($cantidad, self::MAXIMO_CANTIDAD);
        }

        $this->guardar($items);
    }

    public function quitar(int $productoId): void
    {
        $items = $this->crudos();
        unset($items[$productoId]);
        $this->guardar($items);
    }

    /** Vacía un vehículo sin tocar lo que el cliente cargó para los demás. */
    public function quitarVehiculo(int $vehiculoId): void
    {
        $items = array_filter($this->crudos(), fn ($item) => $item['v'] !== $vehiculoId);
        $this->guardar($items);
    }

    public function vaciar(): void
    {
        $this->guardar([]);
    }

    public function vacio(): bool
    {
        return $this->crudos() === [];
    }

    public function totalItems(): int
    {
        return array_sum(array_column($this->crudos(), 'c'));
    }

    public function totalReferencias(): int
    {
        return count($this->crudos());
    }

    /**
     * Los productos reales, con su vehículo. Una sola consulta.
     *
     * @return Collection<int, object{producto: Producto, cantidad: int}>
     */
    public function items(): Collection
    {
        if ($this->hidratados !== null) {
            return $this->hidratados;
        }

        $crudos = $this->crudos();

        if ($crudos === []) {
            return $this->hidratados = collect();
        }

        $productos = Producto::with(['vehiculo.modelo.marca', 'tipoParte.categoria'])
            ->whereIn('id', array_keys($crudos))
            ->get()
            ->keyBy('id');

        // Un producto que ya no existe se cae solo del carrito.
        $vivos = array_intersect_key($crudos, $productos->all());

        if (count($vivos) !== count($crudos)) {
            $this->guardar($vivos);
        }

        return $this->hidratados = collect($vivos)
            ->map(fn (array $item, int $id) => (object) [
                'producto' => $productos[$id],
                'cantidad' => $item['c'],
            ])
            ->values()
            ->sortBy(fn ($i) => $i->producto->nombre)
            ->values();
    }

    /**
     * Agrupado por vehículo, que es como el cliente lo piensa y como el asesor
     * lo necesita leer.
     *
     * @return Collection<string, Collection>
     */
    public function porVehiculo(): Collection
    {
        return $this->items()
            ->groupBy(fn ($item) => $item->producto->vehiculo->nombre_completo)
            ->sortKeys();
    }

    public function vehiculos(): Collection
    {
        return $this->items()
            ->map(fn ($item) => $item->producto->vehiculo)
            ->unique('id')
            ->values();
    }

    public function tiene(int $productoId): bool
    {
        return isset($this->crudos()[$productoId]);
    }

    /** @return array<int, array{v:int, c:int}> */
    private function crudos(): array
    {
        return session()->get(self::LLAVE, []);
    }

    private function guardar(array $items): void
    {
        $this->hidratados = null;
        session()->put(self::LLAVE, $items);
    }
}
