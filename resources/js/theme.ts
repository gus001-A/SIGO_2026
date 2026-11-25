import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

/** Paleta del logo SIGO: azul marino del texto + la red multicolor (azul, turquesa, verde, amarillo, naranja). */
export const palette = {
    brand: '#0f3b63', azul: '#2a7fc4', teal: '#18a3a0', verde: '#6fae3e', amarillo: '#f2b632', naranja: '#ee8a2c', ink: '#0c2742',
};

export const SigoPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#ecf4fb', 100: '#d3e6f6', 200: '#a5cdee', 300: '#6faedf', 400: '#3d8fcf', 500: '#1f6fae',
            600: '#185d93', 700: '#134c7a', 800: '#0f3b63', 900: '#0c2742', 950: '#081a2d',
        },
        focusRing: { width: '2px', style: 'solid', color: '#18a3a0', offset: '2px' },
        formField: { borderRadius: '9px' },
        colorScheme: {
            light: {
                surface: { 0: '#ffffff', 50: '#f3f7fb', 100: '#e9f0f6', 200: '#e1e8f0', 300: '#cbd6e2', 400: '#9fb0c4', 500: '#7b8ba3', 600: '#5b6d88', 700: '#45597a', 800: '#2a3f5f', 900: '#0c2742', 950: '#071a2d' },
                formField: { focusBorderColor: '#18a3a0' },
            },
        },
    },
    components: {
        datatable: { headerCell: { padding: '9px 12px' }, bodyCell: { padding: '8px 12px' }, footerCell: { padding: '7px 8px' } },
        dialog: { root: { borderRadius: '18px' } },
        button: { root: { borderRadius: '10px' } },
    },
});

export const es = {
    startsWith: 'Empieza con', contains: 'Contiene', notContains: 'No contiene', endsWith: 'Termina con', equals: 'Igual', notEquals: 'Distinto',
    noFilter: 'Sin filtro', lt: 'Menor que', lte: 'Menor o igual', gt: 'Mayor que', gte: 'Mayor o igual', dateIs: 'Fecha es', dateIsNot: 'Fecha no es',
    dateBefore: 'Antes de', dateAfter: 'Después de', clear: 'Limpiar', apply: 'Aplicar', matchAll: 'Todos', matchAny: 'Cualquiera',
    addRule: 'Agregar regla', removeRule: 'Quitar regla', accept: 'Sí', reject: 'No', choose: 'Elegir', upload: 'Subir', cancel: 'Cancelar',
    completed: 'Completado', pending: 'Pendiente', fileSizeTypes: ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
    dayNames: ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
    dayNamesShort: ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'], dayNamesMin: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
    monthNames: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
    monthNamesShort: ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'],
    chooseYear: 'Elegir año', chooseMonth: 'Elegir mes', chooseDate: 'Elegir fecha', prevDecade: 'Década anterior', nextDecade: 'Década siguiente',
    prevYear: 'Año anterior', nextYear: 'Año siguiente', prevMonth: 'Mes anterior', nextMonth: 'Mes siguiente', prevHour: 'Hora anterior', nextHour: 'Hora siguiente',
    prevMinute: 'Minuto anterior', nextMinute: 'Minuto siguiente', prevSecond: 'Segundo anterior', nextSecond: 'Segundo siguiente', am: 'am', pm: 'pm',
    today: 'Hoy', now: 'Ahora', weekHeader: 'Sem', firstDayOfWeek: 1, showMonthAfterYear: false, dateFormat: 'dd/mm/yy', weak: 'Débil', medium: 'Media',
    strong: 'Fuerte', passwordPrompt: 'Escribe una contraseña', emptyFilterMessage: 'Sin resultados', searchMessage: '{0} resultados',
    selectionMessage: '{0} seleccionados', emptySelectionMessage: 'Nada seleccionado', emptySearchMessage: 'Sin resultados', emptyMessage: 'Sin opciones',
    aria: { trueLabel: 'Sí', falseLabel: 'No', nullLabel: 'Sin seleccionar', pageLabel: 'Página {page}', firstPageLabel: 'Primera página', lastPageLabel: 'Última página', nextPageLabel: 'Siguiente', previousPageLabel: 'Anterior', rowsPerPageLabel: 'Registros por página', close: 'Cerrar' },
};
