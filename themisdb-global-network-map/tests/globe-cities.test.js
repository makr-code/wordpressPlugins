import test from 'node:test';
import assert from 'node:assert/strict';
import { MAJOR_CITIES, selectCityLabels, rectanglesOverlap } from '../assets/js/globe-cities.js';
import { validCoordinates } from '../assets/js/globe-geo.js';

const city = (name, x, y, tier = 1, extra = {}) => ({ name, x, y, tier, width: 70, distance: 0, ...extra });

test('city reference dataset is unique, geographically valid and separate from telemetry', () => {
    assert.ok(MAJOR_CITIES.length >= 60);
    assert.equal(new Set(MAJOR_CITIES.map(value => value.name)).size, MAJOR_CITIES.length);
    for (const value of MAJOR_CITIES) {
        assert.ok(validCoordinates(value.lat, value.lon), value.name);
        assert.ok([1, 2, 3].includes(value.tier));
        assert.equal(value.instances, undefined);
        assert.equal(value.load, undefined);
    }
    for (const name of ['New York', 'London', 'Tokyo', 'Frankfurt', 'Singapore', 'Sydney', 'Cairo', 'Lagos', 'Sao Paulo']) {
        assert.ok(MAJOR_CITIES.some(value => value.name === name), name);
    }
});

test('label density follows globe, region and country modes', () => {
    const candidates = [city('Global', 30, 50), city('Regional', 150, 50, 2), city('Local', 270, 50, 3)];
    assert.deepEqual(selectCityLabels(candidates, 'globe', 600, 400).map(value => value.name), ['Global']);
    assert.deepEqual(selectCityLabels(candidates, 'region', 600, 400).map(value => value.name), ['Global', 'Regional']);
    assert.deepEqual(selectCityLabels(candidates, 'country', 600, 400).map(value => value.name), ['Global', 'Regional', 'Local']);
});

test('labels do not overlap one another, controls or canvas boundaries', () => {
    const obstacles = [{ left: 150, right: 250, top: 20, bottom: 80 }];
    const candidates = [city('First', 30, 50, 1, { previouslyVisible: true }), city('Collision', 35, 50), city('Menu', 160, 50), city('Clipped', 580, 50), city('Clear', 300, 100)];
    const result = selectCityLabels(candidates, 'country', 600, 400, obstacles);
    assert.deepEqual(result.map(value => value.name), ['First', 'Clear']);
    assert.ok(rectanglesOverlap({ left: 0, top: 0, right: 10, bottom: 10 }, { left: 9, top: 9, right: 20, bottom: 20 }));
});

test('previously visible labels win ties to reduce animation flicker', () => {
    const candidates = [city('New', 50, 50), city('Existing', 52, 50, 1, { previouslyVisible: true, distance: 1 })];
    assert.equal(selectCityLabels(candidates, 'globe', 600, 400)[0].name, 'Existing');
});

test('mobile layout limits visible labels even with many candidates', () => {
    const candidates = Array.from({ length: 60 }, (_, i) => city(`City ${i}`, 12 + i % 4 * 75, 20 + Math.floor(i / 4) * 28, 1, { width: 45 }));
    const result = selectCityLabels(candidates, 'country', 320, 420);
    assert.ok(result.length > 0);
    assert.ok(result.length <= Math.floor(320 * 420 / 14000));
});
