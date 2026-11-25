<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Ajuste;
use App\Models\Catalogo;
use App\Models\Cuota;
use App\Support\Cuotas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CuotaController extends Controller
{
    public function index(Request $request): Response
    {
        $periodos = Cuotas::periodos(6);
        $agremiados = Agremiado::activos()->with(['cuotas.creador'])->orderBy('folio')->get();

        return Inertia::render('Cuotas', [
            'periodos' => $periodos,
            'monto' => Cuotas::monto(),
            'desde' => Cuotas::inicio()->format('Y-m'),
            'filas' => $agremiados->map(function (Agremiado $a) use ($periodos) {
                $pend = Cuotas::pendientes($a);
                $exig = Cuotas::mesesExigibles($a)->flip();
                $porMes = $a->cuotas->keyBy(fn ($c) => sprintf('%04d-%02d', $c->anio, $c->mes));
                $ult = $a->cuotas->sortByDesc('created_at')->first();

                return [
                    ...$this->persona($a),
                    'meses' => collect($periodos)->mapWithKeys(fn ($p) => [$p['clave'] => $porMes->has($p['clave'])
                        ? ['estado' => 'Pagado', 'id' => $porMes[$p['clave']]->id, 'por' => $porMes[$p['clave']]->creador?->name, 'fecha' => $porMes[$p['clave']]->created_at->format('Y-m-d H:i')]
                        : ['estado' => $exig->has($p['clave']) ? 'Pendiente' : 'No aplica']]),
                    'adeudo' => $pend->count(),
                    'pendientes' => $pend->map(fn ($m) => ['clave' => $m, 'label' => Cuotas::etiqueta($m)]),
                    'ultimo' => $ult ? $this->traza($ult)['creado'] : null,
                ];
            }),
            'formasPago' => Catalogo::de('forma_pago'),
            'puede' => ['editar' => $request->user()->puede('cuotas', 'e'), 'total' => $request->user()->puede('cuotas', 't')],
        ]);
    }

    /** Registra uno o varios meses pagados. */
    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'agremiado_id' => ['required', 'exists:agremiados,id'],
            'meses' => ['required', 'array', 'min:1'],
            'meses.*' => ['regex:/^\d{4}-\d{2}$/'],
            'forma_pago' => ['required', 'string', 'max:40'],
            'referencia' => ['nullable', 'string', 'max:80'],
        ], ['meses.required' => 'Selecciona al menos un mes.'], ['forma_pago' => 'forma de pago']);

        $a = Agremiado::with('cuotas')->findOrFail($d['agremiado_id']);
        abort_if($a->estatus === 'Baja', 422, 'El agremiado está dado de baja.');
        $pend = Cuotas::pendientes($a)->flip();
        foreach ($d['meses'] as $m) {
            abort_unless($pend->has($m), 422, 'El mes '.Cuotas::etiqueta($m).' ya está pagado o no aplica.');
        }
        DB::transaction(function () use ($a, $d) {
            foreach ($d['meses'] as $m) {
                [$y, $mm] = explode('-', $m);
                Cuota::create(['agremiado_id' => $a->id, 'anio' => (int) $y, 'mes' => (int) $mm, 'monto' => Cuotas::monto(), 'forma_pago' => $d['forma_pago'], 'referencia' => $d['referencia'] ?? null]);
            }
            Catalogo::asegurar('forma_pago', $d['forma_pago']);
        });
        $n = count($d['meses']);

        return back()->with('success', "Pago registrado: {$n} ".($n === 1 ? 'mes' : 'meses')." de {$a->nombre_completo}");
    }

    public function destroy(Cuota $cuota): RedirectResponse
    {
        $cuota->delete();

        return back()->with('success', 'Pago anulado (queda en la bitácora)');
    }

    public function ajustes(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'monto' => ['required', 'numeric', 'min:1', 'max:100000'],
            'desde' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], [], ['monto' => 'cuota mensual', 'desde' => 'cobrar desde']);
        Ajuste::poner('cuota_mensual', $d['monto']);
        Ajuste::poner('cuotas_desde', $d['desde']);

        return back()->with('success', 'Ajustes de cuotas guardados');
    }
}
