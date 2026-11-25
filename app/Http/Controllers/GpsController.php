<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\ActividadParticipante;
use App\Models\Agremiado;
use App\Models\SysLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Seguimiento por GPS durante actividades: solo participan agremiados que lo autorizaron. */
class GpsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Gps', [
            ...$this->datos(),
            'historial' => Actividad::with(['creador', 'participantes'])->where('estado', 'Finalizada')->orderByDesc('finalizada_at')->take(300)->get()->map(fn ($a) => [
                'id' => $a->id, 'nombre' => $a->nombre, 'participantes' => $a->participantes->count(),
                'inicio' => $a->created_at->format('Y-m-d H:i'), 'fin' => $a->finalizada_at?->format('Y-m-d H:i'), 'inicio_por' => $a->creador?->name,
            ]),
            'disponibles' => Agremiado::activos()->where('gps_autorizado', true)
                ->whereDoesntHave('usuario', fn ($q) => $q->where('activo', false))
                ->whereNotIn('id', ActividadParticipante::whereHas('actividad', fn ($q) => $q->where('estado', 'Activa'))->pluck('agremiado_id'))
                ->orderBy('nombre')->get()->map(fn ($a) => $this->persona($a)),
            'puede' => ['editar' => $request->user()->puede('ubicacion', 'e')],
        ]);
    }

    /** Posiciones en vivo (la vista las consulta cada pocos segundos). */
    public function posiciones(): JsonResponse
    {
        return response()->json($this->datos());
    }

    private function datos(): array
    {
        $activas = Actividad::with(['participantes.agremiado.gpsPor', 'creador'])->where('estado', 'Activa')->orderByDesc('id')->get();

        return [
            'actividades' => $activas->map(fn (Actividad $a) => [
                'id' => $a->id, 'nombre' => $a->nombre, 'inicio' => $this->traza($a)['creado'],
                'participantes' => $a->participantes->map(fn ($p) => [
                    'id' => $p->id, 'agremiado' => $this->persona($p->agremiado),
                    'lat' => $p->latitud, 'lng' => $p->longitud, 'precision' => $p->precision,
                    'senal' => $p->ultima_senal?->format('Y-m-d H:i:s'), 'hace' => $p->ultima_senal?->diffForHumans(),
                    'gps' => $p->agremiado?->gps_autorizado ? ['usuario' => $p->agremiado->gpsPor?->name ?? 'El agremiado', 'fecha' => $p->agremiado->gps_autorizado_at?->format('Y-m-d H:i')] : null,
                ])->values(),
            ])->values(),
            'servidor' => now()->format('Y-m-d H:i:s'),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'participantes' => ['required', 'array', 'min:1'],
            'participantes.*' => ['integer', 'exists:agremiados,id'],
        ], ['participantes.required' => 'Selecciona al menos un participante.'], ['nombre' => 'nombre de la actividad']);

        $validos = Agremiado::activos()->where('gps_autorizado', true)->whereIn('id', $d['participantes'])->pluck('id');
        abort_if($validos->count() !== count($d['participantes']), 422, 'Solo pueden participar agremiados que autorizaron el GPS.');

        DB::transaction(function () use ($d, $validos) {
            $a = Actividad::create(['nombre' => $d['nombre']]);
            foreach (Agremiado::whereIn('id', $validos)->get() as $ag) {
                // Posición inicial: el domicilio registrado (se actualiza cuando la app envía su ubicación)
                $a->participantes()->create(['agremiado_id' => $ag->id, 'latitud' => $ag->latitud, 'longitud' => $ag->longitud]);
            }
            $a->writeAudit('Inició seguimiento GPS', null, count($d['participantes']).' participante(s)');
        });

        return back()->with('success', 'Seguimiento iniciado');
    }

    public function finalizar(Request $request, Actividad $actividad): RedirectResponse
    {
        abort_if($actividad->estado !== 'Activa', 422, 'La actividad ya fue finalizada.');
        $actividad->update(['estado' => 'Finalizada', 'finalizada_at' => now(), 'finalizada_por' => $request->user()->id]);

        return back()->with('success', 'Seguimiento finalizado');
    }

    public function quitar(ActividadParticipante $participante): RedirectResponse
    {
        $a = $participante->actividad;
        SysLog::registrar('ubicacion', 'actividades', $a->id, $a->nombre, 'Finalizó seguimiento de participante', $participante->agremiado?->nombre_completo);
        $participante->delete();
        if (! $a->participantes()->exists()) {
            $a->update(['estado' => 'Finalizada', 'finalizada_at' => now(), 'finalizada_por' => auth()->id()]);
        }

        return back()->with('success', 'Seguimiento del participante finalizado');
    }
}
