<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso en rutas: ->middleware('permiso:agremiados,e'). Niveles: v (ver), e (editar), t (total). */
class Permiso
{
    public function handle(Request $request, Closure $next, string $modulo, string $nivel = 'v'): Response
    {
        $u = $request->user();
        if (! $u || ! $u->puede($modulo, $nivel)) {
            if ($u?->esPortal() && $request->isMethod('GET')) {
                return redirect('/app');
            }
            abort(403, $nivel === 'v' ? 'Tu perfil no tiene acceso a este módulo.' : 'Tu perfil no tiene permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
