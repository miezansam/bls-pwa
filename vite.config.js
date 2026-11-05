import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

const version = String(Date.now());

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            injectRegister: false,
            manifest: false,
            injectManifest: {
                rollupFormat: 'iife',
                globPatterns: ['**/*.{js,css,woff2,png,svg}'],
                // Fichiers du build inutiles hors ligne : inutile de les faire télécharger aux téléphones
                globIgnores: [
                    '**/*-vietnamese-*',
                    '**/leaflet*',
                    '**/marker-icon*',
                    '**/layers*',
                ],
                additionalManifestEntries: [
                    { url: '/hors-ligne', revision: version },
                    { url: '/images/logo-inverse.png', revision: version },
                ],
                manifestTransforms: [
                    // Les fichiers de build sont servis sous /build/ : on leur donne leur adresse complète
                    (entrees) => ({
                        manifest: entrees.map((entree) => (
                            entree.url.startsWith('/') ? entree : { ...entree, url: '/build/' + entree.url }
                        )),
                        warnings: [],
                    }),
                ],
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});