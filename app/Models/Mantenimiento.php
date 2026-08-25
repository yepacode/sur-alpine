<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    protected $fillable = [
        'user_id', 'vehiculo_id', 'placa', 'kilometraje', 'tipo', 'fecha',
        'periodicidad_tipo', 'periodicidad_valor',
        'proximo_fecha', 'proximo_kilometraje', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'proximo_fecha' => 'date',
            'kilometraje' => 'integer',
            'periodicidad_valor' => 'integer',
            'proximo_kilometraje' => 'integer',
        ];
    }

    /** Las tres formas en que el taller mide «cuándo toca el próximo». */
    public const PERIODICIDADES = [
        'meses' => 'Meses',
        'dias' => 'Días',
        'kilometraje' => 'Kilómetros',
    ];

    /**
     * Calcula cuándo toca el próximo, según cómo se mida.
     *
     * Se guarda calculado y no se resuelve al leer: el listado del cliente
     * ordena por esta fecha y tiene que poder hacerlo en la base.
     */
    public function calcularProximo(): static
    {
        $this->proximo_fecha = match ($this->periodicidad_tipo) {
            'dias' => $this->fecha?->copy()->addDays($this->periodicidad_valor),
            'meses' => $this->fecha?->copy()->addMonths($this->periodicidad_valor),
            default => null,
        };

        $this->proximo_kilometraje = $this->periodicidad_tipo === 'kilometraje'
            ? $this->kilometraje + $this->periodicidad_valor
            : null;

        return $this;
    }

    /** Cuánto falta, dicho como lo diría un asesor. */
    public function getAvisoAttribute(): string
    {
        if ($this->proximo_kilometraje) {
            return 'A los '.number_format($this->proximo_kilometraje, 0, ',', '.').' km';
        }

        if (! $this->proximo_fecha) {
            return 'Sin recordatorio';
        }

        $dias = today()->diffInDays($this->proximo_fecha, false);

        // El singular importa: «Vencido hace 1 días» y «En 1 días» delataban
        // la plantilla. Con `proximo_fecha` casteado a `date` los días son
        // enteros, así que no hace falta el guardián `=== 0.0`: `< 1` cubre
        // el caso de hoy y cualquier fracción imposible que aparezca mañana.
        return match (true) {
            $dias < 0 => 'Vencido hace '.abs((int) $dias).' '.(abs((int) $dias) === 1 ? 'día' : 'días'),
            $dias < 1 => 'Hoy',
            $dias <= 30 => 'En '.(int) $dias.' '.((int) $dias === 1 ? 'día' : 'días'),
            default => 'El '.$this->proximo_fecha->translatedFormat('d M Y'),
        };
    }

    public function getVencidoAttribute(): bool
    {
        // `->isPast()` mira contra ahora, y `proximo_fecha` casteado a `date`
        // es medianoche: a las 00:00:01 el mantenimiento de HOY salía como
        // vencido. `->lt(today())` es el corte correcto.
        return $this->proximo_fecha !== null && $this->proximo_fecha->lt(today());
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
