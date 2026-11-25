<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use Registro;

    protected $table = 'movimientos_inventario';

    protected string $modulo = 'tienda';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class)->withTrashed();
    }

    public function auditLabel(): string
    {
        return ($this->producto?->nombre ?? '').' · '.$this->tipo.' '.$this->cantidad;
    }
}
