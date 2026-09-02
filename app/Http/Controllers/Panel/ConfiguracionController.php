<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmacionCotizacion;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConfiguracionController extends Controller
{
    /** Lo que el administrador puede cambiar sin llamar a nadie. */
    private const CAMPOS = [
        'correos_cotizacion' => 'correo',
        'telefono_pbx' => 'contacto',
        'celulares' => 'contacto',
        // La calle y la ciudad van separadas porque el dato estructurado que
        // lee Google las pide por separado.
        'direccion' => 'contacto',
        'ciudad' => 'contacto',
        'whatsapp' => 'contacto',
        // El horario en 24 horas, para el dato estructurado. El texto que se
        // LEE en «Contáctenos» es otro campo, en «Textos e imágenes»:
        // adivinar horas a partir de «de 8 a 6» se rompe solo.
        'horario_semana' => 'contacto',
        'horario_sabado' => 'contacto',
        'horario_festivo' => 'contacto',
        'facebook' => 'redes',
        'instagram' => 'redes',
    ];

    public function editar(): View
    {
        return view('panel.configuracion', [
            'valores' => collect(self::CAMPOS)
                ->mapWithKeys(fn ($grupo, $clave) => [$clave => Configuracion::valor($clave)])
                ->all(),
            'destinos' => Configuracion::correosDestino(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'correos_cotizacion' => ['required', 'string', 'max:500'],
            'telefono_pbx' => ['nullable', 'string', 'max:40'],
            'celulares' => ['nullable', 'string', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:160'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            // El formato se valida aquí y no al leerlo: mejor decirle al
            // administrador que se equivocó que dejar de emitir el horario en
            // silencio y que él crea que quedó puesto.
            'horario_semana' => ['nullable', 'regex:/^\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/'],
            'horario_sabado' => ['nullable', 'regex:/^\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/'],
            'horario_festivo' => ['nullable', 'regex:/^\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/'],
            'facebook' => ['nullable', 'url', 'max:200'],
            'instagram' => ['nullable', 'url', 'max:200'],
        ], [
            'horario_semana.regex' => 'El horario entre semana va así: 08:00-18:00.',
            'horario_sabado.regex' => 'El horario del sábado va así: 08:00-16:00.',
            'horario_festivo.regex' => 'El horario de festivos va así: 09:00-13:00.',
        ]);

        foreach (self::CAMPOS as $clave => $grupo) {
            Configuracion::poner($clave, $datos[$clave] ?? null, $grupo);
        }

        // Se valida después de guardar: si ningún correo es válido, las
        // solicitudes no llegarían a nadie y el equipo tiene que enterarse ya.
        if (Configuracion::correosDestino() === []) {
            return back()->withErrors([
                'correos_cotizacion' => 'Ninguno de esos correos es válido. Las solicitudes no llegarían a nadie.',
            ]);
        }

        return back()->with('mensaje', 'Configuración guardada.');
    }

    /** Manda un correo de prueba para confirmar que la mensajería funciona. */
    public function probarCorreo(Request $request): RedirectResponse
    {
        $destinos = Configuracion::correosDestino();

        if ($destinos === []) {
            return back()->with('problema', 'Configura primero al menos un correo válido.');
        }

        // Cotización FICTICIA, armada en memoria: antes se usaba `Cotizacion::latest()`
        // y un admin podía mandarse a sí mismo el nombre, teléfono y lista de
        // repuestos del último cliente real. La prueba se manda al correo del
        // usuario autenticado, no al de destinos, para que quede en su bandeja.
        $muestra = new Cotizacion([
            'consecutivo' => 'SA-PRUEBA',
            'nombre' => 'Cliente',
            'apellidos' => 'de prueba',
            'telefono' => '000 000 0000',
            'email' => $request->user()->email,
            'notas' => 'Correo de prueba enviado desde la configuración del panel.',
        ]);
        $muestra->setRelation('items', collect([
            new \App\Models\CotizacionItem([
                'producto_nombre' => 'Filtro Aceite AVEO 1600 CHEVROLET',
                'vehiculo_nombre' => 'CHEVROLET AVEO 1600 (2006-2013)',
                'cantidad' => 1,
            ]),
        ]));

        try {
            Mail::to($request->user()->email)->send(new ConfirmacionCotizacion($muestra));
        } catch (\Throwable $e) {
            // En rojo y en castellano.
            //
            // Antes salía en la misma barra azul que un acierto —el dueño la
            // ha visto siempre significar «listo»— y con el mensaje crudo de
            // Symfony en inglés: «Expected response code "250" but got code
            // "535"». Se registra el detalle técnico para nosotros y a él se
            // le dice qué pasó y a quién llamar.
            \Illuminate\Support\Facades\Log::warning('Falló el correo de prueba', ['error' => $e->getMessage()]);

            return back()->with('problema', 'No salió el correo de prueba. Revisa la configuración de correo del hosting, o pídenos que la miremos.');
        }

        return back()->with('mensaje', "Mandamos un correo de prueba a {$request->user()->email}.");
    }
}
