<script setup lang="ts">
/**
 * Tabla estándar de SIGO:
 * - máximo 10 registros por página con paginación (sin selector de «N por página»);
 * - se desplaza solo la tabla, nunca la pantalla; si hay pocos registros la tabla se acorta;
 * - los filtros de búsqueda van únicamente al pie de la tabla, uno por columna.
 * En modo «lazy» la paginación y los filtros se resuelven en el servidor (bitácora).
 */
import { computed, reactive, ref, watch } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import { FilterX, SearchX } from 'lucide-vue-next';
import CampoFecha from './CampoFecha.vue';
import { dinero, fecha, normal } from '@/utils';

export interface Col {
    key: string;
    label: string;
    filtro?: 'texto' | 'lista' | 'fecha' | false;
    opciones?: (string | { label: string; value: any })[];
    valor?: (f: any) => any;
    tipo?: 'dinero' | 'fecha' | 'fechahora' | 'numero' | 'mono';
    ancho?: string;
    alinear?: 'right' | 'center';
}

const props = withDefaults(
    defineProps<{ filas: any[]; columnas: Col[]; llave?: string; claseFila?: (f: any) => string | object; lazy?: boolean; total?: number; pagina?: number; filtrosIniciales?: Record<string, any>; vacio?: string; filaClic?: boolean }>(),
    { llave: 'id', vacio: 'No hay registros todavía.' },
);
const emit = defineEmits<{ cambio: [{ pagina: number; filtros: Record<string, any> }]; fila: [any]; conteo: [number] }>();

const POR_PAGINA = 10;
const filtros = reactive<Record<string, any>>({ ...(props.filtrosIniciales ?? {}) });
const first = ref(props.lazy ? ((props.pagina ?? 1) - 1) * POR_PAGINA : 0);

const valorDe = (c: Col, f: any) => (c.valor ? c.valor(f) : f[c.key]);
const textoDe = (c: Col, f: any) => {
    const v = valorDe(c, f);
    if (c.tipo === 'dinero') return dinero(v);
    if (c.tipo === 'fecha' || c.tipo === 'fechahora') return fecha(v);
    return v ?? '';
};

const opcionesDe = (c: Col) => {
    if (c.opciones) return c.opciones.map((o) => (typeof o === 'string' ? { label: o, value: o } : o));
    const vistos = [...new Set(props.filas.map((f) => valorDe(c, f)).filter((v) => v !== null && v !== undefined && v !== ''))];
    return vistos.sort((a, b) => String(a).localeCompare(String(b), 'es')).map((v) => ({ label: String(v), value: v }));
};

const filtradas = computed(() => {
    if (props.lazy) return props.filas;
    const activos = props.columnas.filter((c) => c.filtro !== false && filtros[c.key] !== undefined && filtros[c.key] !== null && filtros[c.key] !== '');
    if (!activos.length) return props.filas;
    return props.filas.filter((f) =>
        activos.every((c) => {
            const q = filtros[c.key];
            const v = valorDe(c, f);
            if ((c.filtro ?? 'texto') === 'lista') return normal(v) === normal(q);
            if (c.filtro === 'fecha') return String(v ?? '').slice(0, 10) === String(q).slice(0, 10);
            return normal(textoDe(c, f)).includes(normal(q));
        }),
    );
});

const nActivos = computed(() => Object.values(filtros).filter((v) => v !== null && v !== undefined && v !== '').length);

watch(() => filtradas.value.length, (n) => emit('conteo', props.lazy ? props.total ?? n : n), { immediate: true });

// Al filtrar se regresa a la primera página
let espera: ReturnType<typeof setTimeout> | undefined;
watch(filtros, () => {
    first.value = 0;
    if (props.lazy) {
        clearTimeout(espera);
        espera = setTimeout(() => emit('cambio', { pagina: 1, filtros: { ...filtros } }), 400);
    }
});
watch(() => props.pagina, (p) => props.lazy && (first.value = ((p ?? 1) - 1) * POR_PAGINA));

function limpiar() {
    Object.keys(filtros).forEach((k) => (filtros[k] = null));
}
function pagina(e: { first: number }) {
    first.value = e.first;
    if (props.lazy) emit('cambio', { pagina: Math.floor(e.first / POR_PAGINA) + 1, filtros: { ...filtros } });
}
defineExpose({ limpiar, filtros });
</script>

<template>
    <div class="tabla-sigo">
        <DataTable
            :value="filtradas" :data-key="llave" paginator :rows="POR_PAGINA" :first="first" :lazy="lazy" :total-records="lazy ? total : filtradas.length"
            scrollable scroll-height="flex" :row-class="claseFila" :row-hover="filaClic" size="small" @page="pagina" @row-click="filaClic && emit('fila', $event.data)"
            paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            current-page-report-template="{first}–{last} de {totalRecords} registros" :page-link-size="5" :always-show-paginator="true">
            <Column v-for="c in columnas" :key="c.key" :field="c.key" :header="c.label"
                :style="{ minWidth: c.ancho, width: c.ancho }" :header-style="c.alinear ? { textAlign: c.alinear } : {}" :body-style="c.alinear ? { textAlign: c.alinear } : {}">
                <template #body="{ data }">
                    <slot :name="c.key" :fila="data">
                        <span :class="{ mono: c.tipo === 'mono' || c.tipo === 'fecha' || c.tipo === 'fechahora', num: c.tipo === 'dinero' || c.tipo === 'numero' }">{{ textoDe(c, data) }}</span>
                    </slot>
                </template>
            </Column>
            <Column v-if="$slots.acciones" key="_acc" header="Acciones" frozen align-frozen="right" :style="{ width: '1%', whiteSpace: 'nowrap' }">
                <template #body="{ data }"><slot name="acciones" :fila="data" /></template>
            </Column>

            <template #empty>
                <div class="sin-res">
                    <template v-if="nActivos">
                        <SearchX :size="22" style="vertical-align: middle" /> Ningún registro coincide con los filtros.
                        <Button size="small" text label="Limpiar filtros" @click="limpiar" />
                    </template>
                    <template v-else>{{ vacio }}</template>
                </div>
            </template>

            <!-- Filtros al pie de la tabla, uno por columna -->
            <ColumnGroup type="footer">
                <Row>
                    <Column v-for="c in columnas" :key="'f' + c.key">
                        <template #footer>
                            <template v-if="c.filtro !== false">
                                <Select v-if="c.filtro === 'lista'" v-model="filtros[c.key]" :options="opcionesDe(c)" option-label="label" option-value="value" show-clear
                                    :placeholder="'Todos'" class="col-f" :class="{ on: filtros[c.key] != null && filtros[c.key] !== '' }" size="small" append-to="body" :aria-label="'Filtrar ' + c.label" />
                                <div v-else-if="c.filtro === 'fecha'" class="col-f" :class="{ on: !!filtros[c.key] }">
                                    <CampoFecha v-model="filtros[c.key]" placeholder="Fecha" />
                                </div>
                                <InputText v-else v-model="filtros[c.key]" class="col-f" :class="{ on: !!filtros[c.key] }" size="small" :placeholder="'Filtrar ' + c.label.toLowerCase()" :aria-label="'Filtrar ' + c.label" />
                            </template>
                        </template>
                    </Column>
                    <Column v-if="$slots.acciones" key="f_acc" frozen align-frozen="right">
                        <template #footer>
                            <Button v-if="nActivos" size="small" severity="secondary" outlined v-tooltip.top="'Quitar todos los filtros'" @click="limpiar">
                                <FilterX :size="15" /><span style="font-size: 12px">{{ nActivos }}</span>
                            </Button>
                        </template>
                    </Column>
                </Row>
            </ColumnGroup>
        </DataTable>
    </div>
</template>
