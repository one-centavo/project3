import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import { VitePWA } from "vite-plugin-pwa";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
            fonts: [
                bunny("Instrument Sans", {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        VitePWA({
            registerType: "autoUpdate",
            filename: "../sw.js",
            injectRegister: "false",
            workbox: {
                globDirectory: "public",
                globPatterns: [
                    "build/assets/**/*.{js,css}",
                    "build/manifest.json",
                ],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) =>
                            request.mode === "navigate",
                        handler: "NetworkFirst", // Intenta internet; si falla, saca el HTML de la caché
                        options: {
                            cacheName: "laravel-html-cache",
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60 * 60 * 24 * 7, // Guarda la página por una semana
                            },
                        },
                    },
                ],
            },
            manifest: {
                name: "SYNC PROJECT OFFLINE",
                short_name: "SYNCOffline",
                description: "SYNC Client Offline Mode",
                theme_color: "#f53003",
                background_color: "#161615",
                display: "standalone",
                scope: "/",
                start_url: "/",
                icons: [
                    //
                ],
            },
        }),
        tailwindcss(),
    ],
    server: {
        host: "0.0.0.0",
        hmr: {
            host: "localhost",
        },
        watch: {
            usePolling: true,
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
