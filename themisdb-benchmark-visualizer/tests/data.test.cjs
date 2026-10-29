const { test } = require('node:test');
const assert = require('node:assert/strict');
const { filterData, finite, format, csvCell, prismFaces, performanceFactor } = require('../assets/js/benchmark-visualizer.js');
const source = {
    labels: ['first', 'second', 'third'], metric: 'latency',
    datasets: [{ label: 'ThemisDB', data: [null, 0.000002, 0.3] }, { label: 'PostgreSQL', data: [2, null, 1] }],
};
const defaults = { engine: 'all', search: '', top: 'all', compare: '' };
test('sorting uses the chosen reference, keeps missing values last, and precedes Top-N', () => {
    assert.deepEqual(filterData(source, { ...defaults, reference: 'PostgreSQL', top: '1' }).labels, ['third']);
    assert.deepEqual(filterData(source, { ...defaults, reference: 'PostgreSQL', sort: 'worst' }).labels, ['first', 'third', 'second']);
    assert.deepEqual(filterData(source, { ...defaults, sort: 'source' }).labels, source.labels);
    assert.deepEqual(filterData(source, { ...defaults, sort: 'name' }).labels, ['first', 'second', 'third']);
});
test('reference factors respect metric direction, protocol eligibility, zero and absence', () => {
    const data = { labels: ['task'], datasets: [{ label: 'A', data: [2] }, { label: 'B', data: [4] }], metric: 'latency' };
    assert.equal(performanceFactor(data, data, 'B', 'task', 'A'), null);
    const demo = { ...data, source: { type: 'demo' } };
    assert.equal(performanceFactor(demo, demo, 'B', 'task', 'A'), 0.5);
    const throughput = { ...demo, metric: 'throughput' };
    assert.equal(performanceFactor(throughput, throughput, 'B', 'task', 'A'), 2);
    const verified = { ...data, summary: { task_comparison_verified: { task: true } } };
    assert.equal(performanceFactor(verified, verified, 'A', 'task', 'A'), 1);
    assert.equal(performanceFactor(demo, demo, 'B', 'task', 'missing'), null);
    assert.equal(performanceFactor({ ...demo, datasets: [{ label: 'B', data: [0] }] }, demo, 'B', 'task', 'A'), null);
});
test('isometric geometry keeps front values and uses bounded 30-degree depth', () => {
    const [side, top] = prismFaces({ x: 50, y: 20, base: 120, width: 40 });
    assert.deepEqual(side[0], [70, 20]);
    assert.deepEqual(side[3], [70, 120]);
    assert.equal(top[1][0] - top[0][0], 8);
    assert.ok(Math.abs((top[0][1] - top[1][1]) / 8 - Math.tan(Math.PI / 6)) < 1e-10);
    assert.deepEqual(prismFaces({ x: 5, y: 100, base: 100, width: 10 }), []);
    assert.deepEqual(prismFaces({ x: NaN, y: 0, base: 100, width: 10 }), []);
});
test('null is not zero, but measured zero and tiny numbers are preserved', () => {
    assert.equal(finite(null), false);
    assert.equal(finite('1'), false);
    assert.equal(finite(Infinity), false);
    assert.equal(finite(0), true);
    assert.notEqual(format(0.000002), '0');
    assert.deepEqual(filterData(source, { ...defaults, top: '1' }).datasets[0].data, [0.000002]);
});
test('throughput sorting puts missing values last rather than first', () => {
    const data = filterData({ ...source, metric: 'throughput' }, { ...defaults, top: '1' });
    assert.deepEqual(data.labels, ['third']);
});
test('search, engine and compare settings are applied independently', () => {
    assert.deepEqual(filterData(source, { ...defaults, search: 'SECOND' }).labels, ['second']);
    assert.deepEqual(filterData(source, { ...defaults, engine: 'PostgreSQL' }).labels, ['third', 'first']);
    assert.equal(filterData(source, { ...defaults, compare: 'mongodb' }).datasets.length, 1);
    assert.equal(source.labels.length, 3);
    assert.equal(source.datasets[0].data[0], null);
});
test('all really retains more than thirty tasks', () => {
    const data = { labels: Array.from({ length: 60 }, (_, i) => String(i)), metric: 'latency', datasets: [{ label: 'ThemisDB', data: Array.from({ length: 60 }, (_, i) => i) }] };
    assert.equal(filterData(data, defaults).labels.length, 60);
    assert.equal(filterData(data, { ...defaults, top: '50' }).labels.length, 50);
});
test('engines with no measurement after task filtering do not produce invalid statistics', () => {
    const data = filterData(source, { ...defaults, search: 'first' });
    assert.deepEqual(data.datasets.map(dataset => dataset.label), ['PostgreSQL']);
});
test('CSV preserves commas, quotes and empty missing values', () => {
    assert.equal(csvCell('a,"b"'), '"a,""b"""');
    assert.equal(csvCell(null), '""');
    assert.equal(csvCell('=1+1'), '"\'=1+1"');
});
