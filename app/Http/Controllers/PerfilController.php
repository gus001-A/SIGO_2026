<?php

namespace App\Http\Controllers;

use App\Models\Agremiado;
use App\Models\Rol;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Perfiles (roles con permisos por módulo) y cuentas de usuario. */
class PerfilController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Rol::withCount(['usuarios' => fn ($q) => $q->where('activo', true)])->with(['creador', 'editor'])->orderBy('id')->get();

        return Inertia::render('Perfiles', [
            'tab' => $request->tab === 'usuarios' ? 'usuarios' : 'perfiles',
            'roles' => $roles->map(fn (Rol $r) => [
                ...$r->only(['id', 'clave', 'nombre', 'descripcion', 'icono', 'color', 'protegido', 'portal']),
                'permisos' => $r->mapa(), 'usuarios' => $r->usuarios_count, 'traza' => $this->traza($r),
            ]),
            'usuarios' => User::with(['rol', 'agremiado', 'creador', 'editor'])->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'rol_id' => $u->rol_id, 'rol' => $u->rol?->nombre, 'rol_color' => $u->rol?->color,
                'agremiado_id' => $u->agremiado_id, 'agremiado' => $this->persona($u->agremiado), 'activo' => $u->activo,
                'estado' => $u->activo ? 'Activo' : 'Inactivo', 'ultimo_acceso' => $u->ultimo_acceso?->format('Y-m-d H:i'), 'traza' => $this->traza($u),
            ]),
            'modulos' => Permisos::MODULOS,
            'niveles' => Permisos::NIVELES,
            'agremiados' => Agremiado::activos()->orderBy('nombre')->get()->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre_completo, 'folio' => $a->folio]),
            'puede' => ['total' => $request->user()->puede('perfiles', 't')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('roles', 'nombre')->whereNull('deleted_at')],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'base' => ['nullable', 'exists:roles,id'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icono' => ['required', 'string', 'max:40'],
            'portal' => ['boolean'],
        ], ['nombre.unique' => 'Ya existe un perfil con ese nombre.'], ['base' => 'copiar permisos de']);
        $base = isset($d['base']) ? Rol::find($d['base']) : null;
        $r = Rol::create([
            'clave' => Str::slug($d['nombre']).'-'.Str::lower(Str::random(4)), 'nombre' => $d['nombre'], 'descripcion' => $d['descripcion'] ?? null,
            'color' => $d['color'], 'icono' => $d['icono'], 'portal' => $d['portal'] ?? false,
            'permisos' => $base ? $base->mapa() : Permisos::vacio(),
        ]);

        return back()->with('success', "Perfil «{$r->nombre}» creado");
    }

    public function update(Request $request, Rol $rol): RedirectResponse
    {
        abort_if($rol->protegido, 422, 'El Super usuario no se puede modificar.');
        $d = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('roles', 'nombre')->ignore($rol->id)->whereNull('deleted_at')],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icono' => ['required', 'string', 'max:40'],
            'portal' => ['boolean'],
        ]);
        $rol->update($d);

        return back()->with('success', 'Perfil actualizado');
    }

    public function permiso(Request $request, Rol $rol): RedirectResponse
    {
        abort_if($rol->protegido, 422, 'El Super usuario siempre tiene acceso total.');
        $d = $request->validate(['modulo' => ['required', Rule::in(Permisos::ids())], 'nivel' => ['required', Rule::in(array_keys(Permisos::NIVELES))]]);
        abort_if($rol->id === $request->user()->rol_id && $d['modulo'] === 'perfiles' && $d['nivel'] !== 't', 422, 'No puedes quitarte a ti mismo el acceso total a Perfiles.');
        $rol->update(['permisos' => [...$rol->mapa(), $d['modulo'] => $d['nivel']]]);
        $m = collect(Permisos::MODULOS)->firstWhere('id', $d['modulo'])['nombre'];

        return back()->with('success', "{$rol->nombre} · {$m}: ".Permisos::NIVELES[$d['nivel']]);
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        abort_if($rol->protegido, 422, 'El Super usuario no se puede eliminar.');
        $n = $rol->usuarios()->count();
        abort_if($n > 0, 422, "No se puede eliminar: {$n} ".($n === 1 ? 'usuario tiene' : 'usuarios tienen').' este perfil.');
        $rol->delete();

        return back()->with('success', "Perfil «{$rol->nombre}» eliminado");
    }

    // ------------------------------------------------------------------ usuarios

    private function validarUsuario(Request $request, ?User $u): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($u?->id)],
            'rol_id' => ['required', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'agremiado_id' => ['nullable', 'exists:agremiados,id', Rule::unique('users', 'agremiado_id')->ignore($u?->id)->whereNull('deleted_at')],
            'password' => [$u ? 'nullable' : 'required', 'nullable', 'string', 'min:8', 'confirmed'],
            'activo' => ['boolean'],
        ], ['agremiado_id.unique' => 'Ese agremiado ya tiene una cuenta.'], ['name' => 'nombre', 'rol_id' => 'perfil', 'agremiado_id' => 'agremiado vinculado', 'password' => 'contraseña']);
    }

    public function storeUsuario(Request $request): RedirectResponse
    {
        $d = $this->validarUsuario($request, null);
        $this->exigirAgremiadoPortal($d);
        User::create(collect($d)->except('password_confirmation')->all());

        return back()->with('success', 'Usuario creado');
    }

    public function updateUsuario(Request $request, User $usuario): RedirectResponse
    {
        $d = $this->validarUsuario($request, $usuario);
        $this->exigirAgremiadoPortal($d);
        abort_if($usuario->id === $request->user()->id && (! ($d['activo'] ?? true) || (int) $d['rol_id'] !== $usuario->rol_id), 422, 'No puedes desactivarte ni cambiar tu propio perfil.');
        if (empty($d['password'])) {
            unset($d['password']);
        }
        $usuario->update($d);

        return back()->with('success', 'Usuario actualizado');
    }

    private function exigirAgremiadoPortal(array $d): void
    {
        abort_if(Rol::find($d['rol_id'])?->portal && empty($d['agremiado_id']), 422, 'Los perfiles de la app del agremiado deben vincularse a un agremiado.');
    }

    public function destroyUsuario(Request $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->id === $request->user()->id, 422, 'No puedes eliminar tu propia cuenta.');
        $usuario->update(['activo' => false]);
        $usuario->delete();

        return back()->with('success', 'Usuario eliminado');
    }
}
