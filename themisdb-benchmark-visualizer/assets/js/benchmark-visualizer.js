/**
 * ThemisDB Benchmark Visualizer JavaScript
 * Based on TCO Calculator pattern with Chart.js integration
 */

(function($) {
    'use strict';

    // Global namespace
    window.ThemisDBBenchmarks = {
        chart: null,
        rawData: null,
        currentData: null,
        settings: {},
        currentSlideIndex: 0,
        autoRotateTimer: null,
        autoRotatePaused: false,

        /**
         * Initialize the visualizer
         */
        init: function() {
            // Store settings from PHP
            this.settings = themisdbBV.settings || {};
            this.currentSlideIndex = 0;
            this.rebuildCategoryOptions();
            if ($('#bv-chart-type').length && this.settings.default_chart_type) {
                $('#bv-chart-type').val(this.settings.default_chart_type);
            }
            if ($('#bv-top-n').length && this.settings.default_top_n) {
                $('#bv-top-n').val(String(this.settings.default_top_n));
            }

            // Set up event listeners
            this.setupEventListeners();

            // Load initial data
            this.loadData();
        },

        getDefaultCategoryKey: function() {
            const labels = (this.settings && this.settings.category_labels) ? this.settings.category_labels : {};
            const keys = Object.keys(labels).filter(function(key) {
                return String(key) !== 'all';
            });
            if (this.settings && this.settings.default_category && String(this.settings.default_category) !== 'all') {
                return String(this.settings.default_category);
            }
            return keys[0] || 'all';
        },

        rebuildCategoryOptions: function() {
            const $category = $('#bv-category-filter');
            if (!$category.length) return;
            const labels = (this.settings && this.settings.category_labels) ? this.settings.category_labels : {};
            const keys = Object.keys(labels).filter(function(key) {
                return String(key) !== 'all';
            });
            const defaultKey = this.getDefaultCategoryKey();
            const current = $category.val() || defaultKey;
            if (!keys.length) return;
            let html = '';
            keys.forEach(function(key) {
                html += '<option value="' + String(key).replace(/"/g, '&quot;') + '">' + String(labels[key]) + '</option>';
            });
            if (current && current !== 'all' && keys.indexOf(current) === -1) {
                html += '<option value="' + String(current).replace(/"/g, '&quot;') + '">' + String(current) + '</option>';
            }
            $category.html(html);
            if ($category.find('option[value="' + String(current).replace(/"/g, '\\"') + '"]').length) {
                $category.val(current);
            } else if (keys.length) {
                $category.val(keys[0]);
            }
        },

        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            const self = this;

            $('.themisdb-benchmark-menu-toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $menu = $(this).closest('.themisdb-benchmark-menu');
                const isOpen = $menu.hasClass('is-open');
                $('.themisdb-benchmark-menu').removeClass('is-open');
                $('.themisdb-benchmark-menu-toggle').attr('aria-expanded', 'false');
                if (!isOpen) {
                    $menu.addClass('is-open');
                    $(this).attr('aria-expanded', 'true');
                }
            });

            $(document).on('click', function(event) {
                if (!$(event.target).closest('.themisdb-benchmark-menu').length) {
                    $('.themisdb-benchmark-menu').removeClass('is-open');
                    $('.themisdb-benchmark-menu-toggle').attr('aria-expanded', 'false');
                }
            });

            $('.themisdb-split-button__toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $group = $(this).closest('.themisdb-split-button');
                const willOpen = !$group.hasClass('is-open');
                $('.themisdb-split-button').removeClass('is-open');
                $('.themisdb-split-button__toggle').attr('aria-expanded', 'false');
                if (willOpen) {
                    $group.addClass('is-open');
                    $(this).attr('aria-expanded', 'true');
                    self.pauseAutoRotate();
                } else {
                    self.startAutoRotate();
                }
            });

            $(document).on('click', function(event) {
                if (!$(event.target).closest('.themisdb-split-button').length) {
                    $('.themisdb-split-button').removeClass('is-open');
                    $('.themisdb-split-button__toggle').attr('aria-expanded', 'false');
                    self.startAutoRotate();
                }
            });

            $('.themisdb-benchmark-panel').on('mouseenter focusin', function() {
                self.pauseAutoRotate();
            }).on('mouseleave focusout', function(event) {
                if (!$(event.currentTarget).find(':focus').length && !$(event.currentTarget).find('.themisdb-benchmark-menu.is-open').length) {
                    self.startAutoRotate();
                }
            });

            // Filter changes
            $('#bv-category-filter, #bv-metric-filter, #bv-chart-type').on('change', function() {
                self.loadData();
            });

            $('#bv-top-n, #bv-engine-filter').on('change', function() {
                self.applyClientFiltersAndRender();
            });

            $('#bv-task-search').on('input', function() {
                self.applyClientFiltersAndRender();
            });

            $('.themisdb-benchmark-arrow').on('click', function() {
                const dir = $(this).data('bv-dir') === 'prev' ? -1 : 1;
                self.changeSlide(dir);
            });

            // Refresh button
            $('#bv-refresh-data').on('click', function(e) {
                e.preventDefault();
                self.refreshData();
            });

            // Export buttons
            $('#bv-export-csv').on('click', function(e) {
                e.preventDefault();
                self.exportCSV();
            });

            $('#bv-export-pdf').on('click', function(e) {
                e.preventDefault();
                self.exportPDF();
            });

            $('#bv-print').on('click', function(e) {
                e.preventDefault();
                window.print();
            });
        },

        /**
         * Load benchmark data via AJAX
         */
        loadData: function() {
            const self = this;
            const category = $('#bv-category-filter').val() || this.getDefaultCategoryKey();
            const metric = $('#bv-metric-filter').val() || 'latency';

            // Show loading state
            this.showLoading();

            $.ajax({
                url: themisdbBV.ajax_url,
                type: 'POST',
                data: {
                    action: 'themisdb_bv_get_data',
                    nonce: themisdbBV.nonce,
                    category: category,
                    metric: metric
                },
                success: function(response) {
                    if (response.success) {
                        self.rawData = response.data;
                        self.currentSlideIndex = 0;
                        self.rebuildEngineFilterOptions();
                        self.applyClientFiltersAndRender();
                    } else {
                        self.showError('Failed to load benchmark data');
                    }
                    self.hideLoading();
                },
                error: function() {
                    self.showError('Error loading benchmark data');
                    self.hideLoading();
                }
            });
        },

        rebuildEngineFilterOptions: function() {
            const $engineFilter = $('#bv-engine-filter');
            if (!$engineFilter.length || !this.rawData || !Array.isArray(this.rawData.datasets)) {
                return;
            }
            const currentValue = $engineFilter.val() || 'all';
            let html = '<option value="all">Alle Engines</option>';
            this.rawData.datasets.forEach(function(dataset) {
                const label = String(dataset.label || '').trim();
                if (label) {
                    html += '<option value="' + label.replace(/"/g, '&quot;') + '">' + label + '</option>';
                }
            });
            $engineFilter.html(html);
            if ($engineFilter.find('option[value="' + currentValue.replace(/"/g, '\\"') + '"]').length) {
                $engineFilter.val(currentValue);
            }
        },

        applyClientFiltersAndRender: function() {
            if (!this.rawData) return;
            try {
                this.currentData = this.buildFilteredData(this.rawData);
                this.currentSlideIndex = 0;
                this.renderChart();
                this.renderSliderMeta();
                this.renderMermaidDiagram();
                this.renderResultsTable();
                this.generateInsights();
                this.startAutoRotate();
            } catch (error) {
                console.error('ThemisDB benchmark rendering failed:', error);
                this.hideLoading();
                this.showFallbackChartState('The benchmark chart could not be rendered. Please try a different filter combination.');
            }
        },

        buildFilteredData: function(sourceData) {
            const labels = Array.isArray(sourceData.labels) ? sourceData.labels.slice() : [];
            const datasets = Array.isArray(sourceData.datasets)
                ? sourceData.datasets.map(function(ds) {
                    return Object.assign({}, ds, { data: Array.isArray(ds.data) ? ds.data.slice() : [] });
                })
                : [];
            const summary = Object.assign({}, sourceData.summary || {});
            const search = String($('#bv-task-search').val() || '').trim().toLowerCase();
            const engineFilter = String($('#bv-engine-filter').val() || 'all').trim();
            const topNRaw = String($('#bv-top-n').val() || '').trim();
            const defaultTopN = Number(this.settings.default_top_n) || 10;
            const topN = topNRaw === 'all' ? -1 : Math.max(1, Number(topNRaw) || defaultTopN);

            let selectedDatasets = datasets;
            if (engineFilter !== 'all') {
                selectedDatasets = datasets.filter(function(ds) { return String(ds.label || '') === engineFilter; });
            }

            let indexSet = labels.map(function(_, idx) { return idx; });
            if (search) {
                indexSet = indexSet.filter(function(idx) {
                    return String(labels[idx] || '').toLowerCase().includes(search);
                });
            }

            const metric = $('#bv-metric-filter').val() || 'latency';
            const higherIsBetter = metric === 'throughput';
            if (topN > 0 && indexSet.length > topN) {
                const primaryDataset = selectedDatasets.find(function(ds) { return String(ds.label || '').toLowerCase().includes('themisdb'); }) || selectedDatasets[0];
                if (primaryDataset && Array.isArray(primaryDataset.data)) {
                    indexSet = indexSet
                        .map(function(idx) { return { idx: idx, val: Number(primaryDataset.data[idx] ?? Number.POSITIVE_INFINITY) }; })
                        .sort(function(a, b) { return higherIsBetter ? b.val - a.val : a.val - b.val; })
                        .slice(0, topN)
                        .map(function(entry) { return entry.idx; });
                } else {
                    indexSet = indexSet.slice(0, topN);
                }
            }

            const filteredLabels = indexSet.map(function(idx) { return labels[idx]; });
            const filteredDatasets = selectedDatasets.map(function(ds) {
                return Object.assign({}, ds, { data: indexSet.map(function(idx) { return ds.data[idx]; }) });
            });

            summary.total_benchmarks = labels.length;
            summary.displayed_benchmarks = filteredLabels.length;

            return {
                labels: filteredLabels,
                datasets: filteredDatasets,
                metric: sourceData.metric,
                category: sourceData.category,
                description: sourceData.description,
                summary: summary
            };
        },

        formatTaskLabel: function(value, maxLength) {
            const input = String(value || '').trim();
            const limit = Math.max(8, Number(maxLength) || 26);
            if (input.length <= limit) {
                return input;
            }
            return input.slice(0, limit - 1) + '…';
        },

        /**
         * Refresh data (clear cache)
         */
        refreshData: function() {
            // In production, this would make an AJAX call to clear the cache
            this.loadData();
        },

        getMetricUnit: function(metric) {
            const units = {
                latency: 'ms',
                throughput: 'ops/s',
                memory: 'MB'
            };
            return units[metric] || '';
        },

        clearAutoRotate: function() {
            if (this.autoRotateTimer) {
                clearInterval(this.autoRotateTimer);
                this.autoRotateTimer = null;
            }
        },

        pauseAutoRotate: function() {
            this.autoRotatePaused = true;
            this.clearAutoRotate();
        },

        startAutoRotate: function() {
            this.autoRotatePaused = false;
            this.clearAutoRotate();

            if (!this.currentData || !Array.isArray(this.currentData.datasets) || this.currentData.datasets.length < 2) {
                return;
            }

            const self = this;
            this.autoRotateTimer = setInterval(function() {
                if (!self.currentData || !Array.isArray(self.currentData.datasets) || self.autoRotatePaused) {
                    return;
                }

                self.currentSlideIndex = (self.currentSlideIndex + 1) % self.currentData.datasets.length;
                self.renderSliderMeta();
            }, 5000);
        },

        changeSlide: function(direction) {
            if (!this.currentData || !Array.isArray(this.currentData.datasets) || !this.currentData.datasets.length) {
                return;
            }

            const total = this.currentData.datasets.length;
            this.currentSlideIndex = (this.currentSlideIndex + direction + total) % total;
            this.renderSliderMeta();
            this.startAutoRotate();
        },

        renderSliderMeta: function() {
            if (!this.currentData || !Array.isArray(this.currentData.datasets) || !this.currentData.datasets.length) {
                return;
            }

            const datasetIndex = ((this.currentSlideIndex % this.currentData.datasets.length) + this.currentData.datasets.length) % this.currentData.datasets.length;
            const dataset = this.currentData.datasets[datasetIndex];
            const metric = $('#bv-metric-filter').val() || 'latency';
            const unit = this.getMetricUnit(metric);
            const values = Array.isArray(dataset.data) ? dataset.data : [];
            const avg = values.length ? values.reduce(function(sum, value) {
                const numeric = Number(value) || 0;
                return sum + numeric;
            }, 0) / values.length : 0;
            const bestValue = values.length
                ? (metric === 'throughput'
                    ? Math.max.apply(null, values.map(function(value) { return Number(value) || 0; }))
                    : Math.min.apply(null, values.map(function(value) { return Number(value) || 0; })))
                : 0;

            $('#bv-slide-title').text(dataset.label || 'Benchmark');
            $('#bv-slide-value').text(avg.toFixed(2));
            $('#bv-slide-unit').text(unit);
            $('#bv-slide-summary').text(
                metric === 'throughput'
                    ? 'Higher throughput indicates better sustained capacity under demand.'
                    : metric === 'memory'
                        ? 'Lower memory footprint reduces overhead and improves efficiency.'
                        : 'Lower is better for latency-sensitive reads and retrieval workloads.'
            );

            $('#bv-summary-avg').text(avg.toFixed(2) + ' ' + unit);
            $('#bv-summary-fastest').text(bestValue.toFixed(2) + ' ' + unit);
            $('#bv-summary-count').text(String(this.currentData.labels ? this.currentData.labels.length : values.length));

            const dots = this.currentData.datasets.map(function(item, index) {
                const activeClass = index === datasetIndex ? ' is-active' : '';
                return '<button class="themisdb-benchmark-dot' + activeClass + '" type="button" data-bv-index="' + index + '" aria-label="Show ' + (item.label || 'benchmark') + '"></button>';
            }).join('');
            $('#bv-slider-dots').html(dots);

            const self = this;
            $('#bv-slider-dots .themisdb-benchmark-dot').on('click', function() {
                const idx = Number($(this).data('bv-index'));
                if (!isNaN(idx)) {
                    self.currentSlideIndex = idx;
                    self.renderSliderMeta();
                    self.startAutoRotate();
                }
            });
        },

        /**
         * Render chart using Chart.js
         */
        updateFocusState: function(activeIndex) {
            const focus = document.getElementById('themisdb-benchmark-focus');
            if (!focus || !this.currentData || !Array.isArray(this.currentData.labels) || !Array.isArray(this.currentData.datasets)) {
                return;
            }

            const label = this.currentData.labels[activeIndex] || 'Vector Search';
            const datasetIndex = ((this.currentSlideIndex % this.currentData.datasets.length) + this.currentData.datasets.length) % this.currentData.datasets.length;
            const dataset = this.currentData.datasets[datasetIndex] || this.currentData.datasets[0] || {};
            const value = Array.isArray(dataset.data) && dataset.data[activeIndex] !== undefined ? dataset.data[activeIndex] : 0;
            const metric = $('#bv-metric-filter').val() || 'latency';
            const unit = this.getMetricUnit(metric);

            const titleEl = document.getElementById('themisdb-benchmark-focus__title');
            const valueEl = document.getElementById('bv-slide-value');
            const unitEl = document.getElementById('bv-slide-unit');
            if (titleEl) titleEl.textContent = label;
            if (valueEl) valueEl.textContent = Number(value).toFixed(2);
            if (unitEl) unitEl.textContent = unit;

            const panel = document.querySelector('.themisdb-benchmark-panel');
            if (panel) {
                panel.classList.add('chart-hovered');
            }
        },

        hideFocusState: function() {
            const focus = document.getElementById('themisdb-benchmark-focus');
            if (!focus) return;
            const panel = document.querySelector('.themisdb-benchmark-panel');
            if (panel) {
                panel.classList.remove('chart-hovered');
            }
        },

        renderChart: function() {
            if (!this.currentData) return;
            const ctx = document.getElementById('themisdb-benchmark-chart');
            if (!ctx) return;

            const chartType = $('#bv-chart-type').val() || 'bar';
            const metric = $('#bv-metric-filter').val() || 'latency';
            const normalizedChartType = chartType === 'area' ? 'line' : chartType;
            const labelCount = Array.isArray(this.currentData.labels) ? this.currentData.labels.length : 0;
            const isDense = labelCount > 14;
            const isHorizontalBar = normalizedChartType === 'bar' && isDense;

            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }

            const self = this;

            const baseStyle = {
                borderWidth: 0,
                borderRadius: 6,
                borderSkipped: false,
                borderAlign: 'center',
                pointRadius: chartType === 'line' ? 4 : 0,
                pointHoverRadius: chartType === 'line' ? 6 : 0,
                pointBackgroundColor: 'rgba(255, 255, 255, 0.95)',
                pointBorderWidth: 2,
                pointBorderColor: 'rgba(59, 130, 246, 0.9)',
                hoverBorderWidth: 2,
                hoverBorderColor: 'rgba(15, 23, 42, 0.9)',
                hoverBackgroundColor: 'rgba(147, 197, 253, 0.95)'
            };

            const axisLabelColor = '#475569';
            const gridColor = 'rgba(15, 23, 42, 0.08)';

            const config = {
                type: normalizedChartType,
                data: {
                    labels: this.currentData.labels,
                    datasets: this.currentData.datasets.map(function(dataset) {
                        const normalized = Object.assign({}, dataset, baseStyle);
                        normalized.backgroundColor = dataset.backgroundColor || 'rgba(59, 130, 246, 0.72)';
                        normalized.borderColor = dataset.borderColor || 'rgba(37, 99, 235, 0.95)';
                        return normalized;
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 900,
                        easing: 'easeOutQuart',
                        delay: function(context) {
                            return context.type === 'data' ? context.dataIndex * 25 + context.datasetIndex * 15 : 0;
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        intersect: false,
                    },
                    onHover: function(event, activeElements) {
                        if (!activeElements || !activeElements.length) {
                            self.hideFocusState();
                            return;
                        }
                        const activeIndex = activeElements[0].index;
                        if (typeof activeIndex === 'number') {
                            self.updateFocusState(activeIndex);
                        }
                    },
                    onClick: function(event, activeElements) {
                        if (!activeElements || !activeElements.length) {
                            self.hideFocusState();
                            return;
                        }
                        const activeIndex = activeElements[0].index;
                        if (typeof activeIndex === 'number') {
                            self.updateFocusState(activeIndex);
                        }
                    },
                    layout: {
                        padding: {
                            left: 12,
                            right: 12,
                            top: 12,
                            bottom: 8
                        }
                    },
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: this.currentData && Array.isArray(this.currentData.datasets) && this.currentData.datasets.length > 1,
                            position: 'bottom',
                            labels: {
                                color: '#334155',
                                boxWidth: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 14
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    const parsedValue = typeof context.parsed.y === 'number'
                                        ? context.parsed.y
                                        : (typeof context.parsed.x === 'number' ? context.parsed.x : (typeof context.parsed.r === 'number' ? context.parsed.r : Number(context.raw)));
                                    label += Number(parsedValue || 0).toFixed(2);
                                    label += ' ' + (metric === 'latency' ? 'ms' :
                                                   metric === 'throughput' ? 'ops/sec' : 'MB');
                                    return label;
                                }
                            }
                        }
                    },
                    scales: normalizedChartType === 'radar' ? {} : {
                        x: {
                            beginAtZero: false,
                            grid: {
                                display: !isHorizontalBar,
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: axisLabelColor,
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: isHorizontalBar ? 8 : 12,
                                callback: function(value, index) {
                                    if (isHorizontalBar) {
                                        return value;
                                    }
                                    const labels = self.currentData && Array.isArray(self.currentData.labels) ? self.currentData.labels : [];
                                    return self.formatTaskLabel(labels[index] || '', 18);
                                }
                            },
                            border: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Tasks',
                                color: axisLabelColor,
                                font: {
                                    size: 11,
                                    weight: 600
                                }
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: axisLabelColor,
                                maxTicksLimit: 6,
                                callback: function(value) {
                                    return value;
                                }
                            },
                            display: true,
                            border: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: metric === 'latency' ? 'Latency (ms)' : metric === 'throughput' ? 'Throughput (ops/s)' : 'Memory (MB)',
                                color: axisLabelColor,
                                font: {
                                    size: 11,
                                    weight: 600
                                }
                            }
                        }
                    },
                    elements: {
                        bar: {
                            borderRadius: 6,
                            borderSkipped: false,
                            borderWidth: 0,
                            hoverBorderWidth: 2,
                            hoverBorderColor: 'rgba(15, 23, 42, 0.9)'
                        },
                        line: {
                            borderWidth: 3,
                            tension: 0.3,
                            pointRadius: normalizedChartType === 'line' || normalizedChartType === 'radar' ? 4 : 0,
                            pointHoverRadius: 7
                        },
                        point: {
                            radius: 4,
                            hoverRadius: 7,
                            hitRadius: 12
                        }
                    }
                }
            };

            if (isHorizontalBar) {
                config.options.indexAxis = 'y';
                config.options.scales.x.title.text = 'Tasks';
                config.options.scales.y.title.text = metric === 'latency' ? 'Latency (ms)' : metric === 'throughput' ? 'Throughput (ops/s)' : 'Memory (MB)';
                config.options.scales.x.ticks.callback = function(value, index) {
                    return self.formatTaskLabel((self.currentData && Array.isArray(self.currentData.labels) ? self.currentData.labels[index] : '') || '', 18);
                };
                config.options.scales.y.ticks.callback = function(value) {
                    return value;
                };
            }

            if (chartType === 'area') {
                config.data.datasets = config.data.datasets.map(function(dataset) {
                    return Object.assign({}, dataset, {
                        fill: true,
                        backgroundColor: dataset.backgroundColor || 'rgba(103, 189, 255, 0.28)',
                    });
                });
            }
            if (isHorizontalBar) {
                config.options.indexAxis = 'y';
            }
            // Create chart
            this.chart = new Chart(ctx, config);
            this.hideFocusState();
        },

        /**
         * Build a compact Mermaid architecture overview from the loaded benchmark data.
         */
        renderMermaidDiagram: function() {
            if (!this.currentData || !this.currentData.datasets) {
                return;
            }

            const target = document.getElementById('themisdb-benchmark-mermaid');
            if (!target) {
                return;
            }

            if (!window.mermaid) {
                target.innerHTML = '<p class="themisdb-mermaid-error">Mermaid is not available yet.</p>';
                return;
            }

            const graph = this.buildMermaidGraph(this.currentData);
            if (!graph) {
                return;
            }

            target.innerHTML = '<pre class="mermaid">' + graph + '</pre>';
            try {
                const nodes = target.querySelectorAll('.mermaid');
                if (nodes.length) {
                    window.mermaid.initialize({ startOnLoad: false, theme: 'default' });
                    window.mermaid.run({ nodes: Array.from(nodes) });
                }
            } catch (error) {
                target.innerHTML = '<pre class="mermaid">' + graph + '</pre><p class="themisdb-mermaid-error">Mermaid rendering failed.</p>';
            }
        },

        buildMermaidGraph: function(data) {
            const datasets = Array.isArray(data.datasets) ? data.datasets : [];
            if (!datasets.length) {
                return '';
            }

            const metric = $('#bv-metric-filter').val() || 'latency';
            const unit = metric === 'latency' ? 'ms' : (metric === 'throughput' ? 'ops/s' : 'MB');
            const tops = datasets.slice(0, 4);

            const lines = [
                'flowchart LR',
                '    A["Benchmark overview"]',
            ];

            tops.forEach(function(dataset, index) {
                const average = Array.isArray(dataset.data) && dataset.data.length
                    ? dataset.data.reduce(function(sum, value) { return sum + Number(value || 0); }, 0) / dataset.data.length
                    : 0;
                const label = dataset.label || 'Engine';
                const nodeId = 'n' + index;
                lines.push('    ' + nodeId + '["' + label + ': ' + average.toFixed(2) + ' ' + unit + '"]');
                if (index === 0) {
                    lines.push('    A --> ' + nodeId);
                }
            });

            if (tops.length > 1) {
                for (let i = 1; i < tops.length; i++) {
                    lines.push('    n0 --> n' + i);
                }
            }

            return lines.join('\n');
        },

        /**
         * Get chart title based on metric
         */
        getChartTitle: function(metric) {
            const titles = {
                latency: 'Performance Comparison: Latency (Lower is Better)',
                throughput: 'Performance Comparison: Throughput (Higher is Better)',
                memory: 'Performance Comparison: Memory Usage'
            };
            return titles[metric] || 'Performance Comparison';
        },

        /**
         * Get Y-axis label based on metric
         */
        getYAxisLabel: function(metric) {
            const labels = {
                latency: 'Latency (ms)',
                throughput: 'Throughput (operations/second)',
                memory: 'Memory Usage (MB)'
            };
            return labels[metric] || 'Value';
        },

        escapeHtml: function(value) {
            return String(value === undefined || value === null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        /**
         * Render results table
         */
        renderResultsTable: function() {
            if (!this.currentData) return;

            const $tableContainer = $('#bv-results-table');
            const metric = $('#bv-metric-filter').val() || 'latency';

            // Render statistics summary first
            this.renderStatsSummary();

            let html = '<table>';
            html += '<thead><tr>';
            html += '<th>Testaufgabe</th>';

            // Add column for each dataset (database)
            this.currentData.datasets.forEach(function(dataset) {
                html += '<th>' + dataset.label + '</th>';
            });

            html += '</tr></thead><tbody>';

            // Add rows for each label (operation)
            this.currentData.labels.forEach(function(label, index) {
                const summary = this.currentData.summary || {};
                const sourcesByTask = summary.task_sources || {};
                const taskSources = Array.isArray(sourcesByTask[label]) ? sourcesByTask[label] : [];

                html += '<tr>';
                html += '<td><strong>' + this.escapeHtml(label) + '</strong>';
                if (taskSources.length) {
                    html += '<details class="themisdb-task-sources">';
                    html += '<summary>' + taskSources.length + ' Rohlauf' + (taskSources.length === 1 ? '' : 'e') + '</summary>';
                    html += '<ul>';
                    taskSources.slice(0, 10).forEach(function(source) {
                        html += '<li><code>' + this.escapeHtml(source) + '</code></li>';
                    }.bind(this));
                    if (taskSources.length > 10) {
                        html += '<li>… +' + (taskSources.length - 10) + ' weitere</li>';
                    }
                    html += '</ul></details>';
                }
                html += '</td>';

                // Add data for each dataset
                this.currentData.datasets.forEach(function(dataset) {
                    const value = dataset.data[index];
                    const unit = metric === 'latency' ? ' ms' : 
                                metric === 'throughput' ? ' ops/s' : ' MB';
                    if (value === null || value === undefined || Number.isNaN(Number(value))) {
                        html += '<td class="value-cell">—</td>';
                    } else {
                        html += '<td class="value-cell">' + Number(value).toFixed(2) + unit + '</td>';
                    }
                });

                html += '</tr>';
            }.bind(this));

            html += '</tbody></table>';
            $tableContainer.html(html);
        },

        /**
         * Render statistics summary
         */
        renderStatsSummary: function() {
            if (!this.currentData || !this.currentData.summary) return;

            const $summaryContainer = $('#bv-stats-summary');
            const summary = this.currentData.summary;
            const metric = $('#bv-metric-filter').val() || 'latency';
            
            let html = '<div class="themisdb-stats-grid">';
            
            // Total benchmarks
            html += '<div class="themisdb-stat-card">';
            html += '<div class="stat-icon">📊</div>';
            const totalTasks = summary.task_count || summary.total_benchmarks || 0;
            html += '<div class="stat-value">' + totalTasks + '</div>';
            html += '<div class="stat-label">Testaufgaben';
            if (summary.displayed_benchmarks && summary.displayed_benchmarks < summary.total_benchmarks) {
                html += ' (' + summary.displayed_benchmarks + ' sichtbar)';
            }
            html += '</div>';
            html += '</div>';

            if (summary.comparable_task_count !== undefined) {
                html += '<div class="themisdb-stat-card">';
                html += '<div class="stat-icon">⚖️</div>';
                html += '<div class="stat-value">' + Number(summary.comparable_task_count) + '</div>';
                html += '<div class="stat-label">Direkt vergleichbar</div>';
                html += '</div>';
            }
            
            // Files parsed
            if (summary.files_parsed) {
                html += '<div class="themisdb-stat-card">';
                html += '<div class="stat-icon">📁</div>';
                html += '<div class="stat-value">' + summary.files_parsed + '</div>';
                html += '<div class="stat-label">Benchmark Files</div>';
                html += '</div>';
            }
            
            // Average time
            if (summary.avg_time) {
                html += '<div class="themisdb-stat-card">';
                html += '<div class="stat-icon">⏱️</div>';
                html += '<div class="stat-value">' + summary.avg_time.toFixed(2) + '</div>';
                html += '<div class="stat-label">Average ' + (metric === 'latency' ? 'Latency (ms)' : 
                                                             metric === 'throughput' ? 'Throughput (ops/sec)' : 'Value') + '</div>';
                html += '</div>';
            }
            
            // Fastest / Best performance
            const bestValue = summary.best_value !== undefined ? summary.best_value : summary.fastest;
            const worstValue = summary.worst_value !== undefined ? summary.worst_value : summary.slowest;

            if (bestValue !== null && bestValue !== undefined) {
                html += '<div class="themisdb-stat-card success">';
                html += '<div class="stat-icon">🚀</div>';
                html += '<div class="stat-value">' + Number(bestValue).toFixed(2) + '</div>';
                html += '<div class="stat-label">';
                if (metric === 'throughput') {
                    html += 'Peak Throughput';
                } else {
                    html += 'Best Performance';
                }
                html += '</div>';
                html += '</div>';
            }
            
            // Slowest / Worst performance or Peak
            if (worstValue !== null && worstValue !== undefined) {
                html += '<div class="themisdb-stat-card warning">';
                html += '<div class="stat-icon">🐌</div>';
                html += '<div class="stat-value">' + Number(worstValue).toFixed(2) + '</div>';
                html += '<div class="stat-label">';
                if (metric === 'throughput') {
                    html += 'Lowest Throughput';
                } else {
                    html += 'Slowest Operation';
                }
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>';
            
            // Add note if data is limited
            const hasDisplayLimit = summary.displayed_benchmarks && summary.displayed_benchmarks < summary.total_benchmarks;
            const hasComparableExclusions = summary.excluded_non_comparable_tasks && Number(summary.excluded_non_comparable_tasks) > 0;
            const isFallbackMode = summary.comparison_mode === 'single_engine_fallback';
            if (hasDisplayLimit || hasComparableExclusions || isFallbackMode) {
                html += '<div class="themisdb-display-note">';
                if (hasDisplayLimit) {
                    html += '<p><small>📝 Zeige die ' + summary.displayed_benchmarks + ' besten Testaufgaben von insgesamt ' + 
                            summary.total_benchmarks + ' für eine bessere Visualisierung.</small></p>';
                }
                if (hasComparableExclusions) {
                    html += '<p><small>⚖️ ' + Number(summary.excluded_non_comparable_tasks) + ' Aufgaben ohne ausreichende Engine-Abdeckung wurden für faire Vergleichbarkeit ausgeblendet.</small></p>';
                }
                if (isFallbackMode) {
                    html += '<p><small>ℹ️ Keine Cross-Engine-Aufgaben gefunden – Darstellung nutzt Single-Engine-Fallback.</small></p>';
                }
                html += '</div>';
            }
            
            $summaryContainer.html(html);
        },

        /**
         * Generate performance insights
         */
        generateInsights: function() {
            if (!this.currentData) return;

            const $insightsContainer = $('#bv-insights');
            const metric = $('#bv-metric-filter').val() || 'latency';
            const category = $('#bv-category-filter').val() || 'all';
            const description = this.currentData.description;

            // Generate insights HTML
            let html = '';

            // Category-specific detailed description
            if (description) {
                html += '<div class="themisdb-insight-card info">';
                html += '<h4>📊 ' + description.title + '</h4>';
                html += '<p><strong>Getestete Operationen:</strong> ' + description.tests + '</p>';
                html += '<p><strong>Ergebnisse:</strong> ' + description.results + '</p>';
                if (description.stats_summary) {
                    html += '<p><strong>Statistik:</strong> ' + description.stats_summary + '</p>';
                }
                html += '</div>';
                
                // Valid conclusions
                if (description.conclusions && description.conclusions.valid) {
                    html += '<div class="themisdb-insight-card success">';
                    html += '<h4>✅ Gültige Schlussfolgerungen</h4>';
                    html += '<ul>';
                    description.conclusions.valid.forEach(function(conclusion) {
                        html += '<li>' + conclusion + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }
                
                // Invalid conclusions
                if (description.conclusions && description.conclusions.invalid) {
                    html += '<div class="themisdb-insight-card warning">';
                    html += '<h4>⚠️ Ungültige Schlussfolgerungen</h4>';
                    html += '<ul>';
                    description.conclusions.invalid.forEach(function(conclusion) {
                        html += '<li>' + conclusion + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                }
            } else {
                // Fallback to summary insight if no description available
                if (this.currentData.summary) {
                    const summary = this.currentData.summary;
                    
                    html += '<div class="themisdb-insight-card success">';
                    html += '<h4>📊 Benchmark Summary</h4>';
                    html += '<p><strong>' + summary.total_benchmarks + ' test tasks</strong> were executed in the ';
                    html += '<strong>' + this.getCategoryName(category) + '</strong> category. ';
                    
                    if (metric === 'latency') {
                        html += 'Average latency: <strong>' + summary.avg_time.toFixed(2) + ' ms</strong>. ';
                        html += 'Best performance: <strong>' + Number(summary.best_value ?? summary.fastest).toFixed(2) + ' ms</strong>.';
                    } else if (metric === 'throughput') {
                        html += 'Average throughput: <strong>' + summary.avg_time.toFixed(0) + ' ops/sec</strong>. ';
                        html += 'Peak throughput: <strong>' + Number(summary.best_value ?? summary.slowest).toFixed(0) + ' ops/sec</strong>.';
                    } else {
                        html += 'Average value: <strong>' + summary.avg_time.toFixed(2) + '</strong>.';
                    }
                    html += '</p></div>';
                }

                // Performance range insight
                const summaryStats = this.currentData.summary || {};
                const rangeBest = summaryStats.best_value !== undefined ? summaryStats.best_value : summaryStats.fastest;
                const rangeWorst = summaryStats.worst_value !== undefined ? summaryStats.worst_value : summaryStats.slowest;
                if (summaryStats.avg_time && rangeBest !== null && rangeBest !== undefined && rangeWorst !== null && rangeWorst !== undefined) {
                    const range = Math.abs(Number(rangeWorst) - Number(rangeBest));
                    const rangePercent = (range / Number(summaryStats.avg_time) * 100).toFixed(1);
                    
                    html += '<div class="themisdb-insight-card info">';
                    html += '<h4>📈 Performance Variance</h4>';
                    html += '<p>Performance varies by <strong>' + rangePercent + '%</strong> across different operations. ';
                    
                    if (metric === 'latency') {
                        if (parseFloat(rangePercent) < 50) {
                            html += 'ThemisDB shows <strong>consistent low-latency</strong> performance.';
                        } else {
                            html += 'Some operations are more complex and require more time.';
                        }
                    } else {
                        html += 'Different operations have different throughput characteristics.';
                    }
                    html += '</p></div>';
                }

                // Key takeaway
                html += '<div class="themisdb-insight-card warning">';
                html += '<h4>💡 Key Takeaway</h4>';
                html += '<p>These benchmarks show real-world performance on actual hardware. ';
                html += 'Results may vary based on your specific hardware configuration, workload patterns, and data size. ';
                html += 'Use these as a reference for understanding ThemisDB\'s performance characteristics.</p></div>';
            }

            $insightsContainer.html(html);
        },

        /**
         * Get category display name
         */
        getCategoryName: function(category) {
            const configured = (window.themisdbBV && themisdbBV.settings && themisdbBV.settings.category_labels)
                ? themisdbBV.settings.category_labels
                : {};
            if (configured && configured[category]) {
                return configured[category];
            }
            const names = {
                
                'vector_search': 'Vector Search & Embeddings',
                'graph_traversal': 'Graph Traversal & PageRank',
                'encryption': 'Encryption & HSM',
                'compression': 'Compression',
                'transaction': 'MVCC & Transactions',
                'image_analysis': 'Image Analysis',
                'advanced': 'Advanced Patterns & AQL',
                'gpu': 'GPU Backends',
                'content': 'Content Versioning & Indexing'
            };
            return names[category] || category;
        },

        /**
         * Get category-specific insights
         */
        getCategoryInsight: function(category, metric) {
            const insights = {
                'vector_search': '<div class="themisdb-insight-card success"><h4>🎯 Vector Search Performance</h4>' +
                    '<p>ThemisDB provides <strong>native vector search</strong> capabilities with competitive performance for ' +
                    'similarity search, embeddings, and nearest-neighbor queries including GNN embeddings.</p></div>',
                    
                'graph_traversal': '<div class="themisdb-insight-card success"><h4>🕸️ Graph Processing</h4>' +
                    '<p>ThemisDB\'s graph traversal algorithms are optimized for <strong>complex relationship queries</strong>, ' +
                    'BFS/DFS operations, and graph analytics like PageRank with various graph sizes.</p></div>',
                    
                'encryption': '<div class="themisdb-insight-card success"><h4>🔒 Security Performance</h4>' +
                    '<p>Encryption operations show that ThemisDB maintains <strong>strong security</strong> with HSM provider support while ' +
                    'minimizing performance overhead through optimized cryptographic implementations.</p></div>',
                    
                'compression': '<div class="themisdb-insight-card success"><h4>📦 Compression Efficiency</h4>' +
                    '<p>ThemisDB\'s compression algorithms balance <strong>storage savings</strong> with fast ' +
                    'compression and decompression speeds for optimal data management.</p></div>',
                    
                'transaction': '<div class="themisdb-insight-card success"><h4>💼 ACID Transactions</h4>' +
                    '<p>MVCC and transaction benchmarks demonstrate ThemisDB\'s <strong>reliable ACID guarantees</strong> ' +
                    'with efficient concurrency control and minimal lock contention.</p></div>',
                    
                'image_analysis': '<div class="themisdb-insight-card success"><h4>🖼️ Image Processing</h4>' +
                    '<p>ThemisDB\'s image analysis features show strong performance for <strong>AI-powered ' +
                    'image operations</strong>, enabling multimedia database applications with low latency processing.</p></div>',
                    
                'advanced': '<div class="themisdb-insight-card success"><h4>🚀 Advanced Features</h4>' +
                    '<p>Advanced patterns, hybrid AQL queries, changefeed throughput, and micro-optimization benchmarks showcase ' +
                    'ThemisDB\'s <strong>multi-model capabilities</strong>, combining different data paradigms efficiently.</p></div>',
                    
                'gpu': '<div class="themisdb-insight-card success"><h4>⚡ GPU Acceleration</h4>' +
                    '<p>GPU backend benchmarks demonstrate ThemisDB\'s ability to leverage <strong>hardware acceleration</strong> ' +
                    'for compute-intensive operations, improving performance for complex queries.</p></div>',
                    
                'content': '<div class="themisdb-insight-card success"><h4>📝 Content Management</h4>' +
                    '<p>Content versioning and index rebuild benchmarks show ThemisDB\'s <strong>robust data management</strong> ' +
                    'capabilities for applications requiring version control and efficient index maintenance.</p></div>'
            };
            
            return insights[category] || null;
        },

        /**
         * Export data as CSV
         */
        exportCSV: function() {
            if (!this.currentData) return;

            let csv = 'Operation,';
            csv += this.currentData.datasets.map(d => d.label).join(',') + '\n';

            this.currentData.labels.forEach(function(label, index) {
                csv += label + ',';
                csv += this.currentData.datasets.map(d => d.data[index]).join(',') + '\n';
            }.bind(this));

            // Create download
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'themisdb-benchmarks-' + Date.now() + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        },

        /**
         * Export as PDF (basic implementation)
         */
        exportPDF: function() {
            // For a full implementation, you would use a library like jsPDF
            // For now, we'll just trigger print which can save as PDF
            window.print();
        },

        /**
         * Show a fallback state when rendering fails.
         */
        showFallbackChartState: function(message) {
            const $loading = $('#bv-loading');
            if ($loading.length) {
                $loading.html('<div class="themisdb-insight-card warning"><h4>⚠️ Notice</h4><p>' + message + '</p></div>').show();
            }
            $('#themisdb-benchmark-chart').css('opacity', '0.35');
            this.clearAutoRotate();
        },

        /**
         * Show loading state
         */
        showLoading: function() {
            $('#bv-loading').show();
            $('#themisdb-benchmark-chart').css('opacity', '0.3');
        },

        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('#bv-loading').hide();
            $('#themisdb-benchmark-chart').css('opacity', '1');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const $insightsContainer = $('#bv-insights');
            const html = '<div class="themisdb-insight-card warning">' +
                        '<h4>⚠️ Error</h4>' +
                        '<p>' + message + '</p>' +
                        '</div>';
            $insightsContainer.html(html);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.themisdb-benchmark-wrapper').length > 0) {
            window.ThemisDBBenchmarks.init();
        }
    });

})(jQuery);
