<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    use Registro;

    protected $table = 'incidencias';

    protected string $modulo = 'agremiados';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d'];
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }

    public function auditLabel(): string
    {
        return ($this->agremiado?->folio ?? '').' · '.$this->tipo;
    }
}
