<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { router, useForm } from '@inertiajs/vue3';
import L from 'leaflet';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import SelectButton from 'primevue/selectbutton';
import { MapPin, Radar, Activity, Play, Square, Signal, Crosshair, History } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Traza from '@/Components/Traza.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import { enviar, confirmarYEnviar } from '@/acciones';
import { iniciales } from '@/utils';

const props = defineProps<{ actividades: any[]; historial: any[]; disponibles: any[]; servidor: string; puede: { editar: boolean } }>();

const vista = ref('vivo');
const actividades = ref(props.actividades);
watch(() => props.actividades, (v) => { actividades.value = v; pintar(); });

// ------------------------------------------------------------ mapa (OpenStreetMap)
const mapaEl = ref<HTMLElement>();
let mapa: L.Map | null = null;
let capa: L.LayerGroup | null = null;
const marcadores = new Map<number, L.Marker>();

function icono(p: any) {
    const a = p.agremiado;
    return L.divIcon({
        className: 'pin-sigo',
        html: `<div class="pin-in" style="--c:${a.color}"><span>${a.foto ? `<img src="${a.foto}">` : iniciales(a.nombre)}</span><i></i></div>`,
        iconSize: [40, 52], iconAnchor: [20, 50], popupAnchor: [0, -46],
    });
}
const conPos = computed(() => actividades.value.flatMap((a) => a.participantes.filter((p: any) => p.lat && p.lng).map((p: any) => ({ ...p, actividad: a.nombre }))));

function pintar(ajustar = false) {
    if (!mapa || !capa) return;
    const vivos = new Set<number>();
    conPos.value.forEach((p: any) => {
        vivos.add(p.id);
        const txt = `<b>${p.agremiado.nombre}</b><br>${p.actividad}<br><small>Última señal: ${p.hace ?? 'sin señal todavía (domicilio)'}</small>`;
        const m = marcadores.get(p.id);
        if (m) { m.setLatLng([p.lat, p.lng]); m.setPopupContent(txt); }
        else marcadores.set(p.id, L.marker([p.lat, p.lng], { icon: icono(p) }).bindPopup(txt).addTo(capa!));
    });
    [...marcadores.keys()].filter((k) => !vivos.has(k)).forEach((k) => { capa!.removeLayer(marcadores.get(k)!); marcadores.delete(k); });
    if (ajustar) centrar();
}
function centrar() {
    if (!mapa) return;
    const pts = conPos.value.map((p: any) => [p.lat, p.lng] as [number, number]);
    pts.length ? mapa.fitBounds(L.latLngBounds(pts).pad(0.3), { maxZoom: 15 }) : mapa.setView([20.1011, -98.7591], 11);
}
function enfocar(p: any) {
    if (!mapa || !p.lat) return;
    mapa.setView([p.lat, p.lng], 16);
    marcadores.get(p.id)?.openPopup();
}

let timer: ReturnType<typeof setInterval> | undefined;
onMounted(async () => {
    await nextTick();
    mapa = L.map(mapaEl.value!, { zoomControl: true }).setView([20.1011, -98.7591], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(mapa);
    capa = L.layerGroup().addTo(mapa);
    pintar(true);
    // Posiciones en vivo cada 8 segundos
    timer = setInterval(async () => {
        if (document.hidden || vista.value !== 'vivo') return;
        try { actividades.value = (await axios.get('/gps/posiciones')).data.actividades; pintar(); } catch { /* reintenta en el siguiente ciclo */ }
    }, 8000);
});
onBeforeUnmount(() => { clearInterval(timer); mapa?.remove(); });
watch(vista, async (v) => { if (v === 'vivo') { await nextTick(); mapa?.invalidateSize(); } });

// ------------------------------------------------------------ nueva actividad
const visible = ref(false);
const form = useForm({ nombre: '', participantes: [] as number[] });
function nueva() { form.reset(); form.clearErrors(); visible.value = true; }
const guardar = () => enviar(form, 'post', '/gps/actividades', { exito: () => { visible.value = false; router.reload({ only: ['disponibles'] }); } });
const finalizar = (a: any) => confirmarYEnviar('¿Finalizar seguimiento?', `${a.nombre} · ${a.participantes.length} participante(s). Dejará de recibirse su ubicación.`, 'post', `/gps/actividades/${a.id}/finalizar`, {}, { aceptar: 'Sí, finalizar', peligro: true });
const quitar = (p: any) => confirmarYEnviar('¿Finalizar seguimiento de este participante?', p.agremiado.nombre, 'delete', `/gps/participantes/${p.id}`, {}, { aceptar: 'Sí, finalizar', peligro: true });

const colHist: Col[] = [
    { key: 'nombre', label: 'Actividad' },
    { key: 'participantes', label: 'Participantes', tipo: 'numero', alinear: 'right' },
    { key: 'inicio', label: 'Inicio', filtro: 'fecha', tipo: 'fechahora' },
    { key: 'fin', label: 'Fin', filtro: 'fecha', tipo: 'fechahora' },
    { key: 'inicio_por', label: 'Inició', filtro: 'lista' },
];
</script>

<template>
    <div class="page">
        <PageHeader titulo="GPS" desc="Seguimiento en tiempo real por seguridad, solo de quienes lo autorizaron y durante actividades" :icono="MapPin">
            <SelectButton v-model="vista" :options="[{ v: 'vivo', l: 'En vivo' }, { v: 'hist', l: 'Historial' }]" option-label="l" option-value="v" :allow-empty="false" />
            <Button v-if="puede.editar" class="btn-grad" @click="nueva"><Play :size="16" /> Iniciar actividad</Button>
        </PageHeader>

        <div v-show="vista === 'vivo'" class="dos">
            <section class="panel">
                <div class="panel-h"><h2><Radar :size="18" />Seguimiento en tiempo real</h2><span class="sub">Se actualiza cada 8 s</span>
                    <div class="actions"><Button size="small" severity="secondary" outlined @click="centrar"><Crosshair :size="15" /> Ver a todos</Button></div></div>
                <div ref="mapaEl" class="mapa" />
            </section>
            <section class="panel">
                <div class="panel-h"><h2><Activity :size="18" />Actividades activas</h2><span class="pill teal">{{ actividades.length }}</span></div>
                <div class="panel-b lista">
                    <div v-for="a in actividades" :key="a.id" class="card act">
                        <div class="row"><b>{{ a.nombre }}</b><Button v-if="puede.editar" size="small" severity="danger" outlined @click="finalizar(a)"><Square :size="13" /> Finalizar</Button></div>
                        <Traza :t="a.inicio" verbo="Inició" />
                        <div v-for="p in a.participantes" :key="p.id" class="part" @click="enfocar(p)">
                            <Persona :p="p.agremiado" :sub="p.lat ? 'Clic para ubicar en el mapa' : 'Sin ubicación'" :size="30" />
                            <small class="muted"><Signal :size="12" /> {{ p.hace ?? 'sin señal aún' }}</small>
                            <div v-if="p.gps" style="grid-column: 1 / -1"><Traza :t="p.gps" verbo="GPS autorizado por" /></div>
                            <button v-if="puede.editar && a.participantes.length > 1" class="x" v-tooltip.left="'Finalizar solo a este participante'" @click.stop="quitar(p)"><Square :size="11" /></button>
                        </div>
                    </div>
                    <p v-if="!actividades.length" class="empty">No hay actividades con seguimiento en este momento.</p>
                </div>
            </section>
        </div>

        <section v-if="vista === 'hist'" class="panel tabla">
            <div class="panel-h"><h2><History :size="18" />Actividades finalizadas</h2></div>
            <TablaSigo :filas="historial" :columnas="colHist" />
        </section>

        <Modal v-model="visible" titulo="Iniciar actividad con GPS" sub="El seguimiento termina cuando lo finalices" :icono="Radar" ancho="md">
            <form id="fAct" style="display: flex; flex-direction: column; gap: 12px" @submit.prevent="guardar">
                <Campo label="Nombre de la actividad" req :error="form.errors.nombre"><InputText v-model="form.nombre" maxlength="150" placeholder="Ej. Brigada de afiliación · Col. Centro" /></Campo>
                <Campo label="Participantes" req info="Solo aparecen quienes autorizaron el seguimiento y no están ya en otra actividad." :error="form.errors.participantes">
                    <div v-if="disponibles.length" class="chk">
                        <label v-for="p in disponibles" :key="p.id" :class="{ on: form.participantes.includes(p.id) }"><Checkbox v-model="form.participantes" :value="p.id" /><Persona :p="p" :size="26" /></label>
                    </div>
                    <p v-else class="muted" style="margin: 0">No hay agremiados disponibles que hayan autorizado el GPS.</p>
                </Campo>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="visible = false" />
                <Button type="submit" form="fAct" class="btn-grad" :loading="form.processing" :disabled="!form.participantes.length"><Play :size="16" /> Iniciar seguimiento</Button>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.dos { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 14px; }
.dos > .panel { min-height: 0; overflow: hidden; }
.mapa { flex: 1; min-height: 260px; border-radius: 0 0 16px 16px; z-index: 0; }
.lista { flex: 1; overflow: auto; display: flex; flex-direction: column; gap: 12px; }
.act { gap: 8px; }
.part { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 8px; align-items: center; padding: 8px; border-radius: 10px; background: var(--surface-2); cursor: pointer; position: relative; }
.part:hover { background: #e5f0fa; }
.part .x { position: absolute; right: 6px; bottom: 6px; border: 0; background: var(--bad-soft); color: var(--bad); width: 22px; height: 22px; border-radius: 6px; display: grid; place-items: center; cursor: pointer; }
.chk { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
.chk label { display: flex; gap: 8px; align-items: center; padding: 8px; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; }
.chk label.on { border-color: var(--teal); background: #eef8f8; }
:deep(.pin-sigo) { background: none; border: 0; }
:deep(.pin-in) { display: flex; flex-direction: column; align-items: center; }
:deep(.pin-in span) { width: 36px; height: 36px; border-radius: 50%; background: var(--c); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 13px; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0, 0, 0, .3); overflow: hidden; font-family: var(--font-display); }
:deep(.pin-in span img) { width: 100%; height: 100%; object-fit: cover; }
:deep(.pin-in i) { width: 12px; height: 12px; border-radius: 50%; background: var(--naranja); border: 2px solid #fff; margin-top: 2px; animation: pulso 1.6s ease-out infinite; }
@keyframes pulso { 0% { box-shadow: 0 0 0 0 rgba(238, 138, 44, .6); } 100% { box-shadow: 0 0 0 12px transparent; } }
@media (max-width: 1000px) { .dos { grid-template-columns: 1fr; } .mapa { min-height: 360px; } }
</style>
