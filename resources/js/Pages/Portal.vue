<script setup lang="ts">
/**
 * App del agremiado. El agremiado la ve a pantalla completa en su celular; el personal con acceso al padrón
 * la ve dentro del panel, en un marco de celular, como vista previa (solo lectura) de cualquier agremiado.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { Head, router, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Select from 'primevue/select';
import Button from 'primevue/button';
import ToggleSwitch from 'primevue/toggleswitch';
import ProgressBar from 'primevue/progressbar';
import {
    House, Wallet, ShoppingCart, GraduationCap, UserRound, Network, Gift, ClipboardList, Receipt, Plus, Minus, Check, MapPin, Phone, Siren, Map, Vote, IdCard,
    LogOut, Smartphone, ExternalLink, Radar, Info, Package,
} from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import AlertaDulce from '@/Components/AlertaDulce.vue';
import Persona from '@/Components/Persona.vue';
import Traza from '@/Components/Traza.vue';
import { icono } from '@/iconos';
import { alerta } from '@/alerta';
import { dinero, fecha, iniciales, telefono, TONOS } from '@/utils';

defineOptions({ layout: null });
const props = defineProps<{
    vistaPrevia: boolean; opcionesVista: any[]; yo: any; cuota: number; adeudo: number; estadoCuenta: any[]; apoyos: any[]; incidencias: any[];
    productos: any[]; pedidos: any[]; formasPago: string[]; cursos: any[]; actividad: string | null;
}>();
const page = usePage<any>();
const toast = useToast();

const t = ref('inicio');
const NAV = [['inicio', House, 'Inicio'], ['cuotas', Wallet, 'Cuotas'], ['tienda', ShoppingCart, 'Tienda'], ['cursos', GraduationCap, 'Cursos'], ['perfil', UserRound, 'Perfil']] as const;
const TITULO: Record<string, string> = { cuotas: 'Mis cuotas', tienda: 'Tienda', cursos: 'Mis cursos', perfil: 'Mi perfil' };
const titulo = computed(() => (t.value === 'inicio' ? `Hola, ${props.yo.nombre.split(' ')[0]}` : TITULO[t.value]));
const pill = computed(() => (props.adeudo === 0 ? ['ok', 'Al corriente'] : [props.adeudo > 2 ? 'bad' : 'warn', `Adeudo ${props.adeudo} ${props.adeudo === 1 ? 'mes' : 'meses'}`]));

// Avisos para el agremiado (en vista previa los muestra el panel)
watch(() => page.props.flash, (f: any) => {
    if (props.vistaPrevia) return;
    if (f?.success) toast.add({ severity: 'success', summary: f.success, life: 4000 });
    if (f?.error) alerta.error('No se pudo completar', f.error);
}, { deep: true });

// ------------------------------------------------------------ tienda
const carrito = ref<Record<number, number>>({});
const forma = ref(props.formasPago[0]);
const items = computed(() => Object.entries(carrito.value).filter(([, n]) => n > 0).map(([id, n]) => ({ p: props.productos.find((x) => x.id === +id), n })));
const total = computed(() => items.value.reduce((s, it) => s + (it.p?.precio ?? 0) * it.n, 0));
const piezas = computed(() => items.value.reduce((s, it) => s + it.n, 0));
function mas(p: any, d: number) {
    const n = Math.max(0, (carrito.value[p.id] ?? 0) + d);
    if (p.stock !== null && n > p.stock) return toast.add({ severity: 'warn', summary: `Solo hay ${p.stock} disponibles`, life: 2500 });
    carrito.value = { ...carrito.value, [p.id]: n };
}
async function comprar() {
    if (props.vistaPrevia) return alerta.aviso('Vista previa', 'El pedido solo lo puede hacer el agremiado desde su cuenta.');
    const det = items.value.map((it) => `${it.p.nombre} × ${it.n}`).join('\n');
    if (!(await alerta.confirmar('¿Confirmar pedido?', `${det}\n\nTotal ${dinero(total.value)} · ${forma.value}`, { aceptar: 'Sí, pedir' }))) return;
    router.post('/app/pedido', { forma_pago: forma.value, items: items.value.map((it) => ({ producto_id: it.p.id, cantidad: it.n })) }, {
        preserveScroll: true, onSuccess: () => (carrito.value = {}), onError: (e) => alerta.errores(e),
    });
}

// ------------------------------------------------------------ GPS
const gps = ref(props.yo.gps);
watch(() => props.yo.gps, (v) => (gps.value = v));
async function cambiarGps(v: boolean) {
    if (props.vistaPrevia) { gps.value = !v; return alerta.aviso('Vista previa', 'Solo el agremiado puede cambiar su autorización.'); }
    const ok = await alerta.confirmar(v ? '¿Autorizar seguimiento GPS?' : '¿Desactivar seguimiento GPS?', v ? 'Tu ubicación solo se compartirá durante actividades con seguimiento, por tu seguridad. Puedes desactivarlo cuando quieras.' : 'Dejarás de compartir tu ubicación, incluso en actividades activas.', { aceptar: v ? 'Sí, autorizar' : 'Sí, desactivar' });
    if (!ok) return (gps.value = !v);
    router.post('/app/gps', { autorizado: v }, { preserveScroll: true });
}
// Mientras participa en una actividad con seguimiento, la app envía su ubicación
let vigia: number | null = null;
let ultimo = 0;
const enviando = ref(false);
function iniciarSeguimiento() {
    if (props.vistaPrevia || !props.actividad || !gps.value || !navigator.geolocation || vigia !== null) return;
    vigia = navigator.geolocation.watchPosition((p) => {
        if (Date.now() - ultimo < 15000) return;
        ultimo = Date.now();
        axios.post('/app/ubicacion', { lat: p.coords.latitude, lng: p.coords.longitude, precision: p.coords.accuracy }).then(() => (enviando.value = true)).catch(() => {});
    }, () => (enviando.value = false), { enableHighAccuracy: true, maximumAge: 10000 });
}
onMounted(iniciarSeguimiento);
watch(() => [props.actividad, gps.value], iniciarSeguimiento);
onBeforeUnmount(() => vigia !== null && navigator.geolocation.clearWatch(vigia));

const verComo = (id: number) => router.get('/app', { agremiado: id }, { preserveState: false });
async function salir() { if (await alerta.confirmar('¿Cerrar sesión?', '', { aceptar: 'Sí, salir' })) router.post('/logout'); }
</script>

<template>
    <Head title="App del agremiado" />
    <component :is="vistaPrevia ? AppLayout : 'div'" :class="{ 'solo-app': !vistaPrevia }">
        <div :class="vistaPrevia ? 'page escenario' : 'app-full'">
            <div :class="vistaPrevia ? 'phone' : 'phone full'">
                <div class="phone-top">
                    <div class="row" style="align-items: flex-start">
                        <img class="app-logo" src="/img/sigo-logo.png" alt="SIGO" />
                        <button v-if="!vistaPrevia" class="salir" aria-label="Cerrar sesión" @click="salir"><LogOut :size="16" /></button>
                    </div>
                    <small>{{ yo.folio }} · {{ yo.puesto }}</small>
                    <b>{{ titulo }}</b>
                </div>
                <div :key="t" class="phone-body anim">
                    <!-- INICIO -->
                    <template v-if="t === 'inicio'">
                        <div class="gafete">
                            <div class="foto"><img v-if="yo.foto" :src="yo.foto" alt="" /><template v-else>{{ iniciales(yo.nombre) }}</template></div>
                            <div style="min-width: 0; position: relative; z-index: 1">
                                <small style="opacity: .8; font-size: 10.5px; letter-spacing: .08em">CREDENCIAL DIGITAL</small>
                                <b>{{ yo.nombre }}</b>
                                <div class="meta"><span>{{ yo.puesto }}</span><span>Sección {{ yo.seccion }} · {{ yo.municipio }}</span><span>Agremiado desde {{ fecha(yo.ingreso) }}</span></div>
                                <span class="folio">{{ yo.folio }}</span>
                            </div>
                        </div>
                        <div v-if="actividad" class="card aviso"><b><Radar :size="16" /> Actividad con seguimiento</b><small>{{ actividad }} · {{ gps ? (enviando ? 'compartiendo tu ubicación' : 'esperando tu ubicación…') : 'GPS desactivado' }}</small></div>
                        <div class="card"><div class="row"><b><Wallet :size="16" />Mis cuotas</b><span class="pill" :class="pill[0]">{{ pill[1] }}</span></div><small class="muted">Cuota mensual {{ dinero(cuota) }}{{ adeudo ? ` · debes ${dinero(adeudo * cuota)}` : '' }}</small></div>
                        <div v-if="yo.coordinador" class="card"><b><Network :size="16" />Mi coordinador</b><Persona :p="yo.coordinador" :sub="telefono(yo.coordinador.celular)" /></div>
                        <div class="card"><b><Gift :size="16" />Apoyos recibidos</b>
                            <div v-for="(a, i) in apoyos" :key="i" class="sub-item"><div class="row"><b>{{ a.tipo }}</b><span class="num">{{ dinero(a.monto) }}</span></div><small class="muted">{{ a.detalle }}</small><Traza :t="a.traza" verbo="Entregó" /></div>
                            <small v-if="!apoyos.length" class="muted">Aún no has recibido apoyos.</small>
                        </div>
                        <div class="card"><b><ClipboardList :size="16" />Incidencias</b>
                            <div v-for="(i, k) in incidencias" :key="k" class="sub-item"><span class="pill" :class="TONOS[i.tipo] ?? 'brand'">{{ i.tipo }}</span><div>{{ i.descripcion }}</div><Traza :t="i.traza" verbo="Registró" /></div>
                            <small v-if="!incidencias.length" class="muted">Sin incidencias registradas.</small>
                        </div>
                    </template>

                    <!-- CUOTAS -->
                    <template v-else-if="t === 'cuotas'">
                        <div class="card"><div class="row"><b><Receipt :size="16" />Estado de cuenta</b><span class="pill" :class="pill[0]">{{ pill[1] }}</span></div><small class="muted">Últimos 12 meses desde tu ingreso el {{ fecha(yo.ingreso) }}</small></div>
                        <div v-for="m in estadoCuenta" :key="m.clave" class="card">
                            <div class="row"><span>{{ m.label }}</span><span class="num">{{ dinero(cuota) }}</span><span class="pill" :class="m.pagado ? 'ok' : 'bad'"><Check v-if="m.pagado" :size="11" />{{ m.pagado ? 'Pagada' : 'Pendiente' }}</span></div>
                            <Traza v-if="m.pagado" :t="m.traza" verbo="Registró" />
                        </div>
                        <small class="muted" style="text-align: center">Paga en tesorería o por descuento vía nómina.</small>
                    </template>

                    <!-- TIENDA -->
                    <template v-else-if="t === 'tienda'">
                        <div v-for="p in productos" :key="p.id" class="card">
                            <div class="row">
                                <div class="person"><span class="avatar" :style="{ background: p.color, width: '40px', height: '40px' }"><img v-if="p.foto" :src="p.foto" alt="" /><component :is="icono(p.icono)" v-else :size="18" /></span>
                                    <div class="txt"><b>{{ p.nombre }}</b><small>{{ dinero(p.precio) }}{{ p.stock !== null ? ` · ${p.stock} disponibles` : ' · servicio' }}</small></div></div>
                                <div class="cant">
                                    <button v-if="carrito[p.id]" aria-label="Quitar uno" @click="mas(p, -1)"><Minus :size="14" /></button>
                                    <span v-if="carrito[p.id]">{{ carrito[p.id] }}</span>
                                    <button :disabled="p.stock === 0" :aria-label="`Agregar ${p.nombre}`" @click="mas(p, 1)"><Plus :size="14" /></button>
                                </div>
                            </div>
                            <small class="muted">{{ p.descripcion }}</small>
                        </div>
                        <div v-if="pedidos.length" class="card"><b><Package :size="16" />Mis pedidos</b>
                            <div v-for="p in pedidos" :key="p.folio" class="sub-item"><div class="row"><span class="mono">{{ p.folio }}</span><span class="pill" :class="TONOS[p.estado]">{{ p.estado }}</span></div><small class="muted">{{ p.resumen }} · {{ dinero(p.total) }} · {{ fecha(p.fecha) }}</small></div>
                        </div>
                        <div class="card carrito">
                            <div class="row"><b><ShoppingCart :size="16" />Carrito ({{ piezas }})</b><b class="num">{{ dinero(total) }}</b></div>
                            <Select v-model="forma" :options="formasPago" size="small" />
                            <Button class="btn-grad" :disabled="!piezas" @click="comprar"><Check :size="16" /> Confirmar pedido</Button>
                        </div>
                    </template>

                    <!-- CURSOS -->
                    <template v-else-if="t === 'cursos'">
                        <div class="card"><b><GraduationCap :size="16" />Capacitación SAIS</b><small class="muted">Tus cursos asignados por la organización.</small></div>
                        <div v-for="c in cursos" :key="c.id" class="card">
                            <div class="row"><b>{{ c.nombre }}</b><span class="pill" :class="c.avance >= 100 ? 'ok' : c.avance ? 'brand' : 'muted'">{{ c.avance >= 100 ? 'Concluido' : c.avance ? 'En curso' : 'Nuevo' }}</span></div>
                            <ProgressBar :value="c.avance" :show-value="false" style="height: 6px" />
                            <small class="muted">{{ c.horas }} h · {{ c.modalidad }} · {{ c.avance }}% completado{{ c.fecha_limite ? ' · límite ' + fecha(c.fecha_limite) : '' }}</small>
                            <Traza :t="c.asigno" verbo="Asignó" />
                            <a v-if="c.enlace" :href="c.enlace" target="_blank" class="link"><ExternalLink :size="14" /> {{ c.avance ? 'Continuar' : 'Empezar' }} en SAIS</a>
                        </div>
                        <small v-if="!cursos.length" class="muted" style="text-align: center">No tienes cursos asignados.</small>
                    </template>

                    <!-- PERFIL -->
                    <template v-else>
                        <div class="card"><b><IdCard :size="16" />Mis datos</b>
                            <dl class="dl g2">
                                <div><dt><Phone :size="11" />Celular</dt><dd class="mono">{{ telefono(yo.celular) }}</dd></div>
                                <div><dt><Siren :size="11" />Emergencia</dt><dd>{{ yo.emergencia }}</dd></div>
                                <div class="full"><dt><House :size="11" />Domicilio</dt><dd>{{ yo.domicilio }}</dd></div>
                                <div><dt><Map :size="11" />Municipio</dt><dd>{{ yo.municipio }}, {{ yo.estado }}</dd></div>
                                <div><dt><Vote :size="11" />Sección INE</dt><dd class="mono">{{ yo.seccion }}</dd></div>
                                <div class="full"><dt><IdCard :size="11" />Clave de elector</dt><dd class="mono">{{ yo.clave_elector }}</dd></div>
                            </dl>
                        </div>
                        <div class="card">
                            <div class="row"><div><b><MapPin :size="16" />Seguimiento GPS por seguridad</b><small class="muted">Solo durante actividades que lo requieran. Puedes desactivarlo cuando quieras.</small></div>
                                <ToggleSwitch v-model="gps" @update:model-value="cambiarGps" /></div>
                            <Traza v-if="yo.gps_traza" :t="yo.gps_traza" verbo="Autorizado por" />
                        </div>
                    </template>
                </div>
                <nav class="phone-nav">
                    <button v-for="[k, ic, l] in NAV" :key="k" :class="{ on: t === k }" :aria-current="t === k ? 'page' : undefined" @click="t = k">
                        <component :is="ic" :size="20" />{{ l }}<span v-if="k === 'tienda' && piezas" class="dot">{{ piezas }}</span>
                    </button>
                </nav>
            </div>

            <section v-if="vistaPrevia" class="panel lado">
                <div class="panel-h"><h2><Smartphone :size="18" />App del agremiado</h2></div>
                <div class="panel-b" style="display: flex; flex-direction: column; gap: 12px">
                    <p style="margin: 0">Así ve la app <b>{{ yo.nombre }}</b>: credencial digital, cuotas desde su ingreso, apoyos e incidencias con quién y cuándo, tienda, cursos SAIS y su autorización de GPS.</p>
                    <label class="muted" style="font-size: 12px; font-weight: 700">Ver la app de</label>
                    <Select :model-value="yo.id" :options="opcionesVista" option-label="nombre" option-value="id" filter @update:model-value="verComo" />
                    <p class="nota"><Info :size="14" /> Vista previa de solo lectura. Los pedidos y el cambio de GPS los hace el agremiado desde su cuenta y quedan en la bitácora.</p>
                </div>
            </section>
        </div>
        <template v-if="!vistaPrevia"><Toast position="top-right" /><AlertaDulce /></template>
    </component>
</template>

<style scoped>
.escenario { display: grid; grid-template-columns: minmax(0, 390px) minmax(0, 1fr); gap: 24px; align-items: start; overflow: hidden; }
.lado { max-width: 520px; }
.nota { margin: 0; font-size: 12px; color: var(--ink-3); display: flex; gap: 6px; }
.app-full { height: 100dvh; display: flex; justify-content: center; background: linear-gradient(180deg, #e9f0f6, #fff); }
.phone { width: 100%; max-width: 390px; border-radius: 38px; border: 10px solid #0b1215; background: var(--surface-2); box-shadow: var(--shadow-up); overflow: hidden; display: flex; flex-direction: column; height: 100%; max-height: calc(100dvh - var(--nav-h) - 40px); }
.phone.full { max-width: 480px; border: 0; border-radius: 0; max-height: none; }
.phone-top { background: var(--grad); color: #fff; padding: 12px 16px 14px; display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.phone-top small { opacity: .82; font-size: 11px; }
.phone-top b { font-family: var(--font-display); font-size: 20px; }
.app-logo { height: 36px; background: #fff; border-radius: 9px; padding: 3px 7px; margin-bottom: 4px; }
.salir { border: 0; background: rgba(255, 255, 255, .18); color: #fff; width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; cursor: pointer; }
.phone-body { flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 12px; }
.phone-body > * { flex-shrink: 0; }
.phone-nav { display: grid; grid-template-columns: repeat(5, 1fr); border-top: 1px solid var(--line); background: #fff; flex-shrink: 0; }
.phone-nav button { border: 0; background: transparent; padding: 9px 2px 11px; font-size: 10.5px; color: var(--ink-3); display: flex; flex-direction: column; align-items: center; gap: 3px; cursor: pointer; font-family: inherit; position: relative; font-weight: 600; }
.phone-nav button.on { color: var(--brand); font-weight: 800; }
.phone-nav button.on .lucide { transform: translateY(-2px); color: var(--teal); }
.phone-nav .dot { position: absolute; top: 4px; right: 22%; background: var(--naranja); color: #fff; border-radius: 99px; font-size: 10px; padding: 0 5px; }
.card > b { display: flex; align-items: center; gap: 6px; }
.sub-item { display: flex; flex-direction: column; gap: 3px; padding: 8px 0; border-top: 1px dashed var(--line); }
.gafete { border-radius: 16px; overflow: hidden; background: var(--grad); color: #fff; padding: 16px; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 14px; position: relative; animation: pop .45s ease both; }
.gafete::after { content: ""; position: absolute; right: -30px; top: -30px; width: 140px; height: 140px; border-radius: 50%; border: 18px solid rgba(255, 255, 255, .07); }
.gafete .foto { width: 72px; height: 88px; border-radius: 9px; background: rgba(255, 255, 255, .18); display: grid; place-items: center; font-family: var(--font-display); font-size: 26px; font-weight: 800; overflow: hidden; }
.gafete .foto img { width: 100%; height: 100%; object-fit: cover; }
.gafete b { font-family: var(--font-display); font-size: 17px; display: block; line-height: 1.15; }
.gafete .meta { font-size: 11.5px; opacity: .88; display: grid; gap: 1px; margin-top: 6px; }
.gafete .folio { font-family: var(--font-mono); font-size: 12px; background: rgba(0, 0, 0, .22); padding: 2px 7px; border-radius: 5px; display: inline-block; margin-top: 6px; }
.aviso { background: #fdf0e2; border-color: #f5c99a; }
.aviso b { color: #b8600f; }
.cant { display: flex; align-items: center; gap: 6px; }
.cant button { width: 30px; height: 30px; border-radius: 9px; border: 1px solid var(--line); background: #fff; display: grid; place-items: center; cursor: pointer; color: var(--brand); }
.cant button:disabled { opacity: .4; }
.cant span { font-weight: 800; min-width: 16px; text-align: center; }
.carrito { position: sticky; bottom: 0; box-shadow: var(--shadow-up); gap: 8px; }
.link { display: inline-flex; gap: 6px; align-items: center; color: var(--azul); font-weight: 700; text-decoration: none; font-size: 13px; }
@media (max-width: 1000px) { .escenario { grid-template-columns: 1fr; justify-items: center; overflow: auto; } .phone { max-height: 720px; } }
</style>
