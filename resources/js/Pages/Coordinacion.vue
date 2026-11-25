<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Tree from 'primevue/tree';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { Network, ChevronsDown, ChevronsUp, ClipboardList, ArrowRightLeft, Check } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Acciones from '@/Components/Acciones.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import { enviar } from '@/acciones';

const props = defineProps<{ arbol: any[]; filas: any[]; opciones: { id: number; nombre: string; puesto: string }[]; puede: { editar: boolean } }>();

const abiertos = ref<Record<string, boolean>>(Object.fromEntries(props.arbol.flatMap((n) => [[n.key, true], ...n.children.map((c: any) => [c.key, true])])));
const todos = (ns: any[]): any[] => ns.flatMap((n) => [n, ...todos(n.children ?? [])]);
const expandir = () => (abiertos.value = Object.fromEntries(todos(props.arbol).map((n) => [n.key, true])));
const contraer = () => (abiertos.value = Object.fromEntries(props.arbol.map((n) => [n.key, true])));

const columnas: Col[] = [
    { key: 'nombre', label: 'Coordinador', valor: (f) => `${f.nombre} ${f.folio} ${f.puesto}` },
    { key: 'coordinador', label: 'Reporta a', filtro: 'lista', valor: (f) => f.coordinador ?? 'Cabeza de la red' },
    { key: 'directos', label: 'Directos', tipo: 'numero', alinear: 'right', ancho: '90px' },
    { key: 'red', label: 'Red total', tipo: 'numero', alinear: 'right', ancho: '90px' },
];

// Reasignación
const visible = ref(false);
const form = useForm({ agremiado_id: null as number | null, coordinador_id: null as number | null });
const opc = computed(() => props.opciones.map((o) => ({ value: o.id, label: `${o.nombre} · ${o.puesto}` })));
function reasignar(id: number | null = null) {
    form.reset();
    form.clearErrors();
    form.agremiado_id = id;
    visible.value = true;
}
function guardar() {
    if (!form.agremiado_id) return;
    enviar(form, 'put', `/agremiados/${form.agremiado_id}/coordinador`, { exito: () => (visible.value = false) });
}
const ver = (id: number) => router.visit(`/agremiados?ver=${id}`);
</script>

<template>
    <div class="page">
        <PageHeader titulo="Coordinación" desc="Quién coordina a quién, como las cuentas hijas" :icono="Network">
            <Button v-if="puede.editar" class="btn-grad" @click="reasignar()"><ArrowRightLeft :size="17" /> Reasignar coordinador</Button>
        </PageHeader>
        <div class="dos">
            <section class="panel">
                <div class="panel-h"><h2><Network :size="18" />Estructura de coordinación</h2><span class="sub">Cada agremiado tiene un coordinador arriba de él</span>
                    <div class="actions"><Button size="small" severity="secondary" outlined @click="expandir"><ChevronsDown :size="15" /> Expandir</Button><Button size="small" severity="secondary" outlined @click="contraer"><ChevronsUp :size="15" /> Contraer</Button></div>
                </div>
                <div class="panel-b arbol">
                    <Tree v-model:expanded-keys="abiertos" :value="arbol">
                        <template #default="{ node }">
                            <div class="nodo">
                                <Persona :p="node.data" :sub="node.data.puesto" :size="28" />
                                <span v-if="node.data.red" class="pill teal">{{ node.data.red }} en su red</span>
                                <button v-if="puede.editar && node.data.id !== arbol[0]?.data.id" class="mini" v-tooltip.top="'Reasignar coordinador'" @click.stop="reasignar(node.data.id)"><ArrowRightLeft :size="13" /></button>
                            </div>
                        </template>
                    </Tree>
                </div>
            </section>
            <section class="panel tabla">
                <div class="panel-h"><h2><ClipboardList :size="18" />Reporte: quién coordina a quién</h2></div>
                <TablaSigo :filas="filas" :columnas="columnas">
                    <template #nombre="{ fila }"><Persona :p="fila" :sub="fila.puesto" /></template>
                    <template #acciones="{ fila }"><Acciones ver @ver="ver(fila.id)" /></template>
                </TablaSigo>
            </section>
        </div>

        <Modal v-model="visible" titulo="Reasignar coordinador" sub="El cambio queda en la bitácora con tu usuario, fecha y hora" :icono="ArrowRightLeft" ancho="sm">
            <form id="fReasig" style="display: flex; flex-direction: column; gap: 12px" @submit.prevent="guardar">
                <Campo label="Agremiado" req :error="form.errors.agremiado_id"><Select v-model="form.agremiado_id" :options="opc" option-label="label" option-value="value" filter placeholder="— Selecciona —" /></Campo>
                <Campo label="Nuevo coordinador" req info="No puede ser alguien de su propia red." :error="form.errors.coordinador_id"><Select v-model="form.coordinador_id" :options="opc.filter((o) => o.value !== form.agremiado_id)" option-label="label" option-value="value" filter placeholder="— Selecciona —" /></Campo>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="visible = false" />
                <Button type="submit" form="fReasig" class="btn-grad" :loading="form.processing" :disabled="!form.agremiado_id || !form.coordinador_id"><Check :size="16" /> Reasignar</Button>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.dos { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 14px; align-items: start; }
.dos > .panel { max-height: 100%; }
.arbol { flex: 1 1 auto; min-height: 0; overflow: auto; }
.arbol :deep(.p-tree) { padding: 0; }
.arbol :deep(.p-tree-node-content) { padding: 3px 6px; }
.nodo { display: flex; align-items: center; gap: 10px; }
.mini { border: 0; background: #e5f0fa; color: #1f6fae; width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; cursor: pointer; }
@media (max-width: 1000px) { .dos { grid-template-columns: 1fr; } }
</style>
