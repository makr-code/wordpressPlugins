import { build } from 'esbuild';
import { copyFileSync, readFileSync, writeFileSync } from 'node:fs';
import { gzipSync } from 'node:zlib';
import { fileURLToPath } from 'node:url';

const directory = fileURLToPath(new URL('.', import.meta.url));
await build({
    absWorkingDir: directory,
    entryPoints: ['assets/js/themisdb-global-network-map.js'],
    outfile: 'assets/dist/network-map.js',
    bundle: true,
    minify: true,
    sourcemap: false,
    format: 'iife',
    target: 'es2022',
    logLevel: 'info',
});
const output = new URL('assets/dist/network-map.js', import.meta.url);
const bundle = readFileSync(output);
const compressed = gzipSync(bundle, { level: 9 });
writeFileSync(new URL('assets/dist/network-map.js.gz', import.meta.url), compressed);
copyFileSync(new URL('node_modules/three/LICENSE', import.meta.url), new URL('assets/dist/THREE-LICENSE.txt', import.meta.url));
console.log(`Browser bundle: ${bundle.length} bytes; gzip: ${compressed.length} bytes.`);
