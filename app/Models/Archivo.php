<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use Registro;

    protected $table = 'archivos';

    protected string $modulo = 'tienda';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected $appends = ['url'];

    public function archivable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return '/storage/'.$this->ruta;
    }

    public function auditModulo(): string
    {
        return \str_ends_with((string) $this->archivable_type, 'Apoyo') ? 'apoyos' : 'tienda';
    }

    public function auditLabel(): string
    {
        return $this->nombre;
    }
}
