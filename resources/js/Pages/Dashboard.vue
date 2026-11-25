<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard, Users, Wallet, HandHeart, Package, TrendingUp, Siren, PackageX, FileWarning, CircleCheck, ArrowRight, ChartColumn, ChartPie, Store, ShoppingBag } from 'lucide-vue-next';
import PageHeader from '@/Components/PageHeader.vue';
import Persona from '@/Components/Persona.vue';
import Grafica from '@/Components/Grafica.vue';
import { icono } from '@/iconos';
import { dinero } from '@/utils';

const props = defineProps<{ kpis: any; alertas: any; graficas: any; puede: Record<string, boolean> }>();

const total = computed(() => props.alertas.adeudosTotal + props.alertas.bajosTotal + props.alertas.sinDocsTotal);
const COLORES = ['#0f3b63', '#18a3a0', '#2a7fc4', '#ee8a2c', '#6fae3e', '#f2b632', '#3d5f8f'];
const tip = { backgroundColor: '#0c2742', padding: 10, cornerRadius: 10, titleFont: { weight: 700 as const }, boxPadding: 4, usePointStyle: true };
const leyenda = { display: true, position: 'bottom' as const, labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 12 } };
const base = { responsive: true, maintainAspectRatio: false, animation: { duration: 900, easing: 'easeOutQuart' as const } };
const ejeDinero = { beginAtZero: true, grid: { color: '#e1e8f0' }, border: { display: false }, ticks: { callback: (v: any) => '$' + Number(v).toLocaleString('es-MX'), maxTicksLimit: 5 } };

const cfgCuotas = computed<any>(() => {
    const g = props.graficas.cuotas;
    return {
        type: 'bar',
        data: {
            labels: g.labels,
            datasets: [
                { type: 'bar', label: 'Recaudado', data: g.recaudado, backgroundColor: (c: any) => { const a = c.chart.chartArea; if (!a) return '#2a7fc4'; const gr = c.chart.ctx.createLinearGradient(0, a.bottom, 0, a.top); gr.addColorStop(0, '#8fd9d6'); gr.addColorStop(1, '#2a7fc4'); return gr; }, borderRadius: 10, borderSkipped: false, maxBarThickness: 40, order: 2 },
                { type: 'line', label: 'Esperado', data: g.labels.map(() => g.esperado), borderColor: '#ee8a2c', borderDash: [6, 5], borderWidth: 2, pointRadius: 0, order: 1 },
            ],
        },
        options: { ...base, interaction: { mode: 'index', intersect: false }, scales: { x: { grid: { display: false }, border: { display: false } }, y: ejeDinero },
            plugins: { legend: leyenda, tooltip: { ...tip, callbacks: { label: (c: any) => ` ${c.dataset.label}: ${dinero(c.parsed.y)}`, footer: (it: any[]) => { const r = it.find((x) => x.dataset.label === 'Recaudado'); return r && g.esperado ? `Cumplimiento: ${Math.round((r.parsed.y / g.esperado) * 100)}%` : ''; } } } } },
    };
});

const cfgMun = computed<any>(() => {
    const g = props.graficas.municipios;
    const total = g.datos.reduce((s: number, n: number) => s + n, 0);
    const centro = { id: 'centro', afterDraw(chart: any) {
        const { ctx, chartArea: a } = chart; const x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
        ctx.save(); ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillStyle = '#0c2742'; ctx.font = '800 28px Montserrat, sans-serif'; ctx.fillText(String(total), x, y - 6);
        ctx.fillStyle = '#7b8ba3'; ctx.font = '700 10px "Nunito Sans", sans-serif'; ctx.fillText('AGREMIADOS', x, y + 15); ctx.restore();
    } };
    return {
        type: 'doughnut', plugins: [centro],
        data: { labels: g.labels, datasets: [{ data: g.datos, backgroundColor: COLORES, borderWidth: 3, borderColor: '#fff', hoverOffset: 10, borderRadius: 6, spacing: 2 }] },
        options: { ...base, cutout: '68%', layout: { padding: 6 }, plugins: { legend: { ...leyenda, position: 'right' }, tooltip: { ...tip, callbacks: { label: (c: any) => ` ${c.label}: ${c.parsed} (${Math.round((c.parsed / total) * 100)}%)` } } } },
    };
});

const cfgApoyos = computed<any>(() => {
    const g = props.graficas.apoyos;
    return {
        type: 'bar',
        data: { labels: g.labels, datasets: g.series.map((s: any, i: number) => ({ label: s.tipo, data: s.datos, backgroundColor: COLORES[i % COLORES.length], borderRadius: 6, borderSkipped: false, maxBarThickness: 42 })) },
        options: { ...base, interaction: { mode: 'index', intersect: false }, scales: { x: { stacked: true, grid: { display: false }, border: { display: false } }, y: { ...ejeDinero, stacked: true } },
            plugins: { legend: leyenda, tooltip: { ...tip, filter: (it: any) => it.parsed.y > 0, callbacks: { label: (c: any) => ` ${c.dataset.label}: ${dinero(c.parsed.y)}`, footer: (it: any[]) => `Total del mes: ${dinero(it.reduce((s, x) => s + x.parsed.y, 0))}` } } } },
    };
});

const cfgVentas = computed<any>(() => {
    const v = props.graficas.ventas;
    return {
        type: 'bar',
        data: { labels: v.map((p: any) => p.nombre), datasets: [{ label: 'Unidades', data: v.map((p: any) => p.vendidos), backgroundColor: v.map((p: any) => p.color + 'd9'), hoverBackgroundColor: v.map((p: any) => p.color), borderRadius: 8, borderSkipped: false, barThickness: 16 }] },
        options: { ...base, indexAxis: 'y', scales: { x: { beginAtZero: true, grid: { color: '#e1e8f0' }, border: { display: false }, ticks: { maxTicksLimit: 5 } }, y: { grid: { display: false }, border: { display: false }, ticks: { color: '#0c2742', font: { weight: 700 } } } },
            plugins: { legend: { display: false }, tooltip: { ...tip, callbacks: { label: (c: any) => ` ${c.parsed.x} unidades · ${dinero(c.parsed.x * v[c.dataIndex].precio)}` } } } },
    };
});
</script>

<template>
    <div class="page libre-sm">
        <PageHeader titulo="Panel de control" desc="Resumen de la organización y su actividad" :icono="LayoutDashboard" />

        <!-- Requieren atención -->
        <section class="aten">
            <div class="aten-h"><h2><Siren :size="20" /> Requieren atención</h2><span v-if="total" class="cnt">{{ total }}</span></div>
            <div class="alertas anim">
                <article v-if="alertas.adeudosTotal" class="alerta">
                    <div class="alerta-top"><span class="alerta-ico"><Wallet :size="22" /></span><div><b>Cuotas pendientes</b><small>{{ dinero(alertas.porCobrar) }} por cobrar</small></div><span class="alerta-num">{{ alertas.adeudosTotal }}</span></div>
                    <ul>
                        <li v-for="p in alertas.adeudos" :key="p.id"><Persona :p="p" :sub="`${p.meses} ${p.meses === 1 ? 'mes' : 'meses'} · ${dinero(p.monto)}`" :size="30" /></li>
                        <li v-if="alertas.adeudosTotal > 3" class="muted">y {{ alertas.adeudosTotal - 3 }} más…</li>
                    </ul>
                    <Link href="/cuotas" class="btn-alerta"><ArrowRight :size="16" /> Ir a cuotas</Link>
                </article>
                <article v-if="alertas.bajosTotal" class="alerta naranja">
                    <div class="alerta-top"><span class="alerta-ico"><PackageX :size="22" /></span><div><b>Inventario bajo el mínimo</b><small>Reabastece antes de que se agote</small></div><span class="alerta-num">{{ alertas.bajosTotal }}</span></div>
                    <ul>
                        <li v-for="b in alertas.bajos" :key="b.id">
                            <div class="person"><span class="avatar" :style="{ background: b.color, width: '30px', height: '30px' }"><component :is="icono(b.icono)" :size="16" /></span><div class="txt"><b>{{ b.nombre }}</b><small>Quedan {{ b.stock }} · mínimo {{ b.minimo }}</small></div></div>
                        </li>
                    </ul>
                    <Link href="/tienda?tab=inventario" class="btn-alerta"><ArrowRight :size="16" /> Ir al inventario</Link>
                </article>
                <article v-if="alertas.sinDocsTotal" class="alerta azul">
                    <div class="alerta-top"><span class="alerta-ico"><FileWarning :size="22" /></span><div><b>Expedientes incompletos</b><small>Falta foto o INE escaneada</small></div><span class="alerta-num">{{ alertas.sinDocsTotal }}</span></div>
                    <ul>
                        <li v-for="p in alertas.sinDocs" :key="p.id"><Persona :p="p" :sub="'Falta: ' + p.falta" :size="30" /></li>
                        <li v-if="alertas.sinDocsTotal > 3" class="muted">y {{ alertas.sinDocsTotal - 3 }} más…</li>
                    </ul>
                    <Link href="/agremiados" class="btn-alerta"><ArrowRight :size="16" /> Ir al padrón</Link>
                </article>
                <article v-if="!total" class="alerta verde">
                    <div class="alerta-top"><span class="alerta-ico"><CircleCheck :size="22" /></span><div><b>Todo en orden</b><small>No hay pendientes por atender.</small></div></div>
                </article>
            </div>
        </section>

        <div class="kpis">
            <div class="kpi c1"><span>Agremiados activos</span><div class="k-ico"><Users :size="20" /></div><b>{{ kpis.activos }}</b><small style="color: var(--ok)"><TrendingUp :size="13" /> +{{ kpis.altasMes }} altas este mes</small></div>
            <div class="kpi c2"><span>Cuotas de {{ kpis.mesActual }}</span><div class="k-ico"><Wallet :size="20" /></div><b>{{ dinero(kpis.recaudadoMes) }}</b><small>{{ kpis.conAdeudo }} agremiados con adeudo</small></div>
            <div class="kpi c3"><span>Apoyos (6 meses)</span><div class="k-ico"><HandHeart :size="20" /></div><b>{{ kpis.apoyos }}</b><small>{{ dinero(kpis.apoyosMonto) }} entregados</small></div>
            <div class="kpi c4"><span>Tienda</span><div class="k-ico"><Package :size="20" /></div><b :style="{ color: kpis.bajos ? 'var(--naranja)' : '' }">{{ kpis.bajos }}</b><small>bajo mínimo · {{ kpis.pedidosPendientes }} pedidos por entregar</small></div>
        </div>

        <div class="charts anim">
            <section class="panel"><div class="panel-h"><h2><ChartColumn :size="18" />Cuotas por mes</h2><span class="sub">Recaudado contra lo esperado</span></div><div class="panel-b ch"><Grafica :config="cfgCuotas" etiqueta="Cuotas por mes" /></div></section>
            <section class="panel"><div class="panel-h"><h2><ChartPie :size="18" />Agremiados por municipio</h2></div><div class="panel-b ch"><Grafica :config="cfgMun" etiqueta="Agremiados por municipio" /></div></section>
            <section class="panel"><div class="panel-h"><h2><HandHeart :size="18" />Apoyos entregados por mes</h2><span class="sub">Valor por tipo de apoyo</span></div><div class="panel-b ch"><Grafica :config="cfgApoyos" etiqueta="Apoyos por mes" /></div></section>
            <section class="panel"><div class="panel-h"><h2><Store :size="18" />Lo más vendido en la tienda</h2><span class="sub"><ShoppingBag :size="12" /> unidades</span></div><div class="panel-b ch"><Grafica :config="cfgVentas" etiqueta="Ventas de la tienda" /></div></section>
        </div>
    </div>
</template>

<style scoped>
.page { overflow-y: auto; }
.aten { display: flex; flex-direction: column; gap: 10px; flex-shrink: 0; }
.aten-h { display: flex; align-items: center; gap: 10px; }
.aten-h h2 { font-size: 17px; color: var(--brand); display: flex; align-items: center; gap: 8px; }
.aten-h h2 .lucide { color: var(--naranja); }
.cnt { background: var(--bad); color: #fff; font-family: var(--font-display); font-weight: 800; border-radius: 999px; padding: 0 10px; font-size: 13px; }
.alertas { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.alerta { --tono: #d64545; --suave: #fde7e7; --ring: color-mix(in srgb, var(--tono) 45%, transparent); position: relative; overflow: hidden; border-radius: 16px; padding: 14px; display: flex; flex-direction: column; gap: 10px; background: linear-gradient(160deg, var(--suave), #fff 70%); border: 1px solid color-mix(in srgb, var(--tono) 30%, transparent); box-shadow: 0 10px 26px color-mix(in srgb, var(--tono) 14%, transparent); transition: transform .2s; }
.alerta:hover { transform: translateY(-3px); }
.alerta.naranja { --tono: var(--naranja); --suave: #fdf0e2; }
.alerta.azul { --tono: var(--azul); --suave: #e5f0fa; }
.alerta.verde { --tono: var(--ok); --suave: var(--ok-soft); --ring: transparent; }
.alerta::after { content: ""; position: absolute; right: -40px; top: -40px; width: 130px; height: 130px; border-radius: 50%; background: color-mix(in srgb, var(--tono) 10%, transparent); }
.alerta-top { display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; }
.alerta-ico { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; background: var(--tono); color: #fff; flex-shrink: 0; animation: ring 2s ease-out infinite; }
.alerta-top b { display: block; font-family: var(--font-display); font-size: 15.5px; line-height: 1.15; }
.alerta-top small { color: var(--ink-2); font-size: 12.5px; }
.alerta-num { margin-left: auto; font-family: var(--font-display); font-weight: 800; font-size: 36px; line-height: 1; color: var(--tono); }
.alerta ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 5px; position: relative; z-index: 1; }
.alerta li { background: rgba(255, 255, 255, .8); border: 1px solid color-mix(in srgb, var(--tono) 14%, transparent); border-radius: 10px; padding: 5px 8px; font-size: 12.5px; }
.btn-alerta { position: relative; z-index: 1; align-self: flex-start; display: inline-flex; gap: 6px; align-items: center; background: var(--tono); color: #fff; border-radius: 10px; padding: 6px 12px; font-weight: 800; font-size: 12.5px; text-decoration: none; }
.btn-alerta:hover { filter: brightness(1.08); }
.charts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.ch { height: 250px; }
@media (max-width: 1100px) { .alertas, .charts { grid-template-columns: 1fr; } }
</style>
