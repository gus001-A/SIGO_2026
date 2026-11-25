<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Cierra la sesión si la cuenta fue desactivada o dada de baja mientras estaba conectada. */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();
        if ($u && (! $u->activo || $u->trashed())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect('/login')->with('error', 'Tu cuenta fue desactivada.');
        }

        return $next($request);
    }
}
