<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Password from 'primevue/password';
import ToggleSwitch from 'primevue/toggleswitch';
import ColorPicker from 'primevue/colorpicker';
import { ShieldCheck, UsersRound, Users, Plus, Pencil, Trash2, Lock, Eye, Save, LayoutGrid, UserCog, Smartphone, KeyRound } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import TrazaBoton from '@/Components/TrazaBoton.vue';
import Acciones from '@/Components/Acciones.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import { icono, ICONOS_PERFIL } from '@/iconos';
import { enviar, eliminar } from '@/acciones';
import { alerta } from '@/alerta';
import { fecha } from '@/utils';

const props = defineProps<{ tab: string; roles: any[]; usuarios: any[]; modulos: any[]; niveles: Record<string, string>; agremiados: any[]; puede: { total: boolean } }>();
const page = usePage<any>();

const tab = ref(props.tab);
watch(tab, (t) => history.replaceState(history.state, '', `${location.pathname}?tab=${t}`));
const selId = ref(props.roles.find((r) => !r.protegido)?.id ?? props.roles[0]?.id);
const sel = computed(() => props.roles.find((r) => r.id === selId.value) ?? props.roles[0]);
const editable = computed(() => props.puede.total && !sel.value?.protegido);
const conAcceso = (r: any) => Object.values(r.permisos).filter((v) => v !== 'n').length;
const total = (r: any) => Object.values(r.permisos).filter((v) => v === 't').length;
const usuariosDe = computed(() => props.usuarios.filter((u) => u.rol_id === sel.value?.id && u.activo));

function nivel(m: any, k: string) {
    if (!editable.value || sel.value.permisos[m.id] === k) return;
    router.put(`/perfiles/${sel.value.id}/permiso`, { modulo: m.id, nivel: k }, { preserveScroll: true, preserveState: true, onError: (e) => alerta.errores(e) });
}

// Perfil: alta / edición
const rVisible = ref(false);
const rReg = ref<any>(null);
const fR = useForm({ nombre: '', descripcion: '', base: null as number | null, color: '#2a7fc4', icono: 'user-cog', portal: false });
function abrirRol(r: any = null) {
    rReg.value = r;
    fR.clearErrors();
    fR.defaults({ nombre: r?.nombre ?? '', descripcion: r?.descripcion ?? '', base: null, color: r?.color ?? '#2a7fc4', icono: r?.icono ?? 'user-cog', portal: r?.portal ?? false });
    fR.reset();
    rVisible.value = true;
}
const guardarRol = () => enviar(fR, rReg.value ? 'put' : 'post', rReg.value ? `/perfiles/${rReg.value.id}` : '/perfiles', {
    transformar: (d) => ({ ...d, color: d.color.startsWith('#') ? d.color : '#' + d.color }),
    exito: () => { rVisible.value = false; if (!rReg.value) selId.value = Math.max(...props.roles.map((r) => r.id)); },
});
function borrarRol(r: any) {
    if (r.usuarios) return alerta.aviso('No se puede eliminar', `${r.usuarios} ${r.usuarios === 1 ? 'usuario tiene' : 'usuarios tienen'} este perfil. Cámbialos de perfil primero.`);
    eliminar(`/perfiles/${r.id}`, `el perfil «${r.nombre}»`, undefined, () => (selId.value = props.roles[1]?.id));
}

// Usuarios
const colU: Col[] = [
    { key: 'name', label: 'Nombre', valor: (f) => `${f.name} ${f.email}` },
    { key: 'rol', label: 'Perfil', filtro: 'lista' },
    { key: 'agremiado', label: 'Agremiado vinculado', valor: (f) => f.agremiado?.nombre ?? 'Sin vincular', filtro: 'lista' },
    { key: 'ultimo_acceso', label: 'Último acceso', tipo: 'fechahora', filtro: 'fecha' },
    { key: 'estado', label: 'Estado', filtro: 'lista' },
];
const uVisible = ref(false);
const uReg = ref<any>(null);
const fU = useForm({ name: '', email: '', rol_id: null as number | null, agremiado_id: null as number | null, password: '', password_confirmation: '', activo: true });
function abrirUsuario(u: any = null) {
    uReg.value = u;
    fU.clearErrors();
    fU.defaults({ name: u?.name ?? '', email: u?.email ?? '', rol_id: u?.rol_id ?? null, agremiado_id: u?.agremiado_id ?? null, password: '', password_confirmation: '', activo: u?.activo ?? true });
    fU.reset();
    uVisible.value = true;
}
const opcAg = computed(() => props.agremiados.map((a) => ({ value: a.id, label: `${a.nombre} · ${a.folio}` })));
watch(() => fU.agremiado_id, (id) => { if (id && !fU.name) fU.name = props.agremiados.find((a) => a.id === id)?.nombre ?? ''; });
const rolPortal = computed(() => props.roles.find((r) => r.id === fU.rol_id)?.portal);
</script>

<template>
    <div class="page">
        <PageHeader titulo="Perfiles" desc="Qué puede hacer cada perfil en cada módulo y quién tiene acceso al sistema" :icono="ShieldCheck">
            <div class="tabs">
                <button :class="{ on: tab === 'perfiles' }" @click="tab = 'perfiles'"><UsersRound :size="16" /> Perfiles y permisos</button>
                <button :class="{ on: tab === 'usuarios' }" @click="tab = 'usuarios'"><KeyRound :size="16" /> Usuarios</button>
            </div>
            <template v-if="puede.total">
                <Button v-if="tab === 'perfiles'" class="btn-grad" @click="abrirRol()"><Plus :size="17" /> Nuevo perfil</Button>
                <Button v-else class="btn-grad" @click="abrirUsuario()"><Plus :size="17" /> Nuevo usuario</Button>
            </template>
        </PageHeader>

        <div v-if="tab === 'perfiles'" class="perf">
            <section class="panel">
                <div class="panel-h"><h2><UsersRound :size="18" />Perfiles</h2><span class="sub">{{ roles.length }} perfiles</span></div>
                <div class="perf-list">
                    <button v-for="r in roles" :key="r.id" class="perf-item" :class="{ on: r.id === sel?.id }" @click="selId = r.id">
                        <span class="perf-ico" :style="{ background: r.color }"><component :is="icono(r.icono)" :size="20" /></span>
                        <span style="min-width: 0"><b>{{ r.nombre }}</b><small>{{ r.usuarios }} {{ r.usuarios === 1 ? 'usuario' : 'usuarios' }} · {{ r.portal ? 'App del agremiado' : conAcceso(r) + ' módulos' }}</small></span>
                        <span v-if="r.protegido" class="pill"><Lock :size="11" />Protegido</span>
                        <span v-else-if="r.portal" class="pill teal"><Smartphone :size="11" />App</span>
                    </button>
                </div>
            </section>
            <section v-if="sel" class="panel">
                <div class="perf-head">
                    <span class="perf-ico big" :style="{ background: sel.color }"><component :is="icono(sel.icono)" :size="26" /></span>
                    <div style="min-width: 0"><h2>{{ sel.nombre }}</h2><p>{{ sel.descripcion }}</p></div>
                    <div class="perf-stats">
                        <span class="pill brand"><LayoutGrid :size="12" />{{ conAcceso(sel) }} de {{ modulos.length }} módulos</span>
                        <span class="pill ok"><ShieldCheck :size="12" />{{ total(sel) }} con acceso total</span>
                        <Button v-if="editable" size="small" severity="secondary" outlined @click="abrirRol(sel)"><Pencil :size="14" /> Editar</Button>
                        <Button v-if="editable" size="small" severity="danger" outlined @click="borrarRol(sel)"><Trash2 :size="14" /> Eliminar</Button>
                        <TrazaBoton :traza="sel.traza" tabla="roles" :id="sel.id" :titulo="sel.nombre" />
                    </div>
                </div>
                <div class="scroll">
                    <div v-if="sel.protegido" class="lock"><Lock :size="18" /><span><b>Perfil protegido.</b> El Super usuario siempre tiene acceso total a todos los módulos y sus permisos no se pueden modificar.</span></div>
                    <div v-else-if="sel.portal" class="lock teal"><Smartphone :size="18" /><span><b>Perfil de la app del agremiado.</b> Quienes tienen este perfil entran a su app (credencial, cuotas, tienda, cursos y GPS). Los permisos de abajo solo aplican si además deben ver módulos del panel.</span></div>
                    <div v-else-if="!puede.total" class="lock"><Eye :size="18" /><span>Tu perfil puede consultar los permisos, pero solo un perfil con acceso total a Perfiles los puede cambiar.</span></div>
                    <div class="mods">
                        <div v-for="m in modulos" :key="m.id" class="mod-row">
                            <div class="mi"><span><component :is="icono(m.icono)" :size="18" /></span><div style="min-width: 0"><b>{{ m.nombre }}</b><small>{{ m.desc }}</small></div></div>
                            <div class="seg" role="group" :aria-label="`Permiso en ${m.nombre}`">
                                <button v-for="(l, k) in niveles" :key="k" type="button" :class="[k, { on: sel.permisos[m.id] === k }]" :aria-pressed="sel.permisos[m.id] === k" :disabled="!editable" @click="nivel(m, k as string)">{{ l }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="usu">
                        <h3><Users :size="16" /> Usuarios con este perfil <span class="pill muted">{{ usuariosDe.length }}</span></h3>
                        <div class="chips">
                            <span v-for="u in usuariosDe" :key="u.id" class="pill chip"><Persona :p="u.agremiado ?? { nombre: u.name, color: sel.color }" solo-avatar :size="22" />{{ u.name }}</span>
                            <span v-if="!usuariosDe.length" class="muted">Nadie tiene este perfil todavía. Asígnalo en la pestaña Usuarios o desde la ficha del agremiado.</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section v-else class="panel tabla">
            <TablaSigo :filas="usuarios" :columnas="colU" :clase-fila="(f) => (f.activo ? '' : 'baja')">
                <template #name="{ fila }"><Persona :p="fila.agremiado ? { ...fila.agremiado, nombre: fila.name } : { nombre: fila.name, color: fila.rol_color }" :sub="fila.email" /></template>
                <template #rol="{ fila }"><span class="pill" :style="{ background: fila.rol_color + '1f', color: fila.rol_color }">{{ fila.rol }}</span></template>
                <template #agremiado="{ fila }"><span v-if="fila.agremiado">{{ fila.agremiado.folio }} · {{ fila.agremiado.nombre }}</span><span v-else class="muted">Sin vincular</span></template>
                <template #estado="{ fila }"><span class="pill" :class="fila.activo ? 'ok' : 'bad'">{{ fila.estado }}</span></template>
                <template #acciones="{ fila }">
                    <Acciones :editar="puede.total" :eliminar="puede.total && fila.id !== page.props.auth.user.id" @editar="abrirUsuario(fila)" @eliminar="eliminar(`/usuarios/${fila.id}`, 'al usuario ' + fila.email, 'Perderá el acceso. Se conserva en la bitácora de auditoría.')" />
                </template>
            </TablaSigo>
        </section>

        <Modal v-model="rVisible" :titulo="rReg ? 'Editar perfil' : 'Nuevo perfil'" sub="Define qué puede hacer en cada módulo" :icono="UserCog" ancho="md">
            <form id="fRol" class="form-grid g2" @submit.prevent="guardarRol">
                <Campo label="Nombre del perfil" req :error="fR.errors.nombre"><InputText v-model="fR.nombre" maxlength="120" placeholder="Ej. Tesorero, Promotor deportivo" /></Campo>
                <Campo v-if="!rReg" label="Copiar permisos de" info="Después puedes ajustar cada módulo." :error="fR.errors.base"><Select v-model="fR.base" :options="[{ id: null, nombre: 'Empezar sin accesos' }, ...roles.filter((r) => !r.protegido)]" option-label="nombre" option-value="id" /></Campo>
                <Campo label="Ícono" :error="fR.errors.icono">
                    <Select v-model="fR.icono" :options="ICONOS_PERFIL">
                        <template #value="{ value }"><component :is="icono(value)" :size="16" /></template>
                        <template #option="{ option }"><span style="display: flex; gap: 8px; align-items: center"><component :is="icono(option)" :size="16" />{{ option }}</span></template>
                    </Select>
                </Campo>
                <Campo label="Color" :error="fR.errors.color"><div style="display: flex; gap: 8px; align-items: center"><ColorPicker v-model="fR.color" format="hex" /><span class="mono">{{ fR.color.startsWith('#') ? fR.color : '#' + fR.color }}</span></div></Campo>
                <Campo label="Entra a la app del agremiado" info="Actívalo para perfiles de agremiados: entran a su app en lugar del panel." span="full"><div style="display: flex; gap: 10px; align-items: center"><ToggleSwitch v-model="fR.portal" />{{ fR.portal ? 'Sí' : 'No' }}</div></Campo>
                <Campo label="Descripción" :error="fR.errors.descripcion" span="full"><InputText v-model="fR.descripcion" maxlength="255" placeholder="Qué hace este perfil en la organización" /></Campo>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="rVisible = false" />
                <Button type="submit" form="fRol" class="btn-grad" :loading="fR.processing"><Save :size="16" /> Guardar</Button>
            </template>
        </Modal>

        <Modal v-model="uVisible" :titulo="uReg ? 'Editar usuario' : 'Nuevo usuario'" :sub="uReg?.email ?? 'Cuenta de acceso al sistema'" :icono="KeyRound" ancho="md">
            <form id="fUsu" class="form-grid g2" @submit.prevent="enviar(fU, uReg ? 'put' : 'post', uReg ? `/usuarios/${uReg.id}` : '/usuarios', { exito: () => (uVisible = false) })">
                <Campo label="Agremiado vinculado" :req="rolPortal" info="Obligatorio para perfiles de la app del agremiado." :error="fU.errors.agremiado_id"><Select v-model="fU.agremiado_id" :options="opcAg" option-label="label" option-value="value" filter show-clear placeholder="Sin vincular" /></Campo>
                <Campo label="Nombre" req :error="fU.errors.name"><InputText v-model="fU.name" maxlength="120" /></Campo>
                <Campo label="Correo" req :error="fU.errors.email"><InputText v-model="fU.email" type="email" autocomplete="off" /></Campo>
                <Campo label="Perfil" req :error="fU.errors.rol_id"><Select v-model="fU.rol_id" :options="roles" option-label="nombre" option-value="id" placeholder="— Selecciona —" /></Campo>
                <Campo :label="uReg ? 'Nueva contraseña' : 'Contraseña'" :req="!uReg" info="Mínimo 8 caracteres. En edición déjala vacía para conservar la actual." :error="fU.errors.password"><Password v-model="fU.password" :feedback="false" toggle-mask fluid autocomplete="new-password" /></Campo>
                <Campo label="Confirmar contraseña" :req="!uReg"><Password v-model="fU.password_confirmation" :feedback="false" toggle-mask fluid autocomplete="new-password" /></Campo>
                <Campo label="Cuenta activa"><div style="display: flex; gap: 10px; align-items: center; min-height: 38px"><ToggleSwitch v-model="fU.activo" />{{ fU.activo ? 'Puede iniciar sesión' : 'Sin acceso' }}</div></Campo>
                <Campo v-if="uReg" label="Último acceso"><div class="mono" style="padding: 8px 0">{{ uReg.ultimo_acceso ? fecha(uReg.ultimo_acceso) : 'Nunca' }}</div></Campo>
                <div v-if="uReg" class="full"><TrazaBoton :traza="uReg.traza" tabla="users" :id="uReg.id" :titulo="uReg.email" /></div>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="uVisible = false" />
                <Button type="submit" form="fUsu" class="btn-grad" :loading="fU.processing"><Save :size="16" /> Guardar</Button>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.tabs { display: flex; gap: 6px; }
.tabs button { display: flex; gap: 7px; align-items: center; border: 1px solid var(--line); background: #fff; border-radius: 11px; padding: 8px 13px; font-weight: 700; color: var(--ink-2); cursor: pointer; font-family: inherit; font-size: 13px; }
.tabs button.on { background: var(--grad); color: #fff; border-color: transparent; }
.perf { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(0, 330px) minmax(0, 1fr); gap: 14px; }
.perf > .panel { min-height: 0; overflow: hidden; }
.perf-list { display: flex; flex-direction: column; gap: 8px; padding: 12px; overflow: auto; }
.perf-item { display: flex; align-items: center; gap: 12px; padding: 11px; border: 1px solid var(--line); border-radius: 12px; background: #fff; text-align: left; width: 100%; cursor: pointer; font-family: inherit; transition: all .15s; }
.perf-item:hover { border-color: var(--teal); transform: translateX(2px); }
.perf-item.on { border-color: var(--teal); background: #eef8f8; box-shadow: inset 0 0 0 1px var(--teal); }
.perf-item b { display: block; font-size: 13.5px; } .perf-item small { color: var(--ink-3); font-size: 12px; }
.perf-item .pill { margin-left: auto; }
.perf-ico { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; color: #fff; flex-shrink: 0; }
.perf-ico.big { width: 52px; height: 52px; border-radius: 15px; }
.perf-head { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; padding: 14px 18px; border-bottom: 1px solid var(--line); flex-shrink: 0; }
.perf-head h2 { font-size: 20px; color: var(--brand); }
.perf-head p { margin: 2px 0 0; color: var(--ink-3); }
.perf-stats { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; align-items: center; }
.scroll { flex: 1; min-height: 0; overflow: auto; }
.lock { display: flex; gap: 10px; align-items: center; margin: 12px 18px 0; padding: 11px 14px; border-radius: 12px; background: #fff7e6; border: 1px solid #f5d48a; color: #7a4b00; font-size: 13px; }
.lock.teal { background: #eef8f8; border-color: #9ad9d7; color: #0f5e5c; }
.mods { display: flex; flex-direction: column; padding: 4px 18px 6px; }
.mod-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; align-items: center; padding: 9px 0; border-bottom: 1px solid var(--line); }
.mi { display: flex; gap: 12px; align-items: center; min-width: 0; }
.mi > span { width: 34px; height: 34px; border-radius: 10px; background: var(--brand-soft); display: grid; place-items: center; color: var(--azul); flex-shrink: 0; }
.mi b { display: block; font-size: 13.5px; } .mi small { color: var(--ink-3); font-size: 12px; }
.seg { display: inline-flex; background: var(--surface-2); border: 1px solid var(--line); border-radius: 10px; padding: 3px; gap: 2px; }
.seg button { border: 0; background: transparent; border-radius: 7px; padding: 5px 11px; font-size: 12px; font-weight: 700; color: var(--ink-3); cursor: pointer; font-family: inherit; white-space: nowrap; }
.seg button:hover:not(:disabled) { color: var(--ink); }
.seg button:disabled { cursor: not-allowed; }
.seg button.on.n { background: #fff; color: var(--ink-2); box-shadow: 0 1px 3px rgba(0, 0, 0, .12); }
.seg button.on.v { background: #e5f0fa; color: var(--azul); }
.seg button.on.e { background: var(--warn-soft); color: var(--warn); }
.seg button.on.t { background: var(--teal); color: #fff; }
.usu { padding: 12px 18px 18px; }
.usu h3 { font-size: 14px; color: var(--brand); display: flex; gap: 8px; align-items: center; margin-bottom: 10px; }
.chips { display: flex; gap: 8px; flex-wrap: wrap; }
.chip { padding: 3px 10px 3px 3px; gap: 6px; }
@media (max-width: 1000px) { .perf { grid-template-columns: 1fr; } .mod-row { grid-template-columns: 1fr; } }
</style>
