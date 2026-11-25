<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use Registro;

    protected $table = 'proveedores';

    protected string $modulo = 'tienda';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
