<?php

namespace App\Http\Controllers;

use App\Models\ActividadParticipante;
use App\Models\Agremiado;
use App\Models\Catalogo;
use App\Models\Incidencia;
use App\Models\Rol;
use App\Models\SysLog;
use App\Models\User;
use App\Support\Cuotas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgremiadoController extends Controller
{
    public function index(Request $request): Response
    {
        $u = $request->user();
        $todos = Agremiado::with(['coordinador', 'cuotas', 'usuario.rol'])->withCount('coordinados')->orderBy('folio')->get();

        return Inertia::render('Agremiados/Index', [
            'filas' => $todos->map(fn (Agremiado $a) => [
                ...$this->persona($a),
                'coordinador' => $a->coordinador?->nombre_completo,
                'coordinador_id' => $a->coordinador_id,
                'municipio' => $a->municipio,
                'seccion' => $a->seccion,
                'adeudo' => Cuotas::pendientes($a)->count(),
                'gps' => $a->gps_autorizado,
                'perfil' => $a->usuario?->rol?->nombre,
                'coordinados' => $a->coordinados_count,
                'fecha_ingreso' => $a->fecha_ingreso?->format('Y-m-d'),
            ]),
            'opciones' => $this->opciones(),
            'puede' => ['editar' => $u->puede('agremiados', 'e'), 'eliminar' => $u->puede('agremiados', 't'), 'red' => $u->puede('red', 'e'), 'cuotas' => $u->puede('cuotas', 'e'), 'accesos' => $u->puede('perfiles', 't')],
            'abrir' => $request->integer('ver') ?: null,
        ]);
    }

    private function opciones(): array
    {
        return [
            'coordinadores' => Agremiado::activos()->orderBy('nombre')->get()->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre_completo, 'puesto' => $a->puesto, 'folio' => $a->folio]),
            'roles' => Rol::orderBy('id')->get(['id', 'nombre', 'portal', 'protegido']),
            'motivosBaja' => Catalogo::de('motivo_baja'),
            'tiposIncidencia' => Catalogo::de('tipo_incidencia'),
            'puestos' => Catalogo::de('puesto'),
            'estados' => Catalogo::de('estado'),
        ];
    }

    /** Ficha completa para el detalle (show). */
    public function show(Agremiado $agremiado): JsonResponse
    {
        $a = $agremiado->load(['coordinador', 'cuotas.creador', 'incidencias.creador', 'apoyos.creador', 'apoyos.archivos', 'coordinados', 'usuario.rol', 'bajaPor', 'gpsPor', 'creador', 'editor']);
        $pend = Cuotas::pendientes($a);

        SysLog::registrar('agremiados', 'agremiados', $a->id, $a->auditLabel(), 'Consultó ficha');

        return response()->json([
            ...$this->persona($a),
            ...$a->toArray(),
            'foto' => $a->foto ? '/storage/'.$a->foto : null,
            'ine_frente' => $a->ine_frente ? '/storage/'.$a->ine_frente : null,
            'ine_reverso' => $a->ine_reverso ? '/storage/'.$a->ine_reverso : null,
            'coordinador' => $this->persona($a->coordinador),
            'coordinados' => $a->coordinados->map(fn ($c) => $this->persona($c)),
            'adeudo' => $pend->count(),
            'pendientes' => $pend->map(fn ($m) => ['clave' => $m, 'label' => Cuotas::etiqueta($m)]),
            'cuota' => Cuotas::monto(),
            'cuotas' => $a->cuotas->sortByDesc(fn ($c) => sprintf('%04d%02d', $c->anio, $c->mes))->values()->map(fn ($c) => [
                'id' => $c->id, 'periodo' => Cuotas::etiqueta(sprintf('%04d-%02d', $c->anio, $c->mes)), 'monto' => $c->monto, 'forma_pago' => $c->forma_pago, 'traza' => $this->traza($c),
            ]),
            'incidencias' => $a->incidencias->sortByDesc('fecha')->values()->map(fn ($i) => ['id' => $i->id, 'tipo' => $i->tipo, 'descripcion' => $i->descripcion, 'fecha' => $i->fecha->format('Y-m-d'), 'traza' => $this->traza($i)]),
            'apoyos' => $a->apoyos->sortByDesc('fecha_entrega')->values()->map(fn ($p) => ['id' => $p->id, 'tipo' => $p->tipo, 'detalle' => $p->detalle, 'monto' => $p->monto, 'fecha' => $p->fecha_entrega->format('Y-m-d H:i'), 'archivos' => $this->archivosDe($p), 'traza' => $this->traza($p)]),
            'usuario' => $a->usuario ? ['id' => $a->usuario->id, 'email' => $a->usuario->email, 'rol_id' => $a->usuario->rol_id, 'rol' => $a->usuario->rol?->nombre, 'activo' => $a->usuario->activo] : null,
            'baja' => $a->estatus === 'Baja' ? ['motivo' => $a->baja_motivo, 'detalle' => $a->baja_detalle, 'usuario' => $a->bajaPor?->name, 'fecha' => $a->baja_at?->format('Y-m-d H:i')] : null,
            'gps_traza' => $a->gps_autorizado ? ['usuario' => $a->gpsPor?->name ?? 'El agremiado', 'fecha' => $a->gps_autorizado_at?->format('Y-m-d H:i')] : null,
            'traza' => $this->traza($a),
        ]);
    }

    private function reglas(?Agremiado $a, Request $request): array
    {
        $id = $a?->id;
        $hoy = today()->toDateString();
        $esCabeza = $a && ! $a->coordinador_id;

        return [
            'nombre' => ['required', 'string', 'max:80'],
            'apellido_paterno' => ['required', 'string', 'max:60'],
            'apellido_materno' => ['nullable', 'string', 'max:60'],
            'curp' => ['required', 'regex:/^[A-Z]{4}\d{6}[HMX][A-Z]{5}[A-Z0-9]\d$/', Rule::unique('agremiados', 'curp')->ignore($id)],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:'.today()->subYears(15)->toDateString()],
            'sexo' => ['required', Rule::in(['Mujer', 'Hombre', 'Prefiero no decir'])],
            'clave_elector' => ['required', 'regex:/^[A-Z0-9]{18}$/', Rule::unique('agremiados', 'clave_elector')->ignore($id)],
            'seccion' => ['required', 'regex:/^\d{1,4}$/'],
            'calle' => ['required', 'string', 'max:150'],
            'colonia' => ['required', 'string', 'max:120'],
            'cp' => ['required', 'regex:/^\d{5}$/'],
            'municipio' => ['required', 'string', 'max:120'],
            'estado' => ['required', 'string', 'max:60'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'celular' => ['required', 'regex:/^\d{10}$/'],
            'emergencia_nombre' => ['required', 'string', 'max:120'],
            'emergencia_telefono' => ['required', 'regex:/^\d{10}$/', 'different:celular'],
            'puesto' => ['required', 'string', 'max:120'],
            'coordinador_id' => [$esCabeza ? 'nullable' : 'required', 'nullable', Rule::exists('agremiados', 'id')->where('estatus', 'Activo')->whereNull('deleted_at'),
                function ($attr, $v, $fail) use ($id) {
                    if ($id && $v && ((int) $v === $id || in_array((int) $v, Agremiado::descendientes($id), true))) {
                        $fail('No puede coordinarlo alguien de su propia red.');
                    }
                }],
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:'.$hoy, 'after:fecha_nacimiento'],
            'gps_autorizado' => ['boolean'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'ine_frente' => ['nullable', 'image', 'max:4096'],
            'ine_reverso' => ['nullable', 'image', 'max:4096'],
            'acceso' => ['boolean'],
            'email' => ['nullable', 'required_if:acceso,true', 'email', 'max:120', Rule::unique('users', 'email')->ignore($a?->usuario?->id)],
            'rol_id' => ['nullable', 'required_if:acceso,true', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'password' => [$a?->usuario ? 'nullable' : 'nullable', 'string', 'min:8', ...($request->boolean('acceso') && ! $a?->usuario ? ['required'] : [])],
        ];
    }

    private const ATRIBUTOS = [
        'nombre' => 'nombre(s)', 'apellido_paterno' => 'apellido paterno', 'apellido_materno' => 'apellido materno', 'curp' => 'CURP', 'fecha_nacimiento' => 'fecha de nacimiento',
        'clave_elector' => 'clave de elector', 'seccion' => 'sección electoral', 'calle' => 'calle y número', 'cp' => 'código postal', 'celular' => 'teléfono celular',
        'emergencia_nombre' => 'contacto de emergencia', 'emergencia_telefono' => 'teléfono de emergencia', 'coordinador_id' => 'coordinador', 'fecha_ingreso' => 'fecha de ingreso',
        'ine_frente' => 'INE frente', 'ine_reverso' => 'INE reverso', 'email' => 'correo de acceso', 'rol_id' => 'perfil', 'password' => 'contraseña',
    ];

    private const MENSAJES = [
        'curp.regex' => 'La CURP no tiene un formato válido (18 caracteres).',
        'clave_elector.regex' => 'La clave de elector tiene 18 letras y números.',
        'seccion.regex' => 'Solo números, hasta 4 dígitos.',
        'cp.regex' => 'El código postal tiene 5 dígitos.',
        'celular.regex' => 'Debe tener 10 dígitos.',
        'emergencia_telefono.regex' => 'Debe tener 10 dígitos.',
        'emergencia_telefono.different' => 'Usa un teléfono distinto al celular del agremiado.',
        'fecha_nacimiento.before_or_equal' => 'Debe tener al menos 15 años.',
        'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
        'curp.unique' => 'Esta CURP ya está registrada en otro agremiado.',
        'clave_elector.unique' => 'Esta clave de elector ya está registrada.',
    ];

    private function limpiar(Request $request): void
    {
        $request->merge([
            'curp' => mb_strtoupper(trim((string) $request->curp)),
            'clave_elector' => mb_strtoupper(trim((string) $request->clave_elector)),
            'celular' => preg_replace('/\D/', '', (string) $request->celular),
            'emergencia_telefono' => preg_replace('/\D/', '', (string) $request->emergencia_telefono),
            'seccion' => preg_replace('/\D/', '', (string) $request->seccion),
            'gps_autorizado' => $request->boolean('gps_autorizado'),
            'acceso' => $request->boolean('acceso'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->limpiar($request);
        $data = $request->validate($this->reglas(null, $request), self::MENSAJES, self::ATRIBUTOS);

        $a = DB::transaction(function () use ($data, $request) {
            $a = new Agremiado($this->datos($data));
            $a->folio = Agremiado::siguienteFolio();
            $a->color = Agremiado::COLORES[random_int(0, count(Agremiado::COLORES) - 1)];
            if ($a->gps_autorizado) {
                $a->gps_autorizado_at = now();
                $a->gps_autorizado_por = $request->user()->id;
            }
            $this->fotos($a, $request);
            $a->save();
            $this->acceso($a, $data, $request);
            Catalogo::asegurar('puesto', $data['puesto']);

            return $a;
        });

        return back()->with('success', "Alta registrada: {$a->folio} · {$a->nombre_completo}");
    }

    public function update(Request $request, Agremiado $agremiado): RedirectResponse
    {
        $this->limpiar($request);
        $data = $request->validate($this->reglas($agremiado, $request), self::MENSAJES, self::ATRIBUTOS);

        DB::transaction(function () use ($agremiado, $data, $request) {
            $antesGps = $agremiado->gps_autorizado;
            $agremiado->fill($this->datos($data));
            if ($agremiado->gps_autorizado && ! $antesGps) {
                $agremiado->gps_autorizado_at = now();
                $agremiado->gps_autorizado_por = $request->user()->id;
            }
            $this->fotos($agremiado, $request);
            $agremiado->save();
            $this->acceso($agremiado, $data, $request);
            Catalogo::asegurar('puesto', $data['puesto']);
        });

        return back()->with('success', 'Datos actualizados');
    }

    private function datos(array $d): array
    {
        return collect($d)->except(['foto', 'ine_frente', 'ine_reverso', 'acceso', 'email', 'rol_id', 'password'])->all();
    }

    private function fotos(Agremiado $a, Request $request): void
    {
        foreach (['foto', 'ine_frente', 'ine_reverso'] as $k) {
            if ($request->hasFile($k)) {
                if ($a->$k) {
                    Storage::disk('public')->delete($a->$k);
                }
                $a->$k = $request->file($k)->store('agremiados', 'public');
            }
        }
    }

    /** Crea o actualiza la cuenta de acceso del agremiado (solo si el usuario puede administrar perfiles). */
    private function acceso(Agremiado $a, array $d, Request $request): void
    {
        if (! $request->user()->puede('perfiles', 't')) {
            return;
        }
        $u = $a->usuario;
        if (! ($d['acceso'] ?? false)) {
            if ($u && $u->activo) {
                $u->update(['activo' => false]);
            }

            return;
        }
        $campos = ['name' => $a->nombre_completo, 'email' => $d['email'], 'rol_id' => $d['rol_id'], 'activo' => true];
        if (! empty($d['password'])) {
            $campos['password'] = $d['password'];
        }
        $u ? $u->update($campos) : User::create([...$campos, 'agremiado_id' => $a->id]);
    }

    public function baja(Request $request, Agremiado $agremiado): RedirectResponse
    {
        $d = $request->validate(['motivo' => ['required', 'string', 'max:120'], 'detalle' => ['nullable', 'string', 'max:255']], [], ['motivo' => 'motivo']);
        abort_if($agremiado->estatus === 'Baja', 422, 'El agremiado ya está dado de baja.');
        abort_if(! $agremiado->coordinador_id, 422, 'La cabeza de la red no puede darse de baja.');
        $n = $agremiado->coordinados()->activos()->count();
        abort_if($n > 0, 422, "Coordina a {$n} ".($n === 1 ? 'persona' : 'personas').'. Primero reasígnalas a otro coordinador.');

        DB::transaction(function () use ($agremiado, $d, $request) {
            $agremiado->update(['estatus' => 'Baja', 'baja_motivo' => $d['motivo'], 'baja_detalle' => $d['detalle'] ?? null, 'baja_at' => now(), 'baja_por' => $request->user()->id, 'gps_autorizado' => false]);
            ActividadParticipante::where('agremiado_id', $agremiado->id)->whereHas('actividad', fn ($q) => $q->where('estado', 'Activa'))->delete();
            $agremiado->usuario?->update(['activo' => false]);
            Catalogo::asegurar('motivo_baja', $d['motivo']);
            $agremiado->writeAudit('Dio de baja', null, $d['motivo']);
        });

        return back()->with('success', "{$agremiado->nombre_completo} quedó dado de baja");
    }

    public function reactivar(Agremiado $agremiado): RedirectResponse
    {
        abort_if($agremiado->estatus !== 'Baja', 422, 'El agremiado está activo.');
        $coord = $agremiado->coordinador;
        $agremiado->update([
            'estatus' => 'Activo', 'baja_motivo' => null, 'baja_detalle' => null, 'baja_at' => null, 'baja_por' => null,
            'coordinador_id' => $coord && $coord->estatus === 'Activo' && ! $coord->trashed() ? $coord->id : Agremiado::whereNull('coordinador_id')->value('id'),
        ]);
        $agremiado->writeAudit('Reactivó');

        return back()->with('success', 'Agremiado reactivado');
    }

    public function coordinador(Request $request, Agremiado $agremiado): RedirectResponse
    {
        $d = $request->validate(['coordinador_id' => ['required', Rule::exists('agremiados', 'id')->where('estatus', 'Activo')->whereNull('deleted_at')]]);
        abort_if(! $agremiado->coordinador_id, 422, 'La cabeza de la red no tiene coordinador.');
        abort_if((int) $d['coordinador_id'] === $agremiado->id || in_array((int) $d['coordinador_id'], Agremiado::descendientes($agremiado->id), true), 422, 'No puede coordinarlo alguien de su propia red.');
        $antes = $agremiado->coordinador?->nombre_completo;
        $agremiado->update(['coordinador_id' => $d['coordinador_id']]);
        SysLog::registrar('red', 'agremiados', $agremiado->id, $agremiado->auditLabel(), 'Reasignó coordinador', "{$antes} → ".$agremiado->fresh()->coordinador?->nombre_completo);

        return back()->with('success', 'Coordinador reasignado');
    }

    public function incidencia(Request $request, Agremiado $agremiado): RedirectResponse
    {
        $d = $request->validate([
            'tipo' => ['required', 'string', 'max:60'],
            'descripcion' => ['required', 'string', 'max:2000'],
            'fecha' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$agremiado->fecha_ingreso->toDateString()],
        ], ['fecha.before_or_equal' => 'La fecha no puede ser futura.', 'fecha.after_or_equal' => 'La fecha no puede ser anterior a su ingreso.'], ['descripcion' => 'descripción']);
        Incidencia::create([...$d, 'agremiado_id' => $agremiado->id]);
        Catalogo::asegurar('tipo_incidencia', $d['tipo']);

        return back()->with('success', 'Incidencia guardada');
    }

    public function destroyIncidencia(Incidencia $incidencia): RedirectResponse
    {
        $incidencia->delete();

        return back()->with('success', 'Incidencia eliminada');
    }

    public function destroy(Agremiado $agremiado): RedirectResponse
    {
        abort_if(! $agremiado->coordinador_id, 422, 'La cabeza de la red no se puede eliminar.');
        abort_if($agremiado->coordinados()->exists(), 422, 'Coordina a otras personas. Primero reasígnalas.');
        $agremiado->usuario?->update(['activo' => false]);
        $agremiado->delete();

        return back()->with('success', 'Registro eliminado (se conserva en la bitácora)');
    }
}
