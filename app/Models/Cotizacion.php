<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
    public static function siguienteConsecutivo(): string
    {
        $anio = now()->year;
        $prefijo = sprintf('SA-%d-', $anio);

        $ultimo = static::query()
            ->where('consecutivo', 'like', $prefijo.'%')
            ->lockForUpdate()
            ->orderByDesc('consecutivo')
            ->value('consecutivo');

        $numero = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return sprintf('%s%05d', $prefijo, $numero);
    }
}
