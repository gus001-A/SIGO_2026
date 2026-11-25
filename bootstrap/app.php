<?php

use App\Http\Middleware\CuentaActiva;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\Permiso;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Varios sistemas Laravel en el mismo dominio: el .env de cada uno no se publica en el entorno del proceso.
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [CuentaActiva::class, HandleInertiaRequests::class]);
        $middleware->alias(['permiso' => Permiso::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // Reglas de negocio (403/422) en peticiones Inertia: regresa a la página con mensaje
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->header('X-Inertia') && in_array($e->getStatusCode(), [403, 422], true) && ! $request->isMethod('GET')) {
                return back()->with('error', $e->getMessage() ?: 'Acción no permitida.');
            }
        });
    })->create();
