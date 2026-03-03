import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
// Vue is needed for the Inertia/Vue pages
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        // enable Vue support before the laravel plugin
        vue(),

        laravel({
            // include the normal app entry plus the inertia entry
            // and (optionally) any custom filament assets if you add them later
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/inertia/app.js',
                // 'resources/js/filament.js', // uncomment if you create a custom file
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
