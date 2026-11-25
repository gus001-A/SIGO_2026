<script setup lang="ts">
import { FileText, Video } from 'lucide-vue-next';

/** Miniaturas de archivos guardados; abren el archivo en otra pestaña. */
defineProps<{ archivos: { id: number; nombre: string; tipo: string; url: string }[]; sm?: boolean }>();
</script>

<template>
    <div class="evid" :class="{ sm }">
        <a v-for="a in archivos" :key="a.id" class="item" :href="a.url" target="_blank" :title="a.nombre" @click.stop>
            <img v-if="a.tipo === 'imagen'" :src="a.url" :alt="a.nombre" />
            <Video v-else-if="a.tipo === 'video'" :size="sm ? 16 : 22" />
            <FileText v-else :size="sm ? 16 : 22" />
        </a>
        <span v-if="!archivos.length" class="muted">Sin archivos</span>
    </div>
</template>
