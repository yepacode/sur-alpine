<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

/**
 * Registro de clientes.
 *
 * Los mecánicos entran como «cliente»: es el escalón más bajo de la escalera
 * de roles y sólo abre lo suyo —sus vehículos y sus mantenimientos—, nunca el
 * panel. El equipo interno lo crea el administrador desde el panel, no aquí.
 */
class RegistroController extends Controller
{
    public function formulario(): View
    {
        return view('acceso.registro');
    }

    public function crear(Request $request): RedirectResponse
    {
        // Campo trampa, igual que en el cotizador: los formularios automáticos
        // lo llenan y las personas no lo ven.
        if (filled($request->input('sitio_web'))) {
            return redirect()->route('inicio');
        }

        // La validación NO trae `unique:users,email`. Si contestara «ya hay una
        // cuenta con ese correo», el formulario serviría para averiguar qué
        // correos existen en el sistema; y la papeleria del gremio ya circuló
        // más de una lista así. Aquí revisamos aparte y respondemos con la
        // misma pantalla de éxito, sin decir por qué.
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'telefono' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'acepta' => ['accepted'],
        ], [
            'acepta.accepted' => 'Necesitamos tu autorización para tratar tus datos.',
            'telefono.required' => 'Déjanos un teléfono: es por donde te contactamos.',
        ]);

        $emailYaExistia = User::where('email', $datos['email'])->exists();

        if (! $emailYaExistia) {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'password' => $datos['password'],
                'rol' => Rol::Cliente,
                'activo' => true,
                // Habeas Data: la fecha y la versión que aceptó quedan clavadas
                // al usuario. Si mañana el texto cambia, la sesión sabrá que hay
                // una versión nueva y pedirá autorización otra vez.
                'acepto_en' => now(),
                'politica_version' => version_habeas(),
            ]);

            // El enlace para confirmar el correo. Va aquí y no sólo en «Mis
            // datos» porque éste es el momento en que un dedazo en la
            // dirección todavía se puede corregir sin haber perdido nada.
            //
            // No bloquea nada: la cuenta queda usable sin confirmar. Si el
            // correo no sale —el servidor de correo caído, por ejemplo— eso
            // no puede impedir que la persona termine de registrarse.
            try {
                $usuario->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::warning('No salió el correo de confirmación', [
                    'usuario' => $usuario->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Enumeración: la respuesta observable es IDÉNTICA en los dos casos.
        // Antes el registro exitoso redirigía a /mi-cuenta y el duplicado a
        // /acceso — un atacante comparaba el `Location` y confirmaba qué
        // correos existen. Ahora ambos terminan en /acceso con el mismo
        // mensaje; el mecánico legítimo hace login normal (pierde el
        // auto-login, precio pequeño por privacidad).
        return redirect()->route('acceso')
            ->with('mensaje', 'Listo, ya puedes iniciar sesión con tu correo.');
    }
}
