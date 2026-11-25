<script setup lang="ts">
/** Carga de varios archivos con vista previa antes de subir: imágenes, videos y documentos. Permite quitar los ya guardados. */
import { computed, onBeforeUnmount, ref } from 'vue';
import { Upload, FileText, Video, X } from 'lucide-vue-next';
import { peso } from '@/utils';

const props = withDefaults(defineProps<{ existentes?: { id: number; nombre: string; tipo: string; url: string }[]; accept?: string; max?: number; texto?: string }>(), {
    existentes: () => [], accept: 'image/*,video/*,.pdf', max: 10, texto: 'Fotos, videos o documentos',
});
const nuevos = defineModel<File[]>({ default: () => [] });
const quitar = defineModel<number[]>('quitar', { default: () => [] });

const urls = ref<Map<File, string>>(new Map());
const urlDe = (f: File) => {
    if (!urls.value.has(f)) urls.value.set(f, URL.createObjectURL(f));
    return urls.value.get(f)!;
};
onBeforeUnmount(() => urls.value.forEach((u) => URL.revokeObjectURL(u)));

const guardados = computed(() => props.existentes.filter((e) => !quitar.value.includes(e.id)));
const total = computed(() => guardados.value.length + nuevos.value.length);

function elegir(e: Event) {
    const input = e.target as HTMLInputElement;
    const lista = [...(input.files ?? [])].slice(0, Math.max(0, props.max - total.value));
    nuevos.value = [...nuevos.value, ...lista];
    input.value = '';
}
const tipo = (f: File) => (f.type.startsWith('image/') ? 'imagen' : f.type.startsWith('video/') ? 'video' : 'documento');
</script>

<template>
    <div class="subir">
        <label class="zona" :class="{ lleno: total >= max }">
            <Upload :size="20" />
            <span><b>{{ texto }}</b><small>Haz clic para elegir · máximo {{ max }} · verás la vista previa antes de guardar</small></span>
            <input type="file" multiple :accept="accept" :disabled="total >= max" @change="elegir" />
        </label>
        <div v-if="total" class="evid" style="margin-top: 10px">
            <a v-for="e in guardados" :key="'e' + e.id" class="item" :href="e.url" target="_blank" :title="e.nombre">
                <img v-if="e.tipo === 'imagen'" :src="e.url" :alt="e.nombre" />
                <Video v-else-if="e.tipo === 'video'" :size="22" />
                <FileText v-else :size="22" />
                <button type="button" class="x" aria-label="Quitar" @click.prevent="quitar = [...quitar, e.id]"><X :size="12" /></button>
            </a>
            <div v-for="(f, i) in nuevos" :key="'n' + i" class="item nuevo" :title="`${f.name} · ${peso(f.size)}`">
                <img v-if="tipo(f) === 'imagen'" :src="urlDe(f)" :alt="f.name" />
                <video v-else-if="tipo(f) === 'video'" :src="urlDe(f)" muted />
                <FileText v-else :size="22" />
                <button type="button" class="x" aria-label="Quitar" @click="nuevos = nuevos.filter((_, k) => k !== i)"><X :size="12" /></button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.zona { display: flex; gap: 12px; align-items: center; border: 1.5px dashed var(--line); border-radius: 12px; padding: 12px 14px; cursor: pointer; background: var(--surface-2); color: var(--ink-2); position: relative; transition: border-color .15s; }
.zona:hover { border-color: var(--teal); }
.zona.lleno { opacity: .55; cursor: not-allowed; }
.zona b { display: block; font-size: 13px; }
.zona small { color: var(--ink-3); font-size: 11.5px; }
.zona input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.item.nuevo { outline: 2px solid var(--teal); outline-offset: 1px; }
</style>
