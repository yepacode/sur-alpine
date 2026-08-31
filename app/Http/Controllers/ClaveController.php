<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaClave;
use Illuminate\Validation\ValidationException;

/**
 * Volver a entrar cuando se olvidó la contraseña.
 *
 * Hasta ahora no existía: quien olvidaba la clave quedaba afuera para
 * siempre, sin puerta lateral —el ingreso con Facebook y Google todavía
 * espera las llaves del cliente.
 *
 * Dos cosas que aquí se hacen distinto de lo que sale por defecto:
 *
 * · La respuesta es SIEMPRE la misma, exista el correo o no. Decir «ese
 *   correo no está registrado» convierte este formulario en un detector de
 *   clientes: cualquiera prueba direcciones y averigua quién compra aquí.
 *   Es el mismo criterio que ya usa el registro.
 *
 * · Una cuenta desactivada no recibe enlace. Si el administrador sacó a
 *   alguien, no puede volver a entrar cambiándose la contraseña.
 */
class ClaveController extends Controller
{
    /** «Olvidé mi contraseña» · pedir el enlace. */
    public function pedir(): View
    {
        return view('acceso.olvide');
    }

    public function enviar(Request $request): RedirectResponse
    {
        $datos = $request->validate(['email' => ['required', 'email']]);

        $this->limitar($request, $datos['email']);

        Password::sendResetLink($datos);

        // Una sola respuesta, sin excepciones.
        //
        // `sendResetLink` distingue entre «lo mandé», «no existe ese correo» y
        // «ya mandé uno hace poco», y aquí había una excepción por utilidad
        // para el tercero. El problema es que ese tercero SÓLO puede ocurrir
        // con un correo que existe —Laravel devuelve «no existe» antes de
        // mirar el límite—, así que dos intentos seguidos con el mismo correo
        // decían si esa persona es cliente de Sur Alpine o no.
        //
        // Justo eso es lo que le hace falta a quien los está suplantando: una
        // lista confirmada de sus clientes hace creíble cualquier correo falso
        // que les mande. La comodidad de un aviso no lo vale.
        return back()->with('mensaje', 'Si esa dirección tiene una cuenta con nosotros, te llega un enlace en unos minutos. Revisa también la carpeta de no deseados. Si ya lo pediste hace poco, te llegó el de antes.');
    }

    /** El formulario al que lleva el enlace del correo. */
    public function formulario(Request $request, string $token): View
    {
        return view('acceso.restablecer', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function restablecer(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            // La misma regla que el registro: ocho caracteres. No se pide
            // mayúscula ni símbolo a propósito —eso produce «Clave123!» en un
            // papel pegado al monitor— pero sí se rechazan las filtradas.
            'password' => ['required', 'confirmed', ReglaClave::min(8)],
        ], [
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ]);

        $estado = Password::reset($datos, function ($usuario, string $clave) {
            $usuario->forceFill([
                'password' => $clave,
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Ese enlace ya venció o se usó. Pide uno nuevo.',
            ]);
        }

        // Sin iniciar sesión sola: que escriba la contraseña nueva una vez es
        // lo que hace que se le quede.
        return redirect()->route('acceso')
            ->with('mensaje', 'Listo, ya tienes contraseña nueva. Entra con ella.');
    }

    /** Cinco por hora y por correo: un enlace de acceso no se pide diez veces. */
    private function limitar(Request $request, string $email): void
    {
        $limitador = app(\Illuminate\Cache\RateLimiter::class);
        $llave = 'clave-olvidada:'.mb_strtolower($email).'|'.$request->ip();

        if ($limitador->tooManyAttempts($llave, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiadas solicitudes. Vuelve a intentarlo en una hora.',
            ]);
        }

        $limitador->hit($llave, 3600);
    }
}
