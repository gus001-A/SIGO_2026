import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export const dinero = (n: any) => '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** "2026-11-09" → "09/11/2026"; "2026-11-09 08:02" → "09/11/2026 08:02" */
export function fecha(s?: string | null): string {
    if (!s) return '';
    const [y, m, d] = s.slice(0, 10).split('-');
    return `${d}/${m}/${y}${s.length > 10 ? ' ' + s.slice(11, 16) : ''}`;
}

export const hora = (s?: string | null) => (s && s.length > 10 ? s.slice(11, 16) : '');

export const iniciales = (n?: string | null) =>
    (n || '?').replace(/^Ma\. /, 'M ').split(' ').filter(Boolean).slice(0, 2).map((x) => x[0]).join('').toUpperCase();

/** Texto comparable: sin acentos, minúsculas y espacios simples. */
export const normal = (t: any) => String(t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();

export const hoyISO = () => {
    const d = new Date();
    const z = (x: number) => String(x).padStart(2, '0');
    return `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}`;
};

export const ahoraISO = () => {
    const d = new Date();
    const z = (x: number) => String(x).padStart(2, '0');
    return `${hoyISO()} ${z(d.getHours())}:${z(d.getMinutes())}`;
};

export const telefono = (t?: string | null) => (t && t.length === 10 ? `${t.slice(0, 3)} ${t.slice(3, 6)} ${t.slice(6)}` : t || '');

export const peso = (b: number) => (b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');

/** Permisos del usuario en sesión: puede('cuotas', 'e') */
export function usePermisos() {
    const page = usePage<any>();
    const permisos = computed(() => (page.props.auth?.permisos ?? {}) as Record<string, string>);
    const R: Record<string, number> = { n: 0, v: 1, e: 2, t: 3 };
    return { puede: (m: string, n = 'v') => (R[permisos.value[m] ?? 'n'] ?? 0) >= R[n] };
}

export const TONOS: Record<string, string> = {
    Activo: 'ok', Baja: 'bad', Pagado: 'ok', Pendiente: 'warn', Entregado: 'ok', Cancelado: 'bad', Activa: 'ok', Finalizada: 'muted', Inactivo: 'bad',
    Reconocimiento: 'ok', Inasistencia: 'warn', Observación: 'brand', Sanción: 'bad', Producto: 'brand', Servicio: 'teal', Entrada: 'ok', Salida: 'warn', Ajuste: 'brand',
};
