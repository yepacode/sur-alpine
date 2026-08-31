<?php

namespace App\Console\Commands;

use App\Mail\AvisoMantenimiento;
use App\Models\Mantenimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * El recordatorio de mantenimiento que sale a buscar a la persona.
 *
 * Hasta ahora el aviso sólo existía en pantalla: el cliente anotaba «cambio de
 * aceite cada seis meses» y sólo se enteraba de que le tocaba si entraba a
 * mirar. Un recordatorio que hay que ir a buscar no es un recordatorio.
 *
 * Tres decisiones que sostienen esto:
 *
 * · Un correo por PERSONA, no por mantenimiento. Quien lleva tres carros
 *   recibiría tres correos la misma mañana y al tercero los manda a no
 *   deseados —y con ellos, el de su cotización.
 *
 * · Se avisa UNA vez por mantenimiento (`aviso_enviado_en`). El comando corre
 *   todos los días; sin esa marca, el mismo cambio de aceite llegaría cada
 *   mañana hasta que la persona lo anotara.
 *
 * · Lo que va por kilometraje NO dispara el correo. No sabemos cómo va el
 *   odómetro de nadie, y poner una fecha inventada sería peor que callar: se
 *   mencionan de pasada cuando ya le estamos escribiendo por otra cosa.
 */
class AvisarMantenimientos extends Command
{
    protected $signature = 'mantenimientos:avisar
                            {--dias=7 : Con cuántos días de anticipación avisar}
                            {--seco : Muestra a quién se le escribiría, sin enviar nada}';

    protected $description = 'Avisa por correo a quien tiene un mantenimiento vencido o por vencer';

    public function handle(): int
    {
        $dias = max(0, (int) $this->option('dias'));
        $seco = (bool) $this->option('seco');
        $limite = today()->addDays($dias);

        $pendientes = Mantenimiento::query()
            ->whereNull('aviso_enviado_en')
            ->whereNotNull('proximo_fecha')
            ->whereDate('proximo_fecha', '<=', $limite)
            // Una cuenta desactivada o dada de baja no recibe nada: quien pidió
            // que lo borráramos no puede seguir recibiendo correos nuestros.
            ->whereHas('usuario', fn ($q) => $q->where('activo', true))
            ->with('usuario')
            ->get()
            ->groupBy('user_id');

        if ($pendientes->isEmpty()) {
            $this->info('No hay mantenimientos por avisar.');

            return self::SUCCESS;
        }

        // Los de kilometraje, de TODOS los avisados, en una sola consulta.
        //
        // Antes se pedían dentro del bucle: 243 consultas idénticas salvo el
        // `user_id` para 243 usuarios, más la de arriba. Es un trabajo
        // nocturno, así que nadie lo ve tardar —pero cada noche abre una
        // conexión larga contra la base del hosting compartido para nada.
        $porKilometrajePorUsuario = $this->porKilometraje($pendientes->keys()->all());

        $escritos = 0;

        foreach ($pendientes as $delUsuario) {
            $usuario = $delUsuario->first()->usuario;

            [$vencidos, $porVencer] = $delUsuario->partition->vencido;

            // Los de kilometraje viajan de acompañantes: nunca son el motivo
            // del correo, pero ya que le estamos escribiendo, se los
            // recordamos. Sin fecha, porque no la tenemos.
            $porKilometraje = $porKilometrajePorUsuario->get($usuario->id, collect());

            $this->line(sprintf(
                '  %s · %d vencido(s), %d por vencer%s',
                $usuario->email,
                $vencidos->count(),
                $porVencer->count(),
                $porKilometraje->isNotEmpty() ? ', '.$porKilometraje->count().' por km' : ''
            ));

            if ($seco) {
                continue;
            }

            try {
                Mail::to($usuario->email)->send(new AvisoMantenimiento(
                    $usuario,
                    $vencidos->values(),
                    $porVencer->values(),
                    $porKilometraje,
                ));
            } catch (\Throwable $e) {
                // Que le falle el correo a uno no puede dejar sin aviso a los
                // demás. Se registra y se sigue; la marca no se pone, así que
                // mañana se vuelve a intentar.
                Log::error('No se pudo avisar un mantenimiento', [
                    'usuario' => $usuario->id,
                    'error' => $e->getMessage(),
                ]);

                $this->warn("    no salió el correo: {$e->getMessage()}");

                continue;
            }

            Mantenimiento::whereIn('id', $delUsuario->pluck('id'))
                ->update(['aviso_enviado_en' => now()]);

            $escritos++;
        }

        $this->newLine();
        $this->info($seco
            ? 'Simulación: no se envió nada.'
            : "Listo: {$escritos} correo(s) enviado(s).");

        return self::SUCCESS;
    }

    /**
     * Los de kilometraje de esa persona, para mencionarlos de pasada.
     *
     * No se marcan como avisados: como nunca disparan el correo por sí solos,
     * marcarlos los sacaría de la próxima mención sin que nadie los haya
     * atendido.
     *
     * @return Collection<int, Mantenimiento>
     */
    private function porKilometraje(array $usuarios): Collection
    {
        // Sólo el ÚLTIMO de cada (placa, tipo), y como mucho cinco por persona.
        //
        // Antes venía todo: un taller que lleva dos años anotando bujías y
        // filtros por kilometraje recibía cada aviso con cuarenta líneas,
        // incluidos servicios que ya hizo. Es el patrón exacto que lleva a
        // marcar el remitente como no deseado —y con él se van también los
        // correos de cotización, que sí pidió—.
        //
        // Una consulta para todos, agrupada después en memoria: eran 243
        // consultas idénticas salvo el `user_id`.
        return Mantenimiento::query()
            ->whereIn('user_id', $usuarios)
            ->whereNotNull('proximo_kilometraje')
            ->orderByDesc('fecha')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $suyos) => $suyos
                ->unique(fn (Mantenimiento $m) => $m->placa.'|'.mb_strtolower($m->tipo))
                ->sortBy('proximo_kilometraje')
                ->take(5)
                ->values());
    }
}
