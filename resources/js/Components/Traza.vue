<script setup lang="ts">
import { UserRound } from 'lucide-vue-next';
import { fecha } from '@/utils';

/** Sello de trazabilidad: usuario · fecha · hora */
defineProps<{ t?: { usuario?: string | null; fecha?: string | null; hora?: string | null } | null; verbo?: string }>();
</script>

<template>
    <span v-if="t && t.fecha" class="traza">
        <UserRound :size="12" />
        <template v-if="verbo">{{ verbo }}</template>
        <b>{{ t.usuario || 'Sistema' }}</b>
        <time>{{ fecha(t.fecha) }}{{ t.hora ? ' ' + t.hora : '' }}</time>
    </span>
    <span v-else class="muted">—</span>
</template>
