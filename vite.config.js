import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        hmr: {
            host: 'localhost'
        },
        host: '0.0.0.0', // or '0.0.0.0' if you want to access it from other devices
        port: 5173,
        strictPort: true, // prevents Vite from trying to use another port if 5173 is busy
    },
});
