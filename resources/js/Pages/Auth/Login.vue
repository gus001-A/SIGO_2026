<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import { LogIn, Mail, KeyRound, Users, Network, Wallet, HandHeart, Store, MapPin, GraduationCap, ShieldCheck, MousePointerClick } from 'lucide-vue-next';
import Campo from '@/Components/Campo.vue';
import AlertaDulce from '@/Components/AlertaDulce.vue';

const props = defineProps<{ demo: { email: string; nombre: string; rol: string; color: string }[]; demoPassword: string | null }>();
const form = useForm({ email: '', password: '', remember: false });
const entrar = () => form.post('/login', { onFinish: () => form.reset('password') });
function usar(email: string) {
    form.email = email;
    form.password = props.demoPassword ?? '';
}

const saludo = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';
});

const modulos = [
    { icono: Users, titulo: 'Padrón', texto: 'Expediente digital con fotos e INE', color: '#2a7fc4' },
    { icono: Network, titulo: 'Coordinación', texto: 'Tu red organizada en árbol', color: '#18a3a0' },
    { icono: Wallet, titulo: 'Cuotas', texto: 'Cobros y adeudos al día', color: '#6fae3e' },
    { icono: HandHeart, titulo: 'Apoyos', texto: 'Entregas con evidencia', color: '#f2b632' },
    { icono: Store, titulo: 'Tienda', texto: 'Inventario y pedidos', color: '#ee8a2c' },
    { icono: MapPin, titulo: 'GPS', texto: 'Actividades en tiempo real', color: '#5fb6e8' },
];

// Red decorativa: nodos y conexiones en los colores del logo
const nodos = [
    { x: 60, y: 70, r: 9, c: '#5fb6e8' }, { x: 190, y: 40, r: 6, c: '#18a3a0' }, { x: 300, y: 110, r: 11, c: '#f2b632' },
    { x: 420, y: 55, r: 7, c: '#6fae3e' }, { x: 520, y: 130, r: 9, c: '#ee8a2c' }, { x: 140, y: 160, r: 7, c: '#6fae3e' },
    { x: 240, y: 210, r: 8, c: '#5fb6e8' }, { x: 380, y: 200, r: 6, c: '#18a3a0' }, { x: 470, y: 240, r: 8, c: '#f2b632' },
];
const enlaces = [[0, 1], [1, 2], [2, 3], [3, 4], [0, 5], [5, 6], [6, 2], [2, 7], [7, 8], [4, 8], [6, 7], [1, 5]];
</script>

<template>
    <Head title="Iniciar sesión" />
    <div class="login">
        <section class="lado">
            <svg class="red" viewBox="0 0 580 280" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <line v-for="([a, b], i) in enlaces" :key="'l' + i" :x1="nodos[a].x" :y1="nodos[a].y" :x2="nodos[b].x" :y2="nodos[b].y" class="enl" :style="{ animationDelay: i * 0.15 + 's' }" />
                <g v-for="(n, i) in nodos" :key="'n' + i">
                    <circle :cx="n.x" :cy="n.y" :r="n.r * 2.2" :fill="n.c" class="halo" :style="{ animationDelay: i * 0.4 + 's' }" />
                    <circle :cx="n.x" :cy="n.y" :r="n.r" :fill="n.c" />
                </g>
            </svg>
            <div class="burbuja b1" /><div class="burbuja b2" />

            <div class="lado-in">
                <img src="/img/sigo-logo.png" alt="SIGO · Sistema Integral de Gestión Organizacional" class="logo" />
                <h1>Tu organización,<br /><span class="resalta">conectada y en orden.</span></h1>
                <p>Todo lo que tu sindicato necesita en un solo lugar, con la trazabilidad de cada acción: quién, cuándo y qué cambió.</p>

                <div class="mods">
                    <div v-for="(m, i) in modulos" :key="m.titulo" class="mod" :style="{ animationDelay: 0.1 + i * 0.08 + 's' }">
                        <span class="mi" :style="{ background: m.color }"><component :is="m.icono" :size="18" /></span>
                        <div><b>{{ m.titulo }}</b><small>{{ m.texto }}</small></div>
                    </div>
                </div>

                <div class="pie-lado">
                    <span><ShieldCheck :size="15" /> Permisos por perfil</span>
                    <span><GraduationCap :size="15" /> Capacitación SAIS</span>
                    <span class="lineas"><i /><i /><i /><i /><i /></span>
                </div>
            </div>
        </section>

        <section class="form-lado">
            <form class="caja" @submit.prevent="entrar">
                <span class="hola">{{ saludo }} 👋</span>
                <h2>Inicia sesión</h2>
                <p class="muted sub">Entra con tu correo y contraseña para continuar.</p>

                <Campo label="Correo" for="email" :error="form.errors.email">
                    <div class="inp"><Mail :size="17" class="ic" /><InputText id="email" v-model="form.email" type="email" autocomplete="username" autofocus placeholder="tucorreo@organizacion.mx" :invalid="!!form.errors.email" /></div>
                </Campo>
                <Campo label="Contraseña" for="password" :error="form.errors.password" style="margin-top: 14px">
                    <div class="inp"><KeyRound :size="17" class="ic" /><Password v-model="form.password" input-id="password" :feedback="false" toggle-mask autocomplete="current-password" placeholder="••••••••" fluid /></div>
                </Campo>
                <label class="rec"><Checkbox v-model="form.remember" binary /> Mantener la sesión iniciada</label>
                <Button type="submit" class="btn-grad entrar" :loading="form.processing"><LogIn :size="18" /> Entrar a SIGO</Button>

                <div v-if="demo.length" class="demo">
                    <div class="demo-h"><MousePointerClick :size="14" /> Accesos de prueba <em>solo en local</em><code>{{ demoPassword }}</code></div>
                    <div class="demo-g">
                        <button v-for="d in demo" :key="d.email" type="button" :class="{ sel: form.email === d.email }" @click="usar(d.email)">
                            <span class="av" :style="{ background: d.color }">{{ d.rol?.charAt(0) }}</span>
                            <span class="dt"><b>{{ d.rol }}</b><small>{{ d.email }}</small></span>
                        </button>
                    </div>
                </div>

                <p class="copy">© {{ new Date().getFullYear() }} SIGO GAVE</p>
            </form>
        </section>
        <AlertaDulce />
    </div>
</template>

<style scoped>
.login { height: 100dvh; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); overflow: auto; }

/* Lado de marca */
.lado { background: radial-gradient(120% 90% at 0% 0%, #1f6fae 0%, transparent 55%), radial-gradient(90% 80% at 100% 100%, #18a3a0 0%, transparent 60%), #0c3154; color: #fff; display: grid; place-items: center; padding: 36px 44px; position: relative; overflow: hidden; }
.red { position: absolute; inset: auto 0 0 0; width: 100%; height: 46%; opacity: .5; }
.enl { stroke: rgba(255, 255, 255, .35); stroke-width: 1.4; stroke-dasharray: 260; stroke-dashoffset: 260; animation: trazo 2.4s ease forwards; }
.halo { opacity: .25; transform-box: fill-box; transform-origin: center; animation: pulso 3.2s ease-in-out infinite; }
@keyframes trazo { to { stroke-dashoffset: 0; } }
@keyframes pulso { 0%, 100% { transform: scale(.6); opacity: .35; } 50% { transform: scale(1.15); opacity: .08; } }
.burbuja { position: absolute; border-radius: 50%; filter: blur(2px); }
.b1 { width: 380px; height: 380px; right: -140px; top: -140px; border: 56px solid rgba(255, 255, 255, .05); }
.b2 { width: 180px; height: 180px; left: -60px; top: 38%; background: rgba(242, 182, 50, .10); filter: blur(30px); }

.lado-in { max-width: 560px; width: 100%; position: relative; z-index: 1; }
.logo { width: 150px; background: #fff; border-radius: 20px; padding: 12px 16px; box-shadow: 0 18px 40px rgba(0, 0, 0, .25); }
.etq { display: inline-flex; gap: 6px; align-items: center; margin-top: 22px; font-size: 12px; font-weight: 700; letter-spacing: .02em; background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .18); padding: 5px 12px; border-radius: 999px; }
.lado h1 { color: #fff; font-size: 38px; font-weight: 800; margin-top: 24px; line-height: 1.1; }
.resalta { background: linear-gradient(90deg, #5fb6e8, #3fd0c9, #9bd45a, #f6c75a); -webkit-background-clip: text; background-clip: text; color: transparent; }
.lado p { opacity: .85; font-size: 15px; line-height: 1.55; max-width: 480px; margin: 12px 0 0; }

.mods { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 24px; }
.mod { display: flex; gap: 10px; align-items: center; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .14); backdrop-filter: blur(8px); border-radius: 14px; padding: 10px 12px; opacity: 0; animation: sube .5s ease forwards; transition: transform .2s, background .2s; }
.mod:hover { transform: translateY(-3px); background: rgba(255, 255, 255, .14); }
.mi { flex-shrink: 0; width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; color: #fff; box-shadow: 0 6px 14px rgba(0, 0, 0, .2); }
.mod b { display: block; font-size: 13.5px; }
.mod small { display: block; font-size: 11.5px; opacity: .75; line-height: 1.3; }
@keyframes sube { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

.pie-lado { display: flex; gap: 16px; align-items: center; margin-top: 22px; font-size: 12.5px; opacity: .85; flex-wrap: wrap; }
.pie-lado span { display: inline-flex; gap: 6px; align-items: center; }
.lineas { margin-left: auto; gap: 4px !important; }
.lineas i { width: 22px; height: 4px; border-radius: 4px; }
.lineas i:nth-child(1) { background: #2a7fc4; } .lineas i:nth-child(2) { background: #18a3a0; } .lineas i:nth-child(3) { background: #6fae3e; } .lineas i:nth-child(4) { background: #f2b632; } .lineas i:nth-child(5) { background: #ee8a2c; }

/* Formulario */
.form-lado { display: grid; place-items: center; padding: 30px; background: radial-gradient(70% 50% at 100% 0%, #e4f1f8 0%, transparent 70%), radial-gradient(60% 40% at 0% 100%, #eaf7f1 0%, transparent 70%), #f7fafc; position: relative; }
.caja { width: min(430px, 100%); display: flex; flex-direction: column; background: #fff; border-radius: 22px; padding: 30px 30px 22px; box-shadow: 0 24px 60px rgba(15, 59, 99, .12), 0 2px 6px rgba(15, 59, 99, .05); border: 1px solid #e6edf3; position: relative; overflow: hidden; }
.caja::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 5px; background: var(--grad-logo); }
.hola { font-size: 13px; font-weight: 700; color: var(--teal); }
.caja h2 { font-size: 28px; font-weight: 800; color: var(--brand); margin-top: 2px; }
.sub { margin: 4px 0 20px; }
.inp { position: relative; }
.inp .ic { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--ink-3); z-index: 1; pointer-events: none; }
.inp :deep(input) { width: 100%; padding-left: 38px; height: 44px; border-radius: 12px; }
.inp:focus-within .ic { color: var(--teal); }
.rec { display: flex; gap: 8px; align-items: center; margin: 14px 0 10px; font-size: 13px; color: var(--ink-2); cursor: pointer; }
.entrar { width: 100%; height: 46px; border-radius: 12px; font-size: 15px; margin-top: 4px; }

.demo { margin-top: 20px; border-top: 1px dashed var(--line); padding-top: 14px; }
.demo-h { display: flex; gap: 6px; align-items: center; font-size: 12px; font-weight: 700; color: var(--ink-2); margin-bottom: 8px; }
.demo-h em { font-style: normal; font-weight: 600; color: var(--ink-3); }
.demo-h code { margin-left: auto; background: var(--surface-2); padding: 2px 7px; border-radius: 6px; font-size: 11.5px; }
.demo-g { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.demo-g button { display: flex; gap: 8px; align-items: center; border: 1px solid var(--line); background: #fff; border-radius: 11px; padding: 6px 8px; cursor: pointer; font-family: inherit; text-align: left; min-width: 0; transition: border-color .15s, background .15s, transform .15s; }
.demo-g button:hover { border-color: var(--teal); background: #eef8f8; transform: translateY(-1px); }
.demo-g button.sel { border-color: var(--teal); background: #e6f6f5; box-shadow: 0 0 0 2px rgba(24, 163, 160, .15); }
.av { flex-shrink: 0; width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 13px; }
.dt { min-width: 0; display: flex; flex-direction: column; }
.dt b { font-size: 12.5px; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dt small { font-size: 11px; color: var(--ink-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.copy { margin: 16px 0 0; font-size: 11.5px; color: var(--ink-3); text-align: center; }

@media (max-height: 760px) { .mods { margin-top: 16px; } .lado h1 { font-size: 32px; } .mod small { display: none; } }
@media (max-width: 1100px) { .mods { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 860px) { .login { grid-template-columns: 1fr; } .lado { padding: 28px; } .lado h1 { font-size: 26px; } .mods, .pie-lado { display: none; } .caja { padding: 24px 20px 18px; } .demo-g { grid-template-columns: 1fr; } .demo-h em { display: none; } }
</style>
