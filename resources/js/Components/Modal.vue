<script setup lang="ts">
import Dialog from 'primevue/dialog';
import { computed } from 'vue';

/** Modal centrado: encabezado con ícono, título y subtítulo; el contenido se desplaza dentro. */
const props = withDefaults(defineProps<{ titulo: string; sub?: string; icono?: any; ancho?: 'sm' | 'md' | 'lg' | 'xl'; color?: string }>(), { ancho: 'md' });
const visible = defineModel<boolean>({ default: false });
const anchos = { sm: '480px', md: '760px', lg: '1040px', xl: '1200px' };
const estilo = computed(() => ({ width: anchos[props.ancho], maxWidth: '96vw' }));
</script>

<template>
    <Dialog v-model:visible="visible" modal :draggable="false" :style="estilo" :content-style="{ maxHeight: '72vh' }">
        <template #header>
            <div class="modal-h">
                <span class="m-ico" :style="color ? { background: color } : {}"><component :is="icono" v-if="icono" :size="22" /></span>
                <div style="min-width: 0">
                    <h2>{{ titulo }}</h2>
                    <small v-if="sub">{{ sub }}</small>
                    <slot name="sub" />
                </div>
            </div>
        </template>
        <slot />
        <template v-if="$slots.pie" #footer><slot name="pie" /></template>
    </Dialog>
</template>
