<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Listas desplegables (tipos de apoyo, incidencias, motivos de baja…). Si el usuario escribe un valor nuevo se agrega solo. */
class Catalogo extends Model
{
    protected $guarded = ['id'];

    public static function de(string $grupo): array
    {
        return static::where('grupo', $grupo)->orderBy('orden')->orderBy('valor')->pluck('valor')->all();
    }

    public static function asegurar(string $grupo, ?string $valor): void
    {
        $valor = trim((string) $valor);
        if ($valor !== '') {
            static::firstOrCreate(['grupo' => $grupo, 'valor' => $valor], ['orden' => 999, 'created_by' => auth()->id()]);
        }
    }
}
