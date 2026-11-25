<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import { Wallet, Receipt, CircleCheck, CircleDashed, Minus, Settings, Save, Lock, Users, TriangleAlert, Coins } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Traza from '@/Components/Traza.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import CampoFecha from '@/Components/CampoFecha.vue';
import { enviar } from '@/acciones';
import { alerta } from '@/alerta';
import { ahoraISO, dinero, fecha } from '@/utils';

const props = defineProps<{ periodos: any[]; monto: number; desde: string; filas: any[]; formasPago: string[]; puede: { editar: boolean; total: boolean } }>();
const page = usePage<any>();

const estadoTxt = (f: any) => (f.adeudo === 0 ? 'Al corriente' : `Adeudo ${f.adeudo} ${f.adeudo === 1 ? 'mes' : 'meses'}`);
const columnas = computed<Col[]>(() => [
    { key: 'nombre', label: 'Agremiado', valor: (f) => `${f.nombre} ${f.folio}`, ancho: '240px' },
    ...props.periodos.map((p): Col => ({ key: 'm' + p.clave, label: p.label + ' ' + String(p.anio).slice(2), filtro: 'lista', valor: (f) => f.meses[p.clave].estado, opciones: ['Pagado', 'Pendiente', 'No aplica'], alinear: 'center', ancho: '86px' })),
    { key: 'adeudo', label: 'Estado', filtro: 'lista', valor: estadoTxt },
    { key: 'ultimo', label: 'Último pago registrado', valor: (f) => (f.ultimo ? `${f.ultimo.usuario} ${fecha(f.ultimo.fecha)}` : '') },
]);

const kpis = computed(() => {
    const al = props.filas.filter((f) => f.adeudo === 0).length;
    const ult = props.periodos[props.periodos.length - 1];
    return {
        recaudado: props.filas.filter((f) => f.meses[ult.clave].estado === 'Pagado').length * props.monto,
        mes: ult.label, al, con: props.filas.length - al,
        porCobrar: props.filas.reduce((s, f) => s + f.adeudo, 0) * props.monto,
    };
});

// Registrar pago
const visible = ref(false);
const sel = ref<any>(null);
const form = useForm({ agremiado_id: null as number | null, meses: [] as string[], forma_pago: 'Efectivo en tesorería', referencia: '' });
function pagar(f: any) {
    sel.value = f;
    form.reset();
    form.clearErrors();
    form.agremiado_id = f.id;
    form.meses = f.pendientes.length ? [f.pendientes[0].clave] : [];
    visible.value = true;
}
const total = computed(() => form.meses.length * props.monto);
const guardar = () => enviar(form, 'post', '/cuotas', { exito: () => (visible.value = false) });

async function anular(f: any, p: any) {
    const m = f.meses[p.clave];
    if (!props.puede.total || m.estado !== 'Pagado') return;
    if (await alerta.confirmar('¿Anular este pago?', `Cuota de ${p.label} ${p.anio} de ${f.nombre}.\nRegistró ${m.por} el ${fecha(m.fecha)}. El mes volverá a quedar pendiente; la anulación queda en la bitácora.`, { aceptar: 'Sí, anular', peligro: true }))
        router.delete(`/cuotas/${m.id}`, { preserveScroll: true, preserveState: true });
}

// Ajustes
const ajVisible = ref(false);
const fAj = useForm({ monto: props.monto, desde: props.desde as string | null });
function ajustes() { fAj.monto = props.monto; fAj.desde = props.desde; fAj.clearErrors(); ajVisible.value = true; }
</script>

<template>
    <div class="page">
        <PageHeader titulo="Cuotas" :desc="`Cuota ordinaria ${dinero(monto)} mensual · pasa el cursor sobre ✓ para ver quién registró el pago`" :icono="Wallet">
            <Button v-if="puede.total" severity="secondary" outlined @click="ajustes"><Settings :size="16" /> Ajustes de cuota</Button>
        </PageHeader>

        <div class="kpis">
            <div class="kpi c1"><span>Recaudado en {{ kpis.mes }}</span><div class="k-ico"><Coins :size="20" /></div><b>{{ dinero(kpis.recaudado) }}</b><small>de {{ dinero(filas.length * monto) }} esperados</small></div>
            <div class="kpi c2"><span>Al corriente</span><div class="k-ico"><CircleCheck :size="20" /></div><b>{{ kpis.al }}</b><small>agremiados sin adeudo</small></div>
            <div class="kpi c4"><span>Con adeudo</span><div class="k-ico"><TriangleAlert :size="20" /></div><b>{{ kpis.con }}</b><small>agremiados</small></div>
            <div class="kpi c3"><span>Por cobrar</span><div class="k-ico"><Users :size="20" /></div><b>{{ dinero(kpis.porCobrar) }}</b><small>desde {{ desde.split('-').reverse().join('/') }}</small></div>
        </div>

        <section class="panel tabla">
            <div class="panel-h"><h2><Wallet :size="18" />Cuotas sindicales · últimos 6 meses</h2></div>
            <TablaSigo :filas="filas" :columnas="columnas">
                <template #nombre="{ fila }"><Persona :p="fila" /></template>
                <template v-for="p in periodos" :key="p.clave" #[`m${p.clave}`]="{ fila }">
                    <span v-if="fila.meses[p.clave].estado === 'Pagado'" class="mes ok" :class="{ anular: puede.total }" v-tooltip.top="`Registró ${fila.meses[p.clave].por} · ${fecha(fila.meses[p.clave].fecha)}${puede.total ? ' · clic para anular' : ''}`" @click="anular(fila, p)"><CircleCheck :size="18" /></span>
                    <span v-else-if="fila.meses[p.clave].estado === 'Pendiente'" class="mes bad" v-tooltip.top="'Pendiente'"><CircleDashed :size="18" /></span>
                    <span v-else class="mes muted" v-tooltip.top="'No aplica (antes de su ingreso o del inicio de cobro)'"><Minus :size="16" /></span>
                </template>
                <template #adeudo="{ fila }"><span class="pill" :class="fila.adeudo === 0 ? 'ok' : fila.adeudo > 2 ? 'bad' : 'warn'">{{ estadoTxt(fila) }}</span></template>
                <template #ultimo="{ fila }"><Traza :t="fila.ultimo" /></template>
                <template #acciones="{ fila }">
                    <div class="acc"><button v-if="puede.editar && fila.adeudo" class="verde" v-tooltip.top="'Registrar pago'" aria-label="Registrar pago" @click="pagar(fila)"><Receipt :size="16" /></button></div>
                </template>
            </TablaSigo>
        </section>

        <Modal v-model="visible" titulo="Registrar pago" :sub="sel ? `${sel.nombre} · ${sel.folio}` : ''" :icono="Receipt" ancho="md">
            <form id="fPago" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="guardar">
                <Campo label="Meses que paga" req info="Solo aparecen los meses pendientes del agremiado." :error="form.errors.meses">
                    <div class="meses">
                        <label v-for="p in sel?.pendientes ?? []" :key="p.clave" :class="{ on: form.meses.includes(p.clave) }"><Checkbox v-model="form.meses" :value="p.clave" />{{ p.label }}</label>
                    </div>
                </Campo>
                <div class="form-grid">
                    <Campo label="Forma de pago" req info="Elige de la lista o escribe otra." :error="form.errors.forma_pago"><Select v-model="form.forma_pago" :options="formasPago" editable /></Campo>
                    <Campo label="Referencia" info="Folio de recibo, número de transferencia, etc." :error="form.errors.referencia"><InputText v-model="form.referencia" maxlength="80" /></Campo>
                    <Campo label="Total"><div class="total">{{ dinero(total) }}</div></Campo>
                </div>
                <div class="sello"><span><Lock :size="14" /> Se registra automáticamente</span><span><b>{{ page.props.auth.user.nombre }}</b> · <span class="mono">{{ fecha(ahoraISO()) }}</span></span></div>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="visible = false" />
                <Button type="submit" form="fPago" class="btn-grad" :loading="form.processing" :disabled="!form.meses.length"><Save :size="16" /> Registrar {{ dinero(total) }}</Button>
            </template>
        </Modal>

        <Modal v-model="ajVisible" titulo="Ajustes de cuota" sub="Los cambios quedan en la bitácora" :icono="Settings" ancho="sm">
            <form id="fAj" class="form-grid g2" @submit.prevent="enviar(fAj, 'put', '/cuotas/ajustes', { exito: () => (ajVisible = false) })">
                <Campo label="Cuota mensual" req :error="fAj.errors.monto"><InputNumber v-model="fAj.monto" mode="currency" currency="MXN" locale="es-MX" :min="1" fluid /></Campo>
                <Campo label="Cobrar desde" req info="Primer mes desde el que se cobra la cuota; los meses anteriores no generan adeudo." :error="fAj.errors.desde"><CampoFecha v-model="fAj.desde" vista="month" /></Campo>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="ajVisible = false" />
                <Button type="submit" form="fAj" class="btn-grad" :loading="fAj.processing"><Save :size="16" /> Guardar</Button>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.mes { display: inline-grid; place-items: center; }
.mes.ok { color: var(--ok); }
.mes.ok.anular { cursor: pointer; }
.mes.bad { color: var(--bad); }
.meses { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
.meses label { display: flex; gap: 8px; align-items: center; padding: 8px 10px; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; font-weight: 600; }
.meses label.on { border-color: var(--teal); background: #eef8f8; }
.total { font-family: var(--font-display); font-size: 22px; font-weight: 800; color: var(--brand); padding-top: 4px; }
</style>
