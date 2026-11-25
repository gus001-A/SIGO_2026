<script setup lang="ts">
/** Botón «Trazabilidad» del detalle: quién registró, quién modificó (usuario, fecha y hora) y el historial de la bitácora. */
import { ref } from 'vue';
import axios from 'axios';
import Button from 'primevue/button';
import { History, UserPlus, UserPen, Loader2 } from 'lucide-vue-next';
import Modal from './Modal.vue';
import { fecha } from '@/utils';

const props = defineProps<{ traza?: { creado?: any; modificado?: any } | null; tabla?: string; id?: number | null; titulo?: string }>();
const abierto = ref(false);
const historial = ref<any[]>([]);
const cargando = ref(false);

async function abrir() {
    abierto.value = true;
    if (!props.tabla || !props.id) return;
    cargando.value = true;
    try {
        historial.value = (await axios.get('/bitacora/registro', { params: { tabla: props.tabla, id: props.id } })).data;
    } finally {
        cargando.value = false;
    }
}

const etiqueta = (k: string) => k.replace(/_id$/, '').replace(/_/g, ' ');
const valor = (v: any) => (v === null || v === undefined || v === '' ? '—' : typeof v === 'boolean' ? (v ? 'Sí' : 'No') : typeof v === 'object' ? JSON.stringify(v) : String(v));
const cambios = (c: any) => (c && !c.nuevo && !c.anterior && !Array.isArray(c) ? Object.entries(c).filter(([, v]: any) => v && typeof v === 'object' && 'antes' in v) : []);
</script>

<template>
    <Button severity="secondary" outlined size="small" @click="abrir"><History :size="16" /> Trazabilidad</Button>
    <Modal v-model="abierto" titulo="Trazabilidad" :sub="titulo" :icono="History" ancho="md">
        <div class="grid-tz">
            <div class="tz">
                <span class="tz-ico alta"><UserPlus :size="18" /></span>
                <div><small>Registró</small><b>{{ traza?.creado?.usuario ?? '—' }}</b><span class="mono">{{ fecha(traza?.creado?.fecha) }} · {{ traza?.creado?.hora ?? '—' }}</span></div>
            </div>
            <div class="tz">
                <span class="tz-ico mod"><UserPen :size="18" /></span>
                <div><small>Última modificación</small><b>{{ traza?.modificado?.usuario ?? 'Sin modificaciones' }}</b><span v-if="traza?.modificado" class="mono">{{ fecha(traza.modificado.fecha) }} · {{ traza.modificado.hora }}</span></div>
            </div>
        </div>
        <h3 style="font-size: 14px; margin: 18px 0 8px; color: var(--brand)">Historial en la bitácora</h3>
        <div v-if="cargando" class="empty"><Loader2 class="girar" :size="20" /> Cargando…</div>
        <div v-else-if="!historial.length" class="empty">Sin movimientos registrados en la bitácora.</div>
        <div v-else class="hist">
            <div v-for="h in historial" :key="h.id" class="h-item">
                <div class="row" style="justify-content: flex-start; flex-wrap: wrap">
                    <span class="pill brand">{{ h.accion }}</span>
                    <b>{{ h.usuario }}</b><span class="muted" v-if="h.rol">· {{ h.rol }}</span>
                    <span class="mono muted" style="margin-left: auto">{{ fecha(h.fecha) }} {{ h.hora }}</span>
                </div>
                <div v-if="h.descripcion" class="muted" style="font-size: 12.5px">{{ h.descripcion }}</div>
                <table v-if="cambios(h.cambios).length" class="cambios">
                    <tr v-for="[k, v] in cambios(h.cambios)" :key="k"><td>{{ etiqueta(k) }}</td><td class="antes">{{ valor((v as any).antes) }}</td><td>→</td><td class="despues">{{ valor((v as any).despues) }}</td></tr>
                </table>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.grid-tz { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.tz { display: flex; gap: 12px; align-items: center; padding: 12px; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.tz small { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: var(--ink-3); font-weight: 700; }
.tz b { display: block; }
.tz .mono { font-size: 12px; color: var(--ink-2); }
.tz-ico { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; color: #fff; flex-shrink: 0; }
.tz-ico.alta { background: var(--teal); } .tz-ico.mod { background: var(--naranja); }
.hist { display: flex; flex-direction: column; gap: 8px; }
.h-item { border: 1px solid var(--line); border-radius: 10px; padding: 9px 12px; display: flex; flex-direction: column; gap: 4px; }
.cambios { font-size: 12px; border-collapse: collapse; }
.cambios td { padding: 2px 8px 2px 0; vertical-align: top; }
.cambios td:first-child { color: var(--ink-3); text-transform: capitalize; }
.cambios .antes { color: var(--bad); text-decoration: line-through; }
.cambios .despues { color: var(--ok); font-weight: 700; }
.girar { animation: spin 1s linear infinite; vertical-align: middle; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 600px) { .grid-tz { grid-template-columns: 1fr; } }
</style>
