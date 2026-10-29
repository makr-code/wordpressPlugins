import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    latLonToPosition, validCoordinates, longitudeDelta, validateNodes,
    geoJsonPolygons, unwrapRing, drawContinents,
} from '../assets/js/globe-geo.js';

test('geographic coordinates and sphere texture use the same axes', () => {
    const close = (actual, expected) => actual.forEach((value, i) => assert.ok(Math.abs(value - expected[i]) < 1e-10));
    close(latLonToPosition(0, 0), [1, 0, 0]);
    close(latLonToPosition(90, 0), [0, 0, -1]);
    close(latLonToPosition(0, 90), [0, 1, 0]);
    close(latLonToPosition(0, -90), [0, -1, 0]);
    for (const [lon, lat] of [[8.6821, 50.1109], [151.2093, -33.8688], [-118.2437, 34.0522]]) {
        const [x, y, z] = latLonToPosition(lon, lat, 2);
        assert.ok(Math.abs(Math.hypot(x, y, z) - 2) < 1e-10);
        assert.equal(Math.sign(y), Math.sign(lat));
    }
});

test('zero coordinates are valid; missing, coerced and out-of-range values are not', () => {
    assert.ok(validCoordinates(0, 0));
    assert.ok(validCoordinates(-90, 180));
    for (const coordinates of [[null, 0], ['', 0], ['10', 10], [NaN, 0], [91, 0], [0, 181], [0, Infinity]]) {
        assert.equal(validCoordinates(...coordinates), false);
    }
});

test('camera moves across the shortest antimeridian arc', () => {
    assert.equal(longitudeDelta(179, -179), 2);
    assert.equal(longitudeDelta(-179, 179), -2);
    assert.equal(longitudeDelta(720, 5), 5);
});

test('rings crossing the date line do not produce a stripe across the world', () => {
    const ring = unwrapRing([[179, 10], [-179, 10], [-179, 8], [179, 8], [179, 10]]);
    assert.deepEqual(ring.map(point => point[0]), [179, 181, 181, 179, 179]);
});

test('polygon holes use even-odd fill and periodic seam copies', () => {
    const commands = [];
    const context = {
        fillRect() {}, beginPath() {}, closePath() {}, stroke() {},
        moveTo(x, y) { commands.push(['move', x, y]); },
        lineTo(x, y) { commands.push(['line', x, y]); },
        fill(rule) { commands.push(['fill', rule]); },
    };
    const outer = [[179, 10], [-179, 10], [-179, -10], [179, -10], [179, 10]];
    const hole = [[-179.8, 2], [-179.2, 2], [-179.2, -2], [-179.8, -2], [-179.8, 2]];
    drawContinents(context, [[outer, hole]], 360, 180, { ocean: '#000', land: '#fff', coast: '#888' });
    assert.deepEqual(commands.filter(command => command[0] === 'fill'), Array(3).fill(['fill', 'evenodd']));
    const moves = commands.filter(command => command[0] === 'move');
    assert.equal(moves.length, 6);
    assert.ok(Math.abs(moves[0][1] - moves[1][1]) < 5);
});

test('bundled real-world GeoJSON contains complete polygons including Antarctica', () => {
    const geojson = JSON.parse(readFileSync(new URL('../assets/data/world.geojson', import.meta.url), 'utf8'));
    const polygons = geoJsonPolygons(geojson);
    assert.ok(geojson.features.length >= 200);
    assert.ok(polygons.length > geojson.features.length);
    const antarctica = geojson.features.find(feature => feature.properties.name === 'Antarctica');
    assert.ok(antarctica);
    const ring = unwrapRing(antarctica.geometry.coordinates[0][0]);
    assert.ok(ring.some(point => point[1] === -90));
    assert.ok(Math.max(...ring.map(point => point[0])) - Math.min(...ring.map(point => point[0])) >= 359);
    assert.throws(() => geoJsonPolygons({ type: 'FeatureCollection', features: [] }), /no continents/);
});

test('telemetry contract keeps demo and production explicit and rejects malformed nodes', () => {
    const node = { lat: 0, lon: 0, instances: 2, load: 75, city: 'Test', country: 'ZZ' };
    assert.equal(validateNodes({ source: 'production', nodes: [node] }).nodes.length, 1);
    assert.equal(validateNodes({ source: 'demo', nodes: [] }).source, 'demo');
    assert.throws(() => validateNodes({ nodes: [] }), /Invalid telemetry/);
    assert.throws(() => validateNodes({ source: 'production', nodes: [{ ...node, lat: null }] }), /invalid instance/);
    assert.throws(() => validateNodes({ source: 'production', nodes: [{ ...node, load: 101 }] }), /invalid instance/);
    assert.throws(() => validateNodes({ source: 'production', nodes: [{ ...node, instances: 0 }] }), /invalid instance/);
});
