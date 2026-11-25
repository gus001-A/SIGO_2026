<?php

namespace App\Models\Concerns;

use App\Models\SysLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra en la bitácora (sys_logs) cada alta, modificación y baja lógica del modelo,
 * con usuario, fecha, hora, IP y valores anteriores / nuevos.
 */
trait Auditable
{
    protected static array $auditIgnore = ['created_at', 'updated_at', 'updated_by', 'created_by', 'password', 'remember_token', 'html'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => $m->writeAudit('Alta', ['nuevo' => $m->auditPayload($m->getAttributes())]));

        static::updated(function (Model $m) {
            $cambios = [];
            foreach ($m->getChanges() as $k => $v) {
                if ($k === 'deleted_at' || in_array($k, static::$auditIgnore, true)) {
                    continue;
                }
                $cambios[$k] = ['antes' => $m->getOriginal($k), 'despues' => $v];
            }
            if (array_key_exists('password', $m->getChanges())) {
                $cambios['password'] = ['antes' => '••••', 'despues' => '•••• (actualizada)'];
            }
            if ($cambios) {
                $m->writeAudit('Modificación', $cambios);
            }
        });

        static::deleted(fn (Model $m) => $m->writeAudit('Baja lógica', ['anterior' => $m->auditPayload($m->getAttributes())]));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => $m->writeAudit('Restauración'));
        }
    }

    protected function auditPayload(array $attributes): array
    {
        return collect($attributes)->except(static::$auditIgnore)->all();
    }

    public function auditLabel(): string
    {
        return (string) ($this->folio ?? $this->codigo ?? $this->nombre ?? $this->titulo ?? $this->requisito ?? '#'.$this->getKey());
    }

    public function auditModulo(): string
    {
        return property_exists($this, 'modulo') ? $this->modulo : $this->getTable();
    }

    public function writeAudit(string $accion, ?array $cambios = null, ?string $descripcion = null): SysLog
    {
        return SysLog::registrar($this->auditModulo(), $this->getTable(), $this->getKey(), $this->auditLabel(), $accion, $descripcion, $cambios);
    }
}
