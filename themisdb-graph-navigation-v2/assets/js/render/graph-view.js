(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function getCanvasSize(container) {
        const shell = container && container.closest ? container.closest('[data-graph-v2-shell]') : null;
        const rect = container && container.getBoundingClientRect ? container.getBoundingClientRect() : { width: 0, height: 0 };
        const shellRect = shell && shell.getBoundingClientRect ? shell.getBoundingClientRect() : { width: 0, height: 0 };
        const width = Math.max(rect.width || shellRect.width || container.offsetWidth || 800, 320);
        const height = Math.max(rect.height || shellRect.height || container.offsetHeight || 420, 260);
        const worldWidth = Math.max(width * 2.2, 1600);
        const worldHeight = Math.max(height * 2.2, 1100);

        return {
            width: Math.floor(worldWidth),
            height: Math.floor(worldHeight),
        };
    }

    function ensureCanvas(container) {
        const existing = container.querySelector('canvas');
        const { width, height } = getCanvasSize(container);
        const ratio = window.devicePixelRatio || 1;

        const syncCanvasDimension = (canvas) => {
            const desiredWidth = Math.max(1, Math.floor(width * ratio));
            const desiredHeight = Math.max(1, Math.floor(height * ratio));

            if (canvas.width !== desiredWidth || canvas.height !== desiredHeight || canvas.style.width !== width + 'px' || canvas.style.height !== height + 'px') {
                canvas.width = desiredWidth;
                canvas.height = desiredHeight;
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
            }

            if (window.ThemisDBGraphV2 && window.ThemisDBGraphV2.state) {
                window.ThemisDBGraphV2.state.viewport.width = Math.max(320, Math.floor(width / 2.2));
                window.ThemisDBGraphV2.state.viewport.height = Math.max(260, Math.floor(height / 2.2));
                window.ThemisDBGraphV2.state.viewport.worldWidth = width;
                window.ThemisDBGraphV2.state.viewport.worldHeight = height;
            }

            return canvas;
        };

        if (existing) {
            return syncCanvasDimension(existing);
        }

        const canvas = document.createElement('canvas');
        const resolvedCanvas = syncCanvasDimension(canvas);
        container.style.overflow = 'auto';
        container.style.overflowX = 'auto';
        container.style.overflowY = 'auto';
        container.style.scrollbarGutter = 'stable';
        container.innerHTML = '';
        container.appendChild(resolvedCanvas);

        return resolvedCanvas;
    }

    function getOrCreateWebGPURenderer(canvas, state) {
        const cached = window.ThemisDBGraphV2.rendererCache || (window.ThemisDBGraphV2.rendererCache = new WeakMap());
        let renderer = cached.get(canvas);
        if (!renderer) {
            renderer = new window.ThemisDBGraphV2.WebGPURenderer(canvas, state || window.ThemisDBGraphV2.state);
            cached.set(canvas, renderer);
        }
        return renderer;
    }

    function syncViewportFromContainer(container, state) {
        if (!container) {
            return { width: 0, height: 0 };
        }

        const rect = container.getBoundingClientRect ? container.getBoundingClientRect() : { width: 0, height: 0 };
        const width = Math.max(320, Math.floor(rect.width || container.offsetWidth || 800));
        const height = Math.max(260, Math.floor(rect.height || container.offsetHeight || 420));
        const worldWidth = Math.max(width * 2.2, 1600);
        const worldHeight = Math.max(height * 2.2, 1100);

        if (state && state.viewport) {
            state.viewport.width = width;
            state.viewport.height = height;
            state.viewport.worldWidth = worldWidth;
            state.viewport.worldHeight = worldHeight;
        }

        return { width, height };
    }

    function attachResizeObserver(container, state) {
        if (!container || container.__themisdbViewportResizeObserver) {
            return;
        }

        const observer = new ResizeObserver(() => {
            syncViewportFromContainer(container, state || window.ThemisDBGraphV2.state);
            const canvas = container.querySelector('canvas') || ensureCanvas(container);
            if (canvas) {
                ensureCanvas(container);
            }
        });

        observer.observe(container);
        container.__themisdbViewportResizeObserver = observer;
    }

    function applyGraphView(root, graphData, state) {
        const container = root || document.querySelector('[data-graph-v2-canvas]');
        if (!container) {
            return false;
        }

        attachResizeObserver(container, state || window.ThemisDBGraphV2.state);
        syncViewportFromContainer(container, state || window.ThemisDBGraphV2.state);

        const canvas = ensureCanvas(container);
        const renderer = window.ThemisDBGraphV2.CanvasRenderer;
        const activeState = state || window.ThemisDBGraphV2.state;
        const rendererConfig = window.themisdbGraphV2 && window.themisdbGraphV2.renderer ? window.themisdbGraphV2.renderer : {};
        const preferWebGPU = Boolean(rendererConfig.preferWebGPU === true);
        const allowWebGPU = Boolean(rendererConfig.allowExperimentalWebGPU === true);
        const webGPUBlocked = Boolean(window.ThemisDBGraphV2 && window.ThemisDBGraphV2.WebGPUBlocked);
        const hasWebGPU = Boolean(window.navigator && 'gpu' in navigator && window.ThemisDBGraphV2 && window.ThemisDBGraphV2.WebGPURenderer);
        const viewportIsLargeEnough = (activeState && activeState.viewport && activeState.viewport.width >= 320 && activeState.viewport.height >= 220) || true;
        const shouldUseWebGPU = !webGPUBlocked && allowWebGPU && preferWebGPU && hasWebGPU && viewportIsLargeEnough;

        if (shouldUseWebGPU) {
            const gpuRenderer = getOrCreateWebGPURenderer(canvas, activeState);
            gpuRenderer.init().then((ready) => {
                if (ready && gpuRenderer.render(graphData, activeState)) {
                    return;
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
            if (typeof renderer.drawScene === 'function') {
                renderer.drawScene(graphData, canvas, activeState);
            } else {
                renderer.draw(graphData, canvas, activeState);
            }
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
