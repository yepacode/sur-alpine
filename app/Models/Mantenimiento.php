<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    // `proximo_fecha` y `proximo_kilometraje` NO están aquí: no son datos que
    // alguien escriba, son la cuenta que sale de los otros tres. Se calculan
    // solos al guardar (ver `booted()`).
    protected $fillable = [
        'user_id', 'vehiculo_id', 'placa', 'kilometraje', 'tipo', 'fecha',
        'periodicidad_tipo', 'periodicidad_valor', 'notas',
    ];

    /**
     * El recordatorio se calcula SIEMPRE al guardar.
     *
     * Antes había que acordarse de llamar `calcularProximo()` antes de cada
     * `save()`. Quien lo olvidara guardaba un mantenimiento con los dos campos
     * en `null`, y eso no falla ni avisa: la fila queda bien puesta en el
     * historial y desaparece de «Próximos mantenimientos», que es justo la
     * pantalla por la que el cliente entra. Un aviso que no aparece no se
     * reclama; simplemente nadie cambia el aceite.
     */
    protected static function booted(): void
    {
        static::saving(function (self $mantenimiento) {
            // Cuándo tocaba ANTES de recalcular. Se leen los valores crudos
            // de la base y se recorta la fecha a `AAAA-MM-DD`: da igual si
            // vienen con hora o sin ella.
            $crudo = $mantenimiento->getOriginal('proximo_fecha');
            $antesFecha = $crudo ? mb_substr((string) $crudo, 0, 10) : null;
            $antesKm = $mantenimiento->getOriginal('proximo_kilometraje');

            $mantenimiento->calcularProximo();

            // Si cambió CUÁNDO toca, el aviso que ya se mandó dejó de valer.
            //
            // Sin esto pasaba lo peor posible: alguien anota mal la fecha,
            // recibe el correo de «vencido», entra a corregirla —justo la
            // persona que demostró que le importa— y el recordatorio real,
            // seis meses después, no sale nunca. El comando filtra por
            // `aviso_enviado_en` nulo, y esa marca se quedaba puesta.
            // Se compara el RESULTADO, no las entradas.
            //
            // Antes bastaba tocar cualquiera de los cuatro campos, y el
            // kilometraje no mueve la fecha de un mantenimiento por meses: un
            // taller que anota el odómetro cada semana recibía el mismo
            // recordatorio cada semana. Eso es justo lo que lleva a marcar el
            // remitente como no deseado —y con él se van también los correos
            // de cotización, que sí pidió—.
            $cambioCuandoToca = $mantenimiento->proximo_fecha?->toDateString() !== $antesFecha
                || $mantenimiento->proximo_kilometraje !== $antesKm;

            if ($cambioCuandoToca && ! $mantenimiento->isDirty('aviso_enviado_en')) {
                $mantenimiento->aviso_enviado_en = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'proximo_fecha' => 'date',
            'aviso_enviado_en' => 'datetime',
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
            // `addMonthsNoOverflow` y no `addMonths`: sumar seis meses al 31 de
            // agosto da «31 de febrero», y Carbon lo desborda al 3 de marzo. El
            // recordatorio se corría tres días sin que nadie lo pidiera. Sin
            // desbordamiento cae en el 28, que es lo que cualquiera entiende
            // por «dentro de seis meses».
            'meses' => $this->fecha?->copy()->addMonthsNoOverflow($this->periodicidad_valor),
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
