/**
 * ThemisDB Benchmark Visualizer JavaScript
 * Based on TCO Calculator pattern with Chart.js integration
 */

(function($) {
    'use strict';

    // Global namespace
    window.ThemisDBBenchmarks = {
        chart: null,
        currentData: null,
        settings: {},
        currentSlideIndex: 0,
        autoRotateTimer: null,

        /**
         * Initialize the visualizer
         */
        init: function() {
            // Store settings from PHP
            this.settings = themisdbBV.settings || {};
            this.currentSlideIndex = 0;

            // Set up event listeners
            this.setupEventListeners();

            // Load initial data
            this.loadData();
        },

        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            const self = this;

            // Filter changes
            $('#bv-metric-filter, #bv-chart-type').on('change', function() {
                self.loadData();
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
            const category = $('#bv-category-filter').val() || 'all';
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
                        self.currentData = response.data;
                        self.currentSlideIndex = 0;
                        self.renderChart();
                        self.renderSliderMeta();
                        self.renderMermaidDiagram();
                        self.renderResultsTable();
                        self.generateInsights();
                        self.startAutoRotate();
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

        startAutoRotate: function() {
            this.clearAutoRotate();

            if (!this.currentData || !Array.isArray(this.currentData.datasets) || this.currentData.datasets.length < 2) {
                return;
            }

            const self = this;
            this.autoRotateTimer = setInterval(function() {
                if (!self.currentData || !Array.isArray(self.currentData.datasets)) {
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
            const fastest = values.length ? Math.min.apply(null, values.map(function(value) { return Number(value) || 0; })) : 0;
            const slowest = values.length ? Math.max.apply(null, values.map(function(value) { return Number(value) || 0; })) : 0;

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
            $('#bv-summary-fastest').text(fastest.toFixed(2) + ' ' + unit);
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
            const dataset = this.currentData.datasets[0] || {};
            const value = Array.isArray(dataset.data) && dataset.data[activeIndex] !== undefined ? dataset.data[activeIndex] : 0;
            const metric = $('#bv-metric-filter').val() || 'latency';
            const unit = this.getMetricUnit(metric);

            const titleEl = document.getElementById('themisdb-benchmark-focus__title');
            const valueEl = document.getElementById('themisdb-benchmark-focus__value');
            if (titleEl) titleEl.textContent = label;
            if (valueEl) valueEl.textContent = Number(value).toFixed(2) + ' ' + unit;
        },

        renderChart: function() {
            if (!this.currentData) return;
            const ctx = document.getElementById('themisdb-benchmark-chart');
            if (!ctx) return;

            const chartType = $('#bv-chart-type').val() || 'bar';
            const metric = $('#bv-metric-filter').val() || 'latency';

            // Destroy existing chart
            if (this.chart) {
                this.chart.destroy();
            }

            const self = this;

            const baseStyle = {
                borderWidth: 0,
                borderRadius: 0,
                borderSkipped: false,
                borderAlign: 'center',
                pointRadius: chartType === 'line' ? 4 : 0,
                pointHoverRadius: chartType === 'line' ? 6 : 0,
                pointBackgroundColor: 'rgba(255, 255, 255, 0.95)',
                pointBorderWidth: 2,
                pointBorderColor: 'rgba(86, 160, 255, 0.9)',
                hoverBorderWidth: 2,
                hoverBorderColor: 'rgba(255,255,255,0.9)',
                hoverBackgroundColor: 'rgba(159, 214, 255, 0.95)'
            };

            // Chart configuration
            const config = {
                type: chartType,
                data: {
                    labels: this.currentData.labels,
                    datasets: this.currentData.datasets.map(function(dataset) {
                        const normalized = Object.assign({}, dataset, baseStyle);
                        normalized.backgroundColor = dataset.backgroundColor || 'rgba(103, 189, 255, 0.78)';
                        normalized.borderColor = dataset.borderColor || 'rgba(116, 204, 255, 0.95)';
                        return normalized;
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1400,
                        easing: 'easeOutCubic',
                        delay: function(context) {
                            return context.type === 'data' ? context.dataIndex * 80 + context.datasetIndex * 30 : 0;
                        },
                        onComplete: function(animation) {
                            const chart = animation.chart;
                            chart.ctx.save();
                            chart.ctx.shadowColor = 'rgba(52, 152, 219, 0.18)';
                            chart.ctx.shadowBlur = 12;
                            chart.ctx.restore();
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        intersect: false,
                    },
                    onHover: function(event, activeElements) {
                        if (!activeElements || !activeElements.length) {
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
                            bottom: 12
                        }
                    },
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: false,
                            position: 'top',
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
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
                                    label += context.parsed.y.toFixed(2);
                                    label += ' ' + (metric === 'latency' ? 'ms' :
                                                   metric === 'throughput' ? 'ops/sec' : 'MB');
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255,255,255,0.12)',
                                drawBorder: false
                            },
                            ticks: {
                                color: 'rgba(255,255,255,0.8)',
                                maxTicksLimit: 6
                            },
                            display: true,
                            border: {
                                display: false
                            },
                            title: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: 'rgba(255,255,255,0.8)',
                                maxRotation: 0,
                                autoSkip: false
                            },
                            border: {
                                display: false
                            },
                            title: {
                                display: false
                            }
                        }
                    },
                    elements: {
                        bar: {
                            borderRadius: 0,
                            borderSkipped: false,
                            borderWidth: 0,
                            hoverBorderWidth: 2,
                            hoverBorderColor: 'rgba(255,255,255,0.8)'
                        },
                        line: {
                            borderWidth: 3,
                            tension: 0.3,
                            pointRadius: 4,
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

            // Create chart
            this.chart = new Chart(ctx, config);
            this.updateFocusState(0);
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
            html += '<th>Operation</th>';

            // Add column for each dataset (database)
            this.currentData.datasets.forEach(function(dataset) {
                html += '<th>' + dataset.label + '</th>';
            });

            html += '</tr></thead><tbody>';

            // Add rows for each label (operation)
            this.currentData.labels.forEach(function(label, index) {
                html += '<tr>';
                html += '<td><strong>' + label + '</strong></td>';

                // Add data for each dataset
                this.currentData.datasets.forEach(function(dataset) {
                    const value = dataset.data[index];
                    const unit = metric === 'latency' ? ' ms' : 
                                metric === 'throughput' ? ' ops/s' : ' MB';
                    html += '<td class="value-cell">' + value.toFixed(2) + unit + '</td>';
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
            html += '<div class="stat-value">' + summary.total_benchmarks + '</div>';
            html += '<div class="stat-label">Total Benchmarks';
            if (summary.displayed_benchmarks && summary.displayed_benchmarks < summary.total_benchmarks) {
                html += ' (' + summary.displayed_benchmarks + ' shown)';
            }
            html += '</div>';
            html += '</div>';
            
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
            if (summary.fastest !== null) {
                html += '<div class="themisdb-stat-card success">';
                html += '<div class="stat-icon">🚀</div>';
                html += '<div class="stat-value">' + summary.fastest.toFixed(2) + '</div>';
                html += '<div class="stat-label">';
                if (metric === 'throughput') {
                    html += 'Lowest Throughput';
                } else {
                    html += 'Best Performance';
                }
                html += '</div>';
                html += '</div>';
            }
            
            // Slowest / Worst performance or Peak
            if (summary.slowest !== null) {
                html += '<div class="themisdb-stat-card warning">';
                html += '<div class="stat-icon">🐌</div>';
                html += '<div class="stat-value">' + summary.slowest.toFixed(2) + '</div>';
                html += '<div class="stat-label">';
                if (metric === 'throughput') {
                    html += 'Peak Throughput';
                } else {
                    html += 'Slowest Operation';
                }
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>';
            
            // Add note if data is limited
            if (summary.displayed_benchmarks && summary.displayed_benchmarks < summary.total_benchmarks) {
                html += '<div class="themisdb-display-note">';
                html += '<p><small>📝 Showing top ' + summary.displayed_benchmarks + ' best-performing benchmarks out of ' + 
                        summary.total_benchmarks + ' total for better visualization.</small></p>';
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
                    html += '<p><strong>' + summary.total_benchmarks + ' benchmarks</strong> were executed in the ';
                    html += '<strong>' + this.getCategoryName(category) + '</strong> category. ';
                    
                    if (metric === 'latency') {
                        html += 'Average latency: <strong>' + summary.avg_time.toFixed(2) + ' ms</strong>. ';
                        html += 'Best performance: <strong>' + summary.fastest.toFixed(2) + ' ms</strong>.';
                    } else if (metric === 'throughput') {
                        html += 'Average throughput: <strong>' + summary.avg_time.toFixed(0) + ' ops/sec</strong>. ';
                        html += 'Peak throughput: <strong>' + summary.slowest.toFixed(0) + ' ops/sec</strong>.';
                    } else {
                        html += 'Average value: <strong>' + summary.avg_time.toFixed(2) + '</strong>.';
                    }
                    html += '</p></div>';
                }

                // Performance range insight
                if (this.currentData.summary && this.currentData.summary.fastest && this.currentData.summary.slowest) {
                    const range = this.currentData.summary.slowest - this.currentData.summary.fastest;
                    const rangePercent = (range / this.currentData.summary.avg_time * 100).toFixed(1);
                    
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
            const names = {
                'all': 'All Operations',
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
