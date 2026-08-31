<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los roles son una escalera, así que basta con pedir el mínimo:
 * hoy `rol:admin` deja pasar sólo a administradores, pero si mañana el
 * cliente vuelve a querer un escalon intermedio, esto no cambia.
 */
class RequiereRol
{
    public function handle(Request $request, Closure $next, string $minimo): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('acceso')->with('mensaje', 'Inicia sesión para continuar.');
        }

        abort_unless($usuario->puede(Rol::from($minimo)), 403, 'Tu rol no tiene acceso a esta sección.');

        return $next($request);
    }
}
