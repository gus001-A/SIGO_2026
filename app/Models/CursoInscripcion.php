<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CursoInscripcion extends Model
{
    use Registro;

    protected $table = 'curso_inscripciones';

    protected string $modulo = 'capacitacion';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['concluido_at' => 'datetime'];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class)->withTrashed();
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }

    public function auditLabel(): string
    {
        return ($this->curso?->nombre ?? '').' · '.($this->agremiado?->folio ?? '');
    }
}
