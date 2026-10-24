(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function getContainers() {
        return Array.from(document.querySelectorAll('[data-graph-v2-canvas]'));
    }

    function syncViewportToContainer(container, state) {
        if (!state || !state.viewport || !container) {
            return state;
        }

        const rect = container.getBoundingClientRect ? container.getBoundingClientRect() : { width: 0, height: 0 };
        const width = Math.max(320, Math.floor(rect.width || state.viewport.width || 800));
        const height = Math.max(260, Math.floor(rect.height || state.viewport.height || 420));

        state.viewport.width = width;
        state.viewport.height = height;
        return state;
    }

    const graphAnimations = new WeakMap();
    const graphRenderQueue = new WeakMap();

    function scheduleGraphRender(container, graphData, state) {
        if (!container || !graphData) {
            return;
        }

        if (graphRenderQueue.has(container)) {
            return;
        }

        graphRenderQueue.set(container, true);
        requestAnimationFrame(() => {
            graphRenderQueue.delete(container);
            renderGraph(container, graphData, state);
        });
    }

    function getAnimationGraph(container, rawGraph, state) {
        const running = graphAnimations.get(container);
        if (running && Array.isArray(running.nodes) && Array.isArray(running.links)) {
            return {
                nodes: running.nodes,
                links: running.links,
            };
        }

        return buildVisibleGraph(rawGraph, state);
    }

    function computeGraphSignature(graph) {
        const nodeIds = Array.isArray(graph && graph.nodes)
            ? graph.nodes.map((node) => String(node.id)).sort().join('|')
            : '';
        const linkCount = Array.isArray(graph && graph.links) ? graph.links.length : 0;
        return `${nodeIds}::${linkCount}`;
    }

    function assignClusterTargets(nodes, state) {
        const viewportWidth = Number(state && state.viewport ? state.viewport.width : 800) || 800;
        const viewportHeight = Number(state && state.viewport ? state.viewport.height : 420) || 420;
        const centerX = viewportWidth / 2;
        const centerY = viewportHeight / 2;

        const clusterOrder = ['home', 'taxonomy', 'category', 'tag', 'page', 'post', 'generic', 'external_resource'];
        const clusterBuckets = new Map();
        nodes.forEach((node) => {
            const type = String(node.type || 'generic');
            const bucketKey = type === 'home'
                ? 'home'
                : (type === 'category' || type === 'tag' || type === 'taxonomy_term')
                    ? 'taxonomy'
                    : (type === 'page' || type === 'post')
                        ? type
                        : 'generic';
            if (!clusterBuckets.has(bucketKey)) {
                clusterBuckets.set(bucketKey, []);
            }
            clusterBuckets.get(bucketKey).push(node);
        });

        const orderedBuckets = clusterOrder
            .map((key) => ({ key, nodes: clusterBuckets.get(key) || [] }))
            .filter((entry) => entry.nodes.length > 0);

        const homeNodes = clusterBuckets.get('home') || [];
        homeNodes.forEach((node, index) => {
            node.tx = centerX + Math.cos(index * 1.2) * 12;
            node.ty = centerY + Math.sin(index * 1.2) * 12;
        });

        const taxonomyOrbit = Math.min(viewportWidth, viewportHeight) * 0.24;
        const contentOrbit = Math.min(viewportWidth, viewportHeight) * 0.42;

        orderedBuckets.forEach((bucket) => {
            if (bucket.key === 'home') {
                return;
            }

            const orbitRadius = bucket.key === 'taxonomy' ? taxonomyOrbit : contentOrbit;
            const group = bucket.nodes;
            const startAngle = bucket.key === 'taxonomy' ? Math.PI * 0.25 : Math.PI * 0.9;
            const angleStep = (Math.PI * 2) / Math.max(1, group.length);

            group.forEach((node, index) => {
                const localAngle = startAngle + (index * angleStep);
                const radialOffset = 18 + ((index % 5) * 10);
                node.tx = centerX + Math.cos(localAngle) * (orbitRadius + radialOffset);
                node.ty = centerY + Math.sin(localAngle) * (orbitRadius * 0.72 + radialOffset * 0.5);
            });
        });

        const floatingNodes = nodes.filter((node) => !node.tx && !node.ty);
        floatingNodes.forEach((node, index) => {
            node.tx = centerX + Math.cos(index * 0.7) * contentOrbit;
            node.ty = centerY + Math.sin(index * 0.7) * contentOrbit * 0.8;
        });
    }

    function buildAnimatedGraph(baseGraph, previous, state) {
        const nodes = Array.isArray(baseGraph && baseGraph.nodes) ? baseGraph.nodes : [];
        const links = Array.isArray(baseGraph && baseGraph.links) ? baseGraph.links : [];
        const previousById = new Map();

        if (previous && Array.isArray(previous.nodes)) {
            previous.nodes.forEach((node) => {
                previousById.set(String(node.id), node);
            });
        }

        const animatedNodes = nodes.map((node) => {
            const prev = previousById.get(String(node.id));
            const x = prev && Number.isFinite(prev.x) ? prev.x : Number(node.x || 0);
            const y = prev && Number.isFinite(prev.y) ? prev.y : Number(node.y || 0);

            return Object.assign({}, node, {
                x,
                y,
                tx: Number(node.x || x),
                ty: Number(node.y || y),
                vx: prev && Number.isFinite(prev.vx) ? prev.vx : ((Math.random() - 0.5) * 0.8),
                vy: prev && Number.isFinite(prev.vy) ? prev.vy : ((Math.random() - 0.5) * 0.8),
            });
        });

        assignClusterTargets(animatedNodes, state);

        return {
            nodes: animatedNodes,
            links: links.map((link) => Object.assign({}, link)),
            alpha: previous && Number.isFinite(previous.alpha) ? previous.alpha : 0.9,
            alphaTarget: 0.08,
            cooling: 0.985,
        };
    }

    function stepAnimatedGraph(simulation, state, dt) {
        if (!simulation || !Array.isArray(simulation.nodes)) {
            return;
        }

        const nodes = simulation.nodes;
        const effectiveDt = Math.max(0.6, Math.min(1.8, dt / 16.67));
        const viewportWidth = Number(state && state.viewport ? state.viewport.width : 800) || 800;
        const viewportHeight = Number(state && state.viewport ? state.viewport.height : 420) || 420;
        const centerX = viewportWidth / 2;
        const centerY = viewportHeight / 2;

        nodes.forEach((node) => {
            const targetX = Number.isFinite(Number(node.tx)) ? Number(node.tx) : Number(node.x || centerX);
            const targetY = Number.isFinite(Number(node.ty)) ? Number(node.ty) : Number(node.y || centerY);
            const currentX = Number(node.x || targetX);
            const currentY = Number(node.y || targetY);
            const easing = 0.14 * effectiveDt;
            const dx = targetX - currentX;
            const dy = targetY - currentY;

            node.vx = (Number(node.vx || 0) * 0.72) + (dx * easing);
            node.vy = (Number(node.vy || 0) * 0.72) + (dy * easing);

            node.x = currentX + node.vx;
            node.y = currentY + node.vy;

            const padding = 20;
            if (node.x < padding || node.x > viewportWidth - padding) {
                node.vx *= -0.45;
                node.x = Math.min(Math.max(node.x, padding), viewportWidth - padding);
            }
            if (node.y < padding || node.y > viewportHeight - padding) {
                node.vy *= -0.45;
                node.y = Math.min(Math.max(node.y, padding), viewportHeight - padding);
            }
        });
    }

    function stopGraphAnimation(container) {
        const running = graphAnimations.get(container);
        if (!running) {
            return;
        }

        if (running.frameId) {
            cancelAnimationFrame(running.frameId);
        }
        running.running = false;
    }

    function startGraphAnimation(container, rawGraph, state, forceRebuild) {
        if (!container) {
            return;
        }

        syncViewportToContainer(container, state);

        const baseGraph = buildVisibleGraph(rawGraph, state);
        const signature = computeGraphSignature(baseGraph);
        const existing = graphAnimations.get(container);
        const needsRebuild = forceRebuild || !existing || existing.signature !== signature;
        const simulation = needsRebuild
            ? buildAnimatedGraph(baseGraph, existing, state)
            : existing;

        assignClusterTargets(simulation.nodes, state);
        simulation.signature = signature;
        simulation.running = true;
        simulation.lastFrameTime = performance.now();
        simulation.alpha = forceRebuild ? 0.85 : Math.max(0.2, Number(simulation.alpha || 0.6));
        graphAnimations.set(container, simulation);

        if (!needsRebuild && existing && existing.frameId) {
            return;
        }

        const tick = (timestamp) => {
            const current = graphAnimations.get(container);
            if (!current || !current.running) {
                return;
            }

            const dt = Math.max(8, Math.min(40, timestamp - (current.lastFrameTime || timestamp)));
            current.lastFrameTime = timestamp;
            stepAnimatedGraph(current, state, dt);
            renderGraph(container, { nodes: current.nodes, links: current.links }, state);
            current.frameId = requestAnimationFrame(tick);
        };

        simulation.frameId = requestAnimationFrame(tick);
    }

    function getShellId(shell) {
        if (!shell) {
            return '';
        }

        if (!shell.id) {
            shell.id = `themisdb-graph-v2-panel-${Math.random().toString(36).slice(2, 10)}`;
        }

        return shell.id;
    }

    function pruneDuplicateGraphInstances() {
        const shellMap = new Map();
        document.querySelectorAll('[data-graph-v2-shell]').forEach((shell) => {
            const key = getShellId(shell) || shell.getAttribute('data-graph-v2-target') || shell.getAttribute('id') || `shell-${Math.random().toString(36).slice(2, 10)}`;
            const current = shellMap.get(key);
            if (!current) {
                shellMap.set(key, shell);
                return;
            }

            const shouldKeep = current.getAttribute('data-graph-v2-expanded') === '1' || (!current.dataset || current.dataset.graphV2Expanded !== '1');
            const candidateKeeps = shell.getAttribute('data-graph-v2-expanded') === '1';
            if (candidateKeeps && !shouldKeep) {
                current.remove();
                shellMap.set(key, shell);
                return;
            }

            if (!candidateKeeps && shouldKeep) {
                shell.remove();
                return;
            }

            if (current.getBoundingClientRect && shell.getBoundingClientRect) {
                const currentRect = current.getBoundingClientRect();
                const shellRect = shell.getBoundingClientRect();
                if (shellRect.width * shellRect.height > currentRect.width * currentRect.height) {
                    current.remove();
                    shellMap.set(key, shell);
                    return;
                }
            }

            shell.remove();
        });

        const toggleMap = new Map();
        document.querySelectorAll('[data-graph-v2-toggle]').forEach((toggle) => {
            const target = (toggle.dataset.graphV2Target || toggle.getAttribute('aria-controls') || '').trim();
            const key = target || toggle.className || `toggle-${Math.random().toString(36).slice(2, 10)}`;
            const current = toggleMap.get(key);
            if (!current) {
                toggleMap.set(key, toggle);
                return;
            }

            if (toggle.classList.contains('themisdb-graph-v2-toggle--compact') && !current.classList.contains('themisdb-graph-v2-toggle--compact')) {
                current.remove();
                toggleMap.set(key, toggle);
                return;
            }

            if (!toggle.classList.contains('themisdb-graph-v2-toggle--compact') && current.classList.contains('themisdb-graph-v2-toggle--compact')) {
                toggle.remove();
                return;
            }

            toggle.remove();
        });
    }

    function getLinkedToggles(shell) {
        const shellId = getShellId(shell);
        if (!shellId) {
            return [];
        }

        return Array.from(document.querySelectorAll('[data-graph-v2-toggle]')).filter((button) => {
            const target = (button.dataset.graphV2Target || button.getAttribute('aria-controls') || '').trim();
            return target === shellId;
        });
    }

    function syncToggleState(toggle, shell, shouldOpen) {
        if (!toggle) {
            return;
        }

        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

        if (toggle.dataset.headerMode === 'true' || toggle.classList.contains('themisdb-graph-v2-toggle--compact')) {
            toggle.setAttribute('aria-label', shouldOpen ? 'Graph schließen' : 'Graph anzeigen');
            return;
        }

        if (shell) {
            if (!toggle.dataset.defaultText) {
                toggle.dataset.defaultText = (toggle.textContent || '').trim() || 'Graph';
            }
            toggle.textContent = shouldOpen ? 'Graph schließen' : toggle.dataset.defaultText;
        }
    }

    function clampGraphShellToViewport(shell) {
        if (!shell) {
            return;
        }

        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 900;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 1280;
        const safeGap = 12;
        const maxWidth = Math.min(820, Math.max(260, viewportWidth - (safeGap * 2)));
        const maxHeight = Math.min(560, Math.max(260, viewportHeight - (safeGap * 2)));

        shell.style.setProperty('position', 'fixed', 'important');
        shell.style.setProperty('top', 'auto', 'important');
        shell.style.setProperty('left', 'auto', 'important');
        shell.style.setProperty('right', safeGap + 'px', 'important');
        shell.style.setProperty('bottom', safeGap + 'px', 'important');
        shell.style.setProperty('inset', 'auto', 'important');
        shell.style.setProperty('margin', '0', 'important');
        shell.style.setProperty('transform', 'none', 'important');
        shell.style.setProperty('max-width', 'calc(100vw - ' + (safeGap * 2) + 'px)', 'important');
        shell.style.setProperty('max-height', 'calc(100vh - ' + (safeGap * 2) + 'px)', 'important');
        shell.style.setProperty('width', Math.min(maxWidth, viewportWidth - (safeGap * 2)) + 'px', 'important');
        shell.style.setProperty('height', Math.min(maxHeight, viewportHeight - (safeGap * 2)) + 'px', 'important');
    }

    function setGraphShellState(shell, shouldOpen, canvas) {
        if (!shell) {
            return;
        }

        shell.setAttribute('data-graph-v2-expanded', shouldOpen ? '1' : '0');
        shell.classList.toggle('is-open', shouldOpen);

        shell.style.setProperty('position', 'fixed', 'important');
        shell.style.setProperty('inset', 'auto', 'important');
        shell.style.setProperty('top', 'auto', 'important');
        shell.style.setProperty('left', 'auto', 'important');
        shell.style.setProperty('right', '12px', 'important');
        shell.style.setProperty('bottom', '12px', 'important');
        shell.style.setProperty('margin', '0', 'important');
        shell.style.setProperty('transform', 'none', 'important');
        shell.style.setProperty('transform-origin', 'center center', 'important');
        shell.style.setProperty('visibility', 'visible', 'important');
        shell.style.setProperty('opacity', '1', 'important');
        shell.style.setProperty('z-index', '2147483647', 'important');
        shell.style.setProperty('display', shouldOpen ? 'block' : 'none', 'important');

        if (canvas) {
            canvas.hidden = !shouldOpen;
        }

        requestAnimationFrame(() => {
            clampGraphShellToViewport(shell);
        });
    }

    function ensureGraphStatsOverlay(shell) {
        if (!shell) {
            return null;
        }

        let overlay = shell.querySelector('.themisdb-graph-v2-stats');
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.className = 'themisdb-graph-v2-stats';
        overlay.setAttribute('aria-live', 'polite');
        overlay.innerHTML = [
            '<span class="themisdb-graph-v2-stats__item"><strong>0</strong> nodes</span>',
            '<span class="themisdb-graph-v2-stats__item"><strong>0</strong> edges</span>',
            '<span class="themisdb-graph-v2-stats__mode">Full view</span>'
        ].join('');

        const header = shell.querySelector('.themisdb-graph-v2-header');
        if (header) {
            header.appendChild(overlay);
        } else {
            shell.insertBefore(overlay, shell.firstChild);
        }

        return overlay;
    }

    function updateGraphStats(shell, graphData) {
        const overlay = ensureGraphStatsOverlay(shell);
        if (!overlay) {
            return;
        }

        const visibleGraph = graphData && Array.isArray(graphData.nodes) ? graphData : { nodes: [], links: [] };
        const totalNodes = Number((graphData && graphData.stats && graphData.stats.totalNodes) || visibleGraph.nodes.length || 0);
        const totalLinks = Number((graphData && graphData.stats && graphData.stats.totalLinks) || (graphData && graphData.links ? graphData.links.length : 0) || 0);
        const visibleNodes = Array.isArray(visibleGraph.nodes) ? visibleGraph.nodes.length : 0;
        const visibleLinks = Array.isArray(visibleGraph.links) ? visibleGraph.links.length : 0;
        const reduction = graphData && graphData.stats && graphData.stats.reduction ? graphData.stats.reduction : null;
        const modeLabel = reduction && reduction.active ? ('Reduced: ' + (reduction.strategy || 'dynamic')) : 'Full view';

        overlay.innerHTML = [
            '<span class="themisdb-graph-v2-stats__item"><strong>' + visibleNodes + '</strong> / ' + totalNodes + ' nodes</span>',
            '<span class="themisdb-graph-v2-stats__item"><strong>' + visibleLinks + '</strong> / ' + totalLinks + ' edges</span>',
            '<span class="themisdb-graph-v2-stats__mode">' + escapeHtml(modeLabel) + '</span>'
        ].join('');
    }

    function ensureNodeInfoPanel(shell) {
        if (!shell) {
            return null;
        }

        let panel = shell.querySelector('.themisdb-graph-v2-node-panel');
        if (panel) {
            return panel;
        }

        panel = document.createElement('aside');
        panel.className = 'themisdb-graph-v2-node-panel';
        panel.setAttribute('aria-live', 'polite');
        panel.hidden = true;
        panel.innerHTML = [
            '<div class="themisdb-graph-v2-node-panel__header">',
            '  <span class="themisdb-graph-v2-node-panel__eyebrow">Selected node</span>',
            '  <button type="button" class="themisdb-graph-v2-node-panel__close" aria-label="Details schließen">×</button>',
            '</div>',
            '<div class="themisdb-graph-v2-node-panel__body">',
            '  <h3 class="themisdb-graph-v2-node-panel__title">Node</h3>',
            '  <div class="themisdb-graph-v2-node-panel__meta"></div>',
            '  <div class="themisdb-graph-v2-node-panel__actions"></div>',
            '</div>'
        ].join('');

        const closeButton = panel.querySelector('.themisdb-graph-v2-node-panel__close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                panel.hidden = true;
                const canvas = shell.querySelector('[data-graph-v2-canvas]');
                if (canvas) {
                    const state = window.ThemisDBGraphV2.state;
                    state.selection.nodeId = null;
                    state.selection.hoverNodeId = null;
                    startGraphAnimation(canvas, window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] }, state, true);
                }
            });
        }

        shell.appendChild(panel);
        return panel;
    }

    function updateNodeInfoPanel(shell, node) {
        const panel = ensureNodeInfoPanel(shell);
        if (!panel) {
            return;
        }

        if (!node) {
            panel.hidden = true;
            return;
        }

        const titleEl = panel.querySelector('.themisdb-graph-v2-node-panel__title');
        const metaEl = panel.querySelector('.themisdb-graph-v2-node-panel__meta');
        const actionsEl = panel.querySelector('.themisdb-graph-v2-node-panel__actions');
        if (!titleEl || !metaEl || !actionsEl) {
            return;
        }

        const label = node.label || node.title || 'Untitled node';
        const type = String(node.type || 'node').replace(/_/g, ' ');
        const degree = Number(node.degree || 0);
        const url = node.url || '';
        const description = node.metadata && typeof node.metadata === 'object' && node.metadata.description ? node.metadata.description : (node.description || 'Related WordPress content item');

        titleEl.textContent = label;
        metaEl.innerHTML = [
            '<dl>',
            '  <div><dt>Type</dt><dd>' + escapeHtml(type) + '</dd></div>',
            '  <div><dt>Degree</dt><dd>' + escapeHtml(String(degree)) + '</dd></div>',
            '  <div><dt>URL</dt><dd>' + (url ? '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">Open page</a>' : 'No direct URL') + '</dd></div>',
            '</dl>'
        ].join('');

        actionsEl.innerHTML = url
            ? '<a class="themisdb-graph-v2-node-panel__button" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">Open node</a>'
            : '<span class="themisdb-graph-v2-node-panel__button is-disabled">No direct link</span>';

        panel.hidden = false;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function ensureGraphCanvas(container) {
        if (!container) {
            return null;
        }

        let canvas = container.querySelector('canvas');
        if (!canvas && window.ThemisDBGraphV2.GraphView && window.ThemisDBGraphV2.GraphView.ensureCanvas) {
            canvas = window.ThemisDBGraphV2.GraphView.ensureCanvas(container);
        }

        return canvas || container.querySelector('canvas');
    }

    function bindToggleButton(shell, canvas, toggleOverride) {
        const toggle = toggleOverride || (shell ? shell.querySelector('[data-graph-v2-toggle]') : null);
        const closeButton = shell ? shell.querySelector('[data-graph-v2-close]') : null;
        const renderContainer = (canvas && canvas.hasAttribute && canvas.hasAttribute('data-graph-v2-canvas'))
            ? canvas
            : (shell ? shell.querySelector('[data-graph-v2-canvas]') : null);
        const alreadyBound = !!toggle && (toggle.__themisdbGraphHandler || toggle.dataset.bound === 'true');
        if (!toggle || alreadyBound) {
            return;
        }

        toggle.dataset.bound = 'true';
        toggle.dataset.defaultText = (toggle.textContent || '').trim() || 'Graph';
        const shellId = getShellId(shell);
        if (shellId && !toggle.dataset.graphV2Target) {
            toggle.dataset.graphV2Target = shellId;
        }
        if (shellId && !toggle.getAttribute('aria-controls')) {
            toggle.setAttribute('aria-controls', shellId);
        }
        if (shell && !toggle.classList.contains('themisdb-graph-v2-toggle--compact')) {
            toggle.textContent = shell.getAttribute('data-graph-v2-expanded') === '1' ? 'Graph schließen' : toggle.dataset.defaultText;
        }

        const setOpenState = (shouldOpen) => {
            if (shell) {
                setGraphShellState(shell, shouldOpen, renderContainer);
                const nodePanel = shell.querySelector('.themisdb-graph-v2-node-panel');
                if (nodePanel) {
                    nodePanel.hidden = !shouldOpen;
                }
            }
            const linked = getLinkedToggles(shell);
            if (!linked.length) {
                syncToggleState(toggle, shell, shouldOpen);
                return;
            }
            linked.forEach((linkedToggle) => syncToggleState(linkedToggle, shell, shouldOpen));
        };

        const handleToggle = (event) => {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const shouldOpen = shell ? shell.getAttribute('data-graph-v2-expanded') !== '1' : true;
            setOpenState(shouldOpen);

            if (!renderContainer) {
                return;
            }

            if (!shouldOpen) {
                stopGraphAnimation(renderContainer);
                return;
            }

            const ensuredCanvas = ensureGraphCanvas(renderContainer);
            if (ensuredCanvas) {
                ensuredCanvas.hidden = false;
                ensuredCanvas.style.pointerEvents = 'auto';
                renderContainer.style.pointerEvents = 'auto';
            }

            const state = window.ThemisDBGraphV2.state;
            const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };
            state.viewport.width = Math.max(320, renderContainer.clientWidth || renderContainer.getBoundingClientRect().width || 720);
            state.viewport.height = Math.max(320, renderContainer.clientHeight || renderContainer.getBoundingClientRect().height || 420);

            startGraphAnimation(renderContainer, graphData, state, true);
            bindInteraction(renderContainer, graphData, state);
        };

        toggle.__themisdbGraphHandler = handleToggle;
        toggle.addEventListener('click', handleToggle);

        if (closeButton) {
            closeButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                setOpenState(false);
                stopGraphAnimation(renderContainer);
            });
        }
    }

    function normalizeGraphOverlayPlacement() {
        document.querySelectorAll('.themisdb-graph-v2-overlay').forEach((overlay) => {
            overlay.style.setProperty('position', 'static', 'important');
            overlay.style.setProperty('top', 'auto', 'important');
            overlay.style.setProperty('left', 'auto', 'important');
            overlay.style.setProperty('right', 'auto', 'important');
            overlay.style.setProperty('bottom', 'auto', 'important');
            overlay.style.setProperty('inset', 'auto', 'important');
            overlay.style.setProperty('z-index', 'auto', 'important');
            overlay.style.setProperty('transform', 'none', 'important');
            overlay.style.setProperty('width', 'auto', 'important');
            overlay.style.setProperty('height', 'auto', 'important');
        });

        document.querySelectorAll('.themisdb-graph-v2-shell').forEach((shell) => {
            shell.style.setProperty('position', 'fixed', 'important');
            shell.style.setProperty('top', 'auto', 'important');
            shell.style.setProperty('left', 'auto', 'important');
            shell.style.setProperty('right', '12px', 'important');
            shell.style.setProperty('bottom', '12px', 'important');
            shell.style.setProperty('inset', 'auto', 'important');
            shell.style.setProperty('margin', '0', 'important');
            shell.style.setProperty('transform', 'none', 'important');
            shell.style.setProperty('z-index', '2147483647', 'important');
            shell.style.setProperty('max-width', 'calc(100vw - 24px)', 'important');
            shell.style.setProperty('max-height', 'calc(100vh - 24px)', 'important');
            clampGraphShellToViewport(shell);
        });
    }

    function injectHeaderBrandTrigger(shell) {
        const brand = document.querySelector('.tv3-header-brand');
        if (!brand || brand.querySelector('.themisdb-graph-v2-header-toggle')) {
            return;
        }
        if (document.querySelector('.themisdb-graph-v2-toggle--compact')) {
            return;
        }

        const canvas = shell.querySelector('[data-graph-v2-canvas]');
        const anchor = document.createElement('button');
        anchor.type = 'button';
        anchor.className = 'themisdb-graph-v2-header-toggle';
        anchor.setAttribute('data-graph-v2-toggle', '1');
        const shellId = getShellId(shell);
        if (shellId) {
            anchor.dataset.graphV2Target = shellId;
            anchor.setAttribute('aria-controls', shellId);
        }
        anchor.setAttribute('aria-expanded', shell.getAttribute('data-graph-v2-expanded') === '1' ? 'true' : 'false');
        anchor.setAttribute('aria-label', 'Graph anzeigen');
        anchor.dataset.headerMode = 'true';
        anchor.innerHTML = '<span class="themisdb-graph-v2-header-mark" aria-hidden="true">◉</span><span class="themisdb-graph-v2-header-text">Graph</span>';

        const titleTarget = brand.querySelector('.tv3-brand-title, .wp-block-site-title, .site-title, h1, [class*="site-title"]');
        if (titleTarget) {
            brand.insertBefore(anchor, titleTarget);
        } else {
            brand.insertBefore(anchor, brand.firstChild);
        }

        const mainToggle = shell.querySelector('[data-graph-v2-toggle]');
        if (mainToggle && mainToggle !== anchor) {
            mainToggle.hidden = true;
        }

        const setOpenState = (shouldOpen) => {
            setGraphShellState(shell, shouldOpen, canvas);
            const nodePanel = shell.querySelector('.themisdb-graph-v2-node-panel');
            if (nodePanel) {
                nodePanel.hidden = !shouldOpen;
            }
            anchor.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            anchor.setAttribute('aria-label', shouldOpen ? 'Graph schließen' : 'Graph anzeigen');
            if (mainToggle && mainToggle !== anchor) {
                mainToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }
        };

        anchor.addEventListener('click', (event) => {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const shouldOpen = shell.getAttribute('data-graph-v2-expanded') !== '1';
            setOpenState(shouldOpen);

            if (!shouldOpen) {
                if (canvas) {
                    stopGraphAnimation(canvas);
                }
                return;
            }

            const state = window.ThemisDBGraphV2.state;
            const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };
            state.viewport.width = Math.max(320, canvas.clientWidth || canvas.getBoundingClientRect().width || 720);
            state.viewport.height = Math.max(320, canvas.clientHeight || canvas.getBoundingClientRect().height || 420);

            startGraphAnimation(canvas, graphData, state, true);
            bindInteraction(canvas, graphData, state);
        });
    }

    function layoutRadial(nodes, width, height) {
        const centerX = width / 2;
        const centerY = height / 2;
        const minDimension = Math.max(180, Math.min(width, height));
        const radiusBase = minDimension * 0.28;

        const bucketMap = new Map();
        nodes.forEach((node) => {
            const type = String(node.type || 'generic');
            const key = type === 'home'
                ? 'home'
                : (type === 'category' || type === 'tag' || type === 'taxonomy_term')
                    ? 'taxonomy'
                    : (type === 'page' || type === 'post')
                        ? 'content'
                        : 'generic';
            if (!bucketMap.has(key)) {
                bucketMap.set(key, []);
            }
            bucketMap.get(key).push(node);
        });

        const positioned = nodes.map((node) => ({ ...node }));
        const homeNodes = bucketMap.get('home') || [];
        const taxonomyNodes = bucketMap.get('taxonomy') || [];
        const contentNodes = bucketMap.get('content') || [];
        const genericNodes = bucketMap.get('generic') || [];

        const ringMap = [
            { key: 'home', nodes: homeNodes, radius: 0, angleOffset: 0 },
            { key: 'taxonomy', nodes: taxonomyNodes, radius: radiusBase + 32, angleOffset: Math.PI * 0.20 },
            { key: 'content', nodes: contentNodes, radius: radiusBase + 110, angleOffset: Math.PI * 0.85 },
            { key: 'generic', nodes: genericNodes, radius: radiusBase + 190, angleOffset: Math.PI * 1.5 },
        ];

        ringMap.forEach(({ nodes: ringNodes, radius, angleOffset }) => {
            if (!ringNodes || !ringNodes.length) {
                return;
            }

            ringNodes.forEach((node, index) => {
                const total = Math.max(1, ringNodes.length);
                const localAngle = angleOffset + (index / total) * (Math.PI * 2);
                const x = centerX + Math.cos(localAngle) * radius;
                const y = centerY + Math.sin(localAngle) * (radius * 0.72);
                const target = positioned.find((candidate) => String(candidate.id) === String(node.id));
                if (target) {
                    target.x = x;
                    target.y = y;
                    target.tx = x;
                    target.ty = y;
                }
            });
        });

        positioned.forEach((node) => {
            if (!Number.isFinite(node.x) || !Number.isFinite(node.y)) {
                const count = Math.max(1, positioned.length);
                const angle = ((Number(node.level || 0) + 1) * 1.17) + (count * 0.13);
                const radius = radiusBase + 240 + ((Number(node.level || 0) % 5) * 36);
                node.x = centerX + Math.cos(angle) * radius;
                node.y = centerY + Math.sin(angle) * radius * 0.78;
                node.tx = node.x;
                node.ty = node.y;
            }
        });

        return positioned;
    }

    function createLayoutWorker() {
        if (!window.Worker) {
            return null;
        }

        const assetUrl = (window.themisdbGraphV2 && window.themisdbGraphV2.assetUrl) ? window.themisdbGraphV2.assetUrl : '';
        const workerUrl = assetUrl ? (assetUrl + 'assets/js/workers/layout-worker.js') : '';

        if (!workerUrl) {
            return null;
        }

        try {
            return new Worker(workerUrl);
        } catch (error) {
            return null;
        }
    }

    function getGraphState(state) {
        if (!state || !state.selection || !state.filters || !state.viewport) {
            return {
                selection: { nodeId: null, hoverNodeId: null },
                filters: { minDegree: 0, maxVisibleNodes: 120, showExternal: true },
                viewport: { width: 800, height: 420 },
            };
        }

        return state;
    }

    function buildVisibleGraph(graphData, state) {
        const effectiveState = getGraphState(state);
        const model = window.ThemisDBGraphV2.GraphStore ? window.ThemisDBGraphV2.GraphStore.buildGraphModel(graphData) : graphData;
        const visible = window.ThemisDBGraphV2.GraphStore
            ? window.ThemisDBGraphV2.GraphStore.calculateVisibleGraph(model, {
                selectedNodeId: effectiveState.selection.nodeId,
                minDegree: effectiveState.filters.minDegree,
                maxVisibleNodes: effectiveState.filters.maxVisibleNodes,
                showExternal: effectiveState.filters.showExternal,
            })
            : model;

        const width = effectiveState.viewport.width || 800;
        const height = effectiveState.viewport.height || 420;
        const positionedNodes = layoutRadial(visible.nodes, width, height);
        return Object.assign({}, visible, { nodes: positionedNodes });
    }

    function layoutGraph(graphData, state) {
        const effectiveState = getGraphState(state);
        const visible = buildVisibleGraph(graphData, effectiveState);
        const worker = createLayoutWorker();
        if (!worker) {
            return Promise.resolve(visible);
        }

        return new Promise((resolve) => {
            const width = effectiveState.viewport.width || 800;
            const height = effectiveState.viewport.height || 420;

            worker.onmessage = function (event) {
                const payload = event.data || {};
                worker.terminate();
                if (payload && payload.type === 'layout-ready') {
                    resolve(Object.assign({}, visible, { nodes: Array.isArray(payload.nodes) ? payload.nodes : visible.nodes }));
                    return;
                }
                resolve(visible);
            };

            worker.onerror = function () {
                worker.terminate();
                resolve(visible);
            };

            worker.postMessage({
                nodes: visible.nodes,
                centerX: width / 2,
                centerY: height / 2,
                radiusBase: Math.min(width, height) * 0.22,
            });
        });
    }

    function renderGraph(container, graphData, state) {
        syncViewportToContainer(container, state);
        const shell = container && container.closest ? container.closest('[data-graph-v2-shell]') : null;
        if (shell) {
            updateGraphStats(shell, graphData);
        }

        if (window.ThemisDBGraphV2.GraphView && window.ThemisDBGraphV2.GraphView.applyGraphView) {
            return window.ThemisDBGraphV2.GraphView.applyGraphView(container, graphData, state);
        }

        const canvas = container.querySelector('canvas') || document.createElement('canvas');
        const ratio = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        const width = Math.max(320, Math.floor(rect.width || 800));
        const height = Math.max(260, Math.floor(rect.height || 420));
        const desiredWidth = Math.floor(width * ratio);
        const desiredHeight = Math.floor(height * ratio);

        if (canvas.width !== desiredWidth || canvas.height !== desiredHeight || canvas.style.width !== width + 'px' || canvas.style.height !== height + 'px') {
            canvas.width = desiredWidth;
            canvas.height = desiredHeight;
            canvas.style.width = width + 'px';
            canvas.style.height = height + 'px';
        }

        if (!canvas.parentNode) {
            container.innerHTML = '';
            container.appendChild(canvas);
        }

        if (window.ThemisDBGraphV2.CanvasRenderer) {
            window.ThemisDBGraphV2.CanvasRenderer.draw(graphData, canvas, state);
        }

        return true;
    }

    function bindInteraction(container, rawGraph, state) {
        syncViewportToContainer(container, state);

        const canvas = window.ThemisDBGraphV2.GraphView && window.ThemisDBGraphV2.GraphView.ensureCanvas
            ? window.ThemisDBGraphV2.GraphView.ensureCanvas(container)
            : (container.querySelector('canvas') || document.createElement('canvas'));

        if (!canvas) {
            return;
        }
        if (container.dataset.themisdbInteractionBound === 'true' || canvas.dataset.themisdbInteractionBound === 'true') {
            return;
        }

        container.dataset.themisdbInteractionBound = 'true';
        canvas.dataset.themisdbInteractionBound = 'true';
        canvas.dataset.bound = 'true';
        canvas.style.cursor = 'grab';
        canvas.style.pointerEvents = 'auto';
        container.style.pointerEvents = 'auto';

        let dragNodeId = null;
        let panPointerId = null;
        let lastPanPoint = null;
        let hasPanned = false;
        let isDragging = false;

        const toCanvasPoint = (event) => {
            const rect = canvas.getBoundingClientRect();
            const scrollLeft = container && typeof container.scrollLeft === 'number' ? container.scrollLeft : 0;
            const scrollTop = container && typeof container.scrollTop === 'number' ? container.scrollTop : 0;
            return {
                x: event.clientX - rect.left + scrollLeft,
                y: event.clientY - rect.top + scrollTop,
            };
        };

        const refreshHoverState = (event, forceRedraw = false) => {
            const nextGraph = getAnimationGraph(container, rawGraph, state);
            const hitNode = window.ThemisDBGraphV2.GraphStore && window.ThemisDBGraphV2.GraphStore.resolveHitNode
                ? window.ThemisDBGraphV2.GraphStore.resolveHitNode(nextGraph, event, canvas)
                : null;
            const nextHoverNodeId = hitNode ? String(hitNode.id) : null;
            const hoverChanged = state.selection.hoverNodeId !== nextHoverNodeId;

            if (hoverChanged || forceRedraw) {
                state.selection.hoverNodeId = nextHoverNodeId;
                canvas.style.cursor = nextHoverNodeId ? 'pointer' : 'grab';
                scheduleGraphRender(container, nextGraph, state);
            } else if (nextHoverNodeId) {
                canvas.style.cursor = 'pointer';
            }

            return hitNode;
        };

        container.addEventListener('pointermove', (event) => {
            event.preventDefault();
            const point = toCanvasPoint(event);
            const nextGraph = getAnimationGraph(container, rawGraph, state);
            const hitNode = refreshHoverState(event);
            const nextHoverNodeId = hitNode ? String(hitNode.id) : null;

            if (dragNodeId) {
                const activeGraph = getAnimationGraph(container, rawGraph, state);
                const dragged = (activeGraph.nodes || []).find((node) => String(node.id) === dragNodeId);
                if (dragged) {
                    dragged.x = point.x;
                    dragged.y = point.y;
                    dragged.vx = 0;
                    dragged.vy = 0;
                    if (Number.isFinite(dragged.tx) && Number.isFinite(dragged.ty)) {
                        dragged.tx = point.x;
                        dragged.ty = point.y;
                    }
                    isDragging = true;
                    scheduleGraphRender(container, activeGraph, state);
                }
                return;
            }

            if (panPointerId === event.pointerId && lastPanPoint) {
                const dx = point.x - lastPanPoint.x;
                const dy = point.y - lastPanPoint.y;
                const nextScrollLeft = Math.max(0, (container.scrollLeft || 0) - dx);
                const nextScrollTop = Math.max(0, (container.scrollTop || 0) - dy);
                container.scrollLeft = nextScrollLeft;
                container.scrollTop = nextScrollTop;
                state.viewport.panX = nextScrollLeft;
                state.viewport.panY = nextScrollTop;
                lastPanPoint = point;
                hasPanned = true;
                scheduleGraphRender(container, getAnimationGraph(container, rawGraph, state), state);
                return;
            }

            if (state.selection.hoverNodeId !== nextHoverNodeId) {
                state.selection.hoverNodeId = nextHoverNodeId;
                canvas.style.cursor = nextHoverNodeId ? 'pointer' : 'grab';
                scheduleGraphRender(container, nextGraph, state);
            }
        });

        container.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            const nextGraph = getAnimationGraph(container, rawGraph, state);
            const hitNode = window.ThemisDBGraphV2.GraphStore && window.ThemisDBGraphV2.GraphStore.resolveHitNode
                ? window.ThemisDBGraphV2.GraphStore.resolveHitNode(nextGraph, event, canvas)
                : null;

            if (!hitNode) {
                panPointerId = event.pointerId;
                lastPanPoint = toCanvasPoint(event);
                hasPanned = false;
                canvas.setPointerCapture(event.pointerId);
                canvas.style.cursor = 'grabbing';
                state.viewport.panX = container.scrollLeft || 0;
                state.viewport.panY = container.scrollTop || 0;
                return;
            }

            dragNodeId = String(hitNode.id);
            canvas.setPointerCapture(event.pointerId);
            state.selection.nodeId = dragNodeId;
            state.selection.hoverNodeId = dragNodeId;
            isDragging = false;
            scheduleGraphRender(container, nextGraph, state);
        });

        const finishDrag = (event) => {
            if (dragNodeId && event && canvas.hasPointerCapture && canvas.hasPointerCapture(event.pointerId)) {
                canvas.releasePointerCapture(event.pointerId);
            }
            if (panPointerId === (event ? event.pointerId : null) && canvas.hasPointerCapture && canvas.hasPointerCapture(panPointerId)) {
                canvas.releasePointerCapture(panPointerId);
            }
            dragNodeId = null;
            panPointerId = null;
            lastPanPoint = null;
            isDragging = false;
            hasPanned = false;
            canvas.style.cursor = state.selection.hoverNodeId ? 'pointer' : 'grab';
        };

        container.addEventListener('pointerup', finishDrag);
        container.addEventListener('pointercancel', finishDrag);

        container.addEventListener('scroll', () => {
            state.viewport.panX = container.scrollLeft || 0;
            state.viewport.panY = container.scrollTop || 0;
            scheduleGraphRender(container, getAnimationGraph(container, rawGraph, state), state);
        }, { passive: true });

        container.addEventListener('pointerleave', () => {
            if (dragNodeId) {
                return;
            }
            if (state.selection.hoverNodeId !== null) {
                state.selection.hoverNodeId = null;
                canvas.style.cursor = 'grab';
                scheduleGraphRender(container, getAnimationGraph(container, rawGraph, state), state);
            }
        });

        container.addEventListener('click', (event) => {
            if (isDragging || hasPanned) {
                isDragging = false;
                hasPanned = false;
                return;
            }

            const nextGraph = getAnimationGraph(container, rawGraph, state);
            const hitNode = window.ThemisDBGraphV2.GraphStore && window.ThemisDBGraphV2.GraphStore.resolveHitNode
                ? window.ThemisDBGraphV2.GraphStore.resolveHitNode(nextGraph, event, canvas)
                : null;

            if (!hitNode) {
                state.selection.nodeId = null;
                updateNodeInfoPanel(container.closest('[data-graph-v2-shell]'), null);
            } else {
                state.selection.nodeId = String(hitNode.id);
                state.selection.hoverNodeId = String(hitNode.id);
                updateNodeInfoPanel(container.closest('[data-graph-v2-shell]'), hitNode);
            }
            scheduleGraphRender(container, nextGraph, state);
            startGraphAnimation(container, rawGraph, state, true);
        });

        container.addEventListener('dblclick', (event) => {
            if (isDragging || hasPanned) {
                return;
            }
            const nextGraph = getAnimationGraph(container, rawGraph, state);
            const hitNode = window.ThemisDBGraphV2.GraphStore && window.ThemisDBGraphV2.GraphStore.resolveHitNode
                ? window.ThemisDBGraphV2.GraphStore.resolveHitNode(nextGraph, event, canvas)
                : null;
            if (hitNode && hitNode.url) {
                window.open(hitNode.url, '_blank', 'noopener,noreferrer');
            }
        });

        container.addEventListener('wheel', (event) => {
            event.preventDefault();
            const activeGraph = getAnimationGraph(container, rawGraph, state);
            const zoomFactor = event.deltaY < 0 ? 1.08 : 0.92;
            const point = toCanvasPoint(event);
            const currentZoom = Number(state.viewport.zoom || 1);
            const nextZoom = Math.max(0.35, Math.min(2.8, currentZoom * zoomFactor));
            const zoomDelta = nextZoom / currentZoom;
            state.viewport.zoom = nextZoom;

            (activeGraph.nodes || []).forEach((node) => {
                const x = Number(node.x || 0);
                const y = Number(node.y || 0);
                node.x = point.x + ((x - point.x) * zoomDelta);
                node.y = point.y + ((y - point.y) * zoomDelta);
                if (Number.isFinite(node.tx) && Number.isFinite(node.ty)) {
                    node.tx = point.x + ((Number(node.tx) - point.x) * zoomDelta);
                    node.ty = point.y + ((Number(node.ty) - point.y) * zoomDelta);
                }
                node.vx = Number(node.vx || 0) * 0.75;
                node.vy = Number(node.vy || 0) * 0.75;
            });

            const nextWorldWidth = Math.max(state.viewport.width * 2.2, 1600) * nextZoom;
            const nextWorldHeight = Math.max(state.viewport.height * 2.2, 1100) * nextZoom;
            state.viewport.worldWidth = nextWorldWidth;
            state.viewport.worldHeight = nextWorldHeight;
            const canvas = container.querySelector('canvas');
            if (canvas) {
                canvas.style.width = nextWorldWidth + 'px';
                canvas.style.height = nextWorldHeight + 'px';
            }
            startGraphAnimation(container, rawGraph, state, false);
        }, { passive: false });
    }

    function bootstrapGraph() {
        normalizeGraphOverlayPlacement();
        window.addEventListener('resize', normalizeGraphOverlayPlacement, { passive: true });
        pruneDuplicateGraphInstances();

        const state = window.ThemisDBGraphV2.state;
        const containers = getContainers();
        const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };

        const shells = Array.from(document.querySelectorAll('[data-graph-v2-shell]'));
        if (shells.length > 1) {
            const keepShell = shells.find((shell) => shell.getAttribute('data-graph-v2-expanded') === '1') || shells[0];
            shells.forEach((shell) => {
                if (shell === keepShell) {
                    return;
                }
                const parent = shell.closest('.themisdb-graph-v2-overlay');
                if (parent) {
                    parent.remove();
                } else {
                    shell.remove();
                }
            });
        }

        document.querySelectorAll('[data-graph-v2-toggle]').forEach((toggle) => {
            toggle.style.display = 'inline-flex';
            toggle.style.visibility = 'visible';
            toggle.style.opacity = '1';

            const shell = toggle.closest('[data-graph-v2-shell]')
                || (toggle.dataset.graphV2Target ? document.getElementById(toggle.dataset.graphV2Target) : null)
                || document.querySelector('[data-role="graph-container"][data-graph-v2-shell="1"]');

            if (shell) {
                const canvas = shell.querySelector('[data-graph-v2-canvas]');
                if (!toggle.__themisdbGraphHandler && toggle.dataset.bound !== 'true') {
                    bindToggleButton(shell, canvas, toggle);
                }
            }
        });

        document.querySelectorAll('[data-graph-v2-shell]').forEach((shell) => {
            shell.style.display = shell.getAttribute('data-graph-v2-expanded') === '1' ? 'block' : 'none';
        });

        if (document.querySelector('.themisdb-graph-v2-toggle--compact')) {
            document.querySelectorAll('.themisdb-graph-v2-toggle:not(.themisdb-graph-v2-toggle--compact):not(.themisdb-graph-v2-header-toggle)').forEach((toggle) => {
                toggle.style.display = 'none';
                toggle.setAttribute('aria-hidden', 'true');
            });
        }

        document.querySelectorAll('[data-graph-v2-shell]').forEach((shell) => {
            const shellId = getShellId(shell);
            if (!shellId) {
                return;
            }

            const linked = getLinkedToggles(shell);
            const compactToggle = linked.find((toggle) => toggle.classList.contains('themisdb-graph-v2-toggle--compact'));
            if (!compactToggle) {
                return;
            }

            linked.forEach((toggle) => {
                if (toggle === compactToggle) {
                    return;
                }
                if (!toggle.classList.contains('themisdb-graph-v2-toggle--compact')) {
                    toggle.style.display = 'none';
                }
            });
        });

        if (!containers.length) {
            return;
        }

        containers.forEach((container) => {
            syncViewportToContainer(container, state);

            const shell = container.closest('[data-graph-v2-shell]');
            if (shell) {
                bindToggleButton(shell, container);
                injectHeaderBrandTrigger(shell);
                if (shell.getAttribute('data-graph-v2-expanded') === '1') {
                    startGraphAnimation(container, graphData, state, true);
                    bindInteraction(container, graphData, state);
                }
                return;
            }

            layoutGraph(graphData, state).then((visibleGraph) => {
                renderGraph(container, visibleGraph, state);
                startGraphAnimation(container, graphData, state, true);
                bindInteraction(container, graphData, state);
            }).catch(() => {
                const fallback = buildVisibleGraph(graphData, state);
                renderGraph(container, fallback, state);
                startGraphAnimation(container, graphData, state, true);
                bindInteraction(container, graphData, state);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapGraph, { once: true });
    } else {
        bootstrapGraph();
    }
})();
