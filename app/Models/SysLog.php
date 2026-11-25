<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora central de auditoría: no se edita ni se elimina. */
class SysLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['cambios_json' => 'array', 'fecha' => 'date:Y-m-d'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }

    public static function registrar(string $modulo, string $tabla, $registroId, ?string $registro, string $accion, ?string $descripcion = null, ?array $cambios = null): self
    {
        $now = now();
        $req = app()->runningInConsole() ? null : request();

        return static::create([
            'modulo' => $modulo,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'registro' => $registro ? mb_substr($registro, 0, 120) : null,
            'accion' => $accion,
            'usuario_id' => auth()->id(),
            'fecha' => $now->toDateString(),
            'hora' => $now->format('H:i:s'),
            'descripcion' => $descripcion ? mb_substr($descripcion, 0, 250) : null,
            'cambios_json' => $cambios,
            'ip' => $req?->ip(),
            'agente' => $req ? mb_substr((string) $req->userAgent(), 0, 250) : null,
            'created_at' => $now,
        ]);
    }
}
