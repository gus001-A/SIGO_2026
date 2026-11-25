<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

/** Paquete común: baja lógica + trazabilidad (usuario/fecha/hora) + bitácora de auditoría. */
trait Registro
{
    use Auditable, SoftDeletes, Trazable;
}
