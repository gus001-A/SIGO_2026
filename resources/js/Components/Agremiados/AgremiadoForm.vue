<script setup lang="ts">
/** Alta y edición de agremiado: modal ancho a 3 columnas, fotos con vista previa y domicilio por código postal. */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';
import Password from 'primevue/password';
import { UserPlus, Pencil, Camera, UserRound, IdCard, House, Phone, Building2, KeyRound, LocateFixed, Save, Lock, Loader2 } from 'lucide-vue-next';
import Modal from '../Modal.vue';
import Campo from '../Campo.vue';
import CampoFecha from '../CampoFecha.vue';
import { enviar } from '@/acciones';
import { usePage } from '@inertiajs/vue3';
import { ahoraISO, fecha, hoyISO } from '@/utils';

const props = defineProps<{ registro: any | null; opciones: any; puedeAccesos: boolean }>();
const visible = defineModel<boolean>({ default: false });
const emit = defineEmits<{ guardado: [] }>();
const page = usePage<any>();

const vacio = () => ({
    nombre: '', apellido_paterno: '', apellido_materno: '', curp: '', fecha_nacimiento: null as string | null, sexo: 'Mujer', clave_elector: '', seccion: '',
    calle: '', colonia: '', cp: '', municipio: '', estado: 'Hidalgo', latitud: null as any, longitud: null as any,
    celular: '', emergencia_nombre: '', emergencia_telefono: '', puesto: '', coordinador_id: null as number | null, fecha_ingreso: hoyISO() as string | null, gps_autorizado: false,
    foto: null as File | null, ine_frente: null as File | null, ine_reverso: null as File | null,
    acceso: false, email: '', rol_id: null as number | null, password: '',
});
const form = useForm(vacio());
const previas = ref<Record<string, string | null>>({ foto: null, ine_frente: null, ine_reverso: null });
const colonias = ref<string[]>([]);
const buscandoCp = ref(false);
const avisoCp = ref('');
const ubicando = ref('');

watch(visible, (v) => {
    if (!v) return;
    form.clearErrors();
    const r = props.registro;
    Object.assign(form, vacio());
    colonias.value = [];
    avisoCp.value = '';
    if (r) {
        Object.keys(vacio()).forEach((k) => { if (k in r && !['foto', 'ine_frente', 'ine_reverso'].includes(k)) (form as any)[k] = r[k]; });
        form.acceso = !!r.usuario?.activo;
        form.email = r.usuario?.email ?? '';
        form.rol_id = r.usuario?.rol_id ?? null;
        previas.value = { foto: r.foto, ine_frente: r.ine_frente, ine_reverso: r.ine_reverso };
        if (r.colonia) colonias.value = [r.colonia];
    } else {
        previas.value = { foto: null, ine_frente: null, ine_reverso: null };
    }
});

// Vista previa local de las fotos antes de subirlas
const urls: string[] = [];
function foto(k: 'foto' | 'ine_frente' | 'ine_reverso', e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (!f) return;
    form[k] = f;
    const u = URL.createObjectURL(f);
    urls.push(u);
    previas.value[k] = u;
}
onBeforeUnmount(() => urls.forEach((u) => URL.revokeObjectURL(u)));

// Domicilio por código postal (catálogo SEPOMEX local)
watch(() => form.cp, async (cp) => {
    avisoCp.value = '';
    if (!/^\d{5}$/.test(cp || '')) return;
    buscandoCp.value = true;
    try {
        const r = (await axios.get(`/cp/${cp}`)).data;
        if (r.encontrado) {
            form.estado = r.estado;
            form.municipio = r.municipio;
            colonias.value = r.colonias;
            if (!r.colonias.includes(form.colonia)) form.colonia = r.colonias.length === 1 ? r.colonias[0] : form.colonia;
        } else avisoCp.value = 'Código postal no encontrado: captura estado, municipio y colonia.';
    } catch { avisoCp.value = 'No se pudo consultar el código postal.'; } finally { buscandoCp.value = false; }
});

function ubicar() {
    if (!navigator.geolocation) return (ubicando.value = 'El navegador no da la ubicación. Captúrala a mano.');
    ubicando.value = 'Obteniendo ubicación…';
    navigator.geolocation.getCurrentPosition(
        (p) => { form.latitud = +p.coords.latitude.toFixed(6); form.longitud = +p.coords.longitude.toFixed(6); ubicando.value = `Ubicación capturada (±${Math.round(p.coords.accuracy)} m)`; },
        () => (ubicando.value = 'El navegador no dio la ubicación. Captúrala a mano.'), { timeout: 8000 },
    );
}

const esCabeza = computed(() => props.registro && !props.registro.coordinador_id);
const coordinadores = computed(() => props.opciones.coordinadores.filter((c: any) => c.id !== props.registro?.id).map((c: any) => ({ value: c.id, label: `${c.nombre} · ${c.puesto}` })));
const rolPortal = computed(() => props.opciones.roles.find((r: any) => r.id === form.rol_id)?.portal);
const may = (k: 'curp' | 'clave_elector') => (form[k] = (form[k] || '').toUpperCase().replace(/[^A-Z0-9Ñ]/g, ''));
const solo = (k: 'celular' | 'emergencia_telefono' | 'seccion' | 'cp', n: number) => (form[k] = (form[k] || '').replace(/\D/g, '').slice(0, n));
const maxNac = (() => { const d = new Date(); d.setFullYear(d.getFullYear() - 15); return d.toISOString().slice(0, 10); })();

function guardar() {
    const url = props.registro ? `/agremiados/${props.registro.id}` : '/agremiados';
    enviar(form, 'post', url, { archivos: true, exito: () => { visible.value = false; emit('guardado'); } });
}
</script>

<template>
    <Modal v-model="visible" :titulo="registro ? 'Editar agremiado' : 'Alta de agremiado'" :sub="registro ? `${registro.folio} · ${registro.nombre_completo}` : 'Los campos con * son obligatorios'" :icono="registro ? Pencil : UserPlus" ancho="xl">
        <form id="fAgremiado" style="display: flex; flex-direction: column; gap: 16px" @submit.prevent="guardar">
            <div class="fsec">
                <h3><Camera :size="17" />Fotografías <small>Rostro e identificación oficial · vista previa antes de guardar</small></h3>
                <div class="upl">
                    <label v-for="[k, t] in ([['foto', 'Foto del rostro'], ['ine_frente', 'INE frente'], ['ine_reverso', 'INE reverso']] as const)" :key="k" class="tile" :class="{ has: previas[k] }" :style="previas[k] ? { backgroundImage: `url('${previas[k]}')` } : {}">
                        <span><component :is="k === 'foto' ? UserRound : IdCard" :size="22" style="display: block; margin: 0 auto 4px" />{{ t }}</span>
                        <input type="file" accept="image/*" :aria-label="t" @change="foto(k, $event)" />
                    </label>
                </div>
                <span v-if="form.errors.foto || form.errors.ine_frente || form.errors.ine_reverso" class="err" style="color: var(--bad); font-size: 12px; font-weight: 700">{{ form.errors.foto || form.errors.ine_frente || form.errors.ine_reverso }}</span>
            </div>

            <div class="fsec">
                <h3><UserRound :size="17" />Datos personales</h3>
                <div class="form-grid">
                    <Campo label="Nombre(s)" req :error="form.errors.nombre"><InputText v-model="form.nombre" maxlength="80" /></Campo>
                    <Campo label="Apellido paterno" req :error="form.errors.apellido_paterno"><InputText v-model="form.apellido_paterno" maxlength="60" /></Campo>
                    <Campo label="Apellido materno" :error="form.errors.apellido_materno"><InputText v-model="form.apellido_materno" maxlength="60" /></Campo>
                    <Campo label="CURP" req info="18 caracteres. Se valida que no esté registrada en otro agremiado." :error="form.errors.curp"><InputText v-model="form.curp" maxlength="18" class="mono" @input="may('curp')" /></Campo>
                    <Campo label="Fecha de nacimiento" req info="Debe tener al menos 15 años." :error="form.errors.fecha_nacimiento"><CampoFecha v-model="form.fecha_nacimiento" :max="maxNac" min="1920-01-01" /></Campo>
                    <Campo label="Sexo" req :error="form.errors.sexo"><Select v-model="form.sexo" :options="['Mujer', 'Hombre', 'Prefiero no decir']" /></Campo>
                </div>
            </div>

            <div class="fsec">
                <h3><IdCard :size="17" />Identificación (INE)</h3>
                <div class="form-grid">
                    <Campo label="Clave de elector" req info="18 caracteres, viene al frente de la credencial." :error="form.errors.clave_elector" :span="2"><InputText v-model="form.clave_elector" maxlength="18" class="mono" @input="may('clave_elector')" /></Campo>
                    <Campo label="Sección electoral" req info="Hasta 4 dígitos." :error="form.errors.seccion"><InputText v-model="form.seccion" inputmode="numeric" maxlength="4" class="mono" @input="solo('seccion', 4)" /></Campo>
                </div>
            </div>

            <div class="fsec">
                <h3><House :size="17" />Domicilio y ubicación</h3>
                <div class="form-grid">
                    <Campo label="Código postal" req info="Al escribirlo se llenan estado, municipio y colonias (catálogo SEPOMEX)." :error="form.errors.cp || avisoCp">
                        <div style="position: relative"><InputText v-model="form.cp" inputmode="numeric" maxlength="5" class="mono" style="width: 100%" @input="solo('cp', 5)" /><Loader2 v-if="buscandoCp" class="gira" :size="16" /></div>
                    </Campo>
                    <Campo label="Estado" req :error="form.errors.estado"><Select v-model="form.estado" :options="opciones.estados" editable /></Campo>
                    <Campo label="Municipio" req :error="form.errors.municipio"><InputText v-model="form.municipio" /></Campo>
                    <Campo label="Colonia o fraccionamiento" req info="Elige de la lista o escribe otra." :error="form.errors.colonia"><Select v-model="form.colonia" :options="colonias" editable filter placeholder="Elige o escribe" /></Campo>
                    <Campo label="Calle y número" req :error="form.errors.calle" :span="2"><InputText v-model="form.calle" maxlength="150" /></Campo>
                    <Campo label="Ubicación del domicilio" info="Se usa como punto de partida en el mapa del GPS.">
                        <Button type="button" severity="secondary" outlined @click="ubicar"><LocateFixed :size="16" /> Usar mi ubicación actual</Button>
                    </Campo>
                    <Campo label="Latitud" :error="form.errors.latitud"><InputText v-model="form.latitud" inputmode="decimal" placeholder="20.10110" class="mono" /></Campo>
                    <Campo label="Longitud" :error="form.errors.longitud"><InputText v-model="form.longitud" inputmode="decimal" placeholder="-98.75910" class="mono" /></Campo>
                    <small v-if="ubicando" class="muted full">{{ ubicando }}</small>
                </div>
            </div>

            <div class="fsec">
                <h3><Phone :size="17" />Contacto</h3>
                <div class="form-grid">
                    <Campo label="Teléfono celular" req info="10 dígitos." :error="form.errors.celular"><InputText v-model="form.celular" inputmode="numeric" class="mono" @input="solo('celular', 10)" /></Campo>
                    <Campo label="Contacto de emergencia" req info="Nombre y parentesco." :error="form.errors.emergencia_nombre"><InputText v-model="form.emergencia_nombre" maxlength="120" /></Campo>
                    <Campo label="Teléfono de emergencia" req info="10 dígitos, distinto al celular del agremiado." :error="form.errors.emergencia_telefono"><InputText v-model="form.emergencia_telefono" inputmode="numeric" class="mono" @input="solo('emergencia_telefono', 10)" /></Campo>
                </div>
            </div>

            <div class="fsec">
                <h3><Building2 :size="17" />Datos en la organización</h3>
                <div class="form-grid">
                    <Campo label="Puesto o función" req info="Elige de la lista o escribe uno nuevo." :error="form.errors.puesto"><Select v-model="form.puesto" :options="opciones.puestos" editable filter placeholder="Elige o escribe" /></Campo>
                    <Campo label="Coordinador asignado" :req="!esCabeza" info="Todo agremiado debe tener un coordinador arriba de él (excepto la cabeza de la red)." :error="form.errors.coordinador_id">
                        <Select v-model="form.coordinador_id" :options="coordinadores" option-label="label" option-value="value" filter :disabled="!!esCabeza" :placeholder="esCabeza ? 'Cabeza de la red' : '— Selecciona —'" />
                    </Campo>
                    <Campo label="Fecha de ingreso" req :error="form.errors.fecha_ingreso"><CampoFecha v-model="form.fecha_ingreso" :max="hoyISO()" :min="form.fecha_nacimiento" /></Campo>
                    <label class="check full">
                        <Checkbox v-model="form.gps_autorizado" binary />
                        <span><b>Autoriza seguimiento GPS por seguridad</b><br /><small class="muted">Solo durante actividades que lo requieran. Puede revocarlo desde su app en cualquier momento.</small></span>
                    </label>
                </div>
            </div>

            <div v-if="puedeAccesos" class="fsec">
                <h3><KeyRound :size="17" />Acceso al sistema <small>Cuenta para entrar al panel o a la app del agremiado</small></h3>
                <div class="form-grid">
                    <Campo label="¿Tiene acceso?"><div style="display: flex; align-items: center; gap: 10px; min-height: 38px"><ToggleSwitch v-model="form.acceso" /><span>{{ form.acceso ? 'Sí, cuenta activa' : 'Sin acceso' }}</span></div></Campo>
                    <Campo label="Perfil" :req="form.acceso" info="Define a qué módulos entra. El perfil de agremiado entra solo a la app." :error="form.errors.rol_id">
                        <Select v-model="form.rol_id" :options="opciones.roles" option-label="nombre" option-value="id" :disabled="!form.acceso" placeholder="— Selecciona —" />
                    </Campo>
                    <Campo label="Correo de acceso" :req="form.acceso" :error="form.errors.email"><InputText v-model="form.email" type="email" autocomplete="off" :disabled="!form.acceso" /></Campo>
                    <Campo :label="registro?.usuario ? 'Nueva contraseña' : 'Contraseña'" :req="form.acceso && !registro?.usuario" info="Mínimo 8 caracteres. En edición déjala vacía para conservar la actual." :error="form.errors.password">
                        <Password v-model="form.password" :feedback="false" toggle-mask fluid autocomplete="new-password" :disabled="!form.acceso" />
                    </Campo>
                    <small v-if="form.acceso && rolPortal" class="muted s2" style="align-self: end">Entrará a la app del agremiado (credencial, cuotas, tienda, cursos y GPS).</small>
                </div>
            </div>

            <div class="sello">
                <span><Lock :size="14" /> Al guardar se registra automáticamente</span>
                <span><b>{{ page.props.auth.user.nombre }}</b> · {{ page.props.auth.user.rol }} · <span class="mono">{{ fecha(ahoraISO()) }}</span></span>
            </div>
        </form>
        <template #pie>
            <Button label="Cancelar" severity="secondary" text @click="visible = false" />
            <Button type="submit" form="fAgremiado" class="btn-grad" :loading="form.processing"><Save :size="16" /> Guardar</Button>
        </template>
    </Modal>
</template>

<style scoped>
.gira { position: absolute; right: 10px; top: 11px; animation: spin 1s linear infinite; color: var(--teal); }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
