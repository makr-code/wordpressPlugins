(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function getCanvasSize(container) {
        const shell = container && container.closest ? container.closest('[data-graph-v2-shell]') : null;
        const rect = container && container.getBoundingClientRect ? container.getBoundingClientRect() : { width: 0, height: 0 };
        const shellRect = shell && shell.getBoundingClientRect ? shell.getBoundingClientRect() : { width: 0, height: 0 };
        const width = Math.max(rect.width || shellRect.width || container.offsetWidth || 800, 320);
        const height = Math.max(rect.height || shellRect.height || container.offsetHeight || 420, 260);

        return {
            width: Math.floor(width),
            height: Math.floor(height),
        };
    }

    function ensureCanvas(container) {
        const existing = container.querySelector('canvas');
        const { width, height } = getCanvasSize(container);
        const ratio = window.devicePixelRatio || 1;

        if (existing) {
            const desiredWidth = Math.floor(width * ratio);
            const desiredHeight = Math.floor(height * ratio);

            if (existing.width !== desiredWidth || existing.height !== desiredHeight) {
                existing.width = desiredWidth;
                existing.height = desiredHeight;
                existing.style.width = width + 'px';
                existing.style.height = height + 'px';
            }
            return existing;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        container.innerHTML = '';
        container.appendChild(canvas);

        return canvas;
    }

    function applyGraphView(root, graphData, state) {
        const container = root || document.querySelector('[data-graph-v2-canvas]');
        if (!container) {
            return false;
        }

        const canvas = ensureCanvas(container);
        const renderer = window.ThemisDBGraphV2.CanvasRenderer;
        const rendererConfig = window.themisdbGraphV2 && window.themisdbGraphV2.renderer ? window.themisdbGraphV2.renderer : {};
        const preferWebGPU = rendererConfig.preferWebGPU !== false;
        const allowExperimentalWebGPU = rendererConfig.allowExperimentalWebGPU === true;
        const shouldUseWebGPU = preferWebGPU && allowExperimentalWebGPU && window.navigator && 'gpu' in navigator && window.ThemisDBGraphV2.WebGPURenderer;

        if (shouldUseWebGPU) {
            const activeState = state || window.ThemisDBGraphV2.state;
            const gpuRenderer = new window.ThemisDBGraphV2.WebGPURenderer(canvas, activeState);
            gpuRenderer.init().then((ready) => {
                if (ready) {
                    const rendered = gpuRenderer.render(graphData, activeState);
                    if (rendered) {
                        return;
                    }
                }
                if (renderer) {
                    renderer.draw(graphData, canvas, activeState);
                }
            }).catch(() => {
                if (renderer) {
                    renderer.draw(graphData, canvas, activeState);
                }
            });
            return true;
        }

        if (renderer) {
            renderer.draw(graphData, canvas, state || window.ThemisDBGraphV2.state);
        }

        return true;
    }

    function createGraphView(container, state) {
        const instance = {
            container,
            state: state || window.ThemisDBGraphV2.state,
            render(graphData) {
                return applyGraphView(container, graphData, this.state);
            },
            bind(interactionHandler) {
                const canvas = container.querySelector('canvas') || ensureCanvas(container);
                canvas.dataset.bound = canvas.dataset.bound || 'true';
                if (typeof interactionHandler === 'function') {
                    interactionHandler(canvas, this.state);
                }
                return canvas;
            },
        };

        return instance;
    }

    window.ThemisDBGraphV2.GraphView = {
        applyGraphView,
        createGraphView,
        ensureCanvas,
    };
})();
