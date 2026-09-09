import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({

   //base: '/inf513/grupo05cc/coop-agua/public/build/',

    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '../../vendor/tightenco/ziggy': 'ziggy-js',
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-core';
                        }
                        if (id.includes('chart.js') || id.includes('vue-chartjs')) {
                            return 'vendor-charts';
                        }
                        return 'vendor-deps';
                    }
                },
            },
        },
    },
    test: {
        globals: true,
        environment: 'happy-dom',
    },
});



