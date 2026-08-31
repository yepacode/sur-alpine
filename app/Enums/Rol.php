<?php

namespace App\Enums;

/**
 * Dos roles, en escalera:
 *
 *   cliente → navega, cotiza, guarda vehículos y lleva sus mantenimientos
 *   admin   → todo lo anterior más el panel entero
 *
 * Dos y no más, porque es lo que el cliente pidió: en un mostrador de cuatro
 * personas, decidir quién puede tocar el catálogo y quién no era una traba y
 * no un control. La escalera se queda —cuesta lo mismo que un booleano y deja
 * la puerta abierta a partir el panel si algún día hace falta— pero hoy tiene
 * dos peldaños.
 *
 * Los mecánicos entran como «cliente».
 */
enum Rol: string
{
    case Cliente = 'cliente';
    case Admin = 'admin';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Admin => 'Administrador',
        };
    }

    /** Qué tan alto está en la escalera. */
    public function nivel(): int
    {
        return match ($this) {
            self::Cliente => 0,
            self::Admin => 1,
        };
    }

    public function alcanza(self $minimo): bool
    {
        return $this->nivel() >= $minimo->nivel();
    }

    /** Los que pueden entrar al panel. */
    public function entraAlPanel(): bool
    {
        return $this->alcanza(self::Admin);
    }

    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $rol) => [$rol->value => $rol->etiqueta()])
            ->all();
    }
}
