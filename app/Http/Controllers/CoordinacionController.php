<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoordinacionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $todos = Agremiado::activos()->orderBy('nombre')->get();
        $mapa = $todos->pluck('coordinador_id', 'id')->all();
        $porPadre = $todos->groupBy(fn ($a) => (int) $a->coordinador_id);

        $nodo = function (Agremiado $a) use (&$nodo, $porPadre, $mapa) {
            $hijos = $porPadre->get($a->id, collect());

            return [
                'key' => (string) $a->id,
                'data' => [...$this->persona($a), 'red' => count(Agremiado::descendientes($a->id, $mapa)), 'directos' => $hijos->count()],
                'children' => $hijos->map($nodo)->values()->all(),
            ];
        };

        $cabezas = $todos->filter(fn ($a) => ! $a->coordinador_id || ! array_key_exists($a->coordinador_id, $mapa));

        return Inertia::render('Coordinacion', [
            'arbol' => $cabezas->map($nodo)->values(),
            'filas' => $todos->filter(fn ($a) => $porPadre->has($a->id))->map(fn ($a) => [
                ...$this->persona($a),
                'directos' => $porPadre->get($a->id)->count(),
                'red' => count(Agremiado::descendientes($a->id, $mapa)),
                'coordinador' => $a->coordinador?->nombre_completo,
            ])->sortByDesc('red')->values(),
            'opciones' => $todos->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre_completo, 'puesto' => $a->puesto]),
            'puede' => ['editar' => $request->user()->puede('red', 'e')],
        ]);
    }
}
