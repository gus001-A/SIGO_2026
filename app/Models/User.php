<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\Trazable;
use App\Support\Permisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable, HasFactory, Notifiable, SoftDeletes, Trazable;

    protected string $modulo = 'perfiles';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'ultimo_acceso' => 'datetime', 'password' => 'hashed', 'activo' => 'boolean'];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class)->withTrashed();
    }

    public function agremiado(): BelongsTo
    {
        return $this->belongsTo(Agremiado::class)->withTrashed();
    }

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('activo', true);
    }

    public function nivel(string $modulo): string
    {
        return $this->rol?->nivel($modulo) ?? 'n';
    }

    public function puede(string $modulo, string $req = 'v'): bool
    {
        return Permisos::alcanza($this->nivel($modulo), $req);
    }

    /** Usuarios que entran a la app del agremiado en lugar del panel. */
    public function esPortal(): bool
    {
        return (bool) $this->rol?->portal && $this->agremiado_id;
    }

    public function auditLabel(): string
    {
        return $this->email;
    }
}
