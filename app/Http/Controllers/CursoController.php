<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Curso;
use App\Models\CursoInscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CursoController extends Controller
{
    public function index(Request $request): Response
    {
        $cursos = Curso::with(['inscripciones', 'coordinador', 'creador', 'editor'])->orderByDesc('id')->get();

        return Inertia::render('Capacitacion', [
            'filas' => $cursos->map(fn (Curso $c) => [
                ...$c->only(['id', 'nombre', 'descripcion', 'horas', 'modalidad', 'enlace', 'dirigido_coordinador_id']),
                'fecha_limite' => $c->fecha_limite?->format('Y-m-d'),
                'dirigido' => $c->coordinador ? 'Red de '.$c->coordinador->nombre_completo : 'Todos los agremiados',
                'inscritos' => $c->inscripciones->count(),
                'concluidos' => $c->inscripciones->whereNotNull('concluido_at')->count(),
                'avance' => (int) round($c->inscripciones->avg('avance') ?? 0),
                'asigno' => $this->traza($c)['creado'],
                'traza' => $this->traza($c),
            ]),
            'coordinadores' => Agremiado::activos()->whereHas('coordinados')->orderBy('nombre')->get()->map(fn ($a) => ['id' => $a->id, 'nombre' => 'Red de '.$a->nombre_completo]),
            'puede' => ['editar' => $request->user()->puede('capacitacion', 'e'), 'eliminar' => $request->user()->puede('capacitacion', 't')],
        ]);
    }

    public function show(Curso $curso): JsonResponse
    {
        return response()->json($curso->inscripciones()->with(['agremiado', 'editor'])->get()->map(fn ($i) => [
            'id' => $i->id, 'agremiado' => $this->persona($i->agremiado), 'nombre' => $i->agremiado?->nombre_completo, 'avance' => $i->avance,
            'concluido' => $i->concluido_at?->format('Y-m-d H:i'), 'traza' => $this->traza($i),
        ])->sortBy('nombre')->values());
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'horas' => ['required', 'integer', 'min:1', 'max:200'],
            'modalidad' => ['required', Rule::in(['En línea', 'Presencial', 'Mixta'])],
            'enlace' => ['nullable', 'url', 'max:255'],
            'dirigido_coordinador_id' => ['nullable', 'exists:agremiados,id'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['fecha_limite.after_or_equal' => 'La fecha límite no puede ser pasada.'], ['enlace' => 'enlace en SAIS', 'dirigido_coordinador_id' => 'dirigido a', 'fecha_limite' => 'fecha límite']);
    }

    /** Inscribe a todos los activos o a la red del coordinador elegido (los que ya estaban se conservan). */
    private function inscribir(Curso $c): int
    {
        $q = Agremiado::activos();
        if ($c->dirigido_coordinador_id) {
            $q->whereIn('id', [$c->dirigido_coordinador_id, ...Agremiado::descendientes($c->dirigido_coordinador_id)]);
        }
        $ya = $c->inscripciones()->pluck('agremiado_id')->flip();
        $n = 0;
        foreach ($q->pluck('id') as $id) {
            if (! $ya->has($id)) {
                CursoInscripcion::create(['curso_id' => $c->id, 'agremiado_id' => $id]);
                $n++;
            }
        }

        return $n;
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validar($request);
        abort_if(Curso::where('nombre', $d['nombre'])->exists(), 422, 'Ya existe un curso con ese nombre.');
        $n = DB::transaction(fn () => $this->inscribir(Curso::create($d)));

        return back()->with('success', "Curso asignado a {$n} agremiados");
    }

    public function update(Request $request, Curso $curso): RedirectResponse
    {
        $d = $this->validar($request);
        $n = DB::transaction(function () use ($curso, $d) {
            $curso->update($d);

            return $this->inscribir($curso);
        });

        return back()->with('success', 'Curso actualizado'.($n ? " · {$n} inscritos nuevos" : ''));
    }

    public function avance(Request $request, CursoInscripcion $inscripcion): RedirectResponse|JsonResponse
    {
        $d = $request->validate(['avance' => ['required', 'integer', 'min:0', 'max:100']]);
        $inscripcion->update(['avance' => $d['avance'], 'concluido_at' => $d['avance'] >= 100 ? ($inscripcion->concluido_at ?? now()) : null]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        DB::transaction(function () use ($curso) {
            $curso->inscripciones()->get()->each->delete();
            $curso->delete();
        });

        return back()->with('success', 'Curso eliminado');
    }
}
