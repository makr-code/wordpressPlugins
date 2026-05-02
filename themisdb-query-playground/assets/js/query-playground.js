/**
 * ThemisDB Query Playground JavaScript
 */

(function($) {
    'use strict';

    window.ThemisDBQueryPlayground = {
        editor: null,
        currentView: 'table',
        lastResults: null,

        init: function() {
            this.initEditor();
            this.setupEventListeners();
            this.loadExamples();
        },

        initEditor: function() {
            const textarea = document.getElementById('qp-editor');
            if (!textarea) return;

            this.editor = CodeMirror.fromTextArea(textarea, {
                mode: 'text/x-sql',
                theme: themisdbQP.settings.theme || 'monokai',
                lineNumbers: true,
                lineWrapping: true,
                autofocus: true,
                extraKeys: {
                    'Ctrl-Enter': () => this.executeQuery(),
                    'Cmd-Enter': () => this.executeQuery()
                }
            });

            this.editor.on('cursorActivity', () => this.updateEditorInfo());
            this.editor.on('change', () => this.updateEditorInfo());
        },

        setupEventListeners: function() {
            const self = this;

            $('#qp-execute').on('click', () => self.executeQuery());
            $('#qp-clear').on('click', () => self.clearEditor());
            $('#qp-format').on('click', () => self.formatQuery());

            $('.qp-load-example').on('click', function() {
                const category = $(this).data('category');
                self.showExamplesForCategory(category);
                $('.qp-load-example').removeClass('active');
                $(this).addClass('active');
            });

            $('.qp-view-btn').on('click', function() {
                const view = $(this).data('view');
                self.switchView(view);
            });

            $('#qp-export-json').on('click', () => self.exportJSON());
            $('#qp-export-csv').on('click', () => self.exportCSV());
        },

        loadExamples: function() {
            $.ajax({
                url: themisdbQP.ajax_url,
                type: 'POST',
                data: {
                    action: 'themisdb_qp_get_examples',
                    nonce: themisdbQP.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.examples = response.data;
                    }
                }
            });
        },

        showExamplesForCategory: function(category) {
            if (!this.examples) return;

            const filtered = this.examples.filter(ex => ex.category === category);
            const $list = $('#qp-example-list');
            
            if (filtered.length === 0) {
                $list.hide();
                return;
            }

            let html = '';
            filtered.forEach(ex => {
                html += `<div class="themisdb-example-item" data-query="${this.escapeHtml(ex.query)}">
                    <h4>${ex.name}</h4>
                    <p>${ex.description}</p>
                </div>`;
            });

            $list.html(html).show();

            $list.find('.themisdb-example-item').on('click', function() {
                const query = $(this).data('query');
                this.setEditorValue(query);
                $list.hide();
            }.bind(this));
        },

        executeQuery: function() {
            if (!themisdbQP.settings.enable_execution) {
                this.showStatus('Query execution is disabled', 'error');
                return;
            }

            const query = this.editor.getValue().trim();
            if (!query) {
                this.showStatus('Please enter a query', 'error');
                return;
            }

            this.showStatus('Executing query...', 'info');
            $('#qp-execute').prop('disabled', true);

            $.ajax({
                url: themisdbQP.ajax_url,
                type: 'POST',
                data: {
                    action: 'themisdb_qp_execute_query',
                    nonce: themisdbQP.nonce,
                    query: query
                },
                success: (response) => {
                    if (response.success) {
                        this.displayResults(response.data);
                        this.showStatus('Query executed successfully', 'success');
                    } else {
                        this.showStatus('Error: ' + response.data.message, 'error');
                    }
                },
                error: () => {
                    this.showStatus('Network error occurred', 'error');
                },
                complete: () => {
                    $('#qp-execute').prop('disabled', false);
                }
            });
        },

        displayResults: function(data) {
            this.lastResults = data;
            
            $('#qp-result-count').text(data.count + ' result' + (data.count !== 1 ? 's' : ''));
            $('#qp-execution-time').text(data.execution_time + ' ms');
            
            $('#qp-results-section').show();
            
            this.switchView(this.currentView);
        },

        switchView: function(view) {
            this.currentView = view;
            $('.qp-view-btn').removeClass('active');
            $(`.qp-view-btn[data-view="${view}"]`).addClass('active');

            if (!this.lastResults) return;

            const $container = $('#qp-results-container');

            switch(view) {
                case 'table':
                    $container.html(this.renderTable(this.lastResults.items));
                    break;
                case 'json':
                    $container.html(this.renderJSON(this.lastResults.items));
                    break;
                case 'chart':
                    $container.html(this.renderChart(this.lastResults.items));
                    break;
            }
        },

        renderTable: function(items) {
            if (!items || items.length === 0) {
                return '<p>No results</p>';
            }

            const keys = Object.keys(items[0]);
            let html = '<table class="themisdb-results-table"><thead><tr>';
            
            keys.forEach(key => {
                html += `<th>${this.escapeHtml(key)}</th>`;
            });
            html += '</tr></thead><tbody>';

            items.forEach(item => {
                html += '<tr>';
                keys.forEach(key => {
                    const value = item[key];
                    html += `<td>${this.formatValue(value)}</td>`;
                });
                html += '</tr>';
            });

            html += '</tbody></table>';
            return html;
        },

        renderJSON: function(items) {
            const json = JSON.stringify(items, null, 2);
            return `<div class="themisdb-results-json"><pre>${this.escapeHtml(json)}</pre></div>`;
        },

        renderChart: function(items) {
            if (!items || items.length === 0) {
                return '<p class="qp-chart-empty">Keine Ergebnisse zum Visualisieren.</p>';
            }

            const keys = Object.keys(items[0]);

            // Find first string key (label) and first numeric key (value)
            const labelKey = keys.find(k => typeof items[0][k] === 'string') || keys[0];
            const valueKey = keys.find(k => k !== labelKey && (typeof items[0][k] === 'number' || !isNaN(parseFloat(items[0][k]))));

            if (!valueKey) {
                return '<p class="qp-chart-empty">Keine numerischen Felder für ein Diagramm gefunden. Wechsle zur Tabellenansicht.</p>';
            }

            const MAX_BARS = 40;
            const data = items.slice(0, MAX_BARS).map(item => ({
                label: String(item[labelKey] ?? ''),
                value: parseFloat(item[valueKey]) || 0
            }));

            const maxVal = Math.max(...data.map(d => d.value), 1);
            const BAR_H = 28;
            const GAP = 6;
            const LABEL_W = 160;
            const BAR_AREA = 420;
            const CHART_H = data.length * (BAR_H + GAP);

            let bars = '';
            data.forEach((d, i) => {
                const y = i * (BAR_H + GAP);
                const barW = Math.max(2, (d.value / maxVal) * BAR_AREA);
                const labelText = d.label.length > 22 ? d.label.slice(0, 21) + '…' : d.label;
                const valText = Number.isInteger(d.value) ? d.value : d.value.toFixed(2);
                bars += `
                    <g transform="translate(0,${y})">
                        <text x="${LABEL_W - 8}" y="${BAR_H / 2 + 5}" text-anchor="end"
                              font-size="12" fill="var(--qp-chart-label,#555)" font-family="sans-serif"
                              title="${this.escapeHtml(d.label)}">${this.escapeHtml(labelText)}</text>
                        <rect x="${LABEL_W}" y="0" width="${barW}" height="${BAR_H}"
                              rx="3" fill="var(--qp-chart-bar,#4f8ef7)" opacity="0.85" />
                        <text x="${LABEL_W + barW + 6}" y="${BAR_H / 2 + 5}"
                              font-size="11" fill="var(--qp-chart-value,#333)" font-family="sans-serif">${valText}</text>
                    </g>`;
            });

            const truncNote = items.length > MAX_BARS
                ? `<p class="qp-chart-note">Zeige ${MAX_BARS} von ${items.length} Zeilen.</p>`
                : '';

            return `
                <div class="themisdb-results-chart">
                    <p class="qp-chart-meta">Feld: <strong>${this.escapeHtml(labelKey)}</strong>
                        &nbsp;→&nbsp; <strong>${this.escapeHtml(valueKey)}</strong></p>
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="${LABEL_W + BAR_AREA + 80}"
                         height="${CHART_H}"
                         role="img" aria-label="Balkendiagramm der Abfrageergebnisse">
                        ${bars}
                    </svg>
                    ${truncNote}
                </div>`;
        },

        formatValue: function(value) {
            if (value === null) return '<em>null</em>';
            if (typeof value === 'object') return this.escapeHtml(JSON.stringify(value));
            return this.escapeHtml(String(value));
        },

        clearEditor: function() {
            this.editor.setValue('');
            this.editor.focus();
        },

        formatQuery: function() {
            const query = this.editor.getValue();
            // Basic formatting
            const formatted = query
                .replace(/\s+/g, ' ')
                .replace(/\s*([\(\),])\s*/g, '$1 ')
                .trim();
            this.editor.setValue(formatted);
        },

        setEditorValue: function(value) {
            this.editor.setValue(value);
            this.editor.focus();
        },

        updateEditorInfo: function() {
            const cursor = this.editor.getCursor();
            $('#qp-line-info').text(`Line ${cursor.line + 1}, Col ${cursor.ch + 1}`);
            $('#qp-char-count').text(this.editor.getValue().length + ' characters');
        },

        showStatus: function(message, type) {
            const $status = $('#qp-status');
            const $content = $('#qp-status-content');
            
            $status.removeClass('success error info').addClass(type);
            $content.html(this.escapeHtml(message));
            $status.show();

            if (type === 'success') {
                setTimeout(() => $status.fadeOut(), 3000);
            }
        },

        exportJSON: function() {
            if (!this.lastResults) return;

            const json = JSON.stringify(this.lastResults.items, null, 2);
            const blob = new Blob([json], { type: 'application/json' });
            this.downloadBlob(blob, 'query-results.json');
        },

        exportCSV: function() {
            if (!this.lastResults || !this.lastResults.items.length) return;

            const items = this.lastResults.items;
            const keys = Object.keys(items[0]);
            
            let csv = keys.join(',') + '\n';
            items.forEach(item => {
                const row = keys.map(key => {
                    const value = item[key];
                    return '"' + String(value).replace(/"/g, '""') + '"';
                });
                csv += row.join(',') + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            this.downloadBlob(blob, 'query-results.csv');
        },

        downloadBlob: function(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        if ($('.themisdb-query-wrapper').length > 0) {
            window.ThemisDBQueryPlayground.init();
        }
    });

})(jQuery);
