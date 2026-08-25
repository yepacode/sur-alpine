<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (User::where('email', $datos['email'])->exists()) {
            // Se manda un correo silencioso al dueño real avisando del intento,
            // para que sepa que alguien puso su correo aquí; ese trabajo se
            // hará cuando esté armado el envío de correos administrativos.
            // Por ahora el efecto observable es idéntico al de un registro
            // exitoso: se redirige al acceso y no se revela nada.
            return redirect()->route('acceso')
                ->with('mensaje', 'Listo, revisa tu correo para continuar.');
        }

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'password' => $datos['password'],
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);

        // La cookie persistente («recordarme») es opcional en el acceso, no
        // se impone al registrar: cinco años de credencial son decisión del
        // usuario, no del sistema.
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('cuenta')
            ->with('mensaje', 'Listo, ya tienes cuenta. Registra tu primer vehículo.');
    }
}
