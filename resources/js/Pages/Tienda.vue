<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import {
    Store, Package, Boxes, Truck, ShoppingCart, History, Plus, Pencil, Save, Lock, PackagePlus, Check, X, Trash2, Paperclip, Tag, BadgeDollarSign, Gauge, EyeOff,
} from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import TablaSigo, { type Col } from '@/Components/TablaSigo.vue';
import Persona from '@/Components/Persona.vue';
import Traza from '@/Components/Traza.vue';
import TrazaBoton from '@/Components/TrazaBoton.vue';
import Acciones from '@/Components/Acciones.vue';
import Modal from '@/Components/Modal.vue';
import Campo from '@/Components/Campo.vue';
import SubirArchivos from '@/Components/SubirArchivos.vue';
import Evidencias from '@/Components/Evidencias.vue';
import { icono } from '@/iconos';
import { enviar, eliminar, confirmarYEnviar } from '@/acciones';
import { ahoraISO, dinero, fecha, telefono, TONOS } from '@/utils';

const props = defineProps<{ tab: string; productos: any[]; proveedores: any[]; pedidos: any[]; movimientos: any[]; agremiados: any[]; iconos: any[]; formasPago: string[]; puede: { editar: boolean; eliminar: boolean } }>();
const page = usePage<any>();

const tab = ref(props.tab);
watch(tab, (t) => history.replaceState(history.state, '', `${location.pathname}?tab=${t}`));
const TABS = [
    { value: 'productos', label: 'Productos y servicios', icon: Package },
    { value: 'inventario', label: 'Inventario', icon: Boxes },
    { value: 'pedidos', label: 'Pedidos', icon: ShoppingCart },
    { value: 'proveedores', label: 'Proveedores', icon: Truck },
    { value: 'movimientos', label: 'Movimientos', icon: History },
];
const pendientes = computed(() => props.pedidos.filter((p) => p.estado === 'Pendiente').length);
const conStock = computed(() => props.productos.filter((p) => p.categoria === 'Producto'));
const opcProv = computed(() => props.proveedores.map((v) => ({ value: v.id, label: v.nombre })));
const opcProd = computed(() => props.productos.filter((p) => p.publicado).map((p) => ({ value: p.id, label: `${p.nombre} · ${dinero(p.precio)}${p.stock !== null ? ` · ${p.stock} disp.` : ''}` })));

// ---------------------------------------------------------------- columnas
const colInv: Col[] = [
    { key: 'nombre', label: 'Producto' },
    { key: 'proveedor', label: 'Proveedor', filtro: 'lista' },
    { key: 'costo', label: 'Costo', tipo: 'dinero', alinear: 'right' },
    { key: 'precio', label: 'Precio', tipo: 'dinero', alinear: 'right' },
    { key: 'margen', label: 'Margen', alinear: 'right', valor: (f) => f.margen + '%' },
    { key: 'stock', label: 'Existencia', alinear: 'right', tipo: 'numero' },
    { key: 'minmax', label: 'Mín / Máx', alinear: 'right', valor: (f) => `${f.minimo} / ${f.maximo}`, filtro: false },
    { key: 'bajo', label: 'Estado', filtro: 'lista', valor: (f) => (f.bajo ? 'Bajo el mínimo' : 'Suficiente') },
];
const colPed: Col[] = [
    { key: 'folio', label: 'Folio', tipo: 'mono', ancho: '110px' },
    { key: 'cliente', label: 'Agremiado', ancho: '220px' },
    { key: 'resumen', label: 'Artículos' },
    { key: 'total', label: 'Total', tipo: 'dinero', alinear: 'right' },
    { key: 'forma_pago', label: 'Forma de pago', filtro: 'lista' },
    { key: 'origen', label: 'Origen', filtro: 'lista' },
    { key: 'fecha', label: 'Fecha', filtro: 'fecha', tipo: 'fechahora' },
    { key: 'estado', label: 'Estado', filtro: 'lista' },
];
const colProv: Col[] = [
    { key: 'nombre', label: 'Proveedor' },
    { key: 'contacto', label: 'Contacto' },
    { key: 'telefono', label: 'Teléfono', tipo: 'mono', valor: (f) => telefono(f.telefono) },
    { key: 'rfc', label: 'RFC', tipo: 'mono' },
    { key: 'productos', label: 'Productos', tipo: 'numero', alinear: 'right' },
    { key: 'alta', label: 'Alta', valor: (f) => `${f.traza.creado?.usuario} ${fecha(f.traza.creado?.fecha)}` },
];
const colMov: Col[] = [
    { key: 'fecha', label: 'Fecha', filtro: 'fecha', tipo: 'fechahora', ancho: '150px' },
    { key: 'producto', label: 'Producto', filtro: 'lista' },
    { key: 'tipo', label: 'Movimiento', filtro: 'lista' },
    { key: 'cantidad', label: 'Cantidad', alinear: 'right', valor: (f) => (f.cantidad > 0 ? '+' : '') + f.cantidad },
    { key: 'stock', label: 'Existencia', alinear: 'right', tipo: 'numero' },
    { key: 'proveedor', label: 'Proveedor', filtro: 'lista' },
    { key: 'nota', label: 'Nota' },
    { key: 'usuario', label: 'Usuario', filtro: 'lista' },
];

// ---------------------------------------------------------------- productos
const pVisible = ref(false);
const pReg = ref<any>(null);
const fP = useForm({ nombre: '', categoria: 'Producto', proveedor_id: null as number | null, icono: 'package', descripcion: '', costo: 0 as number | null, precio: null as number | null, stock: null as number | null, minimo: null as number | null, maximo: null as number | null, publicado: true, archivos: [] as File[], quitar: [] as number[] });
function abrirProducto(r: any = null) {
    pReg.value = r;
    fP.clearErrors();
    fP.defaults({ nombre: r?.nombre ?? '', categoria: r?.categoria ?? 'Producto', proveedor_id: r?.proveedor_id ?? null, icono: r?.icono ?? 'package', descripcion: r?.descripcion ?? '', costo: r?.costo ?? 0, precio: r?.precio ?? null, stock: r?.stock ?? null, minimo: r?.minimo ?? null, maximo: r?.maximo ?? null, publicado: r?.publicado ?? true, archivos: [], quitar: [] });
    fP.reset();
    detVisible.value = false;
    pVisible.value = true;
}
const esServ = computed(() => fP.categoria === 'Servicio');
watch(esServ, (s) => { if (s) { fP.stock = null; fP.minimo = null; fP.maximo = null; fP.proveedor_id = null; } });
const margen = computed(() => (fP.precio ? `${dinero(fP.precio - (fP.costo || 0))} · ${Math.round(((fP.precio - (fP.costo || 0)) / fP.precio) * 100)}%` : '—'));
const guardarProducto = () => enviar(fP, 'post', pReg.value ? `/tienda/productos/${pReg.value.id}` : '/tienda/productos', { archivos: true, exito: () => (pVisible.value = false) });

const det = ref<any>(null);
const detVisible = ref(false);
const verProducto = (p: any) => { det.value = p; detVisible.value = true; };
const fotoDe = (p: any) => p.archivos.find((a: any) => a.tipo === 'imagen')?.url;
const pct = (p: any) => Math.min(100, (p.stock / Math.max(1, p.maximo)) * 100);

// ---------------------------------------------------------------- inventario
const eVisible = ref(false);
const eProd = ref<any>(null);
const fE = useForm({ cantidad: 1 as number | null, proveedor_id: null as number | null, nota: '' });
function entrada(p: any) {
    eProd.value = p;
    fE.clearErrors();
    fE.cantidad = Math.max(1, (p.maximo ?? 0) - (p.stock ?? 0));
    fE.proveedor_id = p.proveedor_id;
    fE.nota = '';
    eVisible.value = true;
}

// ---------------------------------------------------------------- pedidos
const pedVisible = ref(false);
const fPed = useForm({ agremiado_id: null as number | null, forma_pago: 'Descuento vía nómina', nota: '', items: [{ producto_id: null as number | null, cantidad: 1 }] });
function nuevoPedido() { fPed.reset(); fPed.clearErrors(); fPed.items = [{ producto_id: null, cantidad: 1 }]; pedVisible.value = true; }
const totalPed = computed(() => fPed.items.reduce((s, it) => s + (props.productos.find((p) => p.id === it.producto_id)?.precio ?? 0) * (it.cantidad || 0), 0));
const verPed = ref<any>(null);
const verPedVisible = ref(false);
const opcAg = props.agremiados.map((a) => ({ value: a.id, label: `${a.nombre} · ${a.folio}` }));
const estadoPedido = (p: any, estado: 'Entregado' | 'Cancelado') =>
    confirmarYEnviar(estado === 'Entregado' ? '¿Marcar como entregado?' : '¿Cancelar el pedido?', `${p.folio} · ${p.cliente} · ${dinero(p.total)}${estado === 'Cancelado' ? '\nLa existencia de los productos se devuelve al inventario.' : ''}`, 'put', `/tienda/pedidos/${p.id}/estado`, { estado }, { aceptar: estado === 'Entregado' ? 'Sí, entregado' : 'Sí, cancelar', peligro: estado === 'Cancelado', exito: () => (verPedVisible.value = false) });

// ---------------------------------------------------------------- proveedores
const vVisible = ref(false);
const vReg = ref<any>(null);
const fV = useForm({ nombre: '', rfc: '', contacto: '', telefono: '', correo: '' });
function abrirProveedor(r: any = null) {
    vReg.value = r;
    fV.clearErrors();
    fV.defaults({ nombre: r?.nombre ?? '', rfc: r?.rfc ?? '', contacto: r?.contacto ?? '', telefono: r?.telefono ?? '', correo: r?.correo ?? '' });
    fV.reset();
    vVisible.value = true;
}
</script>

<template>
    <div class="page">
        <PageHeader titulo="Tienda" desc="Productos y servicios para agremiados, inventario, pedidos y proveedores" :icono="Store">
            <template v-if="puede.editar">
                <Button v-if="tab === 'proveedores'" class="btn-grad" @click="abrirProveedor()"><Plus :size="17" /> Nuevo proveedor</Button>
                <Button v-else-if="tab === 'pedidos'" class="btn-grad" @click="nuevoPedido"><Plus :size="17" /> Nuevo pedido</Button>
                <Button v-else class="btn-grad" @click="abrirProducto()"><Plus :size="17" /> Nuevo producto o servicio</Button>
            </template>
        </PageHeader>

        <div class="tabs">
            <button v-for="t in TABS" :key="t.value" :class="{ on: tab === t.value }" @click="tab = t.value">
                <component :is="t.icon" :size="16" />{{ t.label }}
                <span v-if="t.value === 'pedidos' && pendientes" class="pill warn">{{ pendientes }}</span>
                <span v-if="t.value === 'inventario' && conStock.filter((p) => p.bajo).length" class="pill bad">{{ conStock.filter((p) => p.bajo).length }}</span>
            </button>
        </div>

        <!-- Productos: tarjetas -->
        <section v-if="tab === 'productos'" class="panel" style="flex: 0 1 auto">
            <div class="panel-b prod-wrap">
                <div class="prod-grid anim">
                    <button v-for="p in productos" :key="p.id" class="prod" @click="verProducto(p)">
                        <div class="ph" :style="fotoDe(p) ? { backgroundImage: `url('${fotoDe(p)}')`, backgroundSize: 'cover', backgroundPosition: 'center' } : { background: p.color }">
                            <component :is="icono(p.icono)" v-if="!fotoDe(p)" :size="40" />
                            <span v-if="!p.publicado" class="oculto"><EyeOff :size="12" /> No publicado</span>
                        </div>
                        <div class="pb">
                            <div class="row"><b>{{ p.nombre }}</b><span class="pill" :class="TONOS[p.categoria]">{{ p.categoria }}</span></div>
                            <div class="row"><span class="muted">Precio a agremiados</span><b class="num">{{ dinero(p.precio) }}</b></div>
                            <template v-if="p.stock !== null">
                                <div class="stock-bar" :title="`Existencia ${p.stock} · mínimo ${p.minimo} · máximo ${p.maximo}`"><i :class="{ low: p.bajo }" :style="{ width: pct(p) + '%' }" /><u :style="{ left: (p.minimo / Math.max(1, p.maximo)) * 100 + '%' }" /></div>
                                <small :style="p.bajo ? 'color: var(--bad); font-weight: 700' : 'color: var(--ink-3)'">Existencia {{ p.stock }} · mín {{ p.minimo }} · máx {{ p.maximo }}</small>
                            </template>
                            <small v-else class="muted">Servicio · sin inventario</small>
                        </div>
                    </button>
                    <p v-if="!productos.length" class="empty">Aún no hay productos.</p>
                </div>
            </div>
        </section>

        <section v-else-if="tab === 'inventario'" class="panel tabla">
            <TablaSigo :filas="conStock" :columnas="colInv" :clase-fila="(f) => (f.bajo ? 'bajo' : '')">
                <template #nombre="{ fila }"><div class="person"><span class="avatar" :style="{ background: fila.color, width: '30px', height: '30px' }"><component :is="icono(fila.icono)" :size="16" /></span><b>{{ fila.nombre }}</b></div></template>
                <template #stock="{ fila }"><b :style="{ color: fila.bajo ? 'var(--bad)' : '' }">{{ fila.stock }}</b></template>
                <template #bajo="{ fila }"><span class="pill" :class="fila.bajo ? 'bad' : 'ok'">{{ fila.bajo ? 'Bajo el mínimo' : 'Suficiente' }}</span></template>
                <template #acciones="{ fila }">
                    <Acciones ver @ver="verProducto(fila)">
                        <button v-if="puede.editar" class="verde" v-tooltip.top="'Registrar entrada'" aria-label="Registrar entrada" @click="entrada(fila)"><Truck :size="16" /></button>
                    </Acciones>
                </template>
            </TablaSigo>
        </section>

        <section v-else-if="tab === 'pedidos'" class="panel tabla">
            <TablaSigo :filas="pedidos" :columnas="colPed">
                <template #cliente="{ fila }"><Persona :p="fila.agremiado" /></template>
                <template #resumen="{ fila }"><span class="muted">{{ fila.resumen }}</span></template>
                <template #origen="{ fila }"><span class="pill" :class="fila.origen === 'App' ? 'teal' : 'brand'">{{ fila.origen }}</span></template>
                <template #estado="{ fila }"><span class="pill" :class="TONOS[fila.estado]">{{ fila.estado }}</span></template>
                <template #acciones="{ fila }">
                    <Acciones ver @ver="verPed = fila; verPedVisible = true">
                        <template v-if="puede.editar && fila.estado === 'Pendiente'">
                            <button class="verde" v-tooltip.top="'Marcar entregado'" aria-label="Marcar entregado" @click="estadoPedido(fila, 'Entregado')"><Check :size="16" /></button>
                            <button class="eliminar" v-tooltip.top="'Cancelar pedido'" aria-label="Cancelar pedido" @click="estadoPedido(fila, 'Cancelado')"><X :size="16" /></button>
                        </template>
                    </Acciones>
                </template>
            </TablaSigo>
        </section>

        <section v-else-if="tab === 'proveedores'" class="panel tabla">
            <TablaSigo :filas="proveedores" :columnas="colProv">
                <template #nombre="{ fila }"><b>{{ fila.nombre }}</b><br /><small class="muted">{{ fila.correo }}</small></template>
                <template #alta="{ fila }"><Traza :t="fila.traza.creado" /></template>
                <template #acciones="{ fila }"><Acciones :editar="puede.editar" :eliminar="puede.eliminar && !fila.productos" @editar="abrirProveedor(fila)" @eliminar="eliminar(`/tienda/proveedores/${fila.id}`, 'al proveedor ' + fila.nombre)" /></template>
            </TablaSigo>
        </section>

        <section v-else class="panel tabla">
            <TablaSigo :filas="movimientos" :columnas="colMov">
                <template #tipo="{ fila }"><span class="pill" :class="TONOS[fila.tipo]">{{ fila.tipo }}</span></template>
                <template #cantidad="{ fila }"><b :style="{ color: fila.cantidad > 0 ? 'var(--ok)' : 'var(--bad)' }" class="num">{{ fila.cantidad > 0 ? '+' : '' }}{{ fila.cantidad }}</b></template>
            </TablaSigo>
        </section>

        <!-- Detalle de producto -->
        <Modal v-model="detVisible" :titulo="det?.nombre ?? ''" :sub="det ? `${det.categoria}${det.proveedor ? ' · ' + det.proveedor : ''}` : ''" :icono="det ? icono(det.icono) : Package" :color="det?.color" ancho="md">
            <template v-if="det">
                <div class="row" style="margin-bottom: 12px">
                    <Button v-if="puede.editar" size="small" @click="abrirProducto(det)"><Pencil :size="15" /> Editar producto</Button>
                    <Button v-if="puede.eliminar && !det.stock" size="small" severity="danger" outlined @click="eliminar(`/tienda/productos/${det.id}`, 'el producto ' + det.nombre, undefined, () => (detVisible = false))"><Trash2 :size="15" /> Eliminar</Button>
                    <span style="margin-left: auto"><TrazaBoton :traza="det.traza" tabla="productos" :id="det.id" :titulo="det.nombre" /></span>
                </div>
                <p style="margin: 0 0 12px">{{ det.descripcion }}</p>
                <dl class="dl">
                    <div><dt><Tag :size="12" />Costo de compra</dt><dd class="num">{{ det.costo ? dinero(det.costo) : '—' }}</dd></div>
                    <div><dt><BadgeDollarSign :size="12" />Precio de venta</dt><dd class="num">{{ dinero(det.precio) }} <span class="muted">· margen {{ det.margen }}%</span></dd></div>
                    <div><dt><Gauge :size="12" />Existencia · mín / máx</dt><dd>{{ det.stock === null ? 'Servicio' : `${det.stock} · ${det.minimo} / ${det.maximo}` }}</dd></div>
                    <div><dt>Vendidos</dt><dd>{{ det.vendidos }}</dd></div>
                    <div><dt>Publicado en la app</dt><dd>{{ det.publicado ? 'Sí' : 'No' }}</dd></div>
                    <div class="full"><dt><Paperclip :size="12" />Fotografías, videos y documentación</dt><dd style="margin-top: 6px"><Evidencias :archivos="det.archivos" /></dd></div>
                </dl>
            </template>
        </Modal>

        <!-- Alta / edición de producto -->
        <Modal v-model="pVisible" :titulo="pReg ? 'Editar producto' : 'Nuevo producto o servicio'" :sub="pReg ? pReg.nombre : 'Se publica en la tienda de la app de los agremiados'" :icono="pReg ? Pencil : PackagePlus" ancho="lg">
            <form id="fProd" style="display: flex; flex-direction: column; gap: 16px" @submit.prevent="guardarProducto">
                <div class="fsec">
                    <h3><Package :size="17" />Datos del producto o servicio</h3>
                    <div class="form-grid">
                        <Campo label="Nombre" req :error="fP.errors.nombre"><InputText v-model="fP.nombre" maxlength="150" /></Campo>
                        <Campo label="Tipo" req info="Los servicios no llevan inventario." :error="fP.errors.categoria"><SelectButton v-model="fP.categoria" :options="['Producto', 'Servicio']" :allow-empty="false" /></Campo>
                        <Campo label="Proveedor" :req="!esServ" :error="fP.errors.proveedor_id"><Select v-model="fP.proveedor_id" :options="opcProv" option-label="label" option-value="value" :disabled="esServ" show-clear placeholder="— Selecciona —" /></Campo>
                        <Campo label="Ícono" info="Se muestra cuando el producto no tiene fotografía." :error="fP.errors.icono">
                            <Select v-model="fP.icono" :options="iconos" option-label="label" option-value="value">
                                <template #value="{ value }"><span style="display: flex; gap: 8px; align-items: center"><component :is="icono(value)" :size="16" />{{ iconos.find((i) => i.value === value)?.label }}</span></template>
                                <template #option="{ option }"><span style="display: flex; gap: 8px; align-items: center"><component :is="icono(option.value)" :size="16" />{{ option.label }}</span></template>
                            </Select>
                        </Campo>
                        <Campo label="Publicado en la app"><div style="display: flex; gap: 10px; align-items: center; min-height: 38px"><ToggleSwitch v-model="fP.publicado" />{{ fP.publicado ? 'Visible para agremiados' : 'Oculto' }}</div></Campo>
                        <div />
                        <Campo label="Descripción detallada" req :error="fP.errors.descripcion" span="full"><Textarea v-model="fP.descripcion" rows="2" auto-resize /></Campo>
                    </div>
                </div>
                <div class="fsec">
                    <h3><BadgeDollarSign :size="17" />Precios e inventario</h3>
                    <div class="form-grid">
                        <Campo label="Costo de compra" :error="fP.errors.costo"><InputNumber v-model="fP.costo" mode="currency" currency="MXN" locale="es-MX" :min="0" fluid /></Campo>
                        <Campo label="Precio de venta" req info="No puede ser menor al costo." :error="fP.errors.precio"><InputNumber v-model="fP.precio" mode="currency" currency="MXN" locale="es-MX" :min="0" fluid /></Campo>
                        <Campo label="Margen"><div class="mono" style="padding: 8px 0" :style="{ color: fP.precio && (fP.costo || 0) > fP.precio ? 'var(--bad)' : '' }">{{ margen }}</div></Campo>
                        <Campo label="Existencia actual" :req="!esServ" :info="pReg ? 'Si la cambias se registra un movimiento de ajuste.' : undefined" :error="fP.errors.stock"><InputNumber v-model="fP.stock" :min="0" :use-grouping="false" :disabled="esServ" fluid /></Campo>
                        <Campo label="Mínimo" :req="!esServ" info="Avisa cuando la existencia baje de aquí." :error="fP.errors.minimo"><InputNumber v-model="fP.minimo" :min="0" :use-grouping="false" :disabled="esServ" fluid /></Campo>
                        <Campo label="Máximo" :req="!esServ" :error="fP.errors.maximo"><InputNumber v-model="fP.maximo" :min="1" :use-grouping="false" :disabled="esServ" fluid /></Campo>
                    </div>
                </div>
                <div class="fsec">
                    <h3><Paperclip :size="17" />Fotografías, videos y documentación</h3>
                    <SubirArchivos v-model="fP.archivos" v-model:quitar="fP.quitar" :existentes="pReg?.archivos ?? []" accept="image/*,video/*,.pdf,.doc,.docx" :max="12" texto="Fotos, videos, PDF o Word" />
                </div>
                <div class="sello"><span><Lock :size="14" /> Al guardar se registra automáticamente</span><span><b>{{ page.props.auth.user.nombre }}</b> · <span class="mono">{{ fecha(ahoraISO()) }}</span></span></div>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="pVisible = false" />
                <Button type="submit" form="fProd" class="btn-grad" :loading="fP.processing"><Save :size="16" /> Guardar</Button>
            </template>
        </Modal>

        <!-- Entrada de inventario -->
        <Modal v-model="eVisible" titulo="Registrar entrada" :sub="eProd ? `${eProd.nombre} · existencia ${eProd.stock}` : ''" :icono="Truck" ancho="sm">
            <form id="fEnt" style="display: flex; flex-direction: column; gap: 12px" @submit.prevent="enviar(fE, 'post', `/tienda/productos/${eProd.id}/entrada`, { exito: () => (eVisible = false) })">
                <Campo label="Cantidad" req info="Por defecto, lo necesario para llegar al máximo." :error="fE.errors.cantidad"><InputNumber v-model="fE.cantidad" :min="1" :use-grouping="false" fluid /></Campo>
                <Campo label="Proveedor" :error="fE.errors.proveedor_id"><Select v-model="fE.proveedor_id" :options="opcProv" option-label="label" option-value="value" show-clear /></Campo>
                <Campo label="Nota" :error="fE.errors.nota"><InputText v-model="fE.nota" maxlength="255" placeholder="Factura, remisión…" /></Campo>
                <small class="muted">Existencia resultante: <b>{{ (eProd?.stock ?? 0) + (fE.cantidad || 0) }}</b></small>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="eVisible = false" />
                <Button type="submit" form="fEnt" class="btn-grad" :loading="fE.processing"><Save :size="16" /> Registrar entrada</Button>
            </template>
        </Modal>

        <!-- Nuevo pedido -->
        <Modal v-model="pedVisible" titulo="Nuevo pedido" sub="Venta registrada en tesorería; descuenta inventario" :icono="ShoppingCart" ancho="md">
            <form id="fPed" style="display: flex; flex-direction: column; gap: 12px" @submit.prevent="enviar(fPed, 'post', '/tienda/pedidos', { exito: () => (pedVisible = false) })">
                <div class="form-grid g2">
                    <Campo label="Agremiado" req :error="fPed.errors.agremiado_id"><Select v-model="fPed.agremiado_id" :options="opcAg" option-label="label" option-value="value" filter placeholder="— Selecciona —" /></Campo>
                    <Campo label="Forma de pago" req info="Elige de la lista o escribe otra." :error="fPed.errors.forma_pago"><Select v-model="fPed.forma_pago" :options="formasPago" editable /></Campo>
                </div>
                <Campo label="Artículos" req :error="fPed.errors.items">
                    <div v-for="(it, i) in fPed.items" :key="i" class="item-ped">
                        <Select v-model="it.producto_id" :options="opcProd" option-label="label" option-value="value" filter placeholder="Producto o servicio" style="flex: 1" />
                        <InputNumber v-model="it.cantidad" :min="1" :max="999" show-buttons button-layout="horizontal" :use-grouping="false" input-style="width: 50px; text-align: center" />
                        <button type="button" class="borrar" :disabled="fPed.items.length === 1" aria-label="Quitar" @click="fPed.items.splice(i, 1)"><Trash2 :size="14" /></button>
                    </div>
                    <Button type="button" size="small" text @click="fPed.items.push({ producto_id: null, cantidad: 1 })"><Plus :size="15" /> Agregar artículo</Button>
                </Campo>
                <Campo label="Nota"><InputText v-model="fPed.nota" maxlength="255" /></Campo>
                <div class="row"><span class="muted">Total</span><b style="font-family: var(--font-display); font-size: 22px; color: var(--brand)">{{ dinero(totalPed) }}</b></div>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="pedVisible = false" />
                <Button type="submit" form="fPed" class="btn-grad" :loading="fPed.processing"><Save :size="16" /> Registrar pedido</Button>
            </template>
        </Modal>

        <Modal v-model="verPedVisible" :titulo="verPed?.folio ?? ''" :sub="verPed ? `${verPed.estado} · ${fecha(verPed.fecha)} · desde ${verPed.origen === 'App' ? 'la app' : 'el panel'}` : ''" :icono="ShoppingCart" ancho="md">
            <template v-if="verPed">
                <div class="row" style="margin-bottom: 12px"><Persona :p="verPed.agremiado" :size="40" /><TrazaBoton :traza="verPed.traza" tabla="pedidos" :id="verPed.id" :titulo="verPed.folio" /></div>
                <table class="mini-t">
                    <tr><th>Artículo</th><th class="r">Cantidad</th><th class="r">Precio</th><th class="r">Importe</th></tr>
                    <tr v-for="(a, i) in verPed.articulos" :key="i"><td>{{ a.producto }}</td><td class="r">{{ a.cantidad }}</td><td class="r">{{ dinero(a.precio) }}</td><td class="r">{{ dinero(a.precio * a.cantidad) }}</td></tr>
                    <tr><td colspan="3" class="r"><b>Total</b></td><td class="r"><b>{{ dinero(verPed.total) }}</b></td></tr>
                </table>
                <p class="muted" style="margin: 10px 0 0">Forma de pago: {{ verPed.forma_pago }}{{ verPed.nota ? ' · ' + verPed.nota : '' }}</p>
            </template>
            <template v-if="verPed && puede.editar && verPed.estado === 'Pendiente'" #pie>
                <Button severity="danger" outlined @click="estadoPedido(verPed, 'Cancelado')"><X :size="16" /> Cancelar pedido</Button>
                <Button class="btn-grad" @click="estadoPedido(verPed, 'Entregado')"><Check :size="16" /> Marcar entregado</Button>
            </template>
        </Modal>

        <!-- Proveedor -->
        <Modal v-model="vVisible" :titulo="vReg ? 'Editar proveedor' : 'Nuevo proveedor'" sub="Podrás asignarlo a los productos de la tienda" :icono="Truck" ancho="md">
            <form id="fProv" class="form-grid g2" @submit.prevent="enviar(fV, vReg ? 'put' : 'post', vReg ? `/tienda/proveedores/${vReg.id}` : '/tienda/proveedores', { exito: () => (vVisible = false) })">
                <Campo label="Razón social o nombre" req :error="fV.errors.nombre" span="full"><InputText v-model="fV.nombre" maxlength="150" /></Campo>
                <Campo label="RFC" req info="12 caracteres (empresa) o 13 (persona física)." :error="fV.errors.rfc"><InputText v-model="fV.rfc" maxlength="13" class="mono" @input="fV.rfc = fV.rfc.toUpperCase()" /></Campo>
                <Campo label="Persona de contacto" req :error="fV.errors.contacto"><InputText v-model="fV.contacto" maxlength="120" /></Campo>
                <Campo label="Teléfono" req info="10 dígitos." :error="fV.errors.telefono"><InputText v-model="fV.telefono" inputmode="numeric" maxlength="10" class="mono" @input="fV.telefono = fV.telefono.replace(/\D/g, '')" /></Campo>
                <Campo label="Correo" :error="fV.errors.correo"><InputText v-model="fV.correo" type="email" maxlength="120" /></Campo>
            </form>
            <template #pie>
                <Button label="Cancelar" severity="secondary" text @click="vVisible = false" />
                <Button type="submit" form="fProv" class="btn-grad" :loading="fV.processing"><Save :size="16" /> Guardar</Button>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.tabs { display: flex; gap: 6px; flex-wrap: wrap; flex-shrink: 0; }
.tabs button { display: flex; gap: 7px; align-items: center; border: 1px solid var(--line); background: #fff; border-radius: 11px; padding: 8px 13px; font-weight: 700; color: var(--ink-2); cursor: pointer; font-family: inherit; font-size: 13px; transition: all .15s; }
.tabs button:hover { border-color: var(--teal); color: var(--brand); }
.tabs button.on { background: var(--grad); color: #fff; border-color: transparent; box-shadow: 0 6px 14px rgba(15, 59, 99, .22); }
.prod-wrap { overflow: auto; }
.prod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
.prod { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; background: #fff; display: flex; flex-direction: column; text-align: left; padding: 0; cursor: pointer; font-family: inherit; transition: transform .2s, box-shadow .2s, border-color .2s; }
.prod:hover { transform: translateY(-3px); box-shadow: var(--shadow-up); border-color: var(--teal); }
.ph { aspect-ratio: 16 / 8; display: grid; place-items: center; color: #fff; position: relative; overflow: hidden; }
.ph::after { content: ""; position: absolute; inset: auto -20% -60% auto; width: 70%; aspect-ratio: 1; border-radius: 50%; background: rgba(255, 255, 255, .12); }
.prod:hover .ph .lucide { transform: scale(1.12) rotate(-4deg); }
.ph .lucide { transition: transform .3s; }
.oculto { position: absolute; top: 8px; left: 8px; background: rgba(0, 0, 0, .55); color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 99px; display: flex; gap: 4px; align-items: center; z-index: 1; }
.pb { padding: 12px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
.stock-bar { height: 6px; border-radius: 99px; background: var(--surface-2); position: relative; overflow: hidden; }
.stock-bar i { position: absolute; inset: 0 auto 0 0; background: var(--ok); border-radius: 99px; transition: width .8s; }
.stock-bar i.low { background: var(--bad); }
.stock-bar u { position: absolute; top: -2px; bottom: -2px; width: 2px; background: var(--warn); }
.item-ped { display: flex; gap: 8px; align-items: center; margin-bottom: 6px; }
.borrar { border: 0; background: var(--bad-soft); color: var(--bad); border-radius: 8px; width: 32px; height: 32px; display: grid; place-items: center; cursor: pointer; }
.borrar:disabled { opacity: .35; cursor: not-allowed; }
.mini-t { width: 100%; border-collapse: collapse; font-size: 13px; }
.mini-t th { text-align: left; font-size: 11px; text-transform: uppercase; color: var(--ink-3); padding: 6px; border-bottom: 1px solid var(--line); background: var(--surface-2); }
.mini-t td { padding: 7px 6px; border-bottom: 1px solid var(--line); }
.mini-t .r { text-align: right; }
:deep(tr.bajo > td) { background: #fff7f7; }
</style>
