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

    /**
     * La miniatura para el panel.
     *
     * Las filas de tipo imagen guardan el nombre BASE, sin `-{ancho}.webp`,
     * porque cada pieza usa anchos distintos. Aquí se busca el archivo más
     * chico que exista de verdad, en vez de adivinar un ancho y arriesgarse a
     * mostrar un recuadro roto justo en la pantalla donde el cliente viene a
     * comprobar qué foto tiene puesta.
     */
    public function getVistaPreviaAttribute(): ?string
    {
        if ($this->tipo !== 'imagen' || ! $this->valor) {
            return null;
        }

        $encontrados = glob(public_path(ltrim($this->valor, '/').'-*.webp')) ?: [];

        if ($encontrados === []) {
            return null;
        }

        // Ordenados por peso: el más liviano es el más chico, y es el que
        // conviene pedirle al navegador para una miniatura.
        usort($encontrados, fn ($a, $b) => filesize($a) <=> filesize($b));

        // En Windows `glob()` devuelve barras invertidas; la URL las quiere
        // hacia el otro lado.
        $relativa = substr($encontrados[0], strlen(public_path()) + 1);

        return '/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativa);
    }

    /** Invalida la caché al guardar cualquier fila. */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::LLAVE_CACHE));
        static::deleted(fn () => Cache::forget(self::LLAVE_CACHE));
    }
}
