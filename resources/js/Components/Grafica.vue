<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Chart, registerables, type ChartConfiguration } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = '"Nunito Sans", system-ui, sans-serif';
Chart.defaults.color = '#7b8ba3';

const props = defineProps<{ config: ChartConfiguration; etiqueta?: string }>();
const canvas = ref<HTMLCanvasElement>();
let chart: Chart | null = null;

const dibujar = () => {
    chart?.destroy();
    if (canvas.value) chart = new Chart(canvas.value, props.config);
};
onMounted(dibujar);
watch(() => props.config, dibujar, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div style="position: relative; width: 100%; height: 100%; min-height: 0"><canvas ref="canvas" role="img" :aria-label="etiqueta" /></div>
</template>
