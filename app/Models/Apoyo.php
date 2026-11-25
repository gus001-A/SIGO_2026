<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Apoyo extends Model
{
    use Registro;

    protected $table = 'apoyos';

    protected string $modulo = 'apoyos';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['monto' => 'float', 'fecha_entrega' => 'datetime:Y-m-d H:i'];
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }

    public function archivos(): MorphMany
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function auditLabel(): string
    {
        return $this->tipo.' · '.($this->agremiado?->folio ?? '');
    }
}
