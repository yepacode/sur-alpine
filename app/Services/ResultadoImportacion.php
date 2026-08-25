<?php

namespace App\Services;

/**
 * Lo que pasó en una importación, en datos y no en texto de consola: el panel
 * lo pinta como tabla y el comando lo imprime.
 */
class ResultadoImportacion
{
    public function __construct(
        public int $vehiculosLeidos = 0,
        public int $celdasMarcadas = 0,
        public int $marcasNuevas = 0,
        public int $modelosNuevos = 0,
        public int $vehiculosNuevos = 0,
        public int $categoriasNuevas = 0,
        public int $tiposParteNuevos = 0,
        public int $productosCreados = 0,
        public int $productosActualizados = 0,
        public int $productosEnBase = 0,
        public int $partesAmbiguas = 0,
        public int $modelosConGeneraciones = 0,
        /** Piezas que están en el catálogo y ya no vienen marcadas en el Excel. */
        public int $sobrantes = 0,
        /** @var array<int, string> Una muestra de esas piezas, para poder mirarlas. */
        public array $muestraSobrantes = [],
        /** @var array<int, string> */
        public array $errores = [],
        /** @var array<int, array{marca:string, modelo:string, cilindraje:string, anios:string, partes:int}> */
        public array $vehiculos = [],
        public bool $simulacion = false,
    ) {}

    /** Cada celda marcada en el Excel tiene que existir como producto. */
    public function cuadra(): bool
    {
        return $this->simulacion || $this->productosEnBase === $this->celdasMarcadas;
    }

    public function faltantes(): int
    {
        return abs($this->celdasMarcadas - $this->productosEnBase);
    }

    public function correcto(): bool
    {
        return $this->errores === [] && $this->cuadra();
    }

    public function resumen(): array
    {
        return [
            'Filas de vehículo leídas' => $this->vehiculosLeidos,
            'Celdas marcadas en la matriz' => $this->celdasMarcadas,
            'Marcas nuevas' => $this->marcasNuevas,
            'Modelos nuevos' => $this->modelosNuevos,
            'Vehículos nuevos' => $this->vehiculosNuevos,
            'Categorías nuevas' => $this->categoriasNuevas,
            'Tipos de parte nuevos' => $this->tiposParteNuevos,
            'Productos creados' => $this->productosCreados,
            'Productos actualizados' => $this->productosActualizados,
        ];
    }
}
