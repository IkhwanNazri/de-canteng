import './polyfills.js';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AppLayout from './Layouts/AppLayout.vue';

createInertiaApp({
    title: (title) => (title ? `${title} · DeBatik` : 'DeBatik — Pasaraya Batik Malaysia'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        // Halaman Auth guna GuestLayout sendiri; yang lain guna layout kedai.
        if (!name.startsWith('Auth/')) {
            page.default.layout ??= AppLayout;
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Ralat semasa render Vue — papar di skrin dalam mod debug (lihat app.blade.php).
        app.config.errorHandler = (error, instance, info) => {
            console.error(error);
            window.__showJsError?.(`Ralat Vue (${info}): ${error?.stack ?? error}`);
        };

        app.use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: { color: '#D4AF37' },
});
