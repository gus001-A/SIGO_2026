<script setup lang="ts">
import { Eye, Pencil, Trash2 } from 'lucide-vue-next';

/** Botones de acción de una fila: ícono con fondo suave de color. */
defineProps<{ ver?: boolean; editar?: boolean; eliminar?: boolean }>();
defineEmits<{ ver: []; editar: []; eliminar: [] }>();
</script>

<template>
    <div class="acc">
        <button v-if="ver" type="button" class="ver" v-tooltip.top="'Ver detalle'" aria-label="Ver detalle" @click.stop="$emit('ver')"><Eye :size="16" /></button>
        <slot />
        <button v-if="editar" type="button" class="editar" v-tooltip.top="'Editar'" aria-label="Editar" @click.stop="$emit('editar')"><Pencil :size="16" /></button>
        <button v-if="eliminar" type="button" class="eliminar" v-tooltip.top="'Eliminar'" aria-label="Eliminar" @click.stop="$emit('eliminar')"><Trash2 :size="16" /></button>
    </div>
</template>
