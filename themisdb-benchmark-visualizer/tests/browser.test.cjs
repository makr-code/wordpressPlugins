const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const baseURL = process.env.WORDPRESS_URL || 'http://localhost/wordpress/';

test('configured source renders on WordPress with correct provenance and theme', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage({ reducedMotion: 'reduce' });
        await page.goto(baseURL);
        const root = page.locator('[data-bv-config]').first();
        await root.scrollIntoViewIfNeeded();
        await page.waitForFunction(() => document.querySelector('[data-bv-config]')?.dataset.bvState === 'ready');
        const info = await root.evaluate(element => ({
            chart: !!Chart.getChart(element.querySelector('canvas')),
            first: element.querySelector('tbody th').textContent,
            state: element.dataset.bvState,
            demo: JSON.parse(element.dataset.bvConfig).demo,
            engines: Chart.getChart(element.querySelector('canvas')).data.datasets.map(dataset => dataset.label),
        }));
        assert.ok(info.chart && info.first.length > 4);
        assert.equal(info.state, 'ready');
        if (info.demo) {
            assert.match(await root.locator('[data-bv=source]').textContent(), /DEMO: Synthetische/);
            assert.ok(await root.locator('.themisdb-benchmark-demo').isVisible());
            assert.deepEqual(info.engines, ['VectorFox Demo', 'GraphNest Demo', 'NimbusMock Demo']);
        } else {
            assert.match(await root.locator('[data-bv=source]').textContent(), /Referenz-\/Altdaten|Vergleich mit uebereinstimmenden/);
        }
        assert.equal(await root.locator('.themisdb-benchmark-controls > br').count(), 0, 'WordPress formatting does not inflate the options grid');
        const theme = await root.evaluate(element => {
            const style = getComputedStyle(element), chart = Chart.getChart(element.querySelector('canvas'));
            return {
                text: style.getPropertyValue('--bv-text').trim(),
                expectedText: style.getPropertyValue('--tv3-text-primary').trim(),
                series: chart.data.datasets[0].borderColor,
                expectedSeries: style.getPropertyValue('--tv3-accent-primary').trim(),
                legend: chart.options.plugins.legend.labels.color,
            };
        });
        assert.equal(theme.text, theme.expectedText);
        assert.equal(theme.legend, theme.expectedText);
        assert.equal(theme.series, theme.expectedSeries);
        for (const width of [1440, 860, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            await page.waitForFunction(() => {
                const canvas = document.querySelector('[data-bv-config] canvas');
                return Math.abs(canvas.getBoundingClientRect().width - canvas.parentElement.clientWidth) < 2;
            });
            assert.ok(await root.evaluate(element => element.getBoundingClientRect().right <= innerWidth + 1));
        }
    } finally { await browser.close(); }
});

test('standalone multi-instance controls, charts, missing data, errors, CSV and race handling', async () => {
    const browser = await chromium.launch();
    try {
        const page = await browser.newPage({ reducedMotion: 'reduce' });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(baseURL);
        const template = await page.locator('[data-bv-config]').first().evaluate(element => {
            const clone = element.cloneNode(true);
            clone.querySelector('[data-bv=table]').replaceChildren();
            clone.querySelector('[data-bv=status]').textContent = '';
            clone.querySelector('[data-bv=source]').textContent = '';
            clone.dataset.bvState = 'loading';
            return clone.outerHTML;
        });
        const pluginURL = new URL('wp-content/plugins/themisdb-benchmark-visualizer/', baseURL).href;
        const fixtureURL = new URL('bv-test-fixture/', baseURL).href;
        await page.route(fixtureURL, route => route.fulfill({ contentType: 'text/html; charset=utf-8', body:
            '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' +
            '<link rel="stylesheet" href="' + pluginURL + 'assets/css/benchmark-visualizer.css">' +
            '<main>' + template + template.replace(/themisdb-bv-(\d+)-/g, 'themisdb-bv-other-$1-') + '</main>' +
            '<script src="' + pluginURL + 'assets/vendor/chart.umd.min.js"></script>' +
            '<script src="' + pluginURL + 'assets/js/benchmark-visualizer.js"></script>',
        }));
        let fail = false, demoMode = false, calls = 0;
        await page.route('**/admin-ajax.php', async route => {
            const params = new URLSearchParams(route.request().postData());
            if (params.get('action') !== 'themisdb_bv_get_data') return route.continue();
            calls++;
            const category = params.get('category'), metric = params.get('metric');
            if (fail) return route.fulfill({ status: 503, json: { success: false, data: { message: 'Fixture data unavailable' } } });
            if (category === 'graph_traversal') await new Promise(resolve => setTimeout(resolve, 300));
            const labels = Array.from({ length: 60 }, (_, index) => (index === 0 ? 'Quoted, "task"' : 'Task/' + index));
            const data = {
                labels: metric === 'memory' ? [] : labels,
                datasets: metric === 'memory' ? [] : [
                    { label: 'ThemisDB', data: labels.map((_, index) => index === 1 ? null : (index + 1) / 100000) },
                    { label: 'PostgreSQL', data: labels.map((_, index) => (index + 1) / 1000) },
                ],
                metric, category, source: { type: 'fixture', file: 'explicit-test-fixture.json' },
                summary: { unverified_records: 60, task_protocols: { [labels[0]]: { ThemisDB: { issues: ['publication metadata missing'] } } } },
            };
            if (demoMode) {
                data.source = { type: 'demo', file: 'synthetic-test-fixture' };
                data.datasets[0].label = 'VectorFox Demo';
                data.datasets[1].label = 'NimbusMock Demo';
                data.datasets[1].data[2] = 0;
                data.summary = { synthetic: true };
            }
            await route.fulfill({ json: { success: true, data } });
        });
        await page.goto(fixtureURL);
        await page.waitForFunction(() => [...document.querySelectorAll('[data-bv-config]')].every(element => element.dataset.bvState === 'ready'));
        const roots = page.locator('[data-bv-config]'), first = roots.nth(0), second = roots.nth(1);
        assert.equal(await roots.count(), 2);
        assert.match(await first.locator('[data-bv=source]').textContent(), /Kein freigegebener Datenbankvergleich/);
        assert.match(await first.locator('tbody th').first().textContent(), /Vergleichsprotokoll/);
        const standalone = await first.evaluate(element => ({
            background: getComputedStyle(element).backgroundColor,
            themeTokens: getComputedStyle(element).getPropertyValue('--tv3-text-primary'),
        }));
        assert.equal(standalone.background, 'rgb(255, 255, 255)');
        assert.equal(standalone.themeTokens, '');
        await first.evaluate(element => { element.dataset.bvTheme = 'dark'; });
        assert.equal(await first.evaluate(element => getComputedStyle(element).backgroundColor), 'rgb(22, 40, 61)');
        await first.evaluate(element => {
            element.parentElement.style.setProperty('--themisdb-bv-surface', '#faf5ef');
            element.parentElement.style.setProperty('--themisdb-bv-text', '#30271f');
            element.parentElement.style.setProperty('--themisdb-bv-series-1', 'rgb(138, 86, 56)');
            element.parentElement.style.setProperty('--themisdb-bv-color-scheme', 'light');
        });
        await first.locator('.themisdb-benchmark-options > summary').click();
        await first.locator('[data-bv=chart-type]').selectOption('area');
        const themed = await first.evaluate(element => {
            const chart = Chart.getChart(element.querySelector('canvas')), style = getComputedStyle(element);
            return { background: style.backgroundColor, scheme: style.colorScheme,
                series: chart.data.datasets[0].borderColor,
                fill: chart.data.datasets[0].backgroundColor,
                tooltip: chart.options.plugins.tooltip.bodyColor,
                legend: chart.options.plugins.legend.labels.color };
        });
        assert.equal(themed.background, 'rgb(250, 245, 239)', 'Inherited theme colors override dark defaults');
        assert.equal(themed.scheme, 'light');
        assert.equal(themed.series, 'rgb(138, 86, 56)');
        assert.match(themed.fill, /^rgba?\(/, 'RGB tokens work with transparent area fills');
        assert.equal(themed.tooltip, '#30271f');
        assert.equal(themed.legend, '#30271f');
        await first.evaluate(element => {
            element.dataset.bvTheme = 'light';
            element.parentElement.removeAttribute('style');
        });
        await first.locator('[data-bv=chart-type]').selectOption('bar');
        await first.locator('[data-bv=top-n]').selectOption('all');
        assert.equal(await first.locator('tbody tr').count(), 60);
        assert.equal(await second.locator('tbody tr').count(), 30);
        await first.locator('[data-bv=search]').fill('Quoted');
        assert.equal(await first.locator('tbody tr').count(), 1);
        const downloadPromise = page.waitForEvent('download');
        await first.locator('[data-bv=csv]').click();
        const download = await downloadPromise;
        const stream = await download.createReadStream();
        let csv = '';
        for await (const chunk of stream) csv += chunk;
        assert.ok(csv.includes('"Quoted, ""task"""'));
        await first.locator('[data-bv=search]').fill('');
        const before = calls;
        for (const kind of ['line', 'line-dot', 'stepped', 'area', 'radar', 'bar', 'bar-horizontal', 'bar-vertical', 'bar-isometric']) {
            await first.locator('[data-bv=chart-type]').selectOption(kind);
            const chart = await first.locator('canvas').evaluate(canvas => ({
                type: Chart.getChart(canvas).config.type,
                animated: Chart.getChart(canvas).options.animation,
                axis: Chart.getChart(canvas).options.indexAxis,
                dots: Chart.getChart(canvas).data.datasets[0].pointRadius,
                stepped: Chart.getChart(canvas).data.datasets[0].stepped,
                isometric: Chart.getChart(canvas).config.plugins.some(plugin => plugin.id === 'themisdbIsometricBars'),
            }));
            assert.equal(chart.type, kind.startsWith('bar') ? 'bar' : kind === 'radar' ? 'radar' : 'line');
            assert.equal(chart.animated, false);
            assert.equal(chart.dots, kind === 'line-dot' || kind === 'radar' ? 3 : 0);
            assert.equal(chart.stepped, kind === 'stepped' ? 'before' : false);
            assert.equal(chart.isometric, kind === 'bar-isometric');
            if (kind.startsWith('bar-')) assert.equal(chart.axis, kind === 'bar-horizontal' ? 'y' : 'x');
            assert.equal(await first.locator('[data-bv=chart-note]').isVisible(), kind === 'bar-isometric');
            const labelsVisible = () => first.locator('canvas').evaluate(canvas => {
                const chart = Chart.getChart(canvas);
                return chart.config.type === 'radar' ? chart.options.scales.r.pointLabels.display :
                    chart.options.scales[chart.options.indexAxis === 'y' ? 'y' : 'x'].ticks.display;
            });
            assert.equal(await labelsVisible(), false, 'Task labels are hidden by default');
            const plotBefore = await first.locator('canvas').evaluate(canvas => Chart.getChart(canvas).chartArea.width);
            await first.locator('[data-bv=task-labels]').selectOption('visible');
            assert.equal(await labelsVisible(), true);
            if (kind === 'bar') {
                const plotAfter = await first.locator('canvas').evaluate(canvas => Chart.getChart(canvas).chartArea.width);
                assert.ok(plotBefore > plotAfter + 30, 'Hidden horizontal task labels measurably increase bar plot width');
            }
            await first.locator('[data-bv=task-labels]').selectOption('hidden');
        }
        await first.locator('[data-bv=top-n]').selectOption('10');
        await first.locator('[data-bv=chart-type]').selectOption('bar-isometric');
        const prismPixel = await first.locator('canvas').evaluate(canvas => {
            const chart = Chart.getChart(canvas), bar = chart.getDatasetMeta(1).data[9];
            const depth = Math.min(8, bar.width * 0.3), lift = depth * Math.tan(Math.PI / 6);
            const ratio = chart.currentDevicePixelRatio;
            return [...canvas.getContext('2d').getImageData(
                Math.floor((bar.x + depth / 2) * ratio),
                Math.floor((bar.y - lift / 2) * ratio), 1, 1).data];
        });
        assert.ok(prismPixel[3] > 0, 'Isometric top face is actually drawn above the front bar: ' + JSON.stringify(prismPixel));
        await first.locator('[data-bv=top-n]').selectOption('all');
        assert.equal(calls, before, 'chart type changes do not refetch data');
        await first.locator('[data-bv=scale]').selectOption('logarithmic');
        assert.equal(await first.locator('canvas').evaluate(canvas => Chart.getChart(canvas).options.scales.y.type), 'logarithmic');
        assert.match(await first.locator('[data-bv=analysis-note]').textContent(), /Nullwerte/);
        await first.locator('[data-bv=chart-type]').selectOption('radar');
        assert.equal(await first.locator('canvas').evaluate(canvas => Chart.getChart(canvas).scales.r.type), 'radialLinear');
        assert.match(await first.locator('[data-bv=analysis-note]').textContent(), /Radar/);
        await first.locator('[data-bv=chart-type]').selectOption('bar-horizontal');
        assert.equal(await first.locator('canvas').evaluate(canvas => Chart.getChart(canvas).scales.x.type), 'logarithmic');
        await first.locator('[data-bv=reference]').selectOption('PostgreSQL');
        await first.locator('[data-bv=sort]').selectOption('worst');
        assert.match(await first.locator('tbody th').first().textContent(), /Task\/59/);
        await first.locator('[data-bv=scale]').selectOption('linear');
        await first.locator('[data-bv=sort]').selectOption('best');
        await first.locator('[data-bv=reference]').selectOption('auto');
        await first.locator('[data-bv=category]').selectOption('graph_traversal');
        await first.locator('[data-bv=metric]').selectOption('throughput');
        await page.waitForFunction(() => document.querySelector('[data-bv-config]')?.dataset.bvState === 'ready');
        assert.ok((await first.locator('[data-bv=title]').textContent()).includes('ops/s'));
        assert.ok((await second.locator('[data-bv=title]').textContent()).includes('ms'));
        await first.locator('[data-bv=metric]').selectOption('memory');
        await page.waitForFunction(() => document.querySelector('[data-bv-config]')?.dataset.bvState === 'empty');
        assert.equal(await first.locator('tbody tr').count(), 0);
        assert.ok(await first.locator('[data-bv=csv]').isDisabled());
        fail = true;
        await first.locator('[data-bv=refresh]').click();
        await page.waitForFunction(() => document.querySelector('[data-bv-config]')?.dataset.bvState === 'error');
        assert.match(await first.locator('[data-bv=status]').textContent(), /Fixture data unavailable/);
        assert.equal(await second.getAttribute('data-bv-state'), 'ready');
        fail = false;
        await first.locator('[data-bv=metric]').selectOption('latency');
        await page.waitForFunction(() => document.querySelector('[data-bv-config]')?.dataset.bvState === 'ready');
        for (const width of [1440, 860, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            if (await first.locator('.themisdb-benchmark-options').getAttribute('open') === null) {
                await first.locator('.themisdb-benchmark-options > summary').click();
            }
            await first.locator('[data-bv=chart-type]').selectOption('bar-isometric');
            assert.equal(await first.locator('canvas').evaluate(canvas => Chart.getChart(canvas).options.indexAxis), 'x');
            await first.locator('.themisdb-benchmark-options > summary').focus();
            await page.keyboard.press('Escape');
            assert.equal(await first.locator('.themisdb-benchmark-options').getAttribute('open'), null);
            await first.locator('.themisdb-benchmark-data > summary').click();
            assert.ok(await first.locator('table').isVisible());
            const geometry = await first.evaluate(element => ({ width: element.clientWidth, chart: element.querySelector('canvas').getBoundingClientRect().width, scroll: document.documentElement.scrollWidth, viewport: innerWidth }));
            assert.ok(geometry.chart <= geometry.width && geometry.scroll <= geometry.viewport, JSON.stringify(geometry));
            await page.waitForFunction(() => {
                const canvas = document.querySelector('[data-bv-config] canvas');
                return Math.abs(canvas.getBoundingClientRect().width - canvas.parentElement.clientWidth) < 2;
            });
            await first.locator('.themisdb-benchmark-data > summary').click();
        }
        await page.evaluate(() => window.dispatchEvent(new Event('beforeprint')));
        assert.ok(await first.locator('table').isVisible());
        await page.evaluate(() => window.dispatchEvent(new Event('afterprint')));
        assert.equal(await first.locator('.themisdb-benchmark-data').getAttribute('open'), null);
        await page.evaluate(() => { window.fixtureChart = window.Chart; delete window.Chart; });
        await first.locator('.themisdb-benchmark-options > summary').click();
        await first.locator('[data-bv=chart-type]').selectOption('line');
        assert.equal(await first.getAttribute('data-bv-state'), 'error');
        assert.ok(await first.locator('table').isVisible(), 'Chart failure preserves the accessible table');
        assert.equal(await first.locator('[data-bv=csv]').isDisabled(), false);
        await page.evaluate(() => { window.Chart = window.fixtureChart; delete window.fixtureChart; });
        await first.locator('[data-bv=chart-type]').selectOption('bar');
        assert.equal(await first.getAttribute('data-bv-state'), 'ready');
        await second.evaluate(element => element.remove());
        await page.waitForFunction(() => Object.keys(Chart.instances).length === 1);
        demoMode = true;
        await first.locator('[data-bv=refresh]').click();
        await page.waitForFunction(() => document.querySelector('[data-bv-config]')?.dataset.bvState === 'ready');
        assert.match(await first.locator('[data-bv=source]').textContent(), /DEMO: Synthetische/);
        assert.equal(await first.locator('thead th').count(), 3, 'Demo ignores default real-engine compare list');
        assert.match(await first.locator('caption').textContent(), /Keine realen Messungen/);
        assert.match(await first.locator('tbody td').first().textContent(), /1x Leistungsfaktor zu VectorFox Demo/);
        await first.locator('[data-bv=scale]').selectOption('logarithmic');
        const zeroIndex = await first.locator('canvas').evaluate(canvas => {
            const chart = Chart.getChart(canvas);
            const index = chart.data.labels.indexOf('Task/2');
            return { index, plotted: chart.data.datasets[1].data[index] };
        });
        assert.ok(zeroIndex.index >= 0);
        assert.equal(zeroIndex.plotted, null, 'Log plot excludes measured zero without changing data');
        assert.match(await first.locator('tbody tr').filter({ hasText: 'Task/2' }).first().textContent(), /0 ms/);
        const demoDownloadPromise = page.waitForEvent('download');
        await first.locator('[data-bv=csv]').click();
        const demoDownload = await demoDownloadPromise;
        assert.match(demoDownload.suggestedFilename(), /^DEMO-synthetic-/);
        let demoCSV = '';
        for await (const chunk of await demoDownload.createReadStream()) demoCSV += chunk;
        assert.match(demoCSV, /DEMO: Synthetische/);
        assert.match(demoCSV, /VectorFox Demo/);
        assert.match(demoCSV, /"Task\/2","0.00003","0"/, 'CSV retains original measured zero in logarithmic mode');
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});
