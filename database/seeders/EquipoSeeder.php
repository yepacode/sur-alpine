<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cuentas de arranque y configuración inicial.
 *
 * Las contraseñas se cambian en la capacitación: nadie sale a producción con
 * estas. Están aquí para que el equipo pueda probar el panel desde el día uno.
 */
class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        $cuentas = [
            ['name' => 'Administrador Sur Alpine', 'email' => 'admin@suralpine.com', 'rol' => Rol::Admin],
            ['name' => 'Asesor de catálogo', 'email' => 'asesor@suralpine.com', 'rol' => Rol::Asesor],
            ['name' => 'Vendedor de mostrador', 'email' => 'vendedor@suralpine.com', 'rol' => Rol::Vendedor],
        ];

        foreach ($cuentas as $cuenta) {
            User::updateOrCreate(
                ['email' => $cuenta['email']],
                [
                    'name' => $cuenta['name'],
                    'rol' => $cuenta['rol'],
                    'password' => 'suralpine2026',
                    'activo' => true,
                ]
            );
        }

        Configuracion::poner('correos_cotizacion', 'cotizaciones@suralpine.com', 'correo');
        Configuracion::poner('telefono_pbx', '(601) 366 0066', 'contacto');
        Configuracion::poner('celulares', '313 422 3861, 310 205 8051', 'contacto');
        Configuracion::poner('direccion', 'Av. Caracas 19-21 sur', 'contacto');
        Configuracion::poner('ciudad', 'Bogotá D.C.', 'contacto');
    }
}
