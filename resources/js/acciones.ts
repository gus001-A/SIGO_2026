import { router } from '@inertiajs/vue3';
import { alerta } from './alerta';

/** Envía un formulario de Inertia mostrando los errores de validación como alerta con la lista de campos. */
export function enviar(form: any, metodo: 'post' | 'put' | 'delete', url: string, o: { archivos?: boolean; exito?: () => void; transformar?: (d: any) => any } = {}) {
    if (o.transformar) form.transform(o.transformar);
    form.submit(metodo, url, {
        forceFormData: !!o.archivos,
        preserveScroll: true,
        preserveState: true,
        onError: (e: Record<string, string>) => alerta.errores(e),
        onSuccess: () => o.exito?.(),
    });
}

/** Eliminación lógica con confirmación (el registro se conserva en la bitácora). */
export async function eliminar(url: string, que: string, detalle = 'Dejará de mostrarse, pero se conserva en la bitácora de auditoría.', exito?: () => void) {
    if (!(await alerta.confirmar(`¿Eliminar ${que}?`, detalle, { aceptar: 'Sí, eliminar', peligro: true }))) return;
    router.delete(url, { preserveScroll: true, preserveState: true, onSuccess: () => exito?.() });
}

/** Acción sencilla con confirmación. */
export async function confirmarYEnviar(titulo: string, texto: string, metodo: 'post' | 'put' | 'delete', url: string, datos: any = {}, o: { aceptar?: string; peligro?: boolean; exito?: () => void } = {}) {
    if (!(await alerta.confirmar(titulo, texto, { aceptar: o.aceptar ?? 'Sí, continuar', peligro: o.peligro }))) return;
    router.visit(url, { method: metodo, data: datos, preserveScroll: true, preserveState: true, onSuccess: () => o.exito?.(), onError: (e: any) => alerta.errores(e) });
}
