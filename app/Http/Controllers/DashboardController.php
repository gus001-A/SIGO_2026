<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Apoyo;
use App\Models\Cuota;
use App\Models\Pedido;
use App\Models\Producto;
use App\Support\Cuotas;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $u = $request->user();
        $activos = Agremiado::activos()->with('cuotas')->get();
        $monto = Cuotas::monto();
        $periodos = Cuotas::periodos(6);
        $mesActual = end($periodos);

        $adeudos = $activos->map(fn ($a) => ['a' => $a, 'p' => Cuotas::pendientes($a)])->filter(fn ($x) => $x['p']->isNotEmpty())
            ->sortByDesc(fn ($x) => $x['p']->count())->values();
        $bajos = Producto::where('categoria', 'Producto')->whereColumn('stock', '<', 'minimo')->orderBy('stock')->get();
        $sinDocs = $activos->filter(fn ($a) => ! $a->foto || ! $a->ine_frente || ! $a->ine_reverso)->values();

        $recaudadoMes = Cuota::where('anio', $mesActual['anio'])->where('mes', $mesActual['mes'])->sum('monto');
        $inicioMes = now()->startOfMonth();
        $apoyosPeriodo = Apoyo::where('fecha_entrega', '>=', CarbonImmutable::today()->startOfMonth()->subMonths(5))->get();

        // Gráficas
        $cuotasMes = collect($periodos)->map(fn ($p) => [
            'label' => $p['label'],
            'recaudado' => (float) Cuota::where('anio', $p['anio'])->where('mes', $p['mes'])->sum('monto'),
        ]);
        $municipios = $activos->groupBy('municipio')->map->count()->sortDesc();
        $tipos = $apoyosPeriodo->pluck('tipo')->unique()->values();
        $apoyosMes = [
            'labels' => collect($periodos)->pluck('label'),
            'series' => $tipos->map(fn ($t) => ['tipo' => $t, 'datos' => collect($periodos)->map(fn ($p) => (float) $apoyosPeriodo->filter(fn ($a) => $a->tipo === $t && $a->fecha_entrega->format('Y-m') === $p['clave'])->sum('monto'))]),
        ];
        $ventas = Producto::orderByDesc('vendidos')->take(6)->get(['nombre', 'vendidos', 'precio', 'color']);

        return Inertia::render('Dashboard', [
            'puede' => ['cuotas' => $u->puede('cuotas', 'e'), 'tienda' => $u->puede('tienda', 'e'), 'agremiados' => $u->puede('agremiados', 'e')],
            'kpis' => [
                'activos' => $activos->count(),
                'altasMes' => $activos->filter(fn ($a) => $a->created_at && $a->created_at->gte($inicioMes))->count(),
                'recaudadoMes' => (float) $recaudadoMes,
                'mesActual' => Cuotas::etiqueta($mesActual['clave']),
                'conAdeudo' => $adeudos->count(),
                'apoyos' => $apoyosPeriodo->count(),
                'apoyosMonto' => (float) $apoyosPeriodo->sum('monto'),
                'bajos' => $bajos->count(),
                'bajosNombres' => $bajos->pluck('nombre')->join(', '),
                'pedidosPendientes' => Pedido::where('estado', 'Pendiente')->count(),
            ],
            'alertas' => [
                'adeudos' => $adeudos->take(3)->map(fn ($x) => [...$this->persona($x['a']), 'meses' => $x['p']->count(), 'monto' => $x['p']->count() * $monto])->values(),
                'adeudosTotal' => $adeudos->count(),
                'porCobrar' => $adeudos->sum(fn ($x) => $x['p']->count()) * $monto,
                'bajos' => $bajos->take(3)->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'stock' => $p->stock, 'minimo' => $p->minimo, 'maximo' => $p->maximo, 'icono' => $p->icono, 'color' => $p->color])->values(),
                'bajosTotal' => $bajos->count(),
                'sinDocs' => $sinDocs->take(3)->map(fn ($a) => [...$this->persona($a), 'falta' => collect(['foto' => ! $a->foto, 'INE frente' => ! $a->ine_frente, 'INE reverso' => ! $a->ine_reverso])->filter()->keys()->join(', ')])->values(),
                'sinDocsTotal' => $sinDocs->count(),
            ],
            'graficas' => [
                'cuotas' => ['labels' => $cuotasMes->pluck('label'), 'recaudado' => $cuotasMes->pluck('recaudado'), 'esperado' => $activos->count() * $monto],
                'municipios' => ['labels' => $municipios->keys(), 'datos' => $municipios->values()],
                'apoyos' => $apoyosMes,
                'ventas' => $ventas,
            ],
        ]);
    }
}
