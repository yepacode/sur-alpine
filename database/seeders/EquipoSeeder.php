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
        // Una sola cuenta: con dos roles, la del cliente se crea sola al
        // registrarse y la del equipo es ésta.
        $cuentas = [
            ['name' => 'Administrador Sur Alpine', 'email' => 'admin@suralpine.com', 'rol' => Rol::Admin],
        ];

        // La contraseña sólo se toca al CREAR: `updateOrCreate` la reimponía
        // en cada `db:seed`, así que aunque el cliente ya la hubiera cambiado
        // en producción, el siguiente despliegue la devolvía a la del código.
        //
        // En local se usa la de este archivo (para que el equipo pueda entrar
        // desde el día uno); en producción, una aleatoria que se imprime UNA
        // sola vez en la consola. Si no se copia, se olvida —que es el punto.
        $enProduccion = app()->environment('production');

        foreach ($cuentas as $cuenta) {
            $usuario = User::firstWhere('email', $cuenta['email']);

            if ($usuario) {
                // Ya existe: sólo actualizamos nombre y rol si vinieron nuevos.
                // Nada de tocar `password` ni `activo`.
                $usuario->fill([
                    'name' => $cuenta['name'],
                    'rol' => $cuenta['rol'],
                ])->save();

                continue;
            }

            $clave = $enProduccion
                ? \Illuminate\Support\Str::password(16, symbols: false)
                : 'suralpine2026';

            User::create([
                'name' => $cuenta['name'],
                'email' => $cuenta['email'],
                'rol' => $cuenta['rol'],
                'password' => $clave,
                'activo' => true,
            ]);

            if ($enProduccion) {
                $this->command?->warn("  Cuenta creada: {$cuenta['email']}  →  clave: {$clave}");
                $this->command?->line('  Cópiala y guárdala AHORA. No se vuelve a mostrar.');
            }
        }

        Configuracion::poner('correos_cotizacion', 'cotizaciones@suralpine.com', 'correo');
        Configuracion::poner('telefono_pbx', '(601) 366 0066', 'contacto');
        Configuracion::poner('celulares', '313 422 3861, 310 205 8051', 'contacto');
        Configuracion::poner('direccion', 'Av. Caracas #19-15 sur', 'contacto');
        Configuracion::poner('ciudad', 'Bogotá D.C.', 'contacto');
    }
}
