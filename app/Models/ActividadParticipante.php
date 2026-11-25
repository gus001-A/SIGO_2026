<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadParticipante extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ultima_senal' => 'datetime', 'latitud' => 'float', 'longitud' => 'float'];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }
}
