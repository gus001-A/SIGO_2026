<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Apoyo;
use App\Models\Archivo;
use App\Models\Catalogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApoyoController extends Controller
{
    public function index(Request $request): Response
    {
        $apoyos = Apoyo::with(['agremiado', 'archivos', 'creador', 'editor'])->orderByDesc('fecha_entrega')->get();

        return Inertia::render('Apoyos', [
            'filas' => $apoyos->map(fn (Apoyo $a) => [
                'id' => $a->id,
                'agremiado' => $this->persona($a->agremiado),
                'agremiado_id' => $a->agremiado_id,
                'beneficiario' => $a->agremiado?->nombre_completo,
                'tipo' => $a->tipo,
                'detalle' => $a->detalle,
                'monto' => $a->monto,
                'fecha_entrega' => $a->fecha_entrega->format('Y-m-d H:i'),
                'entrego' => $a->creador?->name,
                'archivos' => $this->archivosDe($a),
                'traza' => $this->traza($a),
            ]),
            'resumen' => [
                'total' => (float) $apoyos->sum('monto'),
                'mes' => (float) $apoyos->filter(fn ($a) => $a->fecha_entrega->isCurrentMonth())->sum('monto'),
                'beneficiarios' => $apoyos->pluck('agremiado_id')->unique()->count(),
                'porTipo' => $apoyos->groupBy('tipo')->map(fn ($g) => ['n' => $g->count(), 'monto' => (float) $g->sum('monto')]),
            ],
            'agremiados' => Agremiado::activos()->orderBy('nombre')->get()->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre_completo, 'folio' => $a->folio]),
            'tipos' => Catalogo::de('tipo_apoyo'),
            'puede' => ['editar' => $request->user()->puede('apoyos', 'e'), 'eliminar' => $request->user()->puede('apoyos', 't')],
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'agremiado_id' => ['required', 'exists:agremiados,id'],
            'tipo' => ['required', 'string', 'max:80'],
            'detalle' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'fecha_entrega' => ['required', 'date', 'before_or_equal:now'],
            'evidencias' => ['array', 'max:10'],
            'evidencias.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,pdf'],
            'quitar' => ['array'],
        ], ['fecha_entrega.before_or_equal' => 'La fecha de entrega no puede ser futura.'], ['agremiado_id' => 'beneficiario', 'monto' => 'valor', 'detalle' => 'descripción', 'fecha_entrega' => 'fecha de entrega']);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request);
        abort_if(! $request->hasFile('evidencias'), 422, 'Adjunta al menos una foto, video o documento como evidencia de la entrega.');
        DB::transaction(function () use ($d, $request) {
            $a = Apoyo::create(collect($d)->only(['agremiado_id', 'tipo', 'detalle', 'monto', 'fecha_entrega'])->all());
            foreach ($request->file('evidencias', []) as $f) {
                $this->guardarArchivo($a, $f, 'apoyos');
            }
            Catalogo::asegurar('tipo_apoyo', $d['tipo']);
        });

        return back()->with('success', 'Entrega registrada con usuario, fecha y hora');
    }

    public function update(Request $request, Apoyo $apoyo): RedirectResponse
    {
        $d = $this->validar($request);
        DB::transaction(function () use ($apoyo, $d, $request) {
            $apoyo->update(collect($d)->only(['agremiado_id', 'tipo', 'detalle', 'monto', 'fecha_entrega'])->all());
            Archivo::whereIn('id', $d['quitar'] ?? [])->where('archivable_type', Apoyo::class)->where('archivable_id', $apoyo->id)->get()->each->delete();
            foreach ($request->file('evidencias', []) as $f) {
                $this->guardarArchivo($apoyo, $f, 'apoyos');
            }
            abort_if($apoyo->archivos()->count() === 0, 422, 'La entrega debe conservar al menos una evidencia.');
            Catalogo::asegurar('tipo_apoyo', $d['tipo']);
        });

        return back()->with('success', 'Entrega actualizada');
    }

    public function destroy(Apoyo $apoyo): RedirectResponse
    {
        $apoyo->delete();

        return back()->with('success', 'Entrega eliminada (se conserva en la bitácora)');
    }
}
