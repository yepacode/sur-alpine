<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Banner;
use App\Models\Cotizacion;
use App\Models\Nota;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

/**
 * Datos para auditar, no para producción.
 *
 * Todo lo que crea usa el dominio `@auditoria.test` y el prefijo `AUD-` en
 * los slugs, así que se distingue de un vistazo de lo que ya había y se puede
 * barrer con una sola condición.
 *
 * La gracia está en las PAREJAS: dos clientes con datos equivalentes, para
 * poder comprobar que lo de uno no se ve desde la sesión del otro. Un solo
 * usuario de prueba no deja ver esa clase de fallo.
 *
 *   php artisan db:seed --class=AuditoriaSeeder
 *   php artisan db:seed --class=AuditoriaSeeder   ← es idempotente
 */
class AuditoriaSeeder extends Seeder
{
    private const CLAVE = 'auditoria123';

    public function run(): void
    {
        $vehiculos = Vehiculo::with('modelo.marca')->limit(3)->get();

        if ($vehiculos->isEmpty()) {
            $this->command?->error('No hay vehículos: corre primero `php artisan catalogo:importar`.');

            return;
        }

        [$ana, $beto] = [
            $this->cliente('ana@auditoria.test', 'Ana Pérez Gómez', '3001112233'),
            $this->cliente('beto@auditoria.test', 'Beto Ruiz', '3004445566'),
        ];

        // Dos casos que suelen romper cosas y que nadie prueba a mano.
        $this->cliente('inactivo@auditoria.test', 'Cuenta Cerrada', '3007778899', ['activo' => false]);
        $this->cliente('google@auditoria.test', 'Entró Con Google', '3009990011', [
            'password' => null, 'proveedor' => 'google', 'proveedor_id' => 'aud-g-1',
        ]);
        $this->cliente('sinverificar@auditoria.test', 'Correo Sin Verificar', '3001234567', [
            'email_verified_at' => null,
        ]);

        $admin = $this->cliente('admin@auditoria.test', 'Admin Auditoría', '3002223344', [
            'rol' => Rol::Admin,
        ]);

        foreach ([$ana, $beto] as $i => $usuario) {
            $this->datosDe($usuario, $vehiculos, $i);
        }

        $this->notas();
        $this->banners();

        $this->command?->info('Listo. Todas las cuentas usan la contraseña: '.self::CLAVE);
        $this->command?->line('  ana@auditoria.test / beto@auditoria.test  → dos clientes con datos equivalentes (para IDOR)');
        $this->command?->line('  inactivo@ · google@ (sin contraseña) · sinverificar@ · admin@');
    }

    private function cliente(string $correo, string $nombre, string $telefono, array $extra = []): User
    {
        $usuario = User::firstOrNew(['email' => $correo]);

        $usuario->fill($extra + [
            'name' => $nombre,
            'telefono' => $telefono,
            'rol' => Rol::Cliente,
            'activo' => true,
            'acepto_en' => now(),
            'politica_version' => (string) config('habeas.version'),
        ]);

        // `fill` no toca `password` ni `email_verified_at` porque uno está
        // fuera de `$fillable` y el otro sólo llega cuando la prueba lo pide.
        if (! array_key_exists('password', $extra)) {
            $usuario->password = self::CLAVE;
        } else {
            $usuario->password = $extra['password'];
        }

        // `array_key_exists` y no `??`: un `null` explícito es justo lo que
        // pide la cuenta `sinverificar@`, y con `??` se convertía en `now()`.
        // O sea que la cuenta creada para probar el aviso de «verifica tu
        // correo» salía verificada, y esa comprobación no se probaba nunca.
        $usuario->email_verified_at = array_key_exists('email_verified_at', $extra)
            ? $extra['email_verified_at']
            : now();
        $usuario->save();

        return $usuario;
    }

    private function datosDe(User $usuario, $vehiculos, int $i): void
    {
        foreach ($vehiculos->take(2) as $j => $vehiculo) {
            $usuario->vehiculosGuardados()->syncWithoutDetaching([
                $vehiculo->id => [
                    'placa' => sprintf('AU%d%03d', $i, $j),
                    'alias' => $j === 0 ? 'El de diario' : 'El de la finca',
                ],
            ]);
        }

        $usuario->mantenimientos()->delete();

        // Uno vencido, uno por vencer dentro de la ventana de aviso, uno
        // lejano y uno por kilometraje: las cuatro ramas del comando de avisos.
        $casos = [
            ['tipo' => 'Cambio de aceite', 'fecha' => now()->subMonths(9), 'pt' => 'meses', 'pv' => 6],
            ['tipo' => 'Pastillas de freno', 'fecha' => now()->subMonths(6)->addDays(3), 'pt' => 'meses', 'pv' => 6],
            ['tipo' => 'Kit de distribución', 'fecha' => now()->subMonth(), 'pt' => 'meses', 'pv' => 24],
            ['tipo' => 'Bujías', 'fecha' => now()->subMonths(2), 'pt' => 'kilometraje', 'pv' => 20000],
        ];

        foreach ($casos as $k => $caso) {
            $usuario->mantenimientos()->create([
                'vehiculo_id' => $vehiculos[$k % 2]->id,
                'placa' => sprintf('AU%d%03d', $i, $k % 2),
                'kilometraje' => 60000 + $k * 5000,
                'tipo' => $caso['tipo'],
                'fecha' => $caso['fecha'],
                'periodicidad_tipo' => $caso['pt'],
                'periodicidad_valor' => $caso['pv'],
                'notas' => 'Dato de auditoría',
            ]);
        }

        // Una cotización propia, para comprobar que no se ve desde la otra
        // sesión ni cambiando el id de la URL.
        $productos = Producto::where('publicado', true)->limit(2)->get();

        if ($productos->isEmpty()) {
            return;
        }

        $usuario->cotizaciones()->delete();

        $cotizacion = Cotizacion::create([
            'consecutivo' => Cotizacion::siguienteConsecutivo(),
            'user_id' => $usuario->id,
            'nombre' => explode(' ', $usuario->name)[0],
            'apellidos' => 'Auditoría',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'notas' => 'Solicitud de auditoría de '.$usuario->name,
            'correo_enviado_en' => $i === 0 ? now() : null,
            'error_envio' => $i === 0 ? null : 'Buzón lleno (simulado para auditar el reenvío)',
        ]);

        foreach ($productos as $producto) {
            $cotizacion->items()->create([
                'producto_id' => $producto->id,
                'vehiculo_id' => $producto->vehiculo_id,
                'producto_nombre' => $producto->nombre,
                'vehiculo_nombre' => $producto->vehiculo?->nombre_completo ?? '',
                'cantidad' => 2,
            ]);
        }
    }

    /** Publicada, borrador y programada: las tres visibilidades. */
    private function notas(): void
    {
        $casos = [
            ['aud-publicada', 'AUD · Nota publicada', true, now()->subDay()],
            ['aud-borrador', 'AUD · Borrador', false, null],
            ['aud-programada', 'AUD · Programada', true, now()->addMonth()],
        ];

        foreach ($casos as [$slug, $titulo, $publicada, $cuando]) {
            Nota::updateOrCreate(['slug' => $slug], [
                'titulo' => $titulo,
                'categoria' => 'Auditoría',
                'resumen' => 'Nota creada para auditar visibilidad.',
                'cuerpo' => "Cuerpo de prueba.\n\nSegundo párrafo.",
                'publicada' => $publicada,
                'publicada_en' => $cuando,
            ]);
        }
    }

    /** Una campaña apagada, para ver que no sale en la portada. */
    private function banners(): void
    {
        Banner::updateOrCreate(
            ['archivo' => 'aud-campana-apagada'],
            ['alt' => 'AUD · Campaña apagada', 'orden' => 99, 'activo' => false]
        );
    }
}
