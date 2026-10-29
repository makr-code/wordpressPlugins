import test from 'node:test';
import assert from 'node:assert/strict';
import { DEFAULT_SETTINGS, MODE_ZOOM, parseSettings, createFlight, sampleFlight, coverDistance, sphericalRoute } from '../assets/js/globe-motion.js';

test('spherical routes follow great circles rather than linear latitude interpolation', () => {
    const midpoint = sphericalRoute({ lon: -45, lat: 60 }, { lon: 45, lat: 60 }, 0.5);
    assert.ok(Math.abs(midpoint.lon) < 1e-8);
    assert.ok(midpoint.lat > 67 && midpoint.lat < 68);
    for (const destination of [{ lon: 180, lat: 0 }, { lon: 0, lat: 0 }, { lon: 179.99999, lat: 0.00001 }, { lon: 90, lat: 90 }]) {
        for (const t of [0, 0.1, 0.5, 0.9, 1]) {
            const point = sphericalRoute({ lon: 0, lat: 0 }, destination, t);
            assert.ok(Number.isFinite(point.lon) && Number.isFinite(point.lat));
            assert.ok(Math.abs(point.lat) <= 90);
        }
    }
});

test('new visitors get an enabled hotspot tour and a globe focus', () => {
    assert.equal(DEFAULT_SETTINGS.animation, true);
    assert.equal(DEFAULT_SETTINGS.target, 'auto');
    assert.ok(MODE_ZOOM.globe < MODE_ZOOM.region && MODE_ZOOM.region < MODE_ZOOM.country);
});

test('versioned cookie validates every persisted setting', () => {
    const settings = { ...DEFAULT_SETTINGS, mode: 'country', target: 'japan', animation: false, drag: false, speed: 1.5, zoom: 3.2 };
    const raw = JSON.stringify({ version: 1, ...settings });
    assert.deepEqual(parseSettings(raw, ['japan']), settings);
    for (const change of [{ mode: '__proto__' }, { speed: 0 }, { speed: 3 }, { target: 'missing' }, { animation: 1 }, { cities: 'yes' }, { zoom: 10 }, { version: 2 }]) {
        assert.throws(() => parseSettings(JSON.stringify({ version: 1, ...settings, ...change }), ['japan']));
    }
    assert.throws(() => parseSettings('invalid', []));
});

test('older settings without the city preference retain their other values', () => {
    const { cities, ...oldSettings } = DEFAULT_SETTINGS;
    const restored = parseSettings(JSON.stringify({ version: 1, ...oldSettings, mode: 'region', animation: false }), []);
    assert.equal(restored.cities, true);
    assert.equal(restored.mode, 'region');
    assert.equal(restored.animation, false);
});

test('flight zooms out before travel and zooms in only after arrival', () => {
    const flight = createFlight({ lon: 10, lat: 50, zoom: 3.2 }, { lon: 139, lat: 36 }, MODE_ZOOM.country);
    const depart = sampleFlight(flight, 0.9);
    assert.equal(depart.lon, 10);
    assert.ok(depart.zoom < 3.2 && depart.zoom > 1);
    const travel = sampleFlight(flight, 3.7);
    assert.equal(travel.phase, 'travel');
    assert.equal(travel.zoom, 1);
    assert.ok(travel.lon > 10 && travel.lon < 139);
    const arrive = sampleFlight(flight, 6.7);
    assert.equal(arrive.lon, 139);
    assert.ok(arrive.zoom > 1 && arrive.zoom < 3.2);
    assert.equal(sampleFlight(flight, 8).zoom, 3.2);
    assert.equal(sampleFlight(flight, 8).done, true);
});

test('flight segments are continuous and follow the shortest date-line path', () => {
    const flight = createFlight({ lon: 179, lat: -20, zoom: 2 }, { lon: -179, lat: 40 }, MODE_ZOOM.region);
    assert.equal(flight.to.lon, 181);
    for (const boundary of [1.8, 5.6, 7.8]) {
        const left = sampleFlight(flight, boundary - 0.00001);
        const right = sampleFlight(flight, boundary + 0.00001);
        for (const key of ['lon', 'lat', 'zoom']) assert.ok(Math.abs(left[key] - right[key]) < 0.00001);
    }
});

test('all modes select their own arrival zoom without changing travel altitude', () => {
    for (const zoom of Object.values(MODE_ZOOM)) {
        const flight = createFlight({ lon: 0, lat: 0, zoom: 2 }, { lon: 90, lat: 30 }, zoom);
        assert.equal(sampleFlight(flight, 3).zoom, 1);
        assert.equal(sampleFlight(flight, 9).zoom, zoom);
    }
});

test('sphere covers both canvas axes at any aspect ratio', () => {
    const fov = 38 * Math.PI / 180;
    for (const aspect of [0.5, 1, 2, 3]) {
        const distance = coverDistance(fov, aspect, 1);
        const apparentRadius = 1 / Math.sqrt(distance * distance - 1);
        const halfHeight = Math.tan(fov / 2);
        assert.ok(apparentRadius >= halfHeight - 1e-10);
        assert.ok(apparentRadius >= halfHeight * aspect - 1e-10);
        assert.ok(coverDistance(fov, aspect, 3.2) < distance);
    }
});
