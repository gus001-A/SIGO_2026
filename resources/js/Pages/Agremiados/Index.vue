<script setup lang="ts">
import { onMounted, ref } from 'vue';
import axios from 'axios';
import Button from 'primevue/button';
import { Users, UserPlus, MapPin, Check, TriangleAlert, UserX } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Acciones from '@/Components/Acciones.vue';
import AgremiadoForm from '@/Components/Agremiados/AgremiadoForm.vue';
import AgremiadoFicha from '@/Components/Agremiados/AgremiadoFicha.vue';
import { eliminar } from '@/acciones';

const props = defineProps<{ filas: any[]; opciones: any; puede: Record<string, boolean>; abrir: number | null }>();

const cuotaTxt = (f: any) => (f.estatus === 'Baja' ? 'Baja' : f.adeudo === 0 ? 'Al corriente' : `Adeudo ${f.adeudo} ${f.adeudo === 1 ? 'mes' : 'meses'}`);
const columnas: Col[] = [
    { key: 'nombre', label: 'Agremiado', valor: (f) => `${f.nombre} ${f.folio} ${f.puesto}`, ancho: '260px' },
    { key: 'coordinador', label: 'Coordinador', filtro: 'lista', valor: (f) => f.coordinador ?? 'Cabeza de la red' },
    { key: 'municipio', label: 'Municipio', filtro: 'lista' },
    { key: 'seccion', label: 'Sección', tipo: 'mono', ancho: '90px' },
    { key: 'adeudo', label: 'Cuotas', filtro: 'lista', valor: cuotaTxt },
    { key: 'gps', label: 'GPS', filtro: 'lista', valor: (f) => (f.gps ? 'Autorizado' : 'No') },
    { key: 'perfil', label: 'Acceso', filtro: 'lista', valor: (f) => f.perfil ?? 'Sin acceso' },
    { key: 'estatus', label: 'Estatus', filtro: 'lista' },
];

const conteo = ref(0);
const formVisible = ref(false);
const registro = ref<any>(null);
const fichaVisible = ref(false);
const fichaId = ref<number | null>(null);
const ficha = ref();

function nuevo() { registro.value = null; formVisible.value = true; }
function ver(id: number) { fichaId.value = id; fichaVisible.value = true; }
async function editar(f: any) {
    registro.value = f.curp ? f : (await axios.get(`/agremiados/${f.id}`)).data;
    formVisible.value = true;
}
const tono = (f: any) => (f.estatus === 'Baja' ? 'bad' : f.adeudo === 0 ? 'ok' : f.adeudo > 2 ? 'bad' : 'warn');
onMounted(() => props.abrir && ver(props.abrir));
</script>

<template>
    <div class="page">
        <PageHeader titulo="Agremiados" desc="Padrón con datos generales, incidencias y trazabilidad" :icono="Users">
            <Button v-if="puede.editar" class="btn-grad" @click="nuevo"><UserPlus :size="17" /> Alta de agremiado</Button>
        </PageHeader>

        <section class="panel tabla">
            <div class="panel-h"><h2><Users :size="18" />Padrón de agremiados</h2><span class="pill brand">{{ conteo }} de {{ filas.length }}</span></div>
            <TablaSigo :filas="filas" :columnas="columnas" :clase-fila="(f) => (f.estatus === 'Baja' ? 'baja' : '')" @conteo="conteo = $event">
                <template #nombre="{ fila }"><Persona :p="fila" /></template>
                <template #coordinador="{ fila }"><span v-if="fila.coordinador">{{ fila.coordinador }}</span><span v-else class="muted">Cabeza de la red</span></template>
                <template #adeudo="{ fila }"><span class="pill" :class="tono(fila)"><Check v-if="fila.adeudo === 0 && fila.estatus !== 'Baja'" :size="12" /><TriangleAlert v-else-if="fila.estatus !== 'Baja'" :size="12" /><UserX v-else :size="12" />{{ cuotaTxt(fila) }}</span></template>
                <template #gps="{ fila }"><span class="pill" :class="fila.gps ? 'ok' : 'muted'"><MapPin v-if="fila.gps" :size="12" />{{ fila.gps ? 'Autorizado' : 'No' }}</span></template>
                <template #perfil="{ fila }"><span v-if="fila.perfil" class="pill brand">{{ fila.perfil }}</span><span v-else class="muted">Sin acceso</span></template>
                <template #estatus="{ fila }"><span class="pill" :class="fila.estatus === 'Activo' ? 'ok' : 'bad'">{{ fila.estatus }}</span></template>
                <template #acciones="{ fila }">
                    <Acciones ver :editar="puede.editar && fila.estatus === 'Activo'" :eliminar="puede.eliminar && fila.coordinados === 0 && !!fila.coordinador_id"
                        @ver="ver(fila.id)" @editar="editar(fila)" @eliminar="eliminar(`/agremiados/${fila.id}`, 'al agremiado ' + fila.nombre, 'Se quitará del padrón y su acceso se desactiva. Se conserva en la bitácora de auditoría.')" />
                </template>
            </TablaSigo>
        </section>

        <AgremiadoForm v-model="formVisible" :registro="registro" :opciones="opciones" :puede-accesos="puede.accesos" @guardado="fichaVisible && ficha?.cargar()" />
        <AgremiadoFicha ref="ficha" v-model="fichaVisible" :id="fichaId" :opciones="opciones" :puede="puede" @editar="editar" @ver="ver" />
    </div>
</template>
