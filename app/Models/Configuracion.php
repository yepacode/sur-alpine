<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Ajustes que el administrador cambia desde el panel, sin tocar el código:
 * los correos que reciben las solicitudes, los teléfonos del sitio.
 */
class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor', 'grupo'];

    public const CACHE = 'configuraciones.todas';

    public static function valor(string $clave, mixed $porDefecto = null): mixed
    {
        $todas = Cache::rememberForever(
            self::CACHE,
            fn () => static::query()->pluck('valor', 'clave')->all()
        );

        return $todas[$clave] ?? $porDefecto;
    }

    /** Los correos de destino se guardan separados por coma o salto de línea. */
    public static function correosDestino(): array
    {
        $crudo = (string) self::valor('correos_cotizacion', config('mail.from.address'));

        return collect(preg_split('/[,;\n]+/', $crudo))
            ->map(fn ($correo) => trim($correo))
            ->filter(fn ($correo) => filter_var($correo, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    public static function poner(string $clave, ?string $valor, string $grupo = 'general'): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor, 'grupo' => $grupo]);
        Cache::forget(self::CACHE);
    }
}
