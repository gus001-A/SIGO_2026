/**
 * Alertas al estilo SweetAlert: ícono animado, título, texto y botones centrados.
 * confirmar() regresa una promesa con true/false; errores() muestra la lista de campos con error.
 */
import { reactive } from 'vue';

export type TipoAlerta = 'success' | 'error' | 'warning' | 'info' | 'question';

interface Estado {
    visible: boolean;
    tipo: TipoAlerta;
    titulo: string;
    texto: string;
    lista: string[];
    aceptar: string;
    cancelar: string | null;
    peligro: boolean;
    resolver: ((v: boolean) => void) | null;
}

export const alertaEstado = reactive<Estado>({ visible: false, tipo: 'info', titulo: '', texto: '', lista: [], aceptar: 'Aceptar', cancelar: null, peligro: false, resolver: null });

function abrir(o: Partial<Estado>): Promise<boolean> {
    alertaEstado.resolver?.(false);
    return new Promise((ok) => Object.assign(alertaEstado, { tipo: 'info', texto: '', lista: [], aceptar: 'Aceptar', cancelar: null, peligro: false, ...o, visible: true, resolver: ok }));
}

export function cerrarAlerta(v: boolean) {
    const r = alertaEstado.resolver;
    alertaEstado.visible = false;
    alertaEstado.resolver = null;
    r?.(v);
}

export const alerta = {
    confirmar: (titulo: string, texto = '', o: { aceptar?: string; cancelar?: string; tipo?: TipoAlerta; peligro?: boolean } = {}) =>
        abrir({ tipo: o.tipo ?? (o.peligro ? 'warning' : 'question'), titulo, texto, aceptar: o.aceptar ?? 'Sí, continuar', cancelar: o.cancelar ?? 'Cancelar', peligro: !!o.peligro }),
    exito: (titulo: string, texto = '') => abrir({ tipo: 'success', titulo, texto }),
    error: (titulo: string, texto = '') => abrir({ tipo: 'error', titulo, texto }),
    aviso: (titulo: string, texto = '') => abrir({ tipo: 'warning', titulo, texto }),
    /** Errores de validación del servidor como lista. */
    errores: (errs: Record<string, string>) => {
        const lista = [...new Set(Object.values(errs))];
        return abrir({ tipo: 'error', titulo: 'Revisa los campos marcados', texto: lista.length === 1 ? '' : `Hay ${lista.length} datos por corregir:`, lista });
    },
};
