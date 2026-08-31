<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'consecutivo', 'user_id', 'nombre', 'apellidos',
        'telefono', 'email', 'notas', 'ip',
        'correo_enviado_en', 'error_envio',
    ];

    protected function casts(): array
    {
        return ['correo_enviado_en' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Los ítems agrupados por vehículo, que es como los lee el asesor. */
    public function porVehiculo(): Collection
    {
        return $this->items->groupBy('vehiculo_nombre');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellidos);
    }

    public function seEnvio(): bool
    {
        return $this->correo_enviado_en !== null;
    }

    /** Las que hay que reenviar a mano: el correo no salió. */
    public function scopeSinEnviar(Builder $query): Builder
    {
        return $query->whereNull('correo_enviado_en');
    }

    /**
     * El siguiente número del año.
     *
     * Se calcula sobre el último consecutivo y no contando filas, por dos
     * razones: contar reutiliza el número de una solicitud borrada, y con dos
     * envíos simultáneos las dos cuentas daban lo mismo, el índice único
     * reventaba y el cliente veía un error justo al enviar.
     *
     * El `lockForUpdate` hace que la segunda espere a que la primera termine.
     * Tiene que llamarse dentro de una transacción para que sirva de algo.
     */
    /**
     * Reserva la fila del contador del año en curso.
     *
     * Va aparte y SE LLAMA ANTES de abrir la transacción de la solicitud: si
     * dos envíos simultáneos son los primeros del año, uno crea la fila y el
     * otro la ignora, y ninguno de los dos bloquea nada todavía.
     */
    public static function prepararContador(): string
    {
        $prefijo = sprintf('SA-%d-', now()->year);

        DB::table('contadores')->insertOrIgnore([
            'clave' => $prefijo,
            'valor' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $prefijo;
    }

    /**
     * El siguiente número de solicitud del año.
     *
     * Se bloquea la fila del contador, que SIEMPRE existe: antes se bloqueaba
     * el rango de los `SA-2026-…` ya guardados, y cuando ese rango estaba
     * vacío —la primera solicitud del año— InnoDB tomaba bloqueos de hueco,
     * que no se excluyen entre sí. Cinco envíos simultáneos, cuatro
     * interbloqueos. Rompía el día del estreno y cada 1 de enero.
     *
     * El índice único de `consecutivo` sigue siendo la garantía de fondo: esto
     * evita el choque, no lo sustituye.
     */
    public static function siguienteConsecutivo(): string
    {
        $prefijo = static::prepararContador();

        $valor = (int) DB::table('contadores')
            ->where('clave', $prefijo)
            ->lockForUpdate()
            ->value('valor');

        // Por si quedaran filas de antes del contador —o si alguien insertara
        // una solicitud a mano—: manda el mayor de los dos.
        $ultimo = static::query()
            ->where('consecutivo', 'like', $prefijo.'%')
            // Por el NÚMERO, no por el texto: alfabéticamente
            // 'SA-2026-100000' es menor que 'SA-2026-99999', así que a partir
            // de las 99.999 solicitudes de un año el «último» se congelaba y
            // cada envío chocaba con el índice único.
            ->orderByRaw('CAST(SUBSTRING(consecutivo, ?) AS UNSIGNED) DESC', [strlen($prefijo) + 1])
            ->value('consecutivo');

        $numero = max($valor, $ultimo ? (int) substr($ultimo, strlen($prefijo)) : 0) + 1;

        DB::table('contadores')->where('clave', $prefijo)->update([
            'valor' => $numero,
            'updated_at' => now(),
        ]);

        return sprintf('%s%05d', $prefijo, $numero);
    }
}
