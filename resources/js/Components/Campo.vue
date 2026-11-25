<script setup lang="ts">
import { ref } from 'vue';
import Popover from 'primevue/popover';

/** Etiqueta + campo + error. La ayuda va en el ícono "i" junto a la etiqueta para que todo quede alineado. */
const props = defineProps<{ label: string; req?: boolean; info?: string; error?: string; span?: 1 | 2 | 3 | 'full'; for?: string }>();
const pop = ref();
const clase = props.span === 'full' ? 'full' : props.span && props.span > 1 ? 's' + props.span : '';
</script>

<template>
    <div class="campo" :class="[clase, { invalido: !!error }]">
        <label :for="props.for">
            {{ label }}<span v-if="req" class="req">*</span>
            <button v-if="info" type="button" class="info" aria-label="Más información" @click="pop.toggle($event)">i</button>
        </label>
        <slot />
        <span v-if="error" class="err">{{ error }}</span>
        <Popover v-if="info" ref="pop"><div style="max-width: 280px; font-size: 12.5px">{{ info }}</div></Popover>
    </div>
</template>
