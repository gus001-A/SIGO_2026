<?php

namespace App\Http\Controllers;

use App\Models\SysLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            // Accesos de demostración solo en entorno local (un usuario por perfil del seeder)
            'demo' => app()->isLocal()
                ? User::activos()->with('rol')->orderBy('id')->get()->unique('rol_id')->values()->map(fn ($u) => ['email' => $u->email, 'nombre' => $u->name, 'rol' => $u->rol?->nombre, 'color' => $u->rol?->color])
                : [],
            'demoPassword' => app()->isLocal() ? DatabaseSeeder::PASSWORD_DEMO : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'correo', 'password' => 'contraseña']);

        $llave = 'login:'.mb_strtolower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($llave, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Espera '.RateLimiter::availableIn($llave).' segundos.']);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Auth::attempt([...$data, 'activo' => true], $request->boolean('remember'))) {
            RateLimiter::hit($llave, 60);
            if ($user) {
                SysLog::create(['modulo' => 'perfiles', 'tabla_afectada' => 'users', 'registro_id' => $user->id, 'registro' => $user->email, 'accion' => 'Acceso fallido',
                    'usuario_id' => $user->id, 'fecha' => today(), 'hora' => now()->format('H:i:s'), 'ip' => $request->ip(), 'created_at' => now()]);
            }
            throw ValidationException::withMessages(['email' => $user && ! $user->activo ? 'Tu cuenta está inactiva.' : 'Correo o contraseña incorrectos.']);
        }

        RateLimiter::clear($llave);
        $request->session()->regenerate();
        $user->forceFill(['ultimo_acceso' => now()])->saveQuietly();
        SysLog::registrar('perfiles', 'users', $user->id, $user->email, 'Inicio de sesión', $user->rol?->nombre);

        return redirect()->intended($user->esPortal() ? '/app' : '/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        SysLog::registrar('perfiles', 'users', $request->user()->id, $request->user()->email, 'Cierre de sesión');
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
