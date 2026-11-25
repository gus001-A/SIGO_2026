<script setup lang="ts">
/** Barra superior con los módulos e íconos; el área de trabajo ocupa el resto de la pantalla sin desplazarse. */
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import Popover from 'primevue/popover';
import { useToast } from 'primevue/usetoast';
import { LogOut, Smartphone, ChevronDown, Menu as MenuIco, X } from 'lucide-vue-next';
import AlertaDulce from '@/Components/AlertaDulce.vue';
import Persona from '@/Components/Persona.vue';
import { icono } from '@/iconos';
import { alerta } from '@/alerta';
import { sinBase } from '@/base';

const page = usePage<any>();
const toast = useToast();
const user = computed(() => page.props.auth.user);
const menu = computed(() => page.props.menu as any[]);
const insignias = computed(() => (page.props.insignias ?? {}) as Record<string, number>);
const ruta = computed(() => sinBase(page.url.split('?')[0]));
const activo = (url: string) => (url === '/' ? ruta.value === '/' : ruta.value === url || ruta.value.startsWith(url + '/'));
const puedeApp = computed(() => (page.props.auth.permisos?.agremiados ?? 'n') !== 'n');
const movil = ref(false);
const pop = ref();

// Avisos del servidor: éxito como aviso arriba a la derecha; errores de reglas de negocio como alerta
watch(() => page.props.flash, (f: any) => {
    if (f?.success) toast.add({ severity: 'success', summary: f.success, detail: `${user.value?.nombre ?? ''} · ${new Date().toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })}`, life: 4000 });
    if (f?.error) alerta.error('No se pudo completar', f.error);
}, { deep: true, immediate: true });

router.on('navigate', () => (movil.value = false));

async function salir() {
    if (await alerta.confirmar('¿Cerrar sesión?', 'Se registrará en la bitácora.', { aceptar: 'Sí, salir' })) router.post('/logout');
}
</script>

<template>
    <div class="shell">
        <header class="nav">
            <Link href="/" class="logo" aria-label="Inicio"><img src="/img/sigo-logo.png" alt="SIGO" /><span><b>SIGO</b><small>Gestión Organizacional</small></span></Link>
            <button class="hamb" aria-label="Menú" @click="movil = !movil"><component :is="movil ? X : MenuIco" :size="20" /></button>
            <nav class="mods" :class="{ abierto: movil }" aria-label="Módulos">
                <Link v-for="m in menu" :key="m.id" :href="m.url" class="mod" :class="{ act: activo(m.url) }" :aria-current="activo(m.url) ? 'page' : undefined" v-tooltip.bottom="m.nombre">
                    <component :is="icono(m.icono)" :size="18" />
                    <span class="txt">{{ m.nombre }}</span>
                    <span v-if="insignias[m.id]" class="badge">{{ insignias[m.id] }}</span>
                </Link>
                <Link v-if="puedeApp" href="/app" class="mod" :class="{ act: activo('/app') }" v-tooltip.bottom="'App del agremiado'">
                    <Smartphone :size="18" /><span class="txt">App</span>
                </Link>
            </nav>
            <button class="me" @click="pop.toggle($event)">
                <Persona :p="{ nombre: user?.nombre, foto: user?.foto, color: user?.rol_color }" :sub="user?.rol" :size="32" />
                <ChevronDown :size="16" />
            </button>
            <Popover ref="pop">
                <div class="me-menu">
                    <div><b>{{ user?.nombre }}</b><small>{{ user?.email }}</small><span class="pill brand" style="margin-top: 4px">{{ user?.rol }}</span></div>
                    <button type="button" @click="salir"><LogOut :size="16" /> Cerrar sesión</button>
                </div>
            </Popover>
        </header>
        <main class="main">
            <slot />
        </main>
        <Toast position="top-right" :style="{ top: '74px' }" />
        <AlertaDulce />
    </div>
</template>

<style scoped>
.nav { height: var(--nav-h); flex-shrink: 0; display: flex; align-items: center; gap: 10px; padding: 0 16px; background: #fff; position: relative; z-index: 20; box-shadow: 0 1px 0 var(--line); }
.nav::after { content: ""; position: absolute; left: 0; right: 0; bottom: -3px; height: 3px; background: var(--grad-logo); }
.logo { display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--ink); flex-shrink: 0; padding-right: 8px; border-right: 1px solid var(--line); }
.logo img { height: 44px; width: auto; mix-blend-mode: multiply; }
.logo b { display: block; font-family: var(--font-display); font-size: 17px; font-weight: 800; color: var(--brand); line-height: 1; letter-spacing: .02em; }
.logo small { font-size: 10.5px; color: var(--ink-3); }
.mods { display: flex; align-items: center; gap: 2px; flex: 1; min-width: 0; overflow-x: auto; scrollbar-width: none; }
.mod { position: relative; display: flex; align-items: center; gap: 7px; padding: 8px 10px; border-radius: 10px; color: var(--ink-2); text-decoration: none; font-weight: 700; font-size: 13px; white-space: nowrap; transition: background .15s, color .15s; }
.mod .lucide { color: var(--azul); }
.mod:hover { background: var(--brand-soft); color: var(--brand); }
.mod.act { background: var(--grad); color: #fff; box-shadow: 0 6px 14px rgba(15, 59, 99, .25); }
.mod.act .lucide { color: #fff; }
.badge { min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--naranja); color: #fff; font-size: 10.5px; display: grid; place-items: center; font-weight: 800; }
.me { margin-left: auto; display: flex; align-items: center; gap: 6px; border: 1px solid var(--line); background: #fff; border-radius: 12px; padding: 4px 8px 4px 5px; cursor: pointer; color: var(--ink-3); max-width: 260px; font-family: inherit; text-align: left; }
.me:hover { border-color: var(--teal); }
.me-menu { display: flex; flex-direction: column; gap: 12px; min-width: 220px; }
.me-menu b { display: block; } .me-menu small { display: block; color: var(--ink-3); }
.me-menu button { display: flex; gap: 8px; align-items: center; border: 0; background: var(--bad-soft); color: var(--bad); border-radius: 10px; padding: 8px 12px; font-weight: 800; cursor: pointer; font-family: inherit; }
.hamb { display: none; border: 0; background: var(--surface-2); border-radius: 10px; width: 38px; height: 38px; place-items: center; cursor: pointer; color: var(--brand); }
/* En pantallas medianas solo el módulo activo muestra su nombre; los demás, ícono con su nombre al pasar el cursor */
@media (max-width: 1480px) { .mod:not(.act) .txt { display: none; } .mod { padding: 9px 10px; } .mod .badge { position: absolute; top: 0; right: 0; } }
@media (max-width: 900px) {
    .hamb { display: grid; }
    .logo span { display: none; }
    .mods { display: none; position: absolute; top: calc(var(--nav-h) + 3px); left: 0; right: 0; background: #fff; flex-direction: column; align-items: stretch; padding: 10px; box-shadow: var(--shadow-up); }
    .mods.abierto { display: flex; }
    .mods .mod .txt { display: inline !important; }
    .me :deep(.txt) { display: none; }
}
</style>
