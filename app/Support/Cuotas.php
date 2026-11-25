<?php

namespace App\Support;

use App\Models\Agremiado;
use App\Models\Ajuste;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Reglas de cuotas: monto mensual, periodos a mostrar y adeudo de cada agremiado. */
class Cuotas
{
    public const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public const MESES_LARGOS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public static function monto(): float
    {
        return (float) Ajuste::valor('cuota_mensual', 150);
    }

    /** Primer mes desde el que se cobra la cuota. */
    public static function inicio(): CarbonImmutable
    {
        return CarbonImmutable::parse(Ajuste::valor('cuotas_desde', now()->format('Y').'-01').'-01')->startOfMonth();
    }

    /** Últimos $n meses hasta el actual. */
    public static function periodos(int $n = 6): array
    {
        $fin = CarbonImmutable::today()->startOfMonth();

        return collect(range($n - 1, 0))->map(function ($i) use ($fin) {
            $d = $fin->subMonths($i);

            return ['anio' => $d->year, 'mes' => $d->month, 'clave' => $d->format('Y-m'), 'label' => self::MESES[$d->month - 1]];
        })->all();
    }

    public static function etiqueta(string $clave): string
    {
        [$y, $m] = explode('-', $clave);

        return ucfirst(self::MESES_LARGOS[(int) $m - 1]).' '.$y;
    }

    /** Meses que el agremiado debe pagar (desde su ingreso o el inicio de cobro, hasta el mes actual). */
    public static function mesesExigibles(Agremiado $a): Collection
    {
        $desde = CarbonImmutable::parse($a->fecha_ingreso)->startOfMonth()->max(self::inicio());
        $hasta = CarbonImmutable::today()->startOfMonth();
        $meses = collect();
        for ($d = $desde; $d->lte($hasta); $d = $d->addMonth()) {
            $meses->push($d->format('Y-m'));
        }

        return $meses;
    }

    /** Claves AAAA-MM pendientes de pago (usa la relación cuotas ya cargada). */
    public static function pendientes(Agremiado $a): Collection
    {
        if ($a->estatus === 'Baja') {
            return collect();
        }
        $pagados = $a->cuotas->map(fn ($c) => sprintf('%04d-%02d', $c->anio, $c->mes))->flip();

        return self::mesesExigibles($a)->reject(fn ($m) => $pagados->has($m))->values();
    }
}
