<?php

namespace App\Http\Controllers;

use App\Models\ActividadParticipante;
use App\Models\Agremiado;
use App\Models\CursoInscripcion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\SysLog;
use App\Support\Cuotas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** App del agremiado: credencial, cuotas, apoyos, tienda, cursos y autorización de GPS. */
class PortalController extends Controller
{
    /** El agremiado de la sesión; el personal con acceso al padrón puede ver la app de cualquiera (solo lectura). */
    private function agremiado(Request $request): array
    {
        $u = $request->user();
        if ($u->esPortal()) {
            return [$u->agremiado, false];
        }
        abort_unless($u->puede('agremiados'), 403, 'Tu perfil no puede ver la app del agremiado.');
        $id = $request->integer('agremiado') ?: Agremiado::activos()->whereDoesntHave('coordinados')->value('id');

        return [Agremiado::findOrFail($id), true];
    }

    public function index(Request $request): Response
    {
        [$a, $vista] = $this->agremiado($request);
        $a->load(['coordinador', 'cuotas.creador', 'apoyos.creador', 'incidencias.creador', 'gpsPor']);
        $pagos = $a->cuotas->keyBy(fn ($c) => sprintf('%04d-%02d', $c->anio, $c->mes));
        $estado = Cuotas::mesesExigibles($a)->reverse()->take(12)->values()->map(fn ($m) => [
            'clave' => $m, 'label' => Cuotas::etiqueta($m), 'pagado' => $pagos->has($m),
            'traza' => $pagos->has($m) ? $this->traza($pagos[$m])['creado'] : null,
        ]);

        return Inertia::render('Portal', [
            'vistaPrevia' => $vista,
            'opcionesVista' => $vista ? Agremiado::activos()->orderBy('nombre')->get()->map(fn ($x) => ['id' => $x->id, 'nombre' => $x->nombre_completo.' · '.$x->folio]) : [],
            'yo' => [
                ...$this->persona($a), 'seccion' => $a->seccion, 'municipio' => $a->municipio, 'estado' => $a->estado, 'ingreso' => $a->fecha_ingreso->format('Y-m-d'),
                'celular' => $a->celular, 'emergencia' => $a->emergencia_nombre.' · '.$a->emergencia_telefono, 'domicilio' => "{$a->calle}, {$a->colonia}, C.P. {$a->cp}",
                'clave_elector' => $a->clave_elector, 'curp' => $a->curp, 'gps' => $a->gps_autorizado,
                'gps_traza' => $a->gps_autorizado ? ['usuario' => $a->gpsPor?->name ?? $a->nombre_completo, 'fecha' => $a->gps_autorizado_at?->format('Y-m-d H:i')] : null,
                'coordinador' => $a->coordinador ? [...$this->persona($a->coordinador), 'celular' => $a->coordinador->celular] : null,
            ],
            'cuota' => Cuotas::monto(),
            'adeudo' => Cuotas::pendientes($a)->count(),
            'estadoCuenta' => $estado,
            'apoyos' => $a->apoyos->sortByDesc('fecha_entrega')->values()->map(fn ($p) => ['tipo' => $p->tipo, 'detalle' => $p->detalle, 'monto' => $p->monto, 'traza' => $this->traza($p)['creado']]),
            'incidencias' => $a->incidencias->sortByDesc('fecha')->values()->map(fn ($i) => ['tipo' => $i->tipo, 'descripcion' => $i->descripcion, 'traza' => $this->traza($i)['creado']]),
            'productos' => Producto::where('publicado', true)->with('archivos')->orderBy('nombre')->get()->map(fn ($p) => [
                ...$p->only(['id', 'nombre', 'categoria', 'icono', 'color', 'descripcion', 'precio', 'stock']),
                'foto' => $p->archivos->firstWhere('tipo', 'imagen')?->url,
            ]),
            'pedidos' => Pedido::with('detalles.producto')->where('agremiado_id', $a->id)->orderByDesc('id')->take(20)->get()->map(fn ($p) => [
                'folio' => $p->folio, 'total' => $p->total, 'estado' => $p->estado, 'fecha' => $p->created_at->format('Y-m-d H:i'),
                'resumen' => $p->detalles->map(fn ($d) => $d->producto?->nombre.' × '.$d->cantidad)->join(', '),
            ]),
            'formasPago' => ['Descuento vía nómina', 'Pago en tesorería'],
            'cursos' => CursoInscripcion::with(['curso', 'creador'])->where('agremiado_id', $a->id)->whereHas('curso')->get()->map(fn ($i) => [
                'id' => $i->id, 'nombre' => $i->curso->nombre, 'horas' => $i->curso->horas, 'enlace' => $i->curso->enlace, 'modalidad' => $i->curso->modalidad,
                'avance' => $i->avance, 'fecha_limite' => $i->curso->fecha_limite?->format('Y-m-d'), 'asigno' => $this->traza($i)['creado'],
            ]),
            'actividad' => ActividadParticipante::with('actividad')->where('agremiado_id', $a->id)->whereHas('actividad', fn ($q) => $q->where('estado', 'Activa'))->first()?->actividad?->nombre,
        ]);
    }

    public function pedido(Request $request): RedirectResponse
    {
        [$a, $vista] = $this->agremiado($request);
        abort_if($vista, 422, 'Vista previa: el pedido solo lo puede hacer el agremiado desde su cuenta.');
        $d = $request->validate([
            'forma_pago' => ['required', Rule::in(['Descuento vía nómina', 'Pago en tesorería'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $p = TiendaController::crearPedido($a->id, $d['items'], $d['forma_pago'], 'App');

        return back()->with('success', "Pedido {$p->folio} confirmado. Te avisaremos cuando esté listo.");
    }

    public function gps(Request $request): RedirectResponse
    {
        [$a, $vista] = $this->agremiado($request);
        abort_if($vista, 422, 'Vista previa: solo el agremiado puede cambiar su autorización.');
        $on = $request->boolean('autorizado');
        $a->update(['gps_autorizado' => $on, 'gps_autorizado_at' => $on ? now() : null, 'gps_autorizado_por' => $on ? $request->user()->id : null]);
        if (! $on) {
            ActividadParticipante::where('agremiado_id', $a->id)->whereHas('actividad', fn ($q) => $q->where('estado', 'Activa'))->delete();
        }
        SysLog::registrar('ubicacion', 'agremiados', $a->id, $a->auditLabel(), $on ? 'Autorizó seguimiento GPS' : 'Revocó seguimiento GPS', 'Desde la app');

        return back()->with('success', $on ? 'Seguimiento GPS autorizado' : 'Seguimiento GPS desactivado');
    }

    /** La app envía su ubicación mientras participa en una actividad con seguimiento. */
    public function ubicacion(Request $request): JsonResponse
    {
        [$a, $vista] = $this->agremiado($request);
        abort_if($vista || ! $a->gps_autorizado, 422);
        $d = $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lng' => ['required', 'numeric', 'between:-180,180'], 'precision' => ['nullable', 'numeric', 'min:0']]);
        $n = ActividadParticipante::where('agremiado_id', $a->id)->whereHas('actividad', fn ($q) => $q->where('estado', 'Activa'))
            ->update(['latitud' => $d['lat'], 'longitud' => $d['lng'], 'precision' => isset($d['precision']) ? (int) $d['precision'] : null, 'ultima_senal' => now()]);

        return response()->json(['activa' => $n > 0]);
    }
}
