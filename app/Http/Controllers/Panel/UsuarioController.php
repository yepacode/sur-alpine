<?php

namespace App\Http\Controllers\Panel;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('panel.usuarios.index', [
            'usuarios' => User::orderBy('name')->paginate(30),
            'roles' => Rol::opciones(),
            // El mismo formulario sirve para crear y para editar: sin `?editar`
            // está en blanco. Así funciona igual sin JavaScript.
            'editando' => $request->filled('editar')
                ? User::find($request->integer('editar'))
                : null,
        ]);
    }

    public function guardar(Request $request, ?User $usuario = null): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'rol' => ['required', Rule::enum(Rol::class)],
            'activo' => ['nullable', 'boolean'],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ]);

        $atributos = [
            'name' => $datos['name'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'] ?? null,
            'rol' => $datos['rol'],
            'activo' => $request->boolean('activo'),
        ];

        if (filled($datos['password'] ?? null)) {
            $atributos['password'] = $datos['password'];
        }

        if ($usuario) {
            // Nadie puede quitarse a sí mismo el acceso y dejar el panel sin
            // administrador; menos aún desactivar su propia cuenta.
            if ($usuario->is($request->user())) {
                $atributos['rol'] = $usuario->rol;
                $atributos['activo'] = true;
            }

            $usuario->update($atributos);
        } else {
            User::create($atributos);
        }

        return redirect()->route('panel.usuarios')
            ->with('mensaje', $usuario ? 'Usuario actualizado.' : 'Usuario creado.');
    }
}
