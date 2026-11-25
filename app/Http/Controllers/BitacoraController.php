<?php

namespace App\Http\Controllers;

use App\Models\SysLog;
use App\Support\Permisos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Bitácora inalterable. Se pagina y filtra en el servidor porque crece sin límite. */
class BitacoraController extends Controller
{
    public const POR_PAGINA = 10;

    public function index(Request $request): Response
    {
        $f = $request->only(['fecha', 'hora', 'usuario', 'modulo', 'accion', 'detalle']);
        $q = SysLog::with('usuario.rol')->orderByDesc('id');
        if ($v = $f['fecha'] ?? null) {
            $q->where('fecha', $v);
        }
        if ($v = $f['hora'] ?? null) {
            $q->where('hora', 'like', $v.'%');
        }
        if ($v = $f['usuario'] ?? null) {
            $q->whereHas('usuario', fn ($u) => $u->where('name', 'like', "%{$v}%"));
        }
        if ($v = $f['modulo'] ?? null) {
            $q->where('modulo', $v);
        }
        if ($v = $f['accion'] ?? null) {
            $q->where('accion', 'like', "%{$v}%");
        }
        if ($v = $f['detalle'] ?? null) {
            $q->where(fn ($w) => $w->where('registro', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%"));
        }
        $pag = $q->paginate(self::POR_PAGINA)->withQueryString();

        return Inertia::render('Bitacora', [
            'filas' => collect($pag->items())->map(fn (SysLog $l) => $this->fila($l)),
            'total' => $pag->total(),
            'pagina' => $pag->currentPage(),
            'filtros' => (object) $f,
            'modulos' => collect(Permisos::MODULOS)->map(fn ($m) => ['value' => $m['id'], 'label' => $m['nombre']]),
            'acciones' => SysLog::query()->distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }

    private function fila(SysLog $l): array
    {
        return [
            'id' => $l->id, 'fecha' => $l->fecha->format('Y-m-d'), 'hora' => substr((string) $l->hora, 0, 5),
            'usuario' => $l->usuario?->name ?? 'Sistema', 'rol' => $l->usuario?->rol?->nombre, 'modulo' => $l->modulo,
            'accion' => $l->accion, 'registro' => $l->registro, 'descripcion' => $l->descripcion, 'cambios' => $l->cambios_json, 'ip' => $l->ip,
        ];
    }

    /** Historial de un registro (para el modal de trazabilidad del detalle). */
    public function registro(Request $request): JsonResponse
    {
        abort_if($request->user()->esPortal(), 403);
        $d = $request->validate(['tabla' => ['required', 'string', 'max:60'], 'id' => ['required', 'integer']]);

        return response()->json(SysLog::with('usuario.rol')->where('tabla_afectada', $d['tabla'])->where('registro_id', $d['id'])->orderByDesc('id')->take(200)->get()->map(fn ($l) => $this->fila($l)));
    }
}
