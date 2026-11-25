<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guarda quién registró (created_by) y quién modificó por última vez (updated_by);
 * la fecha y hora quedan en created_at / updated_at.
 */
trait Trazable
{
    public static function bootTrazable(): void
    {
        static::creating(function ($model) {
            $id = auth()->id();
            $model->created_by ??= $id;
            $model->updated_by ??= $id;
        });
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    /** Usuario, fecha y hora del registro y de la última modificación, listos para la vista. */
    public function traza(): array
    {
        $fila = fn ($u, $f) => $f ? ['usuario' => $u?->name ?? 'Sistema', 'fecha' => $f->format('Y-m-d'), 'hora' => $f->format('H:i')] : null;
        $modificado = $this->updated_at && $this->created_at && $this->updated_at->gt($this->created_at);

        return [
            'creado' => $fila($this->creador, $this->created_at),
            'modificado' => $modificado ? $fila($this->editor, $this->updated_at) : null,
        ];
    }
}
