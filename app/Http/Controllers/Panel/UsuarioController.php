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
        // Con buscador y filtro por rol.
        //
        // «Agregar o quitar a alguien del equipo» abría una lista de 254
        // cuentas de las cuales 209 son clientes del sitio, paginada de 30 en
        // 30 y sin forma de buscar: encontrar a Juan del mostrador podía
        // costar nueve páginas, y el formulario está debajo de las 30 filas.
        $filtros = [
            'q' => is_string($request->query('q')) ? trim($request->query('q')) : '',
            'rol' => is_string($request->query('rol')) ? $request->query('rol') : '',
        ];

        return view('panel.usuarios.index', [
            'usuarios' => User::query()
                ->when($filtros['q'], fn ($q, $t) => $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$t}%")
                    ->orWhere('email', 'like', "%{$t}%")))
                ->when(Rol::tryFrom($filtros['rol']), fn ($q, $rol) => $q->where('rol', $rol))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
            'filtros' => $filtros,
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

            // `forceFill` y no `update`: `rol` y `activo` salieron de
            // `$fillable` para que no puedan llegar desde una petición
            // cualquiera. Aquí SÍ deben poder asignarse —es la pantalla que
            // existe justo para eso— y el rol viene validado con
            // `Rule::enum` arriba. Que sea explícito es el punto: se ve de
            // un vistazo cuál es el único sitio que los toca.
            $usuario->forceFill($atributos)->save();
        } else {
            User::forceCreate($atributos);
        }

        return redirect()->route('panel.usuarios')
            ->with('mensaje', $usuario ? 'Usuario actualizado.' : 'Usuario creado.');
    }
}
