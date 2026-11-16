import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { execFileSync } from 'node:child_process';
import { fontsource } from 'laravel-vite-plugin/fonts';
import type { Plugin } from 'vite';
import { defineConfig, lazyPlugins } from 'vite-plus';

/**
 * Regenerates the TypeScript type of the translation keys (used by `t()`) from lang/en whenever the
 * build starts or a translation file changes.
 */
function translationKeys(): Plugin {
    const generate = () =>
        execFileSync('php', ['artisan', 'atlas:translation-types'], {
            stdio: 'inherit',
        });

    return {
        name: 'atlas-translation-keys',
        buildStart() {
            generate();
        },
        handleHotUpdate({ file }) {
            const path = file.replaceAll('\\', '/');

            if (
                /\/lang\/en\/[^/]+\.php$/.test(path) ||
                path.endsWith('/config/atlas.php')
            ) {
                generate();
            }
        },
    };
}

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            // Self-hosted from the npm package, never from a font CDN. Only the Bangla and Latin
            // subsets; the browser fetches a subset when the page uses its characters.
            fonts: [
                fontsource('Noto Sans Bengali', {
                    weights: [400, 500, 600],
                    subsets: ['bengali', 'latin'],
                    display: 'swap',
                    preload: [{ weight: 400 }],
                }),
            ],
        }),
        translationKeys(),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'resources/js/types/*.generated.ts',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            // Prose is wrapped by hand, license and Code of Conduct texts are verbatim copies, and
            // prompt and schema files under data/ are immutable once committed.
            '**/*.md',
            'LICENSES/**',
            'data/**',
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
            'resources/js/types/*.generated.ts',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
