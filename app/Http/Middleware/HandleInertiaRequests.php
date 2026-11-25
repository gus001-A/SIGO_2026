<?php

namespace App\Http\Middleware;

use App\Models\Agremiado;
use App\Models\Producto;
use App\Support\Cuotas;
use App\Support\Permisos;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $permisos = $user?->rol?->mapa() ?? [];

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'ahora' => now()->format('Y-m-d H:i'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->rol?->nombre,
                    'rol_color' => $user->rol?->color,
                    'super' => (bool) $user->rol?->protegido,
                    'portal' => $user->esPortal(),
                    'agremiado_id' => $user->agremiado_id,
                    'foto' => $user->agremiado?->foto ? '/storage/'.$user->agremiado->foto : null,
                ] : null,
                'permisos' => $permisos,
            ],
            'menu' => $user ? collect(Permisos::MODULOS)->filter(fn ($m) => ($permisos[$m['id']] ?? 'n') !== 'n')->values() : [],
            // Insignias del menú: agremiados con adeudo y productos bajo el mínimo
            'insignias' => fn () => $user && ! $user->esPortal() ? [
                'cuotas' => $user->puede('cuotas') ? Agremiado::activos()->with('cuotas')->get()->filter(fn ($a) => Cuotas::pendientes($a)->isNotEmpty())->count() : 0,
                'tienda' => $user->puede('tienda') ? Producto::where('categoria', 'Producto')->whereColumn('stock', '<', 'minimo')->count() : 0,
            ] : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
