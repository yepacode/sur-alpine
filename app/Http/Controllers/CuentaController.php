<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * El área del cliente: sus vehículos y sus mantenimientos.
 *
 * Todo lo de aquí es del usuario que entró. No hay una sola consulta que no
 * cuelgue de `$request->user()`: es la forma de que nadie vea, por cambiar un
 * número en la URL, la placa ni el historial de otro.
 */
class CuentaController extends Controller
{
    public function inicio(Request $request): View
    {
        $usuario = $request->user();

        return view('cuenta.inicio', [
            'vehiculos' => $usuario->vehiculosGuardados()->with('modelo.marca')->get(),
            // Lo que toca pronto va primero: es a lo que el mecánico entra.
            // Incluye los de kilometraje. Antes se filtraban con
            // `whereNotNull('proximo_fecha')` y los de km guardan `null` ahí,
            // así que quien lleva el carro por kilómetros —lo normal en un
            // taller— nunca veía nada en su tablero.
            'proximos' => $usuario->mantenimientos()
                ->where(fn ($q) => $q
                    ->whereNotNull('proximo_fecha')
                    ->orWhereNotNull('proximo_kilometraje'))
                ->orderByRaw("COALESCE(proximo_fecha, '9999-12-31')")
                ->orderBy('proximo_kilometraje')
                ->limit(5)
                ->get(),
            'totalMantenimientos' => $usuario->mantenimientos()->count(),
        ]);
    }

    /** Guardar un carro en «mis vehículos», con su placa y su alias. */
    public function guardarVehiculo(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
            'placa' => ['nullable', 'string', 'max:10'],
            'alias' => ['nullable', 'string', 'max:60'],
        ]);

        // Sólo se mandan las llaves que llegaron: `syncWithoutDetaching`
        // con `null` sobrescribe, y el mecánico que reguardaba su carro sólo
        // para ponerle un alias perdía la placa guardada.
        $pivote = array_filter([
            'placa' => filled($datos['placa'] ?? null) ? mb_strtoupper($datos['placa']) : null,
            'alias' => $datos['alias'] ?? null,
        ], fn ($v) => filled($v));

        $request->user()->vehiculosGuardados()->syncWithoutDetaching([
            $datos['vehiculo_id'] => $pivote,
        ]);

        return back()->with('mensaje', 'Vehículo guardado en tu cuenta.');
    }

    public function quitarVehiculo(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $request->user()->vehiculosGuardados()->detach($vehiculo->id);

        return back()->with('mensaje', 'Quitamos el vehículo de tu cuenta.');
    }

    /** El historial completo, con el filtro por placa que pide un mecánico. */
    public function mantenimientos(Request $request): View
    {
        $usuario = $request->user();

        return view('cuenta.mantenimientos', [
            'mantenimientos' => $usuario->mantenimientos()
                ->with('vehiculo.modelo.marca')
                ->when($request->query('placa'), fn ($q, $placa) => $q->where('placa', mb_strtoupper($placa)))
                ->orderByDesc('fecha')
                ->paginate(20)
                ->withQueryString(),
            'placas' => $usuario->mantenimientos()->distinct()->orderBy('placa')->pluck('placa'),
            'vehiculos' => $usuario->vehiculosGuardados()->with('modelo.marca')->get(),
            'placaFiltrada' => $request->query('placa'),
        ]);
    }

    public function guardarMantenimiento(Request $request): RedirectResponse
    {
        $datos = $this->validarMantenimiento($request);

        $mantenimiento = new Mantenimiento($datos);
        $mantenimiento->user_id = $request->user()->id;
        $mantenimiento->calcularProximo()->save();

        return redirect()->route('cuenta.mantenimientos')
            ->with('mensaje', "Anotamos «{$mantenimiento->tipo}» para la placa {$mantenimiento->placa}.");
    }

    public function actualizarMantenimiento(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->soloSuyo($request, $mantenimiento);

        $mantenimiento->fill($this->validarMantenimiento($request))->calcularProximo()->save();

        return redirect()->route('cuenta.mantenimientos')->with('mensaje', 'Mantenimiento actualizado.');
    }

    public function borrarMantenimiento(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->soloSuyo($request, $mantenimiento);

        $mantenimiento->delete();

        return back()->with('mensaje', 'Borramos el registro.');
    }

    /** @return array<string, mixed> */
    private function validarMantenimiento(Request $request): array
    {
        $datos = $request->validate([
            'placa' => ['required', 'string', 'max:10'],
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
            'tipo' => ['required', 'string', 'max:80'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'kilometraje' => ['required', 'integer', 'min:0', 'max:9999999'],
            'periodicidad_tipo' => ['required', 'in:dias,meses,kilometraje'],
            'periodicidad_valor' => ['required', 'integer', 'min:1', 'max:999999'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'fecha.before_or_equal' => 'La fecha no puede ser futura: es lo que ya se hizo.',
            'tipo.required' => 'Escribe qué se le hizo al carro.',
        ]);

        $datos['placa'] = mb_strtoupper($datos['placa']);

        return $datos;
    }

    /** El historial de otro no se toca, ni cambiando el número de la URL. */
    private function soloSuyo(Request $request, Mantenimiento $mantenimiento): void
    {
        abort_unless($mantenimiento->user_id === $request->user()->id, 403);
    }
}
