<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cuota extends Model
{
    use Registro;

    protected $table = 'cuotas';

    protected string $modulo = 'cuotas';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['monto' => 'float'];
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }

    public function auditLabel(): string
    {
        return ($this->agremiado?->folio ?? '').' · '.sprintf('%04d-%02d', $this->anio, $this->mes);
    }
}
