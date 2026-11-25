import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig(({ command }) => ({
    // Rutas relativas en la compilación: el sistema funciona en cualquier carpeta del dominio (p. ej. /sigo)
    base: command === 'build' ? './' : undefined,
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.ts'], refresh: true }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
        tailwindcss(),
    ],
    resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
    build: { chunkSizeWarningLimit: 1600 },
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
}));
