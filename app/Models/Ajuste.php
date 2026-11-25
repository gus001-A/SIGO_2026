<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    protected $guarded = ['id'];

    public static function valor(string $clave, $def = null)
    {
        return static::where('clave', $clave)->value('valor') ?? $def;
    }

    public static function poner(string $clave, $valor): void
    {
        $antes = static::valor($clave);
        static::updateOrCreate(['clave' => $clave], ['valor' => (string) $valor, 'updated_by' => auth()->id()]);
        if ((string) $antes !== (string) $valor) {
            SysLog::registrar('cuotas', 'ajustes', null, $clave, 'Modificó ajuste', "{$clave}: {$antes} → {$valor}", ['antes' => $antes, 'despues' => $valor]);
        }
    }
}
