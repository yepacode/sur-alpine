<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Entrar con Facebook o con Google.
 *
 * Los botones están en la página de acceso de su sitio actual, así que aquí
 * también. Con dos reglas propias:
 *
 *   · mientras al proveedor le falten las llaves, el botón se ve pero devuelve
 *     al formulario con un aviso claro. Es un estado de puesta en marcha: en
 *     cuanto el cliente mande sus credenciales el mismo botón funciona;
 *   · entrar por aquí nunca da más permisos de los que ya se tienen. Una
 *     cuenta nueva nace como cliente, y una cuenta desactivada sigue sin poder
 *     entrar aunque su dueño tenga el Facebook a la mano.
 */
class AccesoSocialController extends Controller
{
    /** Los únicos dos que existen. Cualquier otro nombre en la URL da 404. */
    public const PROVEEDORES = ['facebook', 'google'];

    /** Cómo se llama cada uno cuando hay que nombrarlo en un mensaje. */
    public const NOMBRES = ['facebook' => 'Facebook', 'google' => 'Google'];

    /**
     * Los que ya tienen sus llaves puestas.
     *
     * Los botones se pintan siempre —su página los tiene, y esconderlos deja la
     * pantalla a medias—, pero mientras el proveedor no esté configurado
     * pulsarlos devuelve al formulario con un aviso claro. Es un estado de
     * puesta en marcha, no un botón muerto: en cuanto lleguen las llaves del
     * cliente, el mismo botón funciona sin tocar una línea.
     *
     * @return array<int, string>
     */
    public static function disponibles(): array
    {
        return array_values(array_filter(
            self::PROVEEDORES,
            fn (string $p) => config("services.{$p}.client_id") && config("services.{$p}.client_secret")
        ));
    }

    public function redirigir(string $proveedor): RedirectResponse
    {
        if ($aviso = $this->sinConfigurar($proveedor)) {
            return $aviso;
        }

        return Socialite::driver($proveedor)->redirect();
    }

    public function volver(string $proveedor): RedirectResponse
    {
        if ($aviso = $this->sinConfigurar($proveedor)) {
            return $aviso;
        }

        try {
            $perfil = Socialite::driver($proveedor)->user();
        } catch (\Throwable $e) {
            // Pasa cuando alguien cancela en la pantalla del proveedor, o
            // cuando las llaves están mal. No es culpa de quien lo intentó.
            Log::warning('Falló el acceso social', ['proveedor' => $proveedor, 'error' => $e->getMessage()]);

            return redirect()->route('acceso')
                ->withErrors(['email' => 'No pudimos entrar con '.ucfirst($proveedor).'. Intenta con tu correo y contraseña.']);
        }

        $correo = mb_strtolower(trim((string) $perfil->getEmail()));

        // Facebook permite cuentas sin correo verificado, y sin correo no hay
        // forma de saber si esta persona ya es cliente nuestro.
        if ($correo === '') {
            return redirect()->route('acceso')->withErrors([
                'email' => ucfirst($proveedor).' no nos compartió tu correo. Entra con tu correo y contraseña, o escríbenos.',
            ]);
        }

        // Dos formas de encontrar la cuenta, y no valen lo mismo.
        //
        // Por `proveedor_id` es prueba dura: ese identificador sólo lo conoce
        // el proveedor. Por CORREO es una conjetura, y Facebook permite
        // cuentas con el correo sin verificar: quien registrara allá el correo
        // de un administrador entraba aquí a su cuenta. Por eso el enlace por
        // correo NO alcanza al equipo.
        $porProveedor = User::query()
            ->where('proveedor', $proveedor)->where('proveedor_id', $perfil->getId())
            ->first();

        $porCorreo = $porProveedor ?: User::query()->whereRaw('LOWER(email) = ?', [$correo])->first();

        // Enlazar una cuenta que ya existe SÓLO si el proveedor jura que ese
        // correo está verificado.
        //
        // Sin esta comprobación, el correo era la vía principal y no un
        // respaldo: quien registrara en Facebook —que permite cuentas con el
        // correo sin verificar— la dirección de un cliente entraba a su
        // «Mi cuenta» con sus placas, su historial y sus cotizaciones, y de
        // paso le reescribía el `proveedor_id`. Google sí informa la
        // verificación; Facebook no, así que allí este camino simplemente no
        // se abre y la persona entra con su contraseña, que es lo correcto.
        // Una cuenta del equipo NUNCA se enlaza por correo, ni aunque el
        // proveedor lo dé por verificado: detrás hay el catálogo, los correos
        // del negocio y los datos de todos los clientes. Esa gente entra con
        // su contraseña.
        if ($porCorreo && ! $porProveedor && $porCorreo->entraAlPanel()) {
            return redirect()->route('acceso')->withErrors([
                'email' => 'Esa cuenta es del equipo: entra con tu correo y contraseña.',
            ]);
        }

        if ($porCorreo && ! $porProveedor && ! $this->correoVerificadoPor($perfil)) {
            return redirect()->route('acceso')->withErrors([
                'email' => 'Ya hay una cuenta con ese correo. Entra con tu contraseña y después podrás enlazar '
                    .ucfirst($proveedor).' desde «Mis datos».',
            ]);
        }

        $usuario = $porCorreo;

        if ($usuario) {
            if (! $usuario->activo) {
                return redirect()->route('acceso')->withErrors([
                    'email' => 'Tu cuenta está desactivada. Escríbenos para reactivarla.',
                ]);
            }

            // Se enlaza la cuenta que ya existía. Nunca se tocan el rol ni el
            // estado: quien entra por Facebook entra con los permisos que ya
            // tenía, no con los que diga el proveedor.
            $usuario->forceFill([
                'proveedor' => $proveedor,
                'proveedor_id' => $perfil->getId(),
            ])->save();
        } else {
            if (! config('portada.modulo_clientes')) {
                return redirect()->route('acceso')->withErrors([
                    'email' => 'Todavía no estamos creando cuentas nuevas. Escríbenos y te ayudamos.',
                ]);
            }

            // `forceCreate` y no `create`: `rol`, `activo`, `proveedor` y
            // `proveedor_id` están fuera de `$fillable` —no pueden llegar
            // desde una petición— y `create()` los descartaba EN SILENCIO. La
            // cuenta nacía sin contraseña y, peor, sin el identificador del
            // proveedor: el único dato duro que permite reconocerla la próxima
            // vez sin tener que fiarse del correo.
            $usuario = User::forceCreate([
                'name' => $perfil->getName() ?: Str::before($correo, '@'),
                'email' => $correo,
                'telefono' => null,
                // Sin contraseña: esta cuenta se abre sólo por el proveedor.
                // El campo es nullable desde la migración que acompaña esto.
                'password' => null,
                'rol' => Rol::Cliente,
                'activo' => true,
                'proveedor' => $proveedor,
                'proveedor_id' => $perfil->getId(),
            ]);
        }

        // «Recordarme» sólo para clientes. Una cookie persistente en la
        // cuenta que administra el catálogo y los correos del negocio es
        // demasiado tiempo de exposición para lo que ahorra.
        Auth::login($usuario, remember: ! $usuario->entraAlPanel());
        request()->session()->regenerate();

        return redirect()->intended($usuario->entraAlPanel() ? route('panel.tablero') : route('cuenta'));
    }

    /**
     * La respuesta de «todavía no» cuando al proveedor le faltan las llaves.
     *
     * Un nombre que no sea `facebook` ni `google` sí es 404: eso no es un
     * proveedor pendiente, es una URL inventada.
     */
    /**
     * ¿El proveedor dice que ese correo está verificado?
     *
     * Google lo informa (`email_verified`, o `verified_email` en la API
     * vieja). Facebook no manda nada, así que aquí devuelve `false` y ese
     * camino queda cerrado: es lo correcto, porque Facebook permite abrir una
     * cuenta con un correo que nunca se comprobó.
     *
     * Ante la duda, `false`. Es la única respuesta segura.
     */
    private function correoVerificadoPor(object $perfil): bool
    {
        $crudo = (array) ($perfil->user ?? []);

        return ($crudo['email_verified'] ?? $crudo['verified_email'] ?? null) === true;
    }

    private function sinConfigurar(string $proveedor): ?RedirectResponse
    {
        if (! in_array($proveedor, self::PROVEEDORES, true)) {
            throw new NotFoundHttpException;
        }

        if (in_array($proveedor, self::disponibles(), true)) {
            return null;
        }

        return redirect()->route('acceso')->withErrors([
            'email' => 'Estamos activando el ingreso con '.self::NOMBRES[$proveedor]
                .'. Por ahora entra con tu correo y contraseña, o crea tu cuenta.',
        ]);
    }
}
