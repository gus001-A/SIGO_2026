<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import { alertaEstado as a, cerrarAlerta } from '@/alerta';

const tecla = (e: KeyboardEvent) => {
    if (!a.visible) return;
    if (e.key === 'Escape') cerrarAlerta(false);
    if (e.key === 'Enter') cerrarAlerta(true);
};
onMounted(() => window.addEventListener('keydown', tecla));
onBeforeUnmount(() => window.removeEventListener('keydown', tecla));
</script>

<template>
    <Teleport to="body">
        <Transition name="dulce">
            <div v-if="a.visible" class="dulce-fondo" @click.self="cerrarAlerta(false)">
                <div class="dulce" role="alertdialog" aria-modal="true" :aria-label="a.titulo">
                    <div class="d-ico" :class="a.tipo">
                        <svg v-if="a.tipo === 'success'" viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" /><path d="M14 27l8 8 16-17" /></svg>
                        <svg v-else-if="a.tipo === 'error'" viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" /><path d="M17 17l18 18M35 17L17 35" /></svg>
                        <span v-else>{{ a.tipo === 'question' ? '?' : a.tipo === 'info' ? 'i' : '!' }}</span>
                    </div>
                    <h2>{{ a.titulo }}</h2>
                    <p v-if="a.texto" class="d-txt">{{ a.texto }}</p>
                    <ul v-if="a.lista.length" class="d-lista">
                        <li v-for="(l, i) in a.lista" :key="i">{{ l }}</li>
                    </ul>
                    <div class="d-btns">
                        <button type="button" class="d-ok" :class="{ peligro: a.peligro }" autofocus @click="cerrarAlerta(true)">{{ a.aceptar }}</button>
                        <button v-if="a.cancelar" type="button" class="d-no" @click="cerrarAlerta(false)">{{ a.cancelar }}</button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.dulce-fondo { position: fixed; inset: 0; z-index: 3000; background: rgba(5, 15, 18, .48); display: grid; place-items: center; padding: 16px; backdrop-filter: blur(2px); }
.dulce { width: min(470px, 100%); background: #fff; border-radius: 20px; padding: 26px 26px 22px; text-align: center; box-shadow: 0 24px 60px rgba(5, 15, 18, .3); }
.dulce h2 { font-size: 21px; color: var(--ink); margin-top: 12px; }
.d-txt { color: var(--ink-2); margin: 8px 0 0; white-space: pre-line; }
.d-lista { text-align: left; margin: 12px 0 0; padding: 10px 14px 10px 30px; background: var(--bad-soft); border-radius: 12px; color: #8a1f1f; font-weight: 600; font-size: 13px; max-height: 220px; overflow: auto; }
.d-btns { display: flex; gap: 10px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
.d-btns button { border: 0; border-radius: 10px; padding: 10px 20px; font-weight: 800; font-size: 14px; cursor: pointer; font-family: inherit; transition: filter .15s, transform .12s; }
.d-btns button:active { transform: scale(.97); }
.d-ok { background: var(--grad); color: #fff; box-shadow: 0 6px 14px rgba(15, 59, 99, .25); }
.d-ok.peligro { background: var(--bad); box-shadow: 0 6px 14px rgba(214, 69, 69, .3); }
.d-ok:hover { filter: brightness(1.08); }
.d-no { background: var(--surface-2); color: var(--ink-2); }
.d-ico { width: 82px; height: 82px; margin: 0 auto; border-radius: 50%; display: grid; place-items: center; border: 4px solid; font-family: var(--font-display); font-size: 44px; font-weight: 800; animation: dulce-pop .5s cubic-bezier(.2, .9, .3, 1.4) both; }
.d-ico svg { width: 60px; height: 60px; fill: none; stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }
.d-ico svg circle { stroke: none; }
.d-ico svg path { stroke-dasharray: 60; stroke-dashoffset: 60; animation: dulce-trazo .5s .25s ease forwards; }
.d-ico.success { border-color: #a5dcb6; color: var(--ok); } .d-ico.success svg path { stroke: var(--ok); }
.d-ico.error { border-color: #f2b2b2; color: var(--bad); } .d-ico.error svg path { stroke: var(--bad); }
.d-ico.warning { border-color: #f8d486; color: #e0950a; }
.d-ico.question { border-color: #a9cbe8; color: #1f6fae; }
.d-ico.info { border-color: #9ad9d7; color: var(--teal); }
@keyframes dulce-pop { from { transform: scale(.4); opacity: 0; } to { transform: none; opacity: 1; } }
@keyframes dulce-trazo { to { stroke-dashoffset: 0; } }
.dulce-enter-active, .dulce-leave-active { transition: opacity .18s; }
.dulce-enter-active .dulce { animation: dulce-pop .3s ease both; }
.dulce-enter-from, .dulce-leave-to { opacity: 0; }
</style>
