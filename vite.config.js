import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [`resources/views/**/*`],
        }),
        tailwindcss(),
    ],
    // أضف هذا الجزء تحديداً:
    server: {
        host: '0.0.0.0', // للسماح بالاتصال من أي جهاز
        hmr: {
            host: '192.168.1.108', // ضع هنا الـ IP الخاص بجهازك
        },
    },
});