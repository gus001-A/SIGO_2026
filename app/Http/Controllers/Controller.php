<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Archivo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /** Datos mínimos de una persona para avatares y celdas de la tabla. */
    protected function persona(?Agremiado $a): ?array
    {
        if (! $a) {
            return null;
        }

        return [
            'id' => $a->id, 'folio' => $a->folio, 'nombre' => $a->nombre_completo, 'puesto' => $a->puesto,
            'color' => $a->color, 'foto' => $a->foto ? '/storage/'.$a->foto : null, 'estatus' => $a->estatus,
        ];
    }

    /** Usuario, fecha y hora de alta y de última modificación. */
    protected function traza(Model $m): array
    {
        return method_exists($m, 'traza') ? $m->traza() : ['creado' => null, 'modificado' => null];
    }

    /** Guarda un archivo subido en el disco público y lo liga al registro. */
    protected function guardarArchivo(Model $dueno, UploadedFile $f, string $carpeta): Archivo
    {
        $mime = (string) $f->getMimeType();
        $tipo = str_starts_with($mime, 'image/') ? 'imagen' : (str_starts_with($mime, 'video/') ? 'video' : 'documento');

        return $dueno->archivos()->create([
            'ruta' => $f->store($carpeta, 'public'),
            'nombre' => mb_substr($f->getClientOriginalName(), 0, 200),
            'tipo' => $tipo,
            'peso' => (int) $f->getSize(),
        ]);
    }

    protected function archivosDe(Model $m): array
    {
        return $m->archivos->map(fn (Archivo $a) => ['id' => $a->id, 'nombre' => $a->nombre, 'tipo' => $a->tipo, 'url' => $a->url, 'peso' => $a->peso])->values()->all();
    }
}
