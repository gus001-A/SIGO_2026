<script setup lang="ts">
import { computed } from 'vue';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

/** Fecha como texto "AAAA-MM-DD" (o "AAAA-MM-DD HH:mm"). Los días fuera de rango se bloquean; el único atajo es «Hoy». */
const props = defineProps<{ min?: string | null; max?: string | null; hora?: boolean; placeholder?: string; invalido?: boolean; id?: string; vista?: 'date' | 'month' }>();
const modelo = defineModel<string | null>();

const aFecha = (s?: string | null) => {
    if (!s) return null;
    const [y, m, d] = s.slice(0, 10).split('-').map(Number);
    const f = new Date(y, m - 1, d || 1);
    if (s.length > 10) f.setHours(+s.slice(11, 13), +s.slice(14, 16));
    return f;
};
const z = (n: number) => String(n).padStart(2, '0');
const valor = computed({
    get: () => aFecha(props.vista === 'month' && modelo.value ? modelo.value + '-01' : modelo.value),
    set: (d: Date | null) => {
        if (!d) return (modelo.value = null);
        if (props.vista === 'month') return (modelo.value = `${d.getFullYear()}-${z(d.getMonth() + 1)}`);
        modelo.value = `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}${props.hora ? ` ${z(d.getHours())}:${z(d.getMinutes())}` : ''}`;
    },
});
const minD = computed(() => aFecha(props.min));
const maxD = computed(() => {
    const d = aFecha(props.max);
    if (d && props.hora) d.setHours(23, 59);
    return d;
});
</script>

<template>
    <DatePicker v-model="valor" :input-id="id" :min-date="minD ?? undefined" :max-date="maxD ?? undefined" :show-time="hora" hour-format="24"
        :view="vista ?? 'date'" :date-format="vista === 'month' ? 'mm/yy' : 'dd/mm/yy'" show-icon icon-display="input" :manual-input="false" :show-button-bar="vista !== 'month'"
        :placeholder="placeholder ?? (vista === 'month' ? 'mm/aaaa' : hora ? 'dd/mm/aaaa hh:mm' : 'dd/mm/aaaa')" :invalid="invalido" fluid append-to="body">
        <template #buttonbar="{ todayCallback }">
            <div style="display: flex; justify-content: center; width: 100%">
                <Button label="Hoy" size="small" text @click="todayCallback" />
            </div>
        </template>
    </DatePicker>
</template>
