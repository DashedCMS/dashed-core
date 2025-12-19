import * as esbuild from 'esbuild'

async function compile(options) {
    const ctx = await esbuild.context(options)
    await ctx.rebuild()
    await ctx.dispose()
}

compile({
    define: {
        'process.env.NODE_ENV': `'production'`,
    },
    bundle: true,
    format: 'esm',              // 👈 required for Filament dynamic import()
    platform: 'neutral',
    target: ['es2020'],
    minify: true,
    sourcemap: false,
    treeShaking: true,
    entryPoints: [
        './resources/js/filament/rich-content-plugins/id-attribute.js',
    ],
    outfile:
        './resources/js/dist/filament/rich-content-plugins/id-attribute.js',
})
