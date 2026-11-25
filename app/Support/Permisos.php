<?php

namespace App\Support;

/** Módulos del sistema y niveles de acceso por perfil: n (sin acceso) · v (ver) · e (editar) · t (total, incluye eliminar). */
class Permisos
{
    public const NIVELES = ['n' => 'Sin acceso', 'v' => 'Ver', 'e' => 'Editar', 't' => 'Total'];

    public const RANGO = ['n' => 0, 'v' => 1, 'e' => 2, 't' => 3];

    public const MODULOS = [
        ['id' => 'inicio', 'nombre' => 'Panel de control', 'icono' => 'layout-dashboard', 'url' => '/', 'grupo' => 'Organización', 'desc' => 'Resumen de la organización y su actividad'],
        ['id' => 'agremiados', 'nombre' => 'Agremiados', 'icono' => 'users', 'url' => '/agremiados', 'grupo' => 'Organización', 'desc' => 'Padrón con datos generales, incidencias y trazabilidad'],
        ['id' => 'red', 'nombre' => 'Coordinación', 'icono' => 'network', 'url' => '/coordinacion', 'grupo' => 'Organización', 'desc' => 'Quién coordina a quién, como las cuentas hijas'],
        ['id' => 'cuotas', 'nombre' => 'Cuotas', 'icono' => 'wallet', 'url' => '/cuotas', 'grupo' => 'Organización', 'desc' => 'Pagos mensuales y adeudos'],
        ['id' => 'apoyos', 'nombre' => 'Apoyos', 'icono' => 'hand-heart', 'url' => '/apoyos', 'grupo' => 'Organización', 'desc' => 'Incentivos entregados con evidencia'],
        ['id' => 'tienda', 'nombre' => 'Tienda', 'icono' => 'store', 'url' => '/tienda', 'grupo' => 'Servicios', 'desc' => 'Productos, servicios, inventario, pedidos y proveedores'],
        ['id' => 'capacitacion', 'nombre' => 'Capacitación', 'icono' => 'graduation-cap', 'url' => '/capacitacion', 'grupo' => 'Servicios', 'desc' => 'Cursos en plataforma SAIS adaptada'],
        ['id' => 'ubicacion', 'nombre' => 'GPS', 'icono' => 'map-pin', 'url' => '/gps', 'grupo' => 'Servicios', 'desc' => 'Seguimiento en tiempo real por seguridad'],
        ['id' => 'bitacora', 'nombre' => 'Bitácora', 'icono' => 'scroll-text', 'url' => '/bitacora', 'grupo' => 'Control', 'desc' => 'Registro íntegro de cada acción de cada usuario'],
        ['id' => 'perfiles', 'nombre' => 'Perfiles', 'icono' => 'shield-check', 'url' => '/perfiles', 'grupo' => 'Control', 'desc' => 'Qué puede hacer cada perfil en cada módulo y sus usuarios'],
    ];

    public static function ids(): array
    {
        return array_column(self::MODULOS, 'id');
    }

    public static function vacio(string $nivel = 'n'): array
    {
        return array_fill_keys(self::ids(), $nivel);
    }

    public static function alcanza(?string $tiene, string $req): bool
    {
        return (self::RANGO[$tiene ?? 'n'] ?? 0) >= (self::RANGO[$req] ?? 3);
    }
}
