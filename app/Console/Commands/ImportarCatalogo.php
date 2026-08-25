<?php

namespace App\Console\Commands;

use App\Services\ImportadorCatalogo;
use Illuminate\Console\Command;

/**
 * Envoltura de consola sobre {@see ImportadorCatalogo}. La lógica vive en el
 * servicio para que el panel importe exactamente por el mismo camino probado.
 */
class ImportarCatalogo extends Command
{
    protected $signature = 'catalogo:importar
        {--archivo= : Ruta del Excel. Por defecto storage/app/catalogo/formato-importacion.xlsx}
        {--hoja= : Nombre de la hoja. Por defecto la primera}
        {--simular : Recorre el archivo y reporta, sin escribir en la base}';

    protected $description = 'Importa vehículos y productos desde la matriz de compatibilidad en Excel';

    public function handle(ImportadorCatalogo $importador): int
    {
        $ruta = $this->option('archivo') ?: storage_path('app/catalogo/formato-importacion.xlsx');
        $simular = (bool) $this->option('simular');

        $this->info('Leyendo '.basename($ruta).'...');

        $resultado = $importador->importar($ruta, $this->option('hoja'), $simular);

        if ($resultado->partesAmbiguas > 0) {
            $this->line("  {$resultado->partesAmbiguas} tipo(s) de parte existen en más de una categoría: se les añade la categoría a la URL.");
        }

        if ($resultado->modelosConGeneraciones > 0) {
            $this->line("  {$resultado->modelosConGeneraciones} modelo(s) tienen más de una generación: se les añade el año a la URL.");
        }

        $this->newLine();
        $this->line($simular ? '  SIMULACIÓN — no se escribió nada' : '  IMPORTACIÓN COMPLETA');

        $this->table(
            ['Concepto', 'Cantidad'],
            collect($resultado->resumen())->map(fn ($valor, $clave) => [$clave, number_format($valor)])->values()->all()
        );

        if ($resultado->errores) {
            $this->newLine();
            $this->warn(count($resultado->errores).' fila(s) con problemas:');
            foreach (array_slice($resultado->errores, 0, 20) as $error) {
                $this->line('  · '.$error);
            }
            if (count($resultado->errores) > 20) {
                $this->line('  · … y '.(count($resultado->errores) - 20).' más.');
            }
        }

        // Nada se borra: sólo se avisa. Retirar piezas es decisión del negocio,
        // y una celda desmarcada por error dejaría al cliente sin repuestos que
        // sí vende.
        if ($resultado->sobrantes > 0) {
            $this->newLine();
            $this->warn(sprintf(
                '  %s pieza(s) siguen en el catálogo y ya no vienen en el Excel:',
                number_format($resultado->sobrantes)
            ));
            foreach ($resultado->muestraSobrantes as $nombre) {
                $this->line('    · '.$nombre);
            }
            if ($resultado->sobrantes > count($resultado->muestraSobrantes)) {
                $this->line('    · … y '.number_format($resultado->sobrantes - count($resultado->muestraSobrantes)).' más.');
            }
            $this->line('    No se tocaron. Decide con el cliente si se retiran.');
        }

        if (! $simular) {
            $this->newLine();

            // Red de seguridad: un catálogo incompleto no se nota hasta que un
            // cliente no encuentra su repuesto.
            if ($resultado->cuadra()) {
                $this->info("  ✓ Cuadre correcto: {$resultado->productosEnBase} productos en base = celdas marcadas en el Excel.");
            } else {
                $this->error(sprintf(
                    '  ✗ DESCUADRE: el Excel marca %s celdas pero la base tiene %s productos (faltan %s).',
                    number_format($resultado->celdasMarcadas),
                    number_format($resultado->productosEnBase),
                    number_format($resultado->faltantes())
                ));
                $this->line('    Revisar colisiones de slug antes de dar el catálogo por bueno.');
            }
        }

        return $resultado->correcto() ? self::SUCCESS : self::FAILURE;
    }
}
