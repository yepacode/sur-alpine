<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => \App\Http\Middleware\RequiereRol::class,
            'slug' => \App\Http\Middleware\SlugCanonico::class,
        ]);

        // El rol se comprueba ANTES de resolver el modelo de la URL.
        //
        // Sin esto, `SubstituteBindings` —que sí está en la lista de
        // prioridad— corría primero, así que un cliente cualquiera recibía 403
        // en `/panel/usuarios/258` y 404 en `/panel/usuarios/99999`. No filtra
        // contenido, pero le dice a cualquiera que se registre cuántos
        // usuarios y cuántas cotizaciones hay y qué ids existen, barriendo.
        // Un 403 uniforme no cuenta nada.
        $middleware->priority([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\CuentaActiva::class,
            \App\Http\Middleware\RequiereRol::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);

        // Corta la sesión de una cuenta desactivada, incluida la cookie
        // persistente de «recordarme». Va DESPUÉS de StartSession y
        // Authenticate, en el grupo web.
        // Antes que nada: nada de bytes que la base no pueda guardar.
        //
        // Va en `prepend` para correr antes de cualquier validación: lo que
        // llegue a un controlador ya es UTF-8 bien formado.
        $middleware->prepend(\App\Http\Middleware\TextoValido::class);

        // Las cabeceras de seguridad, para TODAS las respuestas y no sólo las
        // del grupo `web`: el sitemap y `robots.txt` también salen de aquí.
        $middleware->append(\App\Http\Middleware\CabecerasDeSeguridad::class);

        $middleware->appendToGroup('web', \App\Http\Middleware\CuentaActiva::class);

        // Ata la sesión a la contraseña con la que se abrió. Es lo que hace
        // que al cambiar la contraseña se caigan las OTRAS sesiones: sin
        // esto, `Auth::logoutOtherDevices()` no cierra nada y quien cambia
        // la clave porque alguien más entró le deja la sesión abierta.
        $middleware->appendToGroup('web', \Illuminate\Session\Middleware\AuthenticateSession::class);

        // Con una explicacion, no en seco.
        //
        // Quien deja «Mi cuenta» abierta y vuelve al rato veia la pantalla de
        // acceso limpia: exactamente la misma que si hubiera entrado a
        // proposito, sin una palabra sobre que se le habia vencido la sesion.
        // Se lee como «el sitio me sacó» y no como «pasó el tiempo».
        $middleware->redirectGuestsTo(fn () => redirect()
            ->route('acceso')
            ->with('mensaje', 'Se cerró tu sesión por seguridad. Entra otra vez y sigues donde ibas.')
            ->getTargetUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
