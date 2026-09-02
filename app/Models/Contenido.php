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
        // La foto EFECTIVA: la subida si la hay, y si no la de fábrica.
        //
        // Antes miraba sólo `valor`, y desde que las filas nacen en nulo
        // —para que el panel deje de escribir el contenido del sitio— las seis
        // fotos editables decían «sin foto» mientras la web las mostraba
        // perfectamente. Al lado, la ayuda promete «si no eliges nada, se
        // queda la de ahora»: el dueño concluye que se perdieron.
        $ruta = $this->valor ?: $this->valor_ejemplo;

        if ($this->tipo !== 'imagen' || ! $ruta) {
            return null;
        }

        $encontrados = glob(public_path(ltrim($ruta, '/').'-*.webp')) ?: [];

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
