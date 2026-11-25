<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Consulta de un código postal en el catálogo local de SEPOMEX: estado, municipio y colonias. */
class CodigoPostalController extends Controller
{
    public function show(string $cp): JsonResponse
    {
        abort_unless(preg_match('/^\d{5}$/', $cp), 422, 'El código postal debe tener 5 dígitos.');
        $filas = DB::table('codigos_postales')->where('cp', $cp)->orderBy('asentamiento')->get();

        if ($filas->isEmpty()) {
            return response()->json(['encontrado' => false, 'catalogo' => DB::table('codigos_postales')->exists()]);
        }

        return response()->json([
            'encontrado' => true,
            'cp' => $cp,
            'estado' => $filas->first()->estado,
            'municipio' => $filas->first()->municipio,
            'colonias' => $filas->pluck('asentamiento')->unique()->values(),
        ]);
    }
}
