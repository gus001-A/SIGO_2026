<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    use Registro;

    protected $table = 'actividades';

    protected string $modulo = 'ubicacion';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['finalizada_at' => 'datetime'];
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(ActividadParticipante::class);
    }
}
