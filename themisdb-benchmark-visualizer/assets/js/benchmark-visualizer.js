(function () {
    'use strict';

    const finite = value => typeof value === 'number' && Number.isFinite(value) && value >= 0;
    const format = value => finite(value) ? new Intl.NumberFormat(undefined, { maximumSignificantDigits: 5 }).format(value) : '\u2014';
    const unit = metric => ({ latency: 'ms', throughput: 'ops/s', memory: 'MiB' })[metric];
    const csvCell = value => '"' + String(value ?? '').replace(/^[=+@\-\t\r]/, "'$&").replace(/"/g, '""') + '"';

    function prismFaces(bar) {
        const { x, y, base, width } = bar;
        if (![x, y, base, width].every(Number.isFinite) || width <= 0 || y >= base) return [];
        const depth = Math.min(8, width * 0.3);
        const lift = depth * Math.tan(Math.PI / 6);
        const left = x - width / 2, right = x + width / 2;
        return [
            [[right, y], [right + depth, y - lift], [right + depth, base - lift], [right, base]],
            [[left, y], [left + depth, y - lift], [right + depth, y - lift], [right, y]],
        ];
    }

    const isometricBars = {
        id: 'themisdbIsometricBars',
        afterDatasetDraw(chart, { meta, index }) {
            const ctx = chart.ctx;
            ctx.save();
            ctx.beginPath();
            ctx.rect(chart.chartArea.left, chart.chartArea.top - 8,
                chart.chartArea.width + 8, chart.chartArea.height + 8);
            ctx.clip();
            meta.data.forEach((bar, point) => {
                if (!finite(chart.data.datasets[index].data[point])) return;
                const faces = prismFaces(bar.getProps(['x', 'y', 'base', 'width']));
                faces.forEach((vertices, face) => {
                    ctx.beginPath();
                    vertices.forEach(([x, y], vertex) => vertex ? ctx.lineTo(x, y) : ctx.moveTo(x, y));
                    ctx.closePath();
                    const color = Chart.helpers.color(bar.options.borderColor);
                    ctx.fillStyle = (face ? color.lighten(0.25) : color.darken(0.22)).rgbString();
                    ctx.fill();
                });
            });
            ctx.restore();
        },
    };

    function filterData(source, options) {
        let datasets = source.datasets.filter(dataset => options.engine === 'all' || dataset.label === options.engine);
        const requested = options.compare.split(',').map(value => value.trim().toLowerCase()).filter(Boolean);
        if (requested.length) {
            datasets = datasets.filter(dataset => dataset.label.toLowerCase() === 'themisdb' || requested.includes(dataset.label.toLowerCase()));
        }
        datasets = datasets.filter(dataset => dataset.data.some(finite));
        let indices = source.labels.map((_, index) => index).filter(index =>
            source.labels[index].toLowerCase().includes(options.search.toLowerCase()) &&
            datasets.some(dataset => finite(dataset.data[index]))
        );
        const higher = source.metric === 'throughput';
        const primary = source.datasets.find(dataset => dataset.label === options.reference) ||
            datasets.find(dataset => dataset.label.toLowerCase() === 'themisdb') || datasets[0];
        if (options.sort === 'name') indices.sort((left, right) => source.labels[left].localeCompare(source.labels[right]));
        else if (primary && options.sort !== 'source') {
            indices.sort((left, right) => {
                const a = primary.data[left], b = primary.data[right];
                if (!finite(a)) return finite(b) ? 1 : 0;
                if (!finite(b)) return -1;
                const order = higher ? b - a : a - b;
                return options.sort === 'worst' ? -order : order;
            });
        }
        const total = indices.length;
        if (options.top !== 'all') indices = indices.slice(0, Number(options.top));
        return {
            ...source,
            labels: indices.map(index => source.labels[index]),
            datasets: datasets.map(dataset => ({ ...dataset, data: indices.map(index => dataset.data[index]) })).filter(dataset => dataset.data.some(finite)),
            total,
            reference: primary?.label,
        };
    }

    function performanceFactor(data, source, engine, label, reference) {
        if (source.source?.type !== 'demo' && source.summary?.task_comparison_verified?.[label] !== true) return null;
        const index = source.labels.indexOf(label);
        const baseline = source.datasets.find(dataset => dataset.label === reference)?.data[index];
        const value = data.datasets.find(dataset => dataset.label === engine)?.data[data.labels.indexOf(label)];
        if (!finite(value) || !finite(baseline) || value === 0 || baseline === 0) return null;
        const ratio = source.metric === 'throughput' ? value / baseline : baseline / value;
        return finite(ratio) && ratio > 0 ? ratio : null;
    }

    class Visualizer {
        constructor(root) {
            this.root = root;
            this.config = JSON.parse(root.dataset.bvConfig);
            this.root.dataset.bvTheme = this.config.settings.chart_theme === 'dark' ? 'dark' : 'light';
            this.labels = this.config.labels;
            this.chart = null;
            this.request = null;
            this.sequence = 0;
            this.active = 0;
            this.source = null;
            this.data = null;
            this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.onMotionChange = () => this.renderChart();
            this.motion.addEventListener('change', this.onMotionChange);
            this.root.addEventListener('change', event => {
                if (['category', 'metric'].includes(event.target.dataset.bv)) this.load();
                else if (event.target.matches('select')) this.render();
            });
            this.root.addEventListener('input', event => {
                if (event.target.dataset.bv === 'search') this.render();
            });
            this.root.addEventListener('click', event => {
                const button = event.target.closest('button[data-bv]');
                if (!button) return;
                const action = button.dataset.bv;
                if (action === 'refresh') this.load();
                if (action === 'csv') this.exportCSV();
                if (action === 'print') window.print();
                if (action === 'previous' || action === 'next') {
                    if (!this.data?.datasets.length) return;
                    this.active = (this.active + (action === 'next' ? 1 : -1) + this.data.datasets.length) % this.data.datasets.length;
                    this.renderChart();
                    this.renderStats();
                }
            });
            this.onKey = event => {
                if (event.key === 'Escape' && this.el('options')?.open) {
                    this.el('options').open = false;
                    this.el('options').querySelector('summary').focus();
                }
            };
            this.root.querySelector('details.themisdb-benchmark-options')?.setAttribute('data-bv', 'options');
            this.root.addEventListener('keydown', this.onKey);
            this.onOutside = event => {
                if (!this.root.contains(event.target) && this.el('options')) this.el('options').open = false;
            };
            document.addEventListener('click', this.onOutside);
            this.onBeforePrint = () => {
                if (!this.data) return;
                this.tableWasOpen = this.root.querySelector('.themisdb-benchmark-data').open;
                this.root.querySelector('.themisdb-benchmark-data').open = true;
            };
            this.onAfterPrint = () => {
                if (typeof this.tableWasOpen !== 'boolean') return;
                this.root.querySelector('.themisdb-benchmark-data').open = this.tableWasOpen;
                this.tableWasOpen = null;
            };
            window.addEventListener('beforeprint', this.onBeforePrint);
            window.addEventListener('afterprint', this.onAfterPrint);
            this.load();
        }

        el(name) { return this.root.querySelector('[data-bv="' + name + '"]'); }
        value(name, fallback) { return this.el(name)?.value || fallback; }
        text(name, value) { if (this.el(name)) this.el(name).textContent = value; }

        async load() {
            const sequence = ++this.sequence;
            this.request?.abort();
            this.request = new AbortController();
            this.source = null;
            this.clear();
            this.root.dataset.bvState = 'loading';
            this.root.setAttribute('aria-busy', 'true');
            this.text('status', this.labels.loading);
            const body = new URLSearchParams({
                action: 'themisdb_bv_get_data', nonce: this.config.nonce,
                category: this.value('category', this.config.settings.default_category),
                metric: this.value('metric', this.config.settings.default_metric),
            });
            try {
                const response = await fetch(this.config.ajax_url, { method: 'POST', body, signal: this.request.signal, credentials: 'same-origin' });
                const payload = await response.json();
                if (sequence !== this.sequence) return;
                if (!response.ok || !payload.success) throw new Error(payload.data?.message || this.labels.error);
                const source = payload.data;
                if (!Array.isArray(source.labels) || !Array.isArray(source.datasets) || !unit(source.metric) ||
                    source.labels.some(label => typeof label !== 'string') ||
                    source.datasets.some(dataset => typeof dataset.label !== 'string' || !Array.isArray(dataset.data) ||
                        dataset.data.length !== source.labels.length || dataset.data.some(value => value !== null && !finite(value)))) {
                    throw new Error(this.labels.error);
                }
                this.source = source;
                const select = this.el('engine');
                if (select) {
                    const previous = select.value;
                    select.replaceChildren(new Option(this.labels.all_engines, 'all'), ...source.datasets.map(dataset => new Option(dataset.label, dataset.label)));
                    if ([...select.options].some(option => option.value === previous)) select.value = previous;
                }
                const reference = this.el('reference');
                if (reference) {
                    const previous = reference.value;
                    reference.replaceChildren(new Option(this.labels.automatic, 'auto'),
                        ...source.datasets.map(dataset => new Option(dataset.label, dataset.label)));
                    if ([...reference.options].some(option => option.value === previous)) reference.value = previous;
                }
                this.active = 0;
                this.render();
            } catch (error) {
                if (error.name === 'AbortError' || sequence !== this.sequence) return;
                console.error('ThemisDB Benchmark Visualizer:', error);
                this.clear();
                this.root.dataset.bvState = 'error';
                this.text('status', error.message || this.labels.error);
            } finally {
                if (sequence === this.sequence) this.root.setAttribute('aria-busy', 'false');
            }
        }

        clear() {
            this.chart?.destroy();
            this.chart = null;
            this.data = null;
            ['table', 'stats', 'source', 'active-engine'].forEach(name => this.el(name)?.replaceChildren());
            if (this.el('nav')) this.el('nav').hidden = true;
            if (this.el('chart-note')) this.el('chart-note').hidden = true;
            if (this.el('analysis-note')) this.el('analysis-note').hidden = true;
            ['csv', 'print'].forEach(name => { if (this.el(name)) this.el(name).disabled = true; });
        }

        render() {
            if (!this.source) return;
            this.data = filterData(this.source, {
                engine: this.value('engine', 'all'), compare: this.source.source?.type === 'demo' ? '' : this.config.compare || '',
                search: this.value('search', ''), top: this.value('top-n', this.config.settings.default_top_n),
                sort: this.value('sort', 'best'), reference: this.value('reference', 'auto'),
            });
            if (!this.data.labels.length || !this.data.datasets.length) {
                this.clear();
                this.root.dataset.bvState = 'empty';
                this.text('status', this.labels.empty);
                return;
            }
            this.active = Math.min(this.active, this.data.datasets.length - 1);
            this.root.dataset.bvState = 'ready';
            const metric = this.data.metric;
            this.text('title', this.labels.metrics[metric] + ' (' + unit(metric) + ')');
            this.text('status', this.data.labels.length + ' / ' + this.data.total + ' ' + this.labels.tasks + '. ' +
                (metric === 'throughput' ? this.labels.higher : this.labels.lower));
            const comparable = this.data.datasets.length > 1 && this.data.labels.some((label, index) =>
                this.data.summary?.task_comparison_verified?.[label] === true &&
                this.data.datasets.filter(dataset => finite(dataset.data[index])).length > 1);
            const provenance = this.data.source ? ' ' + this.data.source.file + (this.data.source.modified ? ' | ' + this.data.source.modified : '') : '';
            const excluded = this.data.summary?.excluded_non_comparable_tasks;
            this.text('source', (comparable ? this.labels.comparison : this.data.datasets.length > 1 ? this.labels.disjoint : this.labels.single) + provenance +
                (excluded ? ' ' + this.labels.excluded.replace('%s', String(excluded)) : '') +
                (this.data.summary?.unverified_records ? ' ' + this.labels.unverified : ''));
            if (this.data.source?.type === 'demo') this.text('source', this.labels.demo + provenance);
            if (this.el('nav')) this.el('nav').hidden = this.data.datasets.length < 2;
            ['csv', 'print'].forEach(name => { if (this.el(name)) this.el(name).disabled = false; });
            this.renderTable();
            this.renderChart();
            this.renderStats();
        }

        factor(engine, label) {
            return performanceFactor(this.data, this.source, engine, label, this.data.reference);
        }

        renderChart() {
            this.chart?.destroy();
            this.chart = null;
            const canvas = this.el('chart');
            if (this.el('chart-note')) this.el('chart-note').hidden = !canvas || !this.data ||
                this.value('chart-type', this.config.settings.default_chart_type) !== 'bar-isometric';
            const requestedLog = this.value('scale', 'linear') === 'logarithmic';
            const radar = this.value('chart-type', this.config.settings.default_chart_type) === 'radar';
            const logarithmic = requestedLog && !radar;
            const note = this.el('analysis-note');
            if (note) {
                note.textContent = [this.labels.factor_note, requestedLog ? (radar ? this.labels.radar_linear : this.labels.log_note) : ''].filter(Boolean).join(' ');
                note.hidden = !this.data;
            }
            if (!canvas || !this.data) return;
            try {
                if (typeof Chart !== 'function') throw new Error('Chart.js is unavailable.');
                const kind = this.value('chart-type', this.config.settings.default_chart_type);
                const bar = kind === 'bar' || kind.startsWith('bar-');
                const isometric = kind === 'bar-isometric';
                const horizontal = kind === 'bar-horizontal' || (kind === 'bar' && (this.data.labels.length > 8 || this.root.clientWidth < 600));
                const metric = this.data.metric;
                const style = getComputedStyle(this.root);
                const token = name => style.getPropertyValue('--bv-' + name).trim();
                const colors = Array.from({ length: 6 }, (_, index) => token('series-' + (index + 1)));
                const textColor = token('text');
                const gridColor = token('grid');
                const showTaskLabels = this.value('task-labels', 'hidden') === 'visible';
                this.chart = new Chart(canvas, {
                    type: bar ? 'bar' : kind === 'radar' ? 'radar' : 'line',
                    plugins: isometric ? [isometricBars] : [],
                    data: {
                        labels: this.data.labels,
                        datasets: this.data.datasets.map((dataset, index) => ({
                            label: dataset.label, data: dataset.data.map(value => logarithmic && value === 0 ? null : value), spanGaps: false,
                            backgroundColor: Chart.helpers.color(colors[index % colors.length]).alpha(kind === 'area' || kind === 'radar' ? 0.25 : 0.8).rgbString(),
                            borderColor: colors[index % colors.length],
                            borderWidth: index === this.active ? 2 : 1,
                            fill: kind === 'area' || kind === 'radar',
                            pointRadius: kind === 'line-dot' || kind === 'radar' ? 3 : 0,
                            pointHitRadius: 10,
                            stepped: kind === 'stepped' ? 'before' : false,
                            categoryPercentage: isometric ? 0.7 : 0.8,
                            barPercentage: isometric ? 0.65 : 0.9,
                        })),
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x',
                        animation: this.motion.matches ? false : { duration: 300 },
                        layout: { padding: isometric ? { top: 10, right: 10 } : 0 },
                        interaction: { mode: 'nearest', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { color: textColor } },
                            tooltip: {
                                backgroundColor: token('surface'), titleColor: textColor, bodyColor: textColor,
                                borderColor: token('border'), borderWidth: 1,
                                callbacks: { label: context => {
                                    const factor = this.factor(context.dataset.label, this.data.labels[context.dataIndex]);
                                    return context.dataset.label + ': ' + format(context.raw) + ' ' + unit(metric) +
                                        (factor !== null ? ' | ' + format(factor) + 'x ' + this.labels.factor + ' ' + this.data.reference : '');
                                } },
                            },
                        },
                        scales: kind === 'radar' ? { r: { beginAtZero: true, ticks: { color: textColor, backdropColor: 'transparent' }, pointLabels: { display: showTaskLabels, color: textColor }, grid: { color: gridColor } } } : {
                            x: horizontal ? { type: logarithmic ? 'logarithmic' : 'linear', beginAtZero: !logarithmic, ticks: { color: textColor }, grid: { color: gridColor }, title: { display: true, text: unit(metric), color: textColor } } : {
                                grid: { color: gridColor }, ticks: { display: showTaskLabels, color: textColor, maxRotation: 0, autoSkip: true, callback: (value) => this.shortLabel(this.data.labels[value]) },
                            },
                            y: horizontal ? { grid: { color: gridColor }, ticks: { display: showTaskLabels, color: textColor, autoSkip: true, callback: value => this.shortLabel(this.data.labels[value]) } } :
                                { type: logarithmic ? 'logarithmic' : 'linear', beginAtZero: !logarithmic, ticks: { color: textColor }, grid: { color: gridColor }, title: { display: true, text: unit(metric), color: textColor } },
                        },
                    },
                });
            } catch (error) {
                console.error('ThemisDB Benchmark Visualizer: chart rendering failed.', error);
                this.text('status', this.labels.chart_error);
                this.root.dataset.bvState = 'error';
                this.root.querySelector('.themisdb-benchmark-data').open = true;
            }
        }

        shortLabel(label) { return label.length > 34 ? label.slice(0, 33) + '\u2026' : label; }

        renderTable() {
            const container = this.el('table');
            if (!container || !this.data) return;
            const table = document.createElement('table');
            const caption = table.createCaption();
            caption.textContent = (this.data.source?.type === 'demo' ? this.labels.demo + ' ' : '') + this.labels.metrics[this.data.metric] + ' (' + unit(this.data.metric) + ')';
            const head = table.createTHead().insertRow();
            [this.labels.task, ...this.data.datasets.map(dataset => dataset.label)].forEach(label => {
                const th = document.createElement('th');
                th.scope = 'col'; th.textContent = label; head.append(th);
            });
            const body = table.createTBody();
            this.data.labels.forEach((label, index) => {
                const row = body.insertRow(), th = document.createElement('th');
                th.scope = 'row'; th.textContent = label; row.append(th);
                const sources = this.data.summary?.task_sources?.[label];
                if (Array.isArray(sources) && sources.length) {
                    const details = document.createElement('details'), summary = document.createElement('summary');
                    summary.textContent = this.labels.variants + ' (' + sources.length + ')';
                    details.append(summary);
                    sources.forEach(source => { const p = document.createElement('p'); p.textContent = source; details.append(p); });
                    th.append(details);
                }
                const protocols = this.data.summary?.task_protocols?.[label];
                if (protocols) {
                    const details = document.createElement('details'), summary = document.createElement('summary');
                    summary.textContent = this.labels.protocol;
                    details.append(summary);
                    Object.entries(protocols).forEach(([engine, protocol]) => {
                        const p = document.createElement('p');
                        p.textContent = engine + ': ' + JSON.stringify(protocol);
                        details.append(p);
                    });
                    th.append(details);
                }
                this.data.datasets.forEach(dataset => {
                    const value = dataset.data[index];
                    const factor = this.factor(dataset.label, label);
                    row.insertCell().textContent = finite(value) ? format(value) + ' ' + unit(this.data.metric) +
                        (factor !== null ? ' | ' + format(factor) + 'x ' + this.labels.factor + ' ' + this.data.reference : '') : this.labels.missing;
                });
            });
            container.replaceChildren(table);
        }

        renderStats() {
            if (!this.data) return;
            const dataset = this.data.datasets[this.active];
            this.text('active-engine', dataset.label);
            const values = dataset.data.filter(finite);
            const target = this.el('stats');
            if (!target) return;
            const best = this.data.metric === 'throughput' ? Math.max(...values) : Math.min(...values);
            target.replaceChildren(...[
                dataset.label,
                this.labels.measured + ': ' + values.length,
                this.labels.mean + ': ' + format(values.reduce((a, b) => a + b, 0) / values.length) + ' ' + unit(this.data.metric),
                this.labels.best + ': ' + format(best) + ' ' + unit(this.data.metric),
            ].map(text => { const span = document.createElement('span'); span.textContent = text; return span; }));
        }

        exportCSV() {
            if (!this.data) return;
            const rows = [[this.labels.task + ' (' + unit(this.data.metric) + ')', ...this.data.datasets.map(dataset => dataset.label)],
                ...this.data.labels.map((label, index) => [label, ...this.data.datasets.map(dataset => dataset.data[index])])];
            rows[0].push(...this.data.datasets.map(dataset => dataset.label + ' | ' + this.labels.factor + ' ' + this.data.reference));
            this.data.labels.forEach((label, index) => rows[index + 1].push(...this.data.datasets.map(dataset => this.factor(dataset.label, label))));
            if (this.data.source?.type === 'demo') rows.unshift([this.labels.demo]);
            const csv = '\ufeff' + rows.map(row => row.map(csvCell).join(',')).join('\r\n');
            const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            const link = document.createElement('a');
            link.href = url; link.download = (this.data.source?.type === 'demo' ? 'DEMO-synthetic-' : '') + 'themisdb-benchmarks-' + this.data.metric + '.csv';
            link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }

        dispose() {
            this.request?.abort();
            this.chart?.destroy();
            document.removeEventListener('click', this.onOutside);
            this.motion.removeEventListener('change', this.onMotionChange);
            window.removeEventListener('beforeprint', this.onBeforePrint);
            window.removeEventListener('afterprint', this.onAfterPrint);
        }
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = { filterData, finite, format, csvCell, prismFaces, performanceFactor };
    if (typeof document === 'undefined') return;
    const instances = new Map();
    const initialize = () => document.querySelectorAll('[data-bv-config]').forEach(root => {
        if (!instances.has(root)) instances.set(root, new Visualizer(root));
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
    new MutationObserver(() => {
        instances.forEach((instance, root) => { if (!root.isConnected) { instance.dispose(); instances.delete(root); } });
        initialize();
    }).observe(document.body, { childList: true, subtree: true });
})();
