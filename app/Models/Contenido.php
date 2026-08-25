<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Contenido extends Model
{
    protected $table = 'contenidos';

    protected $fillable = ['clave', 'grupo', 'rotulo', 'valor', 'tipo', 'valor_ejemplo'];

    public const LLAVE_CACHE = 'contenidos.mapa.v1';

    /**
     * Diccionario clave → valor, cacheado. Un solo SELECT alimenta todas
     * las llamadas al helper en una petición.
     */
    public static function mapa(): array
    {
        return Cache::remember(self::LLAVE_CACHE, 3600,
            fn () => self::query()->pluck('valor', 'clave')->all());
    }

    /** Invalida la caché al guardar cualquier fila. */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::LLAVE_CACHE));
        static::deleted(fn () => Cache::forget(self::LLAVE_CACHE));
    }
}
