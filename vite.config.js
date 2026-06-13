import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import { browserslistToTargets } from 'lightningcss';
import { readFileSync, writeFileSync } from 'fs';

const r = (path) => fileURLToPath(new URL(path, import.meta.url));

function themeJsonTokens() {
  return {
    name: 'theme-json-tokens',
    configResolved() {
      const theme = JSON.parse(
        readFileSync('./theme.json', 'utf-8')
      );
      const tokens = [];
      // Spacing scale
      if (theme.settings?.spacing?.spacingScale) {
        const steps = theme.settings.spacing.spacingScale.steps;
        for (let i = 0; i <= steps; i++) {
          tokens.push(`$spacing-${i}: var(--wp--preset--spacing--${i});`);
        }
      }
      // Colors
      if (theme.settings?.color?.palette) {
        for (const c of theme.settings.color.palette) {
          tokens.push(`$color-${c.slug}: var(--wp--preset--color--${c.slug});`);
        }
      }
      // Font families
      if (theme.settings?.typography?.fontFamilies) {
        for (const f of theme.settings.typography.fontFamilies) {
          tokens.push(`$font-${f.slug}: var(--wp--preset--font-family--${f.slug});`);
        }
      }
      // Write to _tokens.scss dynamically
      writeFileSync('./assets/src/scss/base/_tokens.scss', tokens.join('\n'));
    }
  };
}

export default defineConfig({
  base: './',
  plugins: [themeJsonTokens()],
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
        editor: r('./assets/src/js/editor.js')
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
            },
            {
              test: /\/modules\//,
              name: 'modules'
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