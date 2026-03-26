/**
 * ThemisDB Release Timeline Visualizer - JavaScript
 * Version: 1.0.0
 * License: MIT
 */

(function($) {
    'use strict';
    
    // Timeline Visualizer Class
    class ReleaseTimelineVisualizer {
        constructor(container) {
            this.container = container;
            this.currentView = container.dataset.view || 'chronological';
            this.currentTheme = container.dataset.theme || 'neutral';
            this.source = container.dataset.source || 'github';
            this.renderMode = container.dataset.renderMode || 'auto';
            this.releases = [];
            this.zoomLevel = 1.0;
            this.instanceId = `themisdb-rt-${Math.random().toString(36).slice(2, 10)}`;
            this.renderToken = 0;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadData();
        }
        
        bindEvents() {
            const self = this;
            
            // View change
            this.container.querySelectorAll('.themisdb-rt-view-selector').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.currentView = this.dataset.view;
                    self.render();
                });
            });
            
            // Theme change
            const themeSelect = this.container.querySelector('.themisdb-rt-theme-select');
            if (themeSelect) {
                themeSelect.addEventListener('change', function() {
                    self.currentTheme = this.value;
                    self.render();
                });
            }
            
            // Reload button
            const reloadBtn = this.container.querySelector('.themisdb-rt-reload');
            if (reloadBtn) {
                reloadBtn.addEventListener('click', () => this.loadData(true));
            }
            
            // Export buttons
            const exportSvgBtn = this.container.querySelector('.themisdb-rt-export-svg');
            if (exportSvgBtn) {
                exportSvgBtn.addEventListener('click', () => this.exportSVG());
            }
            
            const exportPngBtn = this.container.querySelector('.themisdb-rt-export-png');
            if (exportPngBtn) {
                exportPngBtn.addEventListener('click', () => this.exportPNG());
            }
            
            // Zoom controls
            const zoomInBtn = this.container.querySelector('.themisdb-rt-zoom-in');
            if (zoomInBtn) {
                zoomInBtn.addEventListener('click', () => this.zoom(0.1));
            }
            
            const zoomOutBtn = this.container.querySelector('.themisdb-rt-zoom-out');
            if (zoomOutBtn) {
                zoomOutBtn.addEventListener('click', () => this.zoom(-0.1));
            }
            
            const zoomResetBtn = this.container.querySelector('.themisdb-rt-zoom-reset');
            if (zoomResetBtn) {
                zoomResetBtn.addEventListener('click', () => this.resetZoom());
            }
            
            // Fullscreen
            const fullscreenBtn = this.container.querySelector('.themisdb-rt-fullscreen');
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
            }
        }
        
        loadData(forceReload = false) {
            this.showLoading();
            
            $.ajax({
                url: themisdbRTData.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'themisdb_rt_load_data',
                    nonce: themisdbRTData.nonce,
                    source: this.source,
                    view: this.currentView,
                    releases: this.container.dataset.releases || 10,
                    force_reload: forceReload
                },
                success: (response) => {
                    if (response.success) {
                        this.releases = response.data;
                        this.render();
                    } else {
                        this.showError('Failed to load release data');
                    }
                },
                error: () => {
                    this.showError('Network error occurred');
                }
            });
        }
        
        render() {
            const content = this.container.querySelector('.themisdb-rt-content');
            if (!content) return;

            if (this.renderMode === 'list') {
                this.renderFallbackList(content);
                return;
            }
            
            let diagram = '';
            
            switch (this.currentView) {
                case 'chronological':
                    diagram = this.generateChronological();
                    break;
                case 'gantt':
                    diagram = this.generateGantt();
                    break;
                case 'mindmap':
                    diagram = this.generateMindmap();
                    break;
                default:
                    diagram = this.generateChronological();
            }
            
            if (this.renderMode === 'mermaid') {
                this.renderMermaid(content, diagram, false);
                return;
            }

            this.renderMermaid(content, diagram, true);
        }
        
        generateChronological() {
            let diagram = 'timeline\n';
            diagram += '    title ThemisDB Release Timeline\n';
            
            this.releases.forEach(release => {
                const breaking = release.breaking ? ' [breaking]' : '';
                const safeVersion = this.sanitizeMermaidText(release.version, 80);
                diagram += `    ${release.date} : ${safeVersion}${breaking}\n`;
                
                if (release.features && release.features.length > 0) {
                    const safeFeature = this.sanitizeMermaidText(release.features[0], 120);
                    diagram += `                : ${safeFeature}\n`;
                }
            });
            
            return diagram;
        }
        
        generateGantt() {
            let diagram = 'gantt\n';
            diagram += '    title ThemisDB Release Schedule\n';
            diagram += '    dateFormat YYYY-MM-DD\n';
            diagram += '    section Releases\n';
            
            this.releases.forEach((release, index) => {
                const nextDate = this.releases[index + 1] ? this.releases[index + 1].date : release.date;
                const status = release.breaking ? 'crit' : 'done';
                const safeVersion = this.sanitizeMermaidText(release.version, 60);
                diagram += `    ${safeVersion} : ${status}, ${release.date}, ${nextDate}\n`;
            });
            
            return diagram;
        }
        
        generateMindmap() {
            let diagram = 'mindmap\n';
            diagram += '  root((ThemisDB Releases))\n';
            
            this.releases.slice(0, 5).forEach(release => {
                const safeVersion = this.sanitizeMermaidText(release.version, 50);
                diagram += `    ${safeVersion}\n`;
                
                if (release.features && release.features.length > 0) {
                    release.features.slice(0, 3).forEach(feature => {
                        const cleanFeature = this.sanitizeMermaidText(feature, 30);
                        diagram += `      ${cleanFeature}\n`;
                    });
                }
            });
            
            return diagram;
        }

        sanitizeMermaidText(input, maxLength = 80) {
            const value = String(input || '');
            return value
                .replace(/[\r\n\t]+/g, ' ')
                .replace(/[^\w\s.,\-()/+#]/g, ' ')
                .replace(/\s{2,}/g, ' ')
                .trim()
                .substring(0, maxLength);
        }

        renderFallbackList(container) {
            const items = (this.releases || []).map(release => {
                const title = this.escapeHtml(this.sanitizeMermaidText(release.version || release.name || 'Release', 120));
                const date = this.escapeHtml(String(release.date || ''));
                const feature = Array.isArray(release.features) && release.features.length > 0
                    ? this.escapeHtml(this.sanitizeMermaidText(release.features[0], 180))
                    : '';
                const breaking = release.breaking ? ' <strong>(breaking)</strong>' : '';
                return `<li><strong>${date}</strong> - ${title}${breaking}${feature ? `<br><span>${feature}</span>` : ''}</li>`;
            }).join('');

            container.innerHTML = `
                <div class="themisdb-rt-fallback">
                    <h3 style="margin:0 0 0.75rem;">Release Uebersicht</h3>
                    <ul style="margin:0; padding-left:1.25rem; line-height:1.5;">${items || '<li>Keine Daten vorhanden.</li>'}</ul>
                </div>
            `;
        }

        escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }
        
        renderMermaid(container, diagram, allowFallback = true) {
            if (typeof mermaid === 'undefined' || typeof mermaid.render !== 'function') {
                if (allowFallback) {
                    this.renderFallbackList(container);
                } else {
                    this.showError('Mermaid library not available');
                }
                return;
            }

            const currentToken = ++this.renderToken;
            const diagramId = `${this.instanceId}-${currentToken}`;

            container.innerHTML = `<div class="themisdb-rt-diagram themisdb-rt-diagram-loading">Rendering timeline...</div>`;

            Promise.resolve()
                .then(() => {
                    mermaid.initialize({
                        startOnLoad: false,
                        theme: this.currentTheme || 'neutral',
                        securityLevel: 'loose',
                        deterministicIds: false,
                        flowchart: {
                            useMaxWidth: true,
                            htmlLabels: true
                        }
                    });

                    return mermaid.render(diagramId, diagram);
                })
                .then((result) => {
                    if (currentToken !== this.renderToken) {
                        return;
                    }

                    const svg = (result && result.svg) ? result.svg : '';
                    if (!svg) {
                        throw new Error('Mermaid render returned empty SVG');
                    }

                    container.innerHTML = `<div class="themisdb-rt-diagram">${svg}</div>`;

                    if (result && typeof result.bindFunctions === 'function') {
                        result.bindFunctions(container);
                    }
                })
                .catch(() => {
                    if (currentToken !== this.renderToken) {
                        return;
                    }

                    if (allowFallback) {
                        this.renderFallbackList(container);
                    } else {
                        this.showError('Mermaid render error');
                    }
                });
        }
        
        showLoading() {
            const content = this.container.querySelector('.themisdb-rt-content');
            if (content) {
                content.innerHTML = `
                    <div class="themisdb-rt-loading">
                        <div class="themisdb-rt-spinner"></div>
                        <p>Loading release timeline...</p>
                    </div>
                `;
            }
        }
        
        showError(message) {
            const content = this.container.querySelector('.themisdb-rt-content');
            if (content) {
                content.innerHTML = `
                    <div class="themisdb-rt-error">
                        <strong>Error:</strong> ${message}
                    </div>
                `;
            }
        }
        
        zoom(delta) {
            this.zoomLevel = Math.max(0.5, Math.min(2.0, this.zoomLevel + delta));
            const diagram = this.container.querySelector('.themisdb-rt-diagram');
            if (diagram) {
                diagram.style.transform = `scale(${this.zoomLevel})`;
                diagram.style.transformOrigin = 'top left';
            }
        }
        
        resetZoom() {
            this.zoomLevel = 1.0;
            const diagram = this.container.querySelector('.themisdb-rt-diagram');
            if (diagram) {
                diagram.style.transform = 'scale(1)';
            }
        }
        
        exportSVG() {
            const svg = this.container.querySelector('.themisdb-rt-diagram svg');
            if (!svg) {
                this.showNotification('No diagram available to export', 'warning');
                return;
            }
            
            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svg);
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `themisdb-release-timeline-${Date.now()}.svg`;
            a.click();
            
            URL.revokeObjectURL(url);
        }
        
        exportPNG() {
            const svg = this.container.querySelector('.themisdb-rt-diagram svg');
            if (!svg) {
                this.showNotification('No diagram available to export', 'warning');
                return;
            }
            
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const svgData = new XMLSerializer().serializeToString(svg);
            const img = new Image();
            
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                canvas.toBlob(function(blob) {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `themisdb-release-timeline-${Date.now()}.png`;
                    a.click();
                    URL.revokeObjectURL(url);
                });
            };
            
            // Use modern encoding without deprecated unescape()
            const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
            img.src = URL.createObjectURL(blob);
        }
        
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `themisdb-rt-notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'warning' ? '#f59e0b' : '#10b981'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        toggleFullscreen() {
            this.container.classList.toggle('themisdb-rt-fullscreen');
            
            if (this.container.classList.contains('themisdb-rt-fullscreen')) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'themisdb-rt-fullscreen-close';
                closeBtn.textContent = 'Exit Fullscreen';
                closeBtn.onclick = () => this.toggleFullscreen();
                this.container.appendChild(closeBtn);
            } else {
                const closeBtn = this.container.querySelector('.themisdb-rt-fullscreen-close');
                if (closeBtn) closeBtn.remove();
            }
        }
    }
    
    // Initialize on DOM ready
    $(document).ready(function() {
        $('.themisdb-rt-container').each(function() {
            new ReleaseTimelineVisualizer(this);
        });
    });
    
})(jQuery);
