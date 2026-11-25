<script setup lang="ts">
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import { HandHeart, Gift, PackagePlus, Pencil, Save, Lock, Coins, CalendarDays, Users, Camera } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Traza from '@/Components/Traza.vue';
import TrazaBoton from '@/Components/TrazaBoton.vue';
import Acciones from '@/Components/Acciones.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import CampoFecha from '@/Components/CampoFecha.vue';
import SubirArchivos from '@/Components/SubirArchivos.vue';
import Evidencias from '@/Components/Evidencias.vue';
import { enviar, eliminar } from '@/acciones';
import { ahoraISO, dinero, fecha, TONOS } from '@/utils';

const props = defineProps<{ filas: any[]; resumen: any; agremiados: any[]; tipos: string[]; puede: { editar: boolean; eliminar: boolean } }>();
const page = usePage<any>();

const columnas: Col[] = [
    { key: 'beneficiario', label: 'Beneficiario', valor: (f) => `${f.beneficiario} ${f.agremiado?.folio}`, ancho: '230px' },
    { key: 'tipo', label: 'Apoyo', filtro: 'lista' },
    { key: 'detalle', label: 'Descripción' },
    { key: 'monto', label: 'Valor', tipo: 'dinero', alinear: 'right' },
    { key: 'fecha_entrega', label: 'Entrega', filtro: 'fecha', tipo: 'fechahora' },
    { key: 'entrego', label: 'Entregó', filtro: 'lista' },
    { key: 'archivos', label: 'Evidencia', filtro: false },
];

// Formulario
const visible = ref(false);
const registro = ref<any>(null);
const form = useForm({ agremiado_id: null as number | null, tipo: 'Despensa', detalle: '', monto: 0 as number | null, fecha_entrega: ahoraISO() as string | null, evidencias: [] as File[], quitar: [] as number[] });
function abrir(r: any = null) {
    registro.value = r;
    form.clearErrors();
    form.defaults({ agremiado_id: r?.agremiado_id ?? null, tipo: r?.tipo ?? 'Despensa', detalle: r?.detalle ?? '', monto: r?.monto ?? 0, fecha_entrega: r?.fecha_entrega ?? ahoraISO(), evidencias: [], quitar: [] });
    form.reset();
    visible.value = true;
}
const guardar = () => enviar(form, 'post', registro.value ? `/apoyos/${registro.value.id}` : '/apoyos', { archivos: true, exito: () => (visible.value = false) });
const opcAg = props.agremiados.map((a) => ({ value: a.id, label: `${a.nombre} · ${a.folio}` }));

// Detalle
const det = ref<any>(null);
const detVisible = ref(false);
const ver = (r: any) => { det.value = r; detVisible.value = true; };
</script>

<template>
    <div class="page">
        <PageHeader titulo="Apoyos" desc="Qué se entregó, a quién, quién lo entregó, fecha, hora y evidencia" :icono="HandHeart">
            <Button v-if="puede.editar" class="btn-grad" @click="abrir()"><PackagePlus :size="17" /> Registrar entrega</Button>
        </PageHeader>

        <div class="kpis">
            <div class="kpi c1"><span>Valor entregado</span><div class="k-ico"><Coins :size="20" /></div><b>{{ dinero(resumen.total) }}</b><small>{{ filas.length }} entregas registradas</small></div>
            <div class="kpi c2"><span>Este mes</span><div class="k-ico"><CalendarDays :size="20" /></div><b>{{ dinero(resumen.mes) }}</b><small>en apoyos del mes</small></div>
            <div class="kpi c3"><span>Beneficiarios</span><div class="k-ico"><Users :size="20" /></div><b>{{ resumen.beneficiarios }}</b><small>agremiados apoyados</small></div>
            <div class="kpi c4"><span>Tipo más frecuente</span><div class="k-ico"><Gift :size="20" /></div><b style="font-size: 20px">{{ Object.entries(resumen.porTipo).sort((a: any, b: any) => b[1].n - a[1].n)[0]?.[0] ?? '—' }}</b><small>{{ Object.keys(resumen.porTipo).length }} tipos de apoyo</small></div>
        </div>

        <section class="panel tabla">
            <div class="panel-h"><h2><Gift :size="18" />Entregas registradas</h2></div>
            <TablaSigo :filas="filas" :columnas="columnas">
                <template #beneficiario="{ fila }"><Persona :p="fila.agremiado" /></template>
                <template #tipo="{ fila }"><span class="pill teal">{{ fila.tipo }}</span></template>
                <template #detalle="{ fila }"><span class="muted">{{ fila.detalle }}</span></template>
                <template #entrego="{ fila }"><b style="font-weight: 700">{{ fila.entrego }}</b></template>
                <template #archivos="{ fila }"><Evidencias :archivos="fila.archivos" sm /></template>
                <template #acciones="{ fila }"><Acciones ver :editar="puede.editar" :eliminar="puede.eliminar" @ver="ver(fila)" @editar="abrir(fila)" @eliminar="eliminar(`/apoyos/${fila.id}`, 'la entrega')" /></template>
            </TablaSigo>
        </section>

        <Modal v-model="visible" :titulo="registro ? 'Editar entrega' : 'Registrar entrega de apoyo'" :sub="registro ? registro.beneficiario : 'Los campos con * son obligatorios'" :icono="registro ? Pencil : PackagePlus" ancho="lg">
            <form id="fApoyo" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="guardar">
                <div class="form-grid">
                    <Campo label="Beneficiario" req :error="form.errors.agremiado_id" :span="2"><Select v-model="form.agremiado_id" :options="opcAg" option-label="label" option-value="value" filter placeholder="— Selecciona —" /></Campo>
                    <Campo label="Tipo de apoyo" req info="Elige de la lista o escribe otro tipo." :error="form.errors.tipo"><Select v-model="form.tipo" :options="tipos" editable /></Campo>
                    <Campo label="Valor (MXN)" req :error="form.errors.monto"><InputNumber v-model="form.monto" mode="currency" currency="MXN" locale="es-MX" :min="0" fluid /></Campo>
                    <Campo label="Fecha y hora de entrega" req info="No puede ser futura." :error="form.errors.fecha_entrega" :span="2"><CampoFecha v-model="form.fecha_entrega" hora :max="ahoraISO()" /></Campo>
                    <Campo label="Descripción de lo entregado" req :error="form.errors.detalle" span="full"><Textarea v-model="form.detalle" rows="2" auto-resize maxlength="255" /></Campo>
                    <Campo label="Evidencia de la entrega" :req="!registro" info="Fotos, videos o PDF. Se muestran antes de guardar." :error="form.errors.evidencias || form.errors['evidencias.0']" span="full">
                        <SubirArchivos v-model="form.evidencias" v-model:quitar="form.quitar" :existentes="registro?.archivos ?? []" texto="Fotos o videos de la entrega" />
                    </Campo>
                </div>
                <div class="sello"><span><Lock :size="14" /> Se registra automáticamente quién entregó</span><span><b>{{ page.props.auth.user.nombre }}</b> · <span class="mono">{{ fecha(ahoraISO()) }}</span></span></div>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="visible = false" />
                <Button type="submit" form="fApoyo" class="btn-grad" :loading="form.processing"><Save :size="16" /> Guardar entrega</Button>
            </template>
        </Modal>

        <Modal v-model="detVisible" :titulo="det?.tipo ?? ''" :sub="det ? `${dinero(det.monto)} · ${fecha(det.fecha_entrega)}` : ''" :icono="Gift" ancho="md">
            <template v-if="det">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px">
                    <Persona :p="det.agremiado" :size="44" />
                    <TrazaBoton :traza="det.traza" tabla="apoyos" :id="det.id" :titulo="`${det.tipo} · ${det.beneficiario}`" />
                </div>
                <dl class="dl">
                    <div><dt>Tipo</dt><dd><span class="pill" :class="TONOS[det.tipo] ?? 'teal'">{{ det.tipo }}</span></dd></div>
                    <div><dt>Valor</dt><dd class="num">{{ dinero(det.monto) }}</dd></div>
                    <div><dt>Fecha y hora de entrega</dt><dd class="mono">{{ fecha(det.fecha_entrega) }}</dd></div>
                    <div class="full"><dt>Descripción</dt><dd>{{ det.detalle }}</dd></div>
                    <div class="full"><dt>Entregó</dt><dd><Traza :t="det.traza.creado" /></dd></div>
                    <div class="full"><dt><Camera :size="12" />Evidencia</dt><dd style="margin-top: 6px"><Evidencias :archivos="det.archivos" /></dd></div>
                </dl>
            </template>
        </Modal>
    </div>
</template>
