import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import { browserslistToTargets } from 'lightningcss';
import { readFileSync, writeFileSync } from 'fs';

const r = (path) => fileURLToPath(new URL(path, import.meta.url));

export default defineConfig({
  base: './',
  plugins: [],
  resolve: {
    alias: {
      '@': r('./assets/src'),
      '@js': r('./assets/src/js'),
      '@scss': r('./assets/src/scss'),
      '@fonts': r('./assets/src/fonts'),
      '@components': r('./assets/src/scss/components'),
      '@base': r('./assets/src/scss/base')
    }
  },
  css: {
    devSourcemap: true,
    transformer: 'lightningcss',
    lightningcss: {
      targets: browserslistToTargets([
        'chrome >= 111',
        'edge >= 111',
        'firefox >= 114',
        'safari >= 16.4',
      ]),
    },
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
        silenceDeprecations: ['import'],
        additionalData: `@use "@scss/base/tokens" as *;`
      }
    }
  },
  build: {
    license: true,
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: 'manifest.json',
    target: 'baseline-widely-available',
    cssCodeSplit: true,
    minify: 'oxc',
    cssMinify: 'lightningcss',
    sourcemap: 'hidden',
    modulePreload: false,
    reportCompressedSize: true,
    chunkSizeWarningLimit: 500,
    rolldownOptions: {
      external: [
        'react',
        'react-dom',
        /^@wordpress\//,
        /^wp\./,
      ],
        input: {
        app: r('./assets/src/js/app.js'),
        mobilemenu: r('./assets/src/js/mobilemenu.js'),
        editor: r('./assets/src/js/editor.js'),
        ogpreview: r('./assets/src/js/ogpreview.js'),
        hero: r('./inc/blocks/hero/index.js'),
        intro: r('./inc/blocks/intro/index.js'),
        faq: r('./inc/blocks/faq/index.js'),
        about: r('./inc/blocks/about/index.js'),
        'project-index': r('./inc/blocks/project-index/index.js')
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) return 'css/[name][extname]';
          if (/\.(woff2?|ttf|otf)$/.test(assetInfo.name ?? '')) return 'fonts/[name]-[hash][extname]';
          return 'assets/[name]-[hash][extname]';
        },
        codeSplitting: {                       // ← replaces manualChunks
          groups: [
            {
              test: /node_modules/,
              name: 'vendor'
            }
          ]
        },
        globals: {
          react: 'React',
          'react-dom': 'ReactDOM',
          '@wordpress/element': 'wp.element',
          '@wordpress/i18n': 'wp.i18n',
          '@wordpress/components': 'wp.components',
          '@wordpress/block-editor': 'wp.blockEditor',
          '@wordpress/blocks': 'wp.blocks',
          '@wordpress/data': 'wp.data',
          '@wordpress/hooks': 'wp.hooks',
          '@wordpress/icons': 'wp.icons',
          '@wordpress/primitives': 'wp.primitives'
        }
      }
    }
  }
});