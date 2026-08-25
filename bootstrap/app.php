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
        ]);

        // Corta la sesión de una cuenta desactivada, incluida la cookie
        // persistente de «recordarme». Va DESPUÉS de StartSession y
        // Authenticate, en el grupo web.
        $middleware->appendToGroup('web', \App\Http\Middleware\CuentaActiva::class);

        $middleware->redirectGuestsTo(fn () => route('acceso'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
