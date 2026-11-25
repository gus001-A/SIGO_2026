<script setup lang="ts">
import { iniciales } from '@/utils';

/** Avatar (foto o iniciales con color) + nombre + texto secundario. */
withDefaults(defineProps<{ p?: { nombre?: string; foto?: string | null; color?: string; folio?: string; puesto?: string } | null; sub?: string | null; size?: number; soloAvatar?: boolean }>(), { size: 34 });
</script>

<template>
    <div v-if="p" class="person">
        <span class="avatar" :style="{ background: p.color || '#2a7fc4', width: size + 'px', height: size + 'px', fontSize: Math.round(size / 2.8) + 'px' }">
            <img v-if="p.foto" :src="p.foto" alt="" />
            <template v-else>{{ iniciales(p.nombre) }}</template>
        </span>
        <div v-if="!soloAvatar" class="txt">
            <b>{{ p.nombre }}</b>
            <small>{{ sub ?? [p.folio, p.puesto].filter(Boolean).join(' · ') }}</small>
        </div>
    </div>
    <span v-else class="muted">—</span>
</template>
