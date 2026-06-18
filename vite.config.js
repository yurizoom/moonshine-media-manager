import { defineConfig } from 'vite';
import { promises as fsPromises, readFileSync, writeFileSync } from 'fs';
import autoprefixer from 'autoprefixer';
import { browserslistToTargets } from 'lightningcss';
import browserslist from 'browserslist';

const mediaManagerBuildPlugin = () => ({
    name: 'media-manager-build-plugin',
    async closeBundle() {
        try {
            const filePath = 'dist/media-manager.js';
            const data = readFileSync(filePath);
            const insert = Buffer.from('(()=>{');

            writeFileSync(filePath, insert);
            writeFileSync(filePath, data, { flag: 'a' });

            await fsPromises.appendFile(filePath, '})()');
        } catch (e) {
            console.error(e);
        }
    },
});

export default defineConfig({
    build: {
        emptyOutDir: true,
        outDir: 'dist',
        rollupOptions: {
            input: [
                'resources/js/media-manager.js',
                'resources/css/media-manager.css',
            ],
            output: {
                entryFileNames: (chunk) => (chunk.facadeModuleId?.endsWith('.css') ? '.[name].facade.js' : '[name].js'),
                assetFileNames: (chunk) => {
                    if (chunk.name.endsWith('.woff2')) {
                        return 'fonts/[name].[ext]';
                    }
                    return '[name].css';
                },
            },
            plugins: [mediaManagerBuildPlugin()],
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
