<script setup lang="ts">
/** Detalle (show) del agremiado: datos, cuotas, incidencias, apoyos y coordinados; trazabilidad en su botón. */
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { Link, router, useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import {
    IdCard, Wallet, ClipboardList, Gift, Network, Pencil, UserX, UserCheck, Hash, Calendar, Vote, Phone, Siren, House, MapPin, Radar, Fingerprint,
    Cake, Receipt, Plus, Save, Smartphone, Loader2, Trash2, KeyRound, Check, TriangleAlert,
} from 'lucide-vue-next';
import Persona from '../Persona.vue';
import Traza from '../Traza.vue';
import TrazaBoton from '../TrazaBoton.vue';
import Campo from '../Campo.vue';
import CampoFecha from '../CampoFecha.vue';
import Evidencias from '../Evidencias.vue';
import Modal from '../Modal.vue';
import { alerta } from '@/alerta';
import { enviar, eliminar } from '@/acciones';
import { dinero, fecha, hoyISO, iniciales, telefono, TONOS } from '@/utils';

const props = defineProps<{ id: number | null; opciones: any; puede: Record<string, boolean> }>();
const visible = defineModel<boolean>({ default: false });
const emit = defineEmits<{ editar: [any]; ver: [number] }>();

const a = ref<any>(null);
const cargando = ref(false);
const tab = ref('datos');

async function cargar() {
    if (!props.id) return;
    cargando.value = true;
    try { a.value = (await axios.get(`/agremiados/${props.id}`)).data; } finally { cargando.value = false; }
}
watch(() => [visible.value, props.id], ([v]) => { if (v) { tab.value = 'datos'; a.value = null; cargar(); } });
defineExpose({ cargar });

const recargar = () => { cargar(); };

// Incidencias
const fInc = useForm({ tipo: 'Observación', descripcion: '', fecha: hoyISO() as string | null });
function guardarInc() {
    enviar(fInc, 'post', `/agremiados/${a.value.id}/incidencias`, { exito: () => { fInc.reset(); recargar(); } });
}

// Baja
const bajaVisible = ref(false);
const fBaja = useForm({ motivo: '', detalle: '' });
function pedirBaja() {
    const activos = a.value.coordinados.filter((c: any) => c.estatus === 'Activo').length;
    if (activos) return alerta.aviso('Primero reasigna a sus coordinados', `${a.value.nombre_completo} coordina a ${activos} ${activos === 1 ? 'persona' : 'personas'}. Reasígnalas a otro coordinador desde Coordinación o desde su ficha.`);
    fBaja.reset();
    bajaVisible.value = true;
}
function confirmarBaja() {
    enviar(fBaja, 'post', `/agremiados/${a.value.id}/baja`, { exito: () => { bajaVisible.value = false; recargar(); } });
}
async function reactivar() {
    if (!(await alerta.confirmar('¿Reactivar agremiado?', `${a.value.nombre_completo} volverá al padrón activo.`, { aceptar: 'Sí, reactivar' }))) return;
    router.post(`/agremiados/${a.value.id}/reactivar`, {}, { preserveScroll: true, preserveState: true, onSuccess: recargar });
}

// Reasignar coordinador
const nuevoCoord = ref<number | null>(null);
watch(a, (v) => (nuevoCoord.value = v?.coordinador_id ?? null));
const opcCoord = computed(() => props.opciones.coordinadores.filter((c: any) => c.id !== a.value?.id).map((c: any) => ({ value: c.id, label: `${c.nombre} · ${c.puesto}` })));
async function reasignar() {
    if (!nuevoCoord.value || nuevoCoord.value === a.value.coordinador_id) return;
    const n = opcCoord.value.find((o: any) => o.value === nuevoCoord.value)?.label;
    if (!(await alerta.confirmar('¿Reasignar coordinador?', `${a.value.nombre_completo} pasará a la red de ${n}.`))) return (nuevoCoord.value = a.value.coordinador_id);
    router.put(`/agremiados/${a.value.id}/coordinador`, { coordinador_id: nuevoCoord.value }, { preserveScroll: true, preserveState: true, onSuccess: recargar, onError: (e) => alerta.errores(e) });
}

const adeudoPill = computed(() => !a.value ? null : a.value.estatus === 'Baja' ? ['bad', 'Baja'] : a.value.adeudo === 0 ? ['ok', 'Al corriente'] : [a.value.adeudo > 2 ? 'bad' : 'warn', `Adeudo ${a.value.adeudo} ${a.value.adeudo === 1 ? 'mes' : 'meses'}`]);
</script>

<template>
    <Dialog v-model:visible="visible" modal :draggable="false" :style="{ width: '1040px', maxWidth: '96vw' }" :content-style="{ maxHeight: '74vh' }">
        <template #header>
            <div v-if="a" class="modal-h">
                <span class="avatar" :style="{ background: a.color, width: '56px', height: '56px', fontSize: '20px' }"><img v-if="a.foto" :src="a.foto" alt="" /><template v-else>{{ iniciales(a.nombre_completo) }}</template></span>
                <div style="min-width: 0">
                    <h2>{{ a.nombre_completo }}</h2>
                    <small>{{ a.folio }} · {{ a.puesto }}{{ a.usuario ? ' · ' + a.usuario.rol : '' }}</small>
                    <div style="display: flex; gap: 6px; margin-top: 4px; flex-wrap: wrap">
                        <span class="pill" :class="adeudoPill![0]">{{ adeudoPill![1] }}</span>
                        <span class="pill" :class="a.gps_autorizado ? 'ok' : 'muted'"><MapPin :size="11" />GPS {{ a.gps_autorizado ? 'autorizado' : 'no autorizado' }}</span>
                        <span v-if="a.usuario" class="pill brand"><KeyRound :size="11" />{{ a.usuario.activo ? 'Con acceso' : 'Acceso inactivo' }}</span>
                    </div>
                </div>
            </div>
            <div v-else class="modal-h"><span class="m-ico"><Loader2 class="gira" :size="22" /></span><h2>Cargando ficha…</h2></div>
        </template>

        <div v-if="a" class="ficha">
            <div v-if="a.baja" class="baja-box">
                <b><UserX :size="16" /> Agremiado dado de baja</b>
                <span>Motivo: {{ a.baja.motivo }}{{ a.baja.detalle ? ' · ' + a.baja.detalle : '' }}</span>
                <Traza :t="{ usuario: a.baja.usuario, fecha: a.baja.fecha }" verbo="Baja por" />
            </div>
            <div class="botones">
                <Button v-if="puede.editar && !a.baja" size="small" @click="emit('editar', a)"><Pencil :size="15" /> Editar datos</Button>
                <Button v-if="puede.editar && !a.baja && a.coordinador_id" size="small" severity="danger" outlined @click="pedirBaja"><UserX :size="15" /> Dar de baja</Button>
                <Button v-if="puede.editar && a.baja" size="small" severity="success" outlined @click="reactivar"><UserCheck :size="15" /> Reactivar</Button>
                <Link :href="`/app?agremiado=${a.id}`" class="p-button p-button-sm p-button-secondary p-button-outlined" style="text-decoration: none; display: inline-flex; gap: 6px; align-items: center"><Smartphone :size="15" /> Ver su app</Link>
                <span style="margin-left: auto"><TrazaBoton :traza="a.traza" tabla="agremiados" :id="a.id" :titulo="`${a.folio} · ${a.nombre_completo}`" /></span>
            </div>

            <Tabs v-model:value="tab">
                <TabList>
                    <Tab value="datos"><IdCard :size="15" /> Datos</Tab>
                    <Tab value="cuotas"><Wallet :size="15" /> Cuotas</Tab>
                    <Tab value="incidencias"><ClipboardList :size="15" /> Incidencias <span class="pill muted">{{ a.incidencias.length }}</span></Tab>
                    <Tab value="apoyos"><Gift :size="15" /> Apoyos <span class="pill muted">{{ a.apoyos.length }}</span></Tab>
                    <Tab value="red"><Network :size="15" /> Coordinados <span class="pill muted">{{ a.coordinados.length }}</span></Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="datos">
                        <dl class="dl">
                            <div><dt><Hash :size="12" />Folio</dt><dd class="mono">{{ a.folio }}</dd></div>
                            <div><dt><Calendar :size="12" />Fecha de ingreso</dt><dd>{{ fecha(a.fecha_ingreso) }}</dd></div>
                            <div><dt><Cake :size="12" />Nacimiento · sexo</dt><dd>{{ fecha(a.fecha_nacimiento) }} · {{ a.sexo }}</dd></div>
                            <div><dt><Fingerprint :size="12" />CURP</dt><dd class="mono">{{ a.curp }}</dd></div>
                            <div><dt><IdCard :size="12" />Clave de elector</dt><dd class="mono">{{ a.clave_elector }}</dd></div>
                            <div><dt><Vote :size="12" />Sección electoral</dt><dd class="mono">{{ a.seccion }}</dd></div>
                            <div><dt><Phone :size="12" />Celular</dt><dd class="mono">{{ telefono(a.celular) }}</dd></div>
                            <div class="s2" style="grid-column: span 2"><dt><Siren :size="12" />Contacto de emergencia</dt><dd>{{ a.emergencia_nombre }} · <span class="mono">{{ telefono(a.emergencia_telefono) }}</span></dd></div>
                            <div style="grid-column: span 2"><dt><House :size="12" />Domicilio</dt><dd>{{ a.calle }}, {{ a.colonia }}, C.P. {{ a.cp }}, {{ a.municipio }}, {{ a.estado }}</dd></div>
                            <div><dt><MapPin :size="12" />Ubicación del domicilio</dt><dd class="mono">{{ a.latitud ? `${a.latitud}, ${a.longitud}` : '—' }}</dd></div>
                            <div><dt><Radar :size="12" />Seguimiento GPS</dt><dd><span class="pill" :class="a.gps_autorizado ? 'ok' : 'muted'">{{ a.gps_autorizado ? 'Autorizado' : 'No autorizado' }}</span><br /><Traza v-if="a.gps_traza" :t="a.gps_traza" /></dd></div>
                            <div style="grid-column: span 2"><dt><Network :size="12" />Coordinador asignado</dt>
                                <dd v-if="!a.coordinador_id">Cabeza de la red</dd>
                                <dd v-else-if="puede.red && !a.baja" style="display: flex; gap: 8px"><Select v-model="nuevoCoord" :options="opcCoord" option-label="label" option-value="value" filter style="flex: 1" size="small" /><Button size="small" :disabled="nuevoCoord === a.coordinador_id" @click="reasignar"><Check :size="15" /> Reasignar</Button></dd>
                                <dd v-else><Persona :p="a.coordinador" /></dd>
                            </div>
                            <div class="full"><dt>Fotografías</dt>
                                <dd class="evid" style="margin-top: 6px">
                                    <a v-for="[k, t] in [['foto', 'Rostro'], ['ine_frente', 'INE frente'], ['ine_reverso', 'INE reverso']]" :key="k" class="item" :href="a[k] || undefined" target="_blank" :title="t" :style="{ width: '120px', height: '84px', background: a[k] ? '#000' : 'var(--surface-2)', color: 'var(--ink-3)' }">
                                        <img v-if="a[k]" :src="a[k]" :alt="t" /><span v-else style="font-size: 11px; text-align: center"><TriangleAlert :size="16" /><br />Falta {{ t }}</span>
                                    </a>
                                </dd>
                            </div>
                        </dl>
                    </TabPanel>

                    <TabPanel value="cuotas">
                        <div class="card" style="background: var(--surface-2)">
                            <div class="row"><b>Estado de cuotas</b><span class="pill" :class="adeudoPill![0]">{{ adeudoPill![1] }}</span></div>
                            <small class="muted">Cuota ordinaria {{ dinero(a.cuota) }} mensual · meses pendientes: {{ a.pendientes.map((p: any) => p.label).join(', ') || 'ninguno' }}</small>
                        </div>
                        <div class="lista">
                            <div v-for="c in a.cuotas" :key="c.id" class="card">
                                <div class="row"><span><Receipt :size="14" /> Cuota de {{ c.periodo }} · <span class="muted">{{ c.forma_pago }}</span></span><b class="num">{{ dinero(c.monto) }}</b></div>
                                <Traza :t="c.traza.creado" verbo="Registró" />
                            </div>
                            <p v-if="!a.cuotas.length" class="empty">Sin pagos registrados.</p>
                        </div>
                    </TabPanel>

                    <TabPanel value="incidencias">
                        <div class="dos">
                            <div class="lista">
                                <div v-for="i in a.incidencias" :key="i.id" class="card">
                                    <div class="row"><span class="pill" :class="TONOS[i.tipo] ?? 'brand'">{{ i.tipo }}</span><span class="mono muted">{{ fecha(i.fecha) }}</span>
                                        <button v-if="puede.eliminar" class="borrar" aria-label="Eliminar incidencia" @click="eliminar(`/incidencias/${i.id}`, 'la incidencia', undefined, recargar)"><Trash2 :size="14" /></button></div>
                                    <div>{{ i.descripcion }}</div>
                                    <Traza :t="i.traza.creado" verbo="Registró" />
                                </div>
                                <p v-if="!a.incidencias.length" class="empty">Sin incidencias.</p>
                            </div>
                            <form v-if="puede.editar" class="card nueva" @submit.prevent="guardarInc">
                                <b><Plus :size="15" /> Agregar incidencia</b>
                                <Campo label="Tipo" req info="Elige de la lista o escribe otro tipo." :error="fInc.errors.tipo"><Select v-model="fInc.tipo" :options="opciones.tiposIncidencia" editable /></Campo>
                                <Campo label="Fecha" req :error="fInc.errors.fecha"><CampoFecha v-model="fInc.fecha" :max="hoyISO()" :min="a.fecha_ingreso" /></Campo>
                                <Campo label="Descripción" req :error="fInc.errors.descripcion"><Textarea v-model="fInc.descripcion" rows="3" auto-resize placeholder="Qué ocurrió…" /></Campo>
                                <Button type="submit" size="small" :loading="fInc.processing"><Save :size="15" /> Guardar incidencia</Button>
                                <small class="muted">Se guarda con tu usuario, fecha y hora.</small>
                            </form>
                        </div>
                    </TabPanel>

                    <TabPanel value="apoyos">
                        <div class="lista">
                            <div v-for="p in a.apoyos" :key="p.id" class="card">
                                <div class="row"><b>{{ p.tipo }}</b><b class="num">{{ dinero(p.monto) }}</b></div>
                                <small>{{ p.detalle }} · entregado el <span class="mono">{{ fecha(p.fecha) }}</span></small>
                                <Traza :t="p.traza.creado" verbo="Entregó" />
                                <Evidencias :archivos="p.archivos" sm />
                            </div>
                            <p v-if="!a.apoyos.length" class="empty">No ha recibido apoyos.</p>
                        </div>
                    </TabPanel>

                    <TabPanel value="red">
                        <div class="lista g2">
                            <button v-for="c in a.coordinados" :key="c.id" class="card clic" @click="emit('ver', c.id)"><Persona :p="c" /><span v-if="c.estatus === 'Baja'" class="pill bad">Baja</span></button>
                            <p v-if="!a.coordinados.length" class="empty">No coordina a nadie todavía.</p>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
        <div v-else class="empty"><Loader2 class="gira" :size="22" /></div>
    </Dialog>

    <Modal v-model="bajaVisible" titulo="Dar de baja" :sub="a?.nombre_completo" :icono="UserX" color="var(--bad)" ancho="sm">
        <form id="fBaja" style="display: flex; flex-direction: column; gap: 12px" @submit.prevent="confirmarBaja">
            <Campo label="Motivo" req info="Elige de la lista o escribe otro motivo." :error="fBaja.errors.motivo"><Select v-model="fBaja.motivo" :options="opciones.motivosBaja" editable placeholder="Elige o escribe" /></Campo>
            <Campo label="Detalle" :error="fBaja.errors.detalle"><Textarea v-model="fBaja.detalle" rows="3" placeholder="Ej. número de oficio, fecha efectiva…" /></Campo>
            <small class="muted">El registro no se borra: queda en el padrón como baja, con su historial completo. Su cuenta de acceso y su seguimiento GPS se desactivan.</small>
        </form>
        <template #pie>
            <Button label="Cancelar" severity="secondary" text @click="bajaVisible = false" />
            <Button type="submit" form="fBaja" severity="danger" :loading="fBaja.processing"><UserX :size="16" /> Confirmar baja</Button>
        </template>
    </Modal>
</template>

<style scoped>
.ficha { display: flex; flex-direction: column; gap: 12px; }
.botones { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.baja-box { display: flex; flex-direction: column; gap: 4px; padding: 12px; border-radius: 12px; background: var(--bad-soft); color: #8a1f1f; }
.baja-box b { color: var(--bad); display: flex; gap: 6px; align-items: center; }
.lista { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
.lista.g2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.dos { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 14px; align-items: start; }
.nueva { background: var(--surface-2); margin-top: 10px; gap: 10px; }
.clic { cursor: pointer; text-align: left; font-family: inherit; flex-direction: row; justify-content: space-between; align-items: center; }
.clic:hover { border-color: var(--teal); }
.borrar { border: 0; background: var(--bad-soft); color: var(--bad); border-radius: 8px; width: 26px; height: 26px; display: grid; place-items: center; cursor: pointer; margin-left: 6px; }
.gira { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
:deep(.p-tab) { display: flex; gap: 6px; align-items: center; font-size: 13.5px; padding: 10px 14px; }
@media (max-width: 800px) { .dos, .lista.g2 { grid-template-columns: 1fr; } }
</style>
