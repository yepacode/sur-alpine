<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolver un slug de URL sin depender de cómo compare el motor de base.
 *
 * MySQL con su cotejamiento normal encuentra `ACEITE-12-1300-RENAULT` cuando
 * la fila dice `aceite-12-1300-renault`; SQLite no. O sea que el sitio se
 * comportaba distinto en producción y en el banco de pruebas, y el defecto que
 * de verdad importa —una copia indexable por cada juego de mayúsculas— sólo
 * existía en producción, donde nadie lo iba a ver hasta leerlo en Search
 * Console.
 *
 * Aquí se busca primero exacto y, si no aparece, en minúsculas. Encontrarlo es
 * lo correcto: el enlace mal copiado de un WhatsApp tiene que llevar a la
 * pieza. Lo que hace después el middleware `slug` es mandar un 301 a la
 * dirección buena, para que sólo una de las dos exista de cara a Google.
 */
trait SeResuelvePorSlug
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $campo = $field ?: $this->getRouteKeyName();

        return $this->newQuery()->where($campo, $value)->first()
            ?? $this->newQuery()
                ->whereRaw("lower({$campo}) = ?", [mb_strtolower((string) $value)])
                ->first();
    }
}
