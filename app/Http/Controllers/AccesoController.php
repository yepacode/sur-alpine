<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccesoController extends Controller
{
    public function formulario(): View
    {
        return view('acceso.entrar');
    }

    public function entrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Cinco intentos por minuto y por correo: frena la fuerza bruta sin
        // castigar a quien simplemente se equivocó de tecla.
        $this->limitar($request, $datos['email']);

        if (! Auth::attempt($datos, $request->boolean('recordarme'))) {
            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña no coinciden.',
            ]);
        }

        if (! $request->user()->activo) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Comunícate con el administrador.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            $request->user()->entraAlPanel() ? route('panel.tablero') : route('inicio')
        );
    }

    public function salir(Request $request): RedirectResponse
    {
        // El carrito sobrevive al cierre de sesión: un cliente que sale para
        // entrar con otra cuenta no puede perder la lista que venía armando.
        $carrito = $request->session()->get(\App\Services\Cotizador::LLAVE);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($carrito) {
            $request->session()->put(\App\Services\Cotizador::LLAVE, $carrito);
        }

        return redirect()->route('inicio')->with('mensaje', 'Cerraste sesión.');
    }

    private function limitar(Request $request, string $email): void
    {
        $llave = 'acceso:'.mb_strtolower($email).'|'.$request->ip();

        if (app('Illuminate\Cache\RateLimiter')->tooManyAttempts($llave, 5)) {
            $segundos = app('Illuminate\Cache\RateLimiter')->availableIn($llave);

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Vuelve a probar en {$segundos} segundos.",
            ]);
        }

        app('Illuminate\Cache\RateLimiter')->hit($llave, 60);
    }
}
