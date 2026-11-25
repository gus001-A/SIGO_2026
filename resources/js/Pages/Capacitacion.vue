<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useForm, usePage, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import ProgressBar from 'primevue/progressbar';
import { GraduationCap, Plus, Pencil, Save, Lock, BookOpen, ExternalLink, Loader2, Users } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Traza from '@/Components/Traza.vue';
import TrazaBoton from '@/Components/TrazaBoton.vue';
import Acciones from '@/Components/Acciones.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import CampoFecha from '@/Components/CampoFecha.vue';
import { enviar, eliminar } from '@/acciones';
import { alerta } from '@/alerta';
import { ahoraISO, fecha, hoyISO } from '@/utils';

const props = defineProps<{ filas: any[]; coordinadores: any[]; puede: { editar: boolean; eliminar: boolean } }>();
const page = usePage<any>();

const columnas: Col[] = [
    { key: 'nombre', label: 'Curso' },
    { key: 'horas', label: 'Horas', tipo: 'numero', alinear: 'right', ancho: '80px' },
    { key: 'modalidad', label: 'Modalidad', filtro: 'lista' },
    { key: 'dirigido', label: 'Dirigido a', filtro: 'lista' },
    { key: 'inscritos', label: 'Inscritos', tipo: 'numero', alinear: 'right' },
    { key: 'avance', label: 'Avance promedio', valor: (f) => f.avance + '%', ancho: '200px' },
    { key: 'asigno', label: 'Asignó', valor: (f) => `${f.asigno?.usuario} ${fecha(f.asigno?.fecha)}` },
];

const visible = ref(false);
const reg = ref<any>(null);
const form = useForm({ nombre: '', descripcion: '', horas: null as number | null, modalidad: 'En línea', enlace: '', dirigido_coordinador_id: null as number | null, fecha_limite: null as string | null });
function abrir(r: any = null) {
    reg.value = r;
    form.clearErrors();
    form.defaults({ nombre: r?.nombre ?? '', descripcion: r?.descripcion ?? '', horas: r?.horas ?? null, modalidad: r?.modalidad ?? 'En línea', enlace: r?.enlace ?? '', dirigido_coordinador_id: r?.dirigido_coordinador_id ?? null, fecha_limite: r?.fecha_limite ?? null });
    form.reset();
    visible.value = true;
}
const opcDir = [{ value: null, label: 'Todos los agremiados activos' }, ...props.coordinadores.map((c) => ({ value: c.id, label: c.nombre }))];

// Detalle con inscritos
const det = ref<any>(null);
const detVisible = ref(false);
const inscritos = ref<any[]>([]);
const cargando = ref(false);
async function ver(r: any) {
    det.value = r;
    detVisible.value = true;
    cargando.value = true;
    try { inscritos.value = (await axios.get(`/capacitacion/${r.id}`)).data; } finally { cargando.value = false; }
}
async function avance(i: any, v: number | null) {
    if (v === null || v === i.avance) return;
    try {
        await axios.put(`/inscripciones/${i.id}`, { avance: v });
        i.avance = v;
        router.reload({ only: ['filas'] });
    } catch (e: any) { alerta.error('No se guardó el avance', e.response?.data?.message ?? ''); }
}
</script>

<template>
    <div class="page">
        <PageHeader titulo="Capacitación" desc="Cursos en la plataforma SAIS adaptada a la organización" :icono="GraduationCap">
            <Button v-if="puede.editar" class="btn-grad" @click="abrir()"><Plus :size="17" /> Nuevo curso</Button>
        </PageHeader>

        <section class="panel tabla">
            <div class="panel-h"><h2><BookOpen :size="18" />Cursos asignados</h2></div>
            <TablaSigo :filas="filas" :columnas="columnas">
                <template #nombre="{ fila }"><b style="display: flex; gap: 6px; align-items: center"><BookOpen :size="14" style="color: var(--teal)" />{{ fila.nombre }}</b></template>
                <template #modalidad="{ fila }"><span class="pill brand">{{ fila.modalidad }}</span></template>
                <template #avance="{ fila }"><div class="row"><ProgressBar :value="fila.avance" :show-value="false" style="height: 7px; flex: 1" /><span class="num" style="width: 38px; text-align: right">{{ fila.avance }}%</span></div></template>
                <template #asigno="{ fila }"><Traza :t="fila.asigno" /></template>
                <template #acciones="{ fila }"><Acciones ver :editar="puede.editar" :eliminar="puede.eliminar" @ver="ver(fila)" @editar="abrir(fila)" @eliminar="eliminar(`/capacitacion/${fila.id}`, 'el curso ' + fila.nombre)" /></template>
            </TablaSigo>
        </section>

        <Modal v-model="visible" :titulo="reg ? 'Editar curso' : 'Nuevo curso'" sub="Se asigna en la plataforma SAIS adaptada; inscribe automáticamente a los destinatarios" :icono="reg ? Pencil : GraduationCap" ancho="lg">
            <form id="fCurso" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="enviar(form, reg ? 'put' : 'post', reg ? `/capacitacion/${reg.id}` : '/capacitacion', { exito: () => (visible = false) })">
                <div class="form-grid">
                    <Campo label="Nombre del curso" req :error="form.errors.nombre" :span="2"><InputText v-model="form.nombre" maxlength="150" /></Campo>
                    <Campo label="Duración en horas" req info="Entre 1 y 200 horas." :error="form.errors.horas"><InputNumber v-model="form.horas" :min="1" :max="200" :use-grouping="false" fluid /></Campo>
                    <Campo label="Modalidad" req :error="form.errors.modalidad"><SelectButton v-model="form.modalidad" :options="['En línea', 'Presencial', 'Mixta']" :allow-empty="false" /></Campo>
                    <Campo label="Dirigido a" req info="Todos los activos o la red de un coordinador. Al editar se inscriben los nuevos integrantes." :error="form.errors.dirigido_coordinador_id"><Select v-model="form.dirigido_coordinador_id" :options="opcDir" option-label="label" option-value="value" /></Campo>
                    <Campo label="Fecha límite" :error="form.errors.fecha_limite"><CampoFecha v-model="form.fecha_limite" :min="hoyISO()" /></Campo>
                    <Campo label="Enlace en SAIS" info="Dirección del curso en la plataforma; el agremiado lo abre desde su app." :error="form.errors.enlace" span="full"><InputText v-model="form.enlace" placeholder="https://" /></Campo>
                    <Campo label="Descripción" :error="form.errors.descripcion" span="full"><Textarea v-model="form.descripcion" rows="2" auto-resize /></Campo>
                </div>
                <div class="sello"><span><Lock :size="14" /> Se registra quién asignó el curso</span><span><b>{{ page.props.auth.user.nombre }}</b> · <span class="mono">{{ fecha(ahoraISO()) }}</span></span></div>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="visible = false" />
                <Button type="submit" form="fCurso" class="btn-grad" :loading="form.processing"><Save :size="16" /> Guardar</Button>
            </template>
        </Modal>

        <Modal v-model="detVisible" :titulo="det?.nombre ?? ''" :sub="det ? `${det.horas} h · ${det.modalidad} · ${det.dirigido}` : ''" :icono="GraduationCap" ancho="lg">
            <template v-if="det">
                <div class="row" style="margin-bottom: 12px; flex-wrap: wrap">
                    <span class="pill teal"><Users :size="12" /> {{ det.inscritos }} inscritos · {{ det.concluidos }} concluidos</span>
                    <span v-if="det.fecha_limite" class="pill warn">Fecha límite {{ fecha(det.fecha_limite) }}</span>
                    <a v-if="det.enlace" :href="det.enlace" target="_blank" class="pill brand" style="text-decoration: none"><ExternalLink :size="12" /> Abrir en SAIS</a>
                    <span style="margin-left: auto"><TrazaBoton :traza="det.traza" tabla="cursos" :id="det.id" :titulo="det.nombre" /></span>
                </div>
                <p v-if="det.descripcion" class="muted" style="margin: 0 0 12px">{{ det.descripcion }}</p>
                <div v-if="cargando" class="empty"><Loader2 :size="20" /> Cargando inscritos…</div>
                <table v-else class="ins">
                    <tr><th>Agremiado</th><th style="width: 40%">Avance</th><th>Concluyó</th></tr>
                    <tr v-for="i in inscritos" :key="i.id">
                        <td><Persona :p="i.agremiado" :size="28" /></td>
                        <td><div class="row"><ProgressBar :value="i.avance" :show-value="false" style="height: 7px; flex: 1" />
                            <InputNumber v-if="puede.editar" :model-value="i.avance" :min="0" :max="100" suffix="%" size="small" input-style="width: 70px" @update:model-value="(v) => avance(i, v)" />
                            <span v-else class="num">{{ i.avance }}%</span></div></td>
                        <td class="mono">{{ i.concluido ? fecha(i.concluido) : '—' }}</td>
                    </tr>
                </table>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.ins { width: 100%; border-collapse: collapse; font-size: 13px; }
.ins th { text-align: left; font-size: 11px; text-transform: uppercase; color: var(--ink-3); padding: 6px; border-bottom: 1px solid var(--line); background: var(--surface-2); position: sticky; top: 0; z-index: 1; }
.ins td { padding: 6px; border-bottom: 1px solid var(--line); }
</style>
