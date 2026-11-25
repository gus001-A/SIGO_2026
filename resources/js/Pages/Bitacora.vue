<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ScrollText, Eye } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Modal from '@/Components/Modal.vue';
import { icono } from '@/iconos';
import { fecha } from '@/utils';

/** Bitácora inalterable: la paginación y los filtros del pie se resuelven en el servidor. */
const props = defineProps<{ filas: any[]; total: number; pagina: number; filtros: Record<string, any>; modulos: { value: string; label: string }[]; acciones: string[] }>();

const MODS: Record<string, { nombre: string; icono: string }> = {
    inicio: { nombre: 'Panel', icono: 'layout-dashboard' }, agremiados: { nombre: 'Agremiados', icono: 'users' }, red: { nombre: 'Coordinación', icono: 'network' },
    cuotas: { nombre: 'Cuotas', icono: 'wallet' }, apoyos: { nombre: 'Apoyos', icono: 'hand-heart' }, tienda: { nombre: 'Tienda', icono: 'store' },
    capacitacion: { nombre: 'Capacitación', icono: 'graduation-cap' }, ubicacion: { nombre: 'GPS', icono: 'map-pin' }, bitacora: { nombre: 'Bitácora', icono: 'scroll-text' }, perfiles: { nombre: 'Perfiles', icono: 'shield-check' },
};

const columnas: Col[] = [
    { key: 'fecha', label: 'Fecha', filtro: 'fecha', tipo: 'fecha', ancho: '130px' },
    { key: 'hora', label: 'Hora', tipo: 'mono', ancho: '90px' },
    { key: 'usuario', label: 'Usuario', ancho: '200px' },
    { key: 'modulo', label: 'Módulo', filtro: 'lista', opciones: props.modulos },
    { key: 'accion', label: 'Acción', filtro: 'lista', opciones: props.acciones },
    { key: 'detalle', label: 'Detalle', valor: (f) => [f.registro, f.descripcion].filter(Boolean).join(' · ') },
];

function cambio({ pagina, filtros }: { pagina: number; filtros: Record<string, any> }) {
    const q = Object.fromEntries(Object.entries({ ...filtros, page: pagina > 1 ? pagina : null }).filter(([, v]) => v !== null && v !== '' && v !== undefined));
    router.get('/bitacora', q, { preserveState: true, preserveScroll: true, replace: true, only: ['filas', 'total', 'pagina', 'filtros'] });
}

const det = ref<any>(null);
const detVisible = ref(false);
const cambios = (c: any) => (c && !c.nuevo && !c.anterior ? Object.entries(c).filter(([, v]: any) => v && typeof v === 'object' && 'antes' in v) : []);
const valor = (v: any) => (v === null || v === undefined || v === '' ? '—' : typeof v === 'object' ? JSON.stringify(v) : String(v));
</script>

<template>
    <div class="page">
        <PageHeader titulo="Bitácora" desc="Registro íntegro e inalterable de cada acción de cada usuario" :icono="ScrollText">
            <span class="pill brand">{{ total.toLocaleString('es-MX') }} registros</span>
        </PageHeader>
        <section class="panel tabla">
            <TablaSigo :filas="filas" :columnas="columnas" lazy :total="total" :pagina="pagina" :filtros-iniciales="filtros" vacio="Sin registros." @cambio="cambio">
                <template #usuario="{ fila }"><b>{{ fila.usuario }}</b><br /><small class="muted">{{ fila.rol }}</small></template>
                <template #modulo="{ fila }"><span class="pill teal"><component :is="icono(MODS[fila.modulo]?.icono)" :size="12" />{{ MODS[fila.modulo]?.nombre ?? fila.modulo }}</span></template>
                <template #accion="{ fila }"><b>{{ fila.accion }}</b></template>
                <template #detalle="{ fila }"><span class="muted">{{ [fila.registro, fila.descripcion].filter(Boolean).join(' · ') }}</span></template>
                <template #acciones="{ fila }"><div class="acc"><button class="ver" v-tooltip.top="'Ver detalle'" aria-label="Ver detalle" @click="det = fila; detVisible = true"><Eye :size="16" /></button></div></template>
            </TablaSigo>
        </section>

        <Modal v-model="detVisible" :titulo="det?.accion ?? ''" :sub="det ? `${fecha(det.fecha)} ${det.hora} · ${det.usuario}` : ''" :icono="ScrollText" ancho="md">
            <dl v-if="det" class="dl">
                <div><dt>Usuario</dt><dd>{{ det.usuario }}</dd></div>
                <div><dt>Perfil</dt><dd>{{ det.rol ?? '—' }}</dd></div>
                <div><dt>Módulo</dt><dd>{{ MODS[det.modulo]?.nombre ?? det.modulo }}</dd></div>
                <div><dt>Fecha y hora</dt><dd class="mono">{{ fecha(det.fecha) }} {{ det.hora }}</dd></div>
                <div><dt>IP</dt><dd class="mono">{{ det.ip ?? '—' }}</dd></div>
                <div><dt>Registro</dt><dd>{{ det.registro ?? '—' }}</dd></div>
                <div class="full"><dt>Detalle</dt><dd>{{ det.descripcion ?? '—' }}</dd></div>
                <div v-if="cambios(det.cambios).length" class="full"><dt>Cambios</dt>
                    <dd><table class="cmb"><tr v-for="[k, v] in cambios(det.cambios)" :key="k"><td>{{ k }}</td><td class="a">{{ valor((v as any).antes) }}</td><td>→</td><td class="d">{{ valor((v as any).despues) }}</td></tr></table></dd>
                </div>
            </dl>
        </Modal>
    </div>
</template>

<style scoped>
.cmb { font-size: 12.5px; border-collapse: collapse; }
.cmb td { padding: 2px 8px 2px 0; }
.cmb .a { color: var(--bad); text-decoration: line-through; }
.cmb .d { color: var(--ok); font-weight: 700; }
</style>
