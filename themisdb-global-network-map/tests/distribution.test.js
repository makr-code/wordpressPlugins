import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { gzipSync, gunzipSync, brotliCompressSync } from 'node:zlib';

test('browser distribution is minified, self-contained and ships without source maps', () => {
    const directory = new URL('../assets/dist/', import.meta.url);
    const bundle = readFileSync(new URL('network-map.js', directory));
    const source = bundle.toString();
    assert.ok(bundle.length < 1100 * 1024, `Bundle too large: ${bundle.length}`);
    assert.ok(gzipSync(bundle).length < 350 * 1024, 'Gzip payload exceeds budget');
    assert.ok(brotliCompressSync(bundle).length < 300 * 1024, 'Brotli payload exceeds budget');
    assert.deepEqual(gunzipSync(readFileSync(new URL('network-map.js.gz', directory))), bundle);
    assert.equal(source.includes('sourceMappingURL='), false);
    assert.equal(readdirSync(directory).some(name => name.endsWith('.map')), false);
    assert.equal(source.includes('class NetworkGlobe'), false);
    assert.equal(source.includes('from "three/'), false);
    assert.match(readFileSync(new URL('THREE-LICENSE.txt', directory), 'utf8'), /MIT License/);
});
