<?php

namespace App\Enums;

/**
 * Los cuatro roles, en escalera: cada uno puede todo lo del anterior más algo.
 *
 *   cliente  → navega, cotiza, guarda vehículos y lleva mantenimientos
 *   vendedor → además recibe solicitudes, ve la bandeja y el tablero
 *   asesor   → además edita el catálogo y da de alta vehículos
 *   admin    → además configura correos y administra usuarios
 *
 * Los mecánicos entran como «cliente».
 */
enum Rol: string
{
    case Cliente = 'cliente';
    case Vendedor = 'vendedor';
    case Asesor = 'asesor';
    case Admin = 'admin';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Vendedor => 'Vendedor',
            self::Asesor => 'Asesor',
            self::Admin => 'Administrador',
        };
    }

    /** Qué tan alto está en la escalera. */
    public function nivel(): int
    {
        return match ($this) {
            self::Cliente => 0,
            self::Vendedor => 1,
            self::Asesor => 2,
            self::Admin => 3,
        };
    }

    public function alcanza(self $minimo): bool
    {
        return $this->nivel() >= $minimo->nivel();
    }

    /** Los que pueden entrar al panel. */
    public function entraAlPanel(): bool
    {
        return $this->alcanza(self::Vendedor);
    }

    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $rol) => [$rol->value => $rol->etiqueta()])
            ->all();
    }
}
