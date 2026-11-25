<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Producto extends Model
{
    use Registro;

    protected $table = 'productos';

    protected string $modulo = 'tienda';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['costo' => 'float', 'precio' => 'float', 'publicado' => 'boolean'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class)->withTrashed();
    }

    public function archivos(): MorphMany
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function esServicio(): bool
    {
        return $this->categoria === 'Servicio';
    }

    public function bajoMinimo(): bool
    {
        return ! $this->esServicio() && $this->stock !== null && $this->stock < (int) $this->minimo;
    }
}
