<?php

use App\Http\Controllers\AgremiadoController;
use App\Http\Controllers\ApoyoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\CoordinacionController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpsController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\TiendaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy']);
    Route::get('/cp/{cp}', [CodigoPostalController::class, 'show']);
    Route::get('/bitacora/registro', [BitacoraController::class, 'registro']);

    Route::get('/', DashboardController::class)->middleware('permiso:inicio,v');

    // Agremiados
    Route::get('/agremiados', [AgremiadoController::class, 'index'])->middleware('permiso:agremiados,v');
    Route::get('/agremiados/{agremiado}', [AgremiadoController::class, 'show'])->middleware('permiso:agremiados,v');
    Route::middleware('permiso:agremiados,e')->group(function () {
        Route::post('/agremiados', [AgremiadoController::class, 'store']);
        Route::post('/agremiados/{agremiado}', [AgremiadoController::class, 'update']);
        Route::post('/agremiados/{agremiado}/baja', [AgremiadoController::class, 'baja']);
        Route::post('/agremiados/{agremiado}/reactivar', [AgremiadoController::class, 'reactivar']);
        Route::post('/agremiados/{agremiado}/incidencias', [AgremiadoController::class, 'incidencia']);
    });
    Route::delete('/agremiados/{agremiado}', [AgremiadoController::class, 'destroy'])->middleware('permiso:agremiados,t');
    Route::delete('/incidencias/{incidencia}', [AgremiadoController::class, 'destroyIncidencia'])->middleware('permiso:agremiados,t');

    // Coordinación
    Route::get('/coordinacion', CoordinacionController::class)->middleware('permiso:red,v');
    Route::put('/agremiados/{agremiado}/coordinador', [AgremiadoController::class, 'coordinador'])->middleware('permiso:red,e');

    // Cuotas
    Route::get('/cuotas', [CuotaController::class, 'index'])->middleware('permiso:cuotas,v');
    Route::post('/cuotas', [CuotaController::class, 'store'])->middleware('permiso:cuotas,e');
    Route::put('/cuotas/ajustes', [CuotaController::class, 'ajustes'])->middleware('permiso:cuotas,t');
    Route::delete('/cuotas/{cuota}', [CuotaController::class, 'destroy'])->middleware('permiso:cuotas,t');

    // Apoyos
    Route::get('/apoyos', [ApoyoController::class, 'index'])->middleware('permiso:apoyos,v');
    Route::post('/apoyos', [ApoyoController::class, 'store'])->middleware('permiso:apoyos,e');
    Route::post('/apoyos/{apoyo}', [ApoyoController::class, 'update'])->middleware('permiso:apoyos,e');
    Route::delete('/apoyos/{apoyo}', [ApoyoController::class, 'destroy'])->middleware('permiso:apoyos,t');

    // Tienda
    Route::get('/tienda', [TiendaController::class, 'index'])->middleware('permiso:tienda,v');
    Route::middleware('permiso:tienda,e')->group(function () {
        Route::post('/tienda/productos', [TiendaController::class, 'storeProducto']);
        Route::post('/tienda/productos/{producto}', [TiendaController::class, 'updateProducto']);
        Route::post('/tienda/productos/{producto}/entrada', [TiendaController::class, 'entrada']);
        Route::post('/tienda/proveedores', [TiendaController::class, 'storeProveedor']);
        Route::put('/tienda/proveedores/{proveedor}', [TiendaController::class, 'updateProveedor']);
        Route::post('/tienda/pedidos', [TiendaController::class, 'storePedido']);
        Route::put('/tienda/pedidos/{pedido}/estado', [TiendaController::class, 'estadoPedido']);
    });
    Route::middleware('permiso:tienda,t')->group(function () {
        Route::delete('/tienda/productos/{producto}', [TiendaController::class, 'destroyProducto']);
        Route::delete('/tienda/proveedores/{proveedor}', [TiendaController::class, 'destroyProveedor']);
    });

    // Capacitación
    Route::get('/capacitacion', [CursoController::class, 'index'])->middleware('permiso:capacitacion,v');
    Route::get('/capacitacion/{curso}', [CursoController::class, 'show'])->middleware('permiso:capacitacion,v');
    Route::middleware('permiso:capacitacion,e')->group(function () {
        Route::post('/capacitacion', [CursoController::class, 'store']);
        Route::put('/capacitacion/{curso}', [CursoController::class, 'update']);
        Route::put('/inscripciones/{inscripcion}', [CursoController::class, 'avance']);
    });
    Route::delete('/capacitacion/{curso}', [CursoController::class, 'destroy'])->middleware('permiso:capacitacion,t');

    // GPS
    Route::get('/gps', [GpsController::class, 'index'])->middleware('permiso:ubicacion,v');
    Route::get('/gps/posiciones', [GpsController::class, 'posiciones'])->middleware('permiso:ubicacion,v');
    Route::middleware('permiso:ubicacion,e')->group(function () {
        Route::post('/gps/actividades', [GpsController::class, 'store']);
        Route::post('/gps/actividades/{actividad}/finalizar', [GpsController::class, 'finalizar']);
        Route::delete('/gps/participantes/{participante}', [GpsController::class, 'quitar']);
    });

    // Bitácora
    Route::get('/bitacora', [BitacoraController::class, 'index'])->middleware('permiso:bitacora,v');

    // Perfiles y usuarios
    Route::get('/perfiles', [PerfilController::class, 'index'])->middleware('permiso:perfiles,v');
    Route::middleware('permiso:perfiles,t')->group(function () {
        Route::post('/perfiles', [PerfilController::class, 'store']);
        Route::put('/perfiles/{rol}', [PerfilController::class, 'update']);
        Route::put('/perfiles/{rol}/permiso', [PerfilController::class, 'permiso']);
        Route::delete('/perfiles/{rol}', [PerfilController::class, 'destroy']);
        Route::post('/usuarios', [PerfilController::class, 'storeUsuario']);
        Route::put('/usuarios/{usuario}', [PerfilController::class, 'updateUsuario']);
        Route::delete('/usuarios/{usuario}', [PerfilController::class, 'destroyUsuario']);
    });

    // App del agremiado
    Route::get('/app', [PortalController::class, 'index']);
    Route::post('/app/pedido', [PortalController::class, 'pedido']);
    Route::post('/app/gps', [PortalController::class, 'gps']);
    Route::post('/app/ubicacion', [PortalController::class, 'ubicacion']);
});
