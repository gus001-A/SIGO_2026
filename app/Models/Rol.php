<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use App\Support\Permisos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use Registro;

    protected $table = 'roles';

    protected string $modulo = 'perfiles';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['permisos' => 'array', 'protegido' => 'boolean', 'portal' => 'boolean'];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Nivel del perfil en un módulo; el perfil protegido (Super usuario) siempre tiene acceso total. */
    public function nivel(string $modulo): string
    {
        return $this->protegido ? 't' : ($this->permisos[$modulo] ?? 'n');
    }

    public function mapa(): array
    {
        return collect(Permisos::ids())->mapWithKeys(fn ($m) => [$m => $this->nivel($m)])->all();
    }
}
