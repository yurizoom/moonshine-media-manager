import { defineConfig } from 'vite';
import autoprefixer from 'autoprefixer';
import { browserslistToTargets } from 'lightningcss';
import browserslist from 'browserslist';

export default defineConfig({
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: [
                'resources/js/media-manager.js',
                'resources/css/media-manager.css',
            ],
            output: {
                entryFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
        cssMinify: 'lightningcss',
        minify: true,
        target: 'es2017',
    },
    css: {
        lightningcss: {
            targets: browserslistToTargets(
                browserslist(['> 0.5%', 'last 2 versions', 'Firefox ESR', 'not dead'])
            ),
        },
        postcss: {
            plugins: [autoprefixer],
        },
    },
});
