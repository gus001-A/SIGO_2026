<?php

namespace App\Models;

use App\Models\Concerns\Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agremiado extends Model
{
    use Registro;

    protected $table = 'agremiados';

    protected string $modulo = 'agremiados';

    protected $guarded = ['id', 'created_by', 'updated_by'];

    protected $appends = ['nombre_completo'];

    public const COLORES = ['#0f3b63', '#2a7fc4', '#ee8a2c', '#18a3a0', '#5f9e2f', '#c97a1a', '#0c2742', '#13857f', '#3d5f8f', '#1f6fae', '#d9971c'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date:Y-m-d', 'fecha_ingreso' => 'date:Y-m-d', 'gps_autorizado' => 'boolean',
            'gps_autorizado_at' => 'datetime', 'baja_at' => 'datetime', 'latitud' => 'float', 'longitud' => 'float',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function auditLabel(): string
    {
        return "{$this->folio} · {$this->nombre_completo}";
    }

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('estatus', 'Activo');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coordinador_id')->withTrashed();
    }

    public function coordinados(): HasMany
    {
        return $this->hasMany(self::class, 'coordinador_id');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class);
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class);
    }

    public function apoyos(): HasMany
    {
        return $this->hasMany(Apoyo::class);
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function bajaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baja_por')->withTrashed();
    }

    public function gpsPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gps_autorizado_por')->withTrashed();
    }

    /** IDs de toda la red debajo de un agremiado (para no asignarle como coordinador a alguien de su propia red). */
    public static function descendientes(int $id, ?array $mapa = null): array
    {
        $mapa ??= static::query()->pluck('coordinador_id', 'id')->all();
        $hijos = array_keys(array_filter($mapa, fn ($p) => (int) $p === $id));
        $todos = $hijos;
        foreach ($hijos as $h) {
            $todos = array_merge($todos, static::descendientes($h, $mapa));
        }

        return $todos;
    }

    public static function siguienteFolio(): string
    {
        $max = static::withTrashed()->pluck('folio')->map(fn ($f) => (int) preg_replace('/\D/', '', $f))->max() ?? 0;

        return 'SUT-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
