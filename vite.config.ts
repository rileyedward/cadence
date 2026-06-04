import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        // Installable PWA shell (doc 18). Offline *data* is deferred; we serve a
        // network-first strategy so data is never stale, with an offline fallback.
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            // Laravel serves the built manifest from public/build; emit the webmanifest there too.
            manifestFilename: 'manifest.webmanifest',
            manifest: {
                name: 'Cadence',
                short_name: 'Cadence',
                description: 'A life-structure compiler that turns intention into an adaptive day.',
                start_url: '/today',
                scope: '/',
                display: 'standalone',
                background_color: '#0a0a0a',
                theme_color: '#6366f1',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                // Precache the built app shell/assets emitted by Vite.
                globPatterns: ['**/*.{js,css,woff2,png,svg,ico}'],
                navigateFallback: null, // SSR/Inertia documents are network-first, not an SPA fallback.
                runtimeCaching: [
                    {
                        // App documents + Inertia data: never serve stale; cache only as offline fallback.
                        urlPattern: ({ request }) =>
                            request.mode === 'navigate' || request.destination === 'document',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'cadence-documents',
                            networkTimeoutSeconds: 5,
                            expiration: { maxEntries: 32, maxAgeSeconds: 60 * 60 * 24 },
                        },
                    },
                    {
                        urlPattern: ({ url }) => url.pathname.startsWith('/build/'),
                        handler: 'StaleWhileRevalidate',
                        options: { cacheName: 'cadence-assets' },
                    },
                    {
                        urlPattern: ({ url }) =>
                            url.origin === 'https://fonts.bunny.net' || url.pathname.startsWith('/icons/'),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'cadence-fonts-icons',
                            expiration: { maxEntries: 32, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                ],
            },
            devOptions: {
                // Keep the SW off in dev to avoid caching surprises during the build.
                enabled: false,
            },
        }),
    ],
});
