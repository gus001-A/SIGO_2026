<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    use Registro;

    protected $table = 'cursos';

    protected string $modulo = 'capacitacion';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['fecha_limite' => 'date:Y-m-d'];
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(CursoInscripcion::class);
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class, 'dirigido_coordinador_id')->withTrashed();
    }
}
