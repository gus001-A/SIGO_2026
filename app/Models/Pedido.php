<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use Registro;

    protected $table = 'pedidos';

    protected string $modulo = 'tienda';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['total' => 'float'];
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class);
    }
}
