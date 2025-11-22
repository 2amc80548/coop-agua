import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy'; 
import ApexCharts from 'apexcharts';

import '@fortawesome/fontawesome-free/css/all.min.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            // AQUÍ EL CAMBIO: Pasamos window.Ziggy para que lea la URL del servidor/local
            .use(ZiggyVue, window.Ziggy) 
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});