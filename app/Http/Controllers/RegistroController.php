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

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:120', 'unique:users,email'],
            'telefono' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'acepta' => ['accepted'],
        ], [
            'email.unique' => 'Ya hay una cuenta con ese correo. Inicia sesión o usa otro.',
            'acepta.accepted' => 'Necesitamos tu autorización para tratar tus datos.',
            'telefono.required' => 'Déjanos un teléfono: es por donde te contactamos.',
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'password' => $datos['password'],
            'rol' => Rol::Cliente,
            'activo' => true,
        ]);

        Auth::login($usuario, remember: true);
        $request->session()->regenerate();

        return redirect()->route('cuenta')
            ->with('mensaje', 'Listo, ya tienes cuenta. Registra tu primer vehículo.');
    }
}
