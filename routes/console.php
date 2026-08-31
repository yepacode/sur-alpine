<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * El recordatorio de mantenimiento, cada mañana a las 8.
 *
 * A las 8 y no a medianoche a propósito: un correo que llega a las 3 a.m.
 * queda sepultado bajo lo que entra en la mañana. `withoutOverlapping` porque
 * si un día el servidor de correo se demora, dos corridas encimadas escribirían
 * dos veces a la misma persona.
 *
 * OJO AL DESPLEGAR: esto no corre solo. El servidor necesita una tarea de cron
 * que llame al planificador cada minuto:
 *
 *   * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
 *
 * Sin esa línea el comando nunca se ejecuta y los avisos no salen, sin que
 * nada falle ni avise.
 */
Schedule::command('mantenimientos:avisar')
    ->dailyAt('08:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();
