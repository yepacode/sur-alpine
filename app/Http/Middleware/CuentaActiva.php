<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cortar la sesión de una cuenta desactivada.
 *
 * `User::puede()` mira `activo`, pero sólo se ejecuta en el middleware `rol:`,
 * así que el cliente cuya cuenta se desactivó seguía entrando a `/mi-cuenta`.
 * Y con «recordarme» —que el registro forzaba a `true`— la credencial vivía
 * cinco años en el navegador.
 *
 * Este middleware corre en todas las rutas web autenticadas y cierra la
 * sesión —incluida la cookie persistente— apenas se detecta que la cuenta
 * está desactivada.
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->activo) {
            // Rompe la sesión y el token de «recordarme» que quedara guardado.
            $usuario->forceFill(['remember_token' => null])->save();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('acceso')->with('mensaje', 'Tu cuenta está desactivada. Comunícate con el administrador.');
        }

        return $next($request);
    }
}
