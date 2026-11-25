import '../css/app.css';
import 'leaflet/dist/leaflet.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import { instalarBase } from './base';
import AppLayout from './Layouts/AppLayout.vue';
import { SigoPreset, es, palette } from './theme';

// Si el sistema está publicado en una carpeta del dominio, las rutas fijas reciben su prefijo
instalarBase(router, axios);
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} · SIGO` : 'SIGO · Sistema Integral de Gestión Organizacional'),
    resolve: async (name) => {
        const page = await pages[`./Pages/${name}.vue`]();
        const component = page.default;
        if (component.layout === undefined && !name.startsWith('Auth/') && name !== 'Portal') {
            component.layout = AppLayout;
        }
        return component;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, { theme: { preset: SigoPreset, options: { darkModeSelector: false, cssLayer: false } }, locale: es, ripple: true })
            .use(ToastService)
            .directive('tooltip', Tooltip)
            .mount(el!);
    },
    progress: { color: palette.teal, showSpinner: false },
});
