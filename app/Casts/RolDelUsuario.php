<?php

namespace App\Casts;

use App\Enums\Rol;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * El rol, sin que un valor inesperado tumbe el panel.
 *
 * Con el casteo normal a enum, una fila cuyo `rol` no esté entre los valores
 * conocidos lanza `ValueError` al hidratarse. Y eso no rompe esa fila: rompe
 * la PANTALLA entera. Pasó de verdad en producción —quedaban cuentas con los
 * roles del esquema viejo, «vendedor» y «mostrador»— y la lista de usuarios
 * del panel respondía 500 completa: el administrador no podía ver a su equipo
 * ni arreglar el problema desde ahí. Un callejón perfecto.
 *
 * Aquí un valor desconocido se lee como `cliente`, que es el privilegio
 * MÍNIMO: si algo raro llega a la columna, el fallo es hacia menos permisos y
 * nunca hacia más. Y se deja constancia en el registro, porque leerlo como
 * cliente arregla la pantalla pero no arregla el dato.
 */
class RolDelUsuario implements CastsAttributes
{
    public function get(Model $model, string $clave, mixed $valor, array $atributos): ?Rol
    {
        if ($valor === null) {
            return null;
        }

        $rol = Rol::tryFrom((string) $valor);

        if ($rol === null) {
            Log::warning('Rol desconocido en la base; se lee como cliente.', [
                'usuario' => $model->getKey(),
                'valor' => $valor,
            ]);

            return Rol::Cliente;
        }

        return $rol;
    }

    public function set(Model $model, string $clave, mixed $valor, array $atributos): ?string
    {
        // Al escribir NO se perdona: guardar un rol que no existe es un error
        // del código, y taparlo aquí lo escondería para siempre.
        return match (true) {
            $valor === null => null,
            $valor instanceof Rol => $valor->value,
            default => Rol::from((string) $valor)->value,
        };
    }
}
