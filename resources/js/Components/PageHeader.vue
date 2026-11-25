<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{ titulo: string; desc?: string; icono?: any }>();
</script>

<template>
    <Head :title="titulo" />
    <div class="page-h">
        <span class="ico"><component :is="icono" v-if="icono" :size="22" /></span>
        <div>
            <h1>{{ titulo }}</h1>
            <p v-if="desc">{{ desc }}</p>
        </div>
        <div class="actions"><slot /></div>
    </div>
</template>
