(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function getContainers() {
        return Array.from(document.querySelectorAll('[data-graph-v2-canvas]'));
    }

    function getContainerState(container) {
        const baseState = window.ThemisDBGraphV2.state;
        if (!container) {
            return baseState;
        }
        if (!container.__themisdbGraphState) {
            container.__themisdbGraphState = {
                viewport: Object.assign({}, baseState.viewport, { zoom: 1, panX: 0, panY: 0 }),
                selection: Object.assign({}, baseState.selection),
                filters: Object.assign({}, baseState.filters),
                layout: Object.assign({}, baseState.layout, { world: Object.assign({}, baseState.layout.world) }),
            };
        }
        return container.__themisdbGraphState;
    }

    function syncViewportToContainer(container, state) {
        if (!state || !state.viewport || !container) {
            return state;
        }

        const rect = container.getBoundingClientRect ? container.getBoundingClientRect() : { width: 0, height: 0 };
        const width = Math.max(320, Math.floor(rect.width || state.viewport.width || 800));
        const height = Math.max(260, Math.floor(rect.height || state.viewport.height || 420));
        const zoom = Math.max(0.35, Math.min(2.8, Number(state.viewport.zoom || 1)));

        state.viewport.width = width;
        state.viewport.height = height;
        state.viewport.worldWidth = Math.max(width, Math.max(Math.floor(width * 2.2), Math.min(Math.floor(width + 600), 2000), 1600) * zoom);
        state.viewport.worldHeight = Math.max(height, Math.max(Math.floor(height * 2.2), Math.min(Math.floor(height + 400), 1400), 1100) * zoom);
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

    function assignNetworkTargets(nodes, links, state, selectedNodeId) {
        const viewportWidth = Number(state && state.viewport ? (state.viewport.worldWidth || state.viewport.width) : 800) || 800;
        const viewportHeight = Number(state && state.viewport ? (state.viewport.worldHeight || state.viewport.height) : 420) || 420;
        const centerX = viewportWidth / 2;
        const centerY = viewportHeight / 2;
        const nodeById = new Map(nodes.map((node) => [String(node.id), node]));
        const adjacency = new Map(nodes.map((node) => [String(node.id), []]));
        (Array.isArray(links) ? links : []).forEach((link) => {
            const source = String(link.source);
            const target = String(link.target);
            if (adjacency.has(source) && adjacency.has(target)) {
                adjacency.get(source).push(target);
                adjacency.get(target).push(source);
            }
        });

        const anchorId = nodeById.has(String(selectedNodeId)) ? String(selectedNodeId) : (nodes[0] ? String(nodes[0].id) : null);
        const depthById = new Map();
        const parentById = new Map();
        const queue = anchorId ? [anchorId] : [];
        if (anchorId) {
            depthById.set(anchorId, 0);
        }

        for (let cursor = 0; cursor < queue.length; cursor += 1) {
            const currentId = queue[cursor];
            const neighbors = adjacency.get(currentId) || [];
            neighbors.forEach((neighborId) => {
                if (!depthById.has(neighborId)) {
                    depthById.set(neighborId, depthById.get(currentId) + 1);
                    parentById.set(neighborId, currentId);
                    queue.push(neighborId);
                }
            });
        }

        const maxRadius = Math.max(220, Math.min(viewportWidth, viewportHeight) * 0.46);
        const maxDepth = Math.max(1, ...depthById.values());
        const ringSpacing = Math.min(150, maxRadius / Math.min(4, maxDepth));
        const levels = new Map();
        nodes.forEach((node) => {
            const id = String(node.id);
            if (!depthById.has(id)) {
                depthById.set(id, 1);
            }
            const depth = depthById.get(id);
            if (!levels.has(depth)) {
                levels.set(depth, []);
            }
            levels.get(depth).push(node);
        });

        const angleById = new Map();
        angleById.set(anchorId, -Math.PI / 2);
        Array.from(levels.keys()).sort((a, b) => a - b).forEach((depth) => {
            const levelNodes = levels.get(depth);
            levelNodes.sort((a, b) => {
                const parentA = parentById.get(String(a.id)) || '';
                const parentB = parentById.get(String(b.id)) || '';
                return parentA.localeCompare(parentB) || String(a.id).localeCompare(String(b.id));
            });
            if (depth === 0) {
                levelNodes.forEach((node) => angleById.set(String(node.id), -Math.PI / 2));
                return;
            }

            const baseRadius = Math.min(maxRadius, 88 + (depth - 1) * ringSpacing);
            const minimumDistance = nodes.length > 100 ? 48 : 64;
            let cursor = 0;
            let ringIndex = 0;
            while (cursor < levelNodes.length) {
                const radius = Math.min(maxRadius, baseRadius + (ringIndex * minimumDistance * 1.25));
                const capacity = Math.max(8, Math.floor((Math.PI * 2 * radius) / minimumDistance));
                const nodesInRing = Math.min(capacity, levelNodes.length - cursor);
                for (let index = 0; index < nodesInRing; index += 1) {
                    const node = levelNodes[cursor + index];
                    const angle = -Math.PI / 2 + ((index + 0.5) / nodesInRing) * Math.PI * 2;
                    angleById.set(String(node.id), angle);
                    node.tx = centerX + Math.cos(angle) * radius;
                    node.ty = centerY + Math.sin(angle) * radius;
                }
                cursor += nodesInRing;
                ringIndex += 1;
            }
        });

        nodes.forEach((node) => {
            const id = String(node.id);
            const depth = depthById.get(id) || 0;
            if (depth === 0) {
                node.tx = centerX;
                node.ty = centerY;
            } else if (!Number.isFinite(node.tx) || !Number.isFinite(node.ty)) {
                const angle = angleById.get(id) ?? -Math.PI / 2;
                const radius = Math.min(maxRadius, 88 + (depth - 1) * ringSpacing);
                node.tx = centerX + Math.cos(angle) * radius;
                node.ty = centerY + Math.sin(angle) * radius;
            }
        });

        const targetPositions = nodes.map((node) => ({ x: node.tx, y: node.ty }));
        const minimumDistance = nodes.length > 100 ? 48 : 64;
        for (let iteration = 0; iteration < 36; iteration += 1) {
            const forces = nodes.map(() => ({ x: 0, y: 0 }));
            for (let i = 0; i < nodes.length; i += 1) {
                for (let j = i + 1; j < nodes.length; j += 1) {
                    const dx = nodes[j].tx - nodes[i].tx;
                    const dy = nodes[j].ty - nodes[i].ty;
                    const distance = Math.max(1, Math.hypot(dx, dy));
                    if (distance < minimumDistance) {
                        const force = (minimumDistance - distance) * 0.075 / distance;
                        forces[i].x -= dx * force;
                        forces[i].y -= dy * force;
                        forces[j].x += dx * force;
                        forces[j].y += dy * force;
                    }
                }
            }
            nodes.forEach((node, index) => {
                if (String(node.id) === anchorId) {
                    return;
                }
                const radialForce = 0.08;
                node.tx += forces[index].x + ((targetPositions[index].x - node.tx) * radialForce);
                node.ty += forces[index].y + ((targetPositions[index].y - node.ty) * radialForce);
            });
        }

        nodes.forEach((node) => {
            node.tx = Math.max(24, Math.min(viewportWidth - 24, node.tx));
            node.ty = Math.max(24, Math.min(viewportHeight - 24, node.ty));
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

        const previousPositions = new Map(previousById);
        const positionsById = new Map();
        previousPositions.forEach((node, id) => {
            if (Number.isFinite(node.x) && Number.isFinite(node.y)) {
                positionsById.set(id, { x: node.x, y: node.y });
            }
        });
        const anchor = nodes.find((node) => String(node.id) === String(state.selection.nodeId))
            || nodes.find((node) => String(node.type) === 'home')
            || nodes[0];
        const centerX = Number(state.viewport.worldWidth || state.viewport.width) / 2;
        const centerY = Number(state.viewport.worldHeight || state.viewport.height) / 2;
        const neighborMap = new Map(nodes.map((node) => [String(node.id), []]));
        links.forEach((link) => {
            const source = String(link.source);
            const target = String(link.target);
            if (neighborMap.has(source) && neighborMap.has(target)) {
                neighborMap.get(source).push(target);
                neighborMap.get(target).push(source);
            }
        });

        const animatedNodes = nodes.map((node) => {
            const prev = previousById.get(String(node.id));
            let x = prev && Number.isFinite(prev.x) ? prev.x : Number(node.tx);
            let y = prev && Number.isFinite(prev.y) ? prev.y : Number(node.ty);
            if (!prev) {
                const neighborPositions = (neighborMap.get(String(node.id)) || [])
                    .map((id) => positionsById.get(id))
                    .filter(Boolean);
                if (neighborPositions.length) {
                    x = neighborPositions.reduce((sum, position) => sum + position.x, 0) / neighborPositions.length;
                    y = neighborPositions.reduce((sum, position) => sum + position.y, 0) / neighborPositions.length;
                } else {
                    x = (Number(node.tx) + centerX) / 2;
                    y = (Number(node.ty) + centerY) / 2;
                }
            }

            return Object.assign({}, node, {
                x,
                y,
                tx: Number(node.tx),
                ty: Number(node.ty),
                vx: prev && Number.isFinite(prev.vx) ? prev.vx : 0,
                vy: prev && Number.isFinite(prev.vy) ? prev.vy : 0,
            });
        });

        return {
            nodes: animatedNodes,
            links: links.map((link) => Object.assign({}, link)),
        };
    }

    function stepAnimatedGraph(simulation, state, dt) {
        if (!simulation || !Array.isArray(simulation.nodes)) {
            return;
        }

        const nodes = simulation.nodes;
        const elapsed = Math.max(8, Math.min(40, dt)) / 1000;
        const viewportWidth = Number(state && state.viewport ? (state.viewport.worldWidth || state.viewport.width) : 800) || 800;
        const viewportHeight = Number(state && state.viewport ? (state.viewport.worldHeight || state.viewport.height) : 420) || 420;
        const spring = 24;
        const damping = Math.exp(-9 * elapsed);
        let moving = false;

        nodes.forEach((node) => {
            const dx = Number(node.tx) - Number(node.x);
            const dy = Number(node.ty) - Number(node.y);
            node.vx = (Number(node.vx || 0) + (dx * spring * elapsed)) * damping;
            node.vy = (Number(node.vy || 0) + (dy * spring * elapsed)) * damping;
            node.x += node.vx * elapsed;
            node.y += node.vy * elapsed;

            if (Math.abs(dx) > 0.6 || Math.abs(dy) > 0.6 || Math.abs(node.vx) > 3 || Math.abs(node.vy) > 3) {
                moving = true;
            } else {
                node.x = Number(node.tx);
                node.y = Number(node.ty);
                node.vx = 0;
                node.vy = 0;
            }
        });
        return moving && viewportWidth > 0 && viewportHeight > 0;
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
        running.frameId = 0;
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
        if (needsRebuild && existing && existing.frameId) {
            cancelAnimationFrame(existing.frameId);
            existing.running = false;
        }
        const simulation = needsRebuild
            ? buildAnimatedGraph(baseGraph, existing, state)
            : existing;

        simulation.signature = signature;
        simulation.running = true;
        simulation.lastFrameTime = performance.now();
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
            const stillMoving = stepAnimatedGraph(current, state, dt);
            renderGraph(container, { nodes: current.nodes, links: current.links }, state);
            if (stillMoving) {
                current.frameId = requestAnimationFrame(tick);
            } else {
                current.frameId = 0;
                current.running = false;
            }
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
        const viewportWidth = document.documentElement.clientWidth || window.innerWidth || 1280;
        const safeGap = 12;
        const maxWidth = Math.min(820, Math.max(260, viewportWidth - (safeGap * 2)));
        const maxHeight = Math.min(560, Math.max(260, viewportHeight - (safeGap * 2)));

        shell.style.setProperty('position', 'fixed', 'important');
        shell.style.setProperty('top', 'auto', 'important');
        shell.style.setProperty('left', 'auto', 'important');
        shell.style.setProperty('right', safeGap + 'px', 'important');
        shell.style.setProperty('bottom', safeGap + 'px', 'important');
        shell.style.setProperty('margin', '0', 'important');
        shell.style.setProperty('transform', 'none', 'important');
        shell.style.setProperty('max-width', 'calc(100vw - ' + (safeGap * 2) + 'px)', 'important');
        shell.style.setProperty('max-height', 'calc(100vh - ' + (safeGap * 2) + 'px)', 'important');
        shell.style.setProperty('width', Math.min(maxWidth, viewportWidth - (safeGap * 2)) + 'px', 'important');
        shell.style.setProperty('height', Math.min(maxHeight, viewportHeight - (safeGap * 2)) + 'px', 'important');

        const canvas = shell.querySelector('[data-graph-v2-canvas]');
        if (canvas) {
            canvas.style.setProperty('max-width', '100%', 'important');
            canvas.style.setProperty('max-height', '100%', 'important');
            canvas.style.setProperty('overflow', 'auto', 'important');
            canvas.style.setProperty('width', '100%', 'important');
        }
    }

    function centerGraphViewport(container, state) {
        if (!container || !state || !state.viewport) {
            return;
        }

        const viewport = state.viewport;
        const visibleWidth = Number(viewport.width || container.clientWidth || 0) || 0;
        const visibleHeight = Number(viewport.height || container.clientHeight || 0) || 0;
        const worldWidth = Number(viewport.worldWidth || container.scrollWidth || 0) || 0;
        const worldHeight = Number(viewport.worldHeight || container.scrollHeight || 0) || 0;

        if (!visibleWidth || !visibleHeight || !worldWidth || !worldHeight) {
            return;
        }

        if (worldWidth <= visibleWidth) {
            container.scrollLeft = 0;
            viewport.panX = 0;
        } else {
            const nextLeft = Math.max(0, Math.round((worldWidth - visibleWidth) / 2));
            container.scrollLeft = nextLeft;
            viewport.panX = nextLeft;
        }

        if (worldHeight <= visibleHeight) {
            container.scrollTop = 0;
            viewport.panY = 0;
        } else {
            const nextTop = Math.max(0, Math.round((worldHeight - visibleHeight) / 2));
            container.scrollTop = nextTop;
            viewport.panY = nextTop;
        }
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
        shell.style.setProperty('display', shouldOpen ? 'flex' : 'none', 'important');

        if (canvas) {
            canvas.hidden = !shouldOpen;
        }

        requestAnimationFrame(() => {
            clampGraphShellToViewport(shell);
            if (shouldOpen && canvas) {
                centerGraphViewport(canvas, getContainerState(canvas));
            }
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
        const completeGraph = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : visibleGraph;
        const totalNodes = Number((graphData && graphData.stats && graphData.stats.totalNodes)
            || (completeGraph.stats && completeGraph.stats.nodeCount)
            || visibleGraph.nodes.length
            || 0);
        const totalLinks = Number((graphData && graphData.stats && graphData.stats.totalLinks)
            || (completeGraph.stats && completeGraph.stats.linkCount)
            || (completeGraph.links ? completeGraph.links.length : 0)
            || 0);
        const visibleNodes = Array.isArray(visibleGraph.nodes) ? visibleGraph.nodes.length : 0;
        const visibleLinks = Array.isArray(visibleGraph.links) ? visibleGraph.links.length : 0;
        const reduction = graphData && graphData.stats && graphData.stats.reduction ? graphData.stats.reduction : null;
        const isFocused = visibleNodes < totalNodes;
        const modeLabel = reduction && reduction.active
            ? ('Reduced: ' + (reduction.strategy || 'dynamic'))
            : (isFocused ? 'Focused view' : 'Full view');

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
            '  <div class="themisdb-graph-v2-node-panel__related"></div>',
            '</div>'
        ].join('');

        const closeButton = panel.querySelector('.themisdb-graph-v2-node-panel__close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                panel.hidden = true;
                const canvas = shell.querySelector('[data-graph-v2-canvas]');
                if (canvas) {
                    const state = getContainerState(canvas);
                    state.selection.nodeId = null;
                    state.selection.hoverNodeId = null;
                    startGraphAnimation(canvas, window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] }, state, true);
                }
            });
        }

        shell.appendChild(panel);
        return panel;
    }

    function ensureGraphNavigationTools(shell, container, graphData, state) {
        if (!shell || !container) {
            return null;
        }

        const labels = (window.themisdbGraphV2 && window.themisdbGraphV2.labels) || {};
        let toolbar = shell.querySelector('.themisdb-graph-v2-navigation');
        if (!toolbar) {
            toolbar = document.createElement('div');
            toolbar.className = 'themisdb-graph-v2-navigation';
            toolbar.innerHTML = [
                '<label class="screen-reader-text" for="' + escapeHtml(getShellId(shell)) + '-search">' + escapeHtml(labels.search || 'Search graph') + '</label>',
                '<input id="' + escapeHtml(getShellId(shell)) + '-search" type="search" data-graph-v2-search autocomplete="off" placeholder="' + escapeHtml(labels.searchPlaceholder || 'Search nodes…') + '" />',
                '<div class="themisdb-graph-v2-navigation__actions">',
                '  <button type="button" data-graph-v2-back aria-label="' + escapeHtml(labels.back || 'Previous selection') + '" title="' + escapeHtml(labels.back || 'Previous selection') + '" disabled>‹</button>',
                '  <button type="button" data-graph-v2-forward aria-label="' + escapeHtml(labels.forward || 'Next selection') + '" title="' + escapeHtml(labels.forward || 'Next selection') + '" disabled>›</button>',
                '</div>',
                '<div class="themisdb-graph-v2-search-results" data-graph-v2-search-results role="listbox" hidden></div>',
                '<span class="screen-reader-text" data-graph-v2-search-status aria-live="polite"></span>',
            ].join('');
            const canvas = shell.querySelector('[data-graph-v2-canvas]');
            shell.insertBefore(toolbar, canvas || null);
        }

        const search = toolbar.querySelector('[data-graph-v2-search]');
        const results = toolbar.querySelector('[data-graph-v2-search-results]');
        const status = toolbar.querySelector('[data-graph-v2-search-status]');
        const back = toolbar.querySelector('[data-graph-v2-back]');
        const forward = toolbar.querySelector('[data-graph-v2-forward]');
        const history = container.__themisdbGraphNavigation || { entries: [], index: -1 };
        if (history.entries.length === 0 && state.selection.nodeId) {
            history.entries.push(String(state.selection.nodeId));
            history.index = 0;
        }

        const updateHistoryButtons = () => {
            if (back) {
                back.disabled = history.index <= 0;
            }
            if (forward) {
                forward.disabled = history.index < 0 || history.index >= history.entries.length - 1;
            }
        };

        const selectNode = (nodeId, recordHistory = true) => {
            const node = (graphData.nodes || []).find((candidate) => String(candidate.id) === String(nodeId));
            if (!node) {
                return;
            }

            if (recordHistory && String(history.entries[history.index] || '') !== String(node.id)) {
                history.entries = history.entries.slice(0, history.index + 1);
                history.entries.push(String(node.id));
                history.index = history.entries.length - 1;
            }
            container.__themisdbGraphNavigation = history;
            updateHistoryButtons();
            state.selection.nodeId = String(node.id);
            state.selection.hoverNodeId = String(node.id);
            updateNodeInfoPanel(shell, node, graphData, selectNode);
            centerGraphViewport(container, state);
            startGraphAnimation(container, graphData, state, true);
        };

        if (search && !search.dataset.bound) {
            search.addEventListener('input', () => {
                const query = search.value.trim().toLocaleLowerCase();
                results.replaceChildren();
                if (!query) {
                    results.hidden = true;
                    status.textContent = '';
                    return;
                }

                const matches = (graphData.nodes || [])
                    .filter((node) => String(node.label || '').toLocaleLowerCase().includes(query)
                        || String(node.type || '').toLocaleLowerCase().includes(query))
                    .filter((node) => state.filters.showExternal || String(node.type) !== 'external_resource')
                    .slice(0, 10);
                matches.forEach((node) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'themisdb-graph-v2-search-result';
                    button.setAttribute('role', 'option');
                    button.textContent = node.label + ' · ' + String(node.type || 'node').replace(/_/g, ' ');
                    button.addEventListener('click', () => {
                        selectNode(node.id);
                        results.hidden = true;
                        search.value = node.label || '';
                        status.textContent = (labels.selected || 'Selected') + ': ' + (node.label || '');
                    });
                    results.appendChild(button);
                });
                results.hidden = matches.length === 0;
                status.textContent = matches.length
                    ? (labels.results || 'Search results') + ': ' + matches.length
                    : (labels.noResults || 'No matching nodes');
            });
            search.dataset.bound = 'true';
        }

        if (back && !back.dataset.bound) {
            back.addEventListener('click', () => {
                if (history.index > 0) {
                    history.index -= 1;
                    selectNode(history.entries[history.index], false);
                }
            });
            back.dataset.bound = 'true';
        }
        if (forward && !forward.dataset.bound) {
            forward.addEventListener('click', () => {
                if (history.index < history.entries.length - 1) {
                    history.index += 1;
                    selectNode(history.entries[history.index], false);
                }
            });
            forward.dataset.bound = 'true';
        }

        container.__themisdbGraphNavigation = history;
        container.__themisdbGraphNavigation.select = selectNode;
        updateHistoryButtons();
        return container.__themisdbGraphNavigation;
    }

    function updateNodeInfoPanel(shell, node, graphData, selectNode) {
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
        const relatedEl = panel.querySelector('.themisdb-graph-v2-node-panel__related');
        if (!titleEl || !metaEl || !actionsEl || !relatedEl) {
            return;
        }

        const label = node.label || node.title || 'Untitled node';
        const type = String(node.type || 'node').replace(/_/g, ' ');
        const degree = Number(node.degree || 0);
        const url = node.url || '';
        const description = node.metadata && typeof node.metadata === 'object' && node.metadata.excerpt ? node.metadata.excerpt : '';

        titleEl.textContent = label;
        metaEl.innerHTML = [
            '<dl>',
            '  <div><dt>Type</dt><dd>' + escapeHtml(type) + '</dd></div>',
            '  <div><dt>Degree</dt><dd>' + escapeHtml(String(degree)) + '</dd></div>',
            '  <div><dt>URL</dt><dd>' + (url ? '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">Open page</a>' : 'No direct URL') + '</dd></div>',
            description ? '  <div class="themisdb-graph-v2-node-panel__description"><dt>About</dt><dd>' + escapeHtml(description) + '</dd></div>' : '',
            '</dl>'
        ].join('');

        actionsEl.innerHTML = url
            ? '<a class="themisdb-graph-v2-node-panel__button" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">Open node</a>'
            : '<span class="themisdb-graph-v2-node-panel__button is-disabled">No direct link</span>';

        const nodeMap = new Map((graphData && graphData.nodes || []).map((candidate) => [String(candidate.id), candidate]));
        const neighbors = (node.neighbors || [])
            .map((nodeId) => nodeMap.get(String(nodeId)))
            .filter(Boolean)
            .slice(0, 8);
        relatedEl.replaceChildren();
        if (neighbors.length) {
            const heading = document.createElement('h4');
            heading.textContent = 'Connected nodes';
            relatedEl.appendChild(heading);
            neighbors.forEach((neighbor) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'themisdb-graph-v2-node-panel__related-link';
                button.textContent = neighbor.label + ' · ' + String(neighbor.type || 'node').replace(/_/g, ' ');
                button.addEventListener('click', () => selectNode && selectNode(neighbor.id));
                relatedEl.appendChild(button);
            });
        }

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
            canvas = window.ThemisDBGraphV2.GraphView.ensureCanvas(container, getContainerState(container));
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

            const state = getContainerState(renderContainer);
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
        document.querySelectorAll('[data-graph-v2-shell]').forEach((shell) => {
            if (shell.parentElement !== document.body) {
                document.body.appendChild(shell);
            }
        });

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

            const state = getContainerState(canvas);
            const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };
            state.viewport.width = Math.max(320, canvas.clientWidth || canvas.getBoundingClientRect().width || 720);
            state.viewport.height = Math.max(320, canvas.clientHeight || canvas.getBoundingClientRect().height || 420);

            startGraphAnimation(canvas, graphData, state, true);
            bindInteraction(canvas, graphData, state);
        });
    }

    function normalizeNodeUrl(value) {
        if (!value) {
            return '';
        }

        const raw = String(value).trim();
        if (!raw) {
            return '';
        }

        try {
            const parsed = new URL(raw, window.location.origin || 'http://localhost');
            parsed.hash = '';
            parsed.search = '';
            if (parsed.origin && parsed.pathname) {
                return (parsed.origin + parsed.pathname).replace(/\/+$/, '');
            }
            return raw.replace(/\/+$/, '');
        } catch (error) {
            return raw.replace(/\/+$/, '');
        }
    }

    function resolveCurrentPageNodeId(graphData, state) {
        if (!graphData || !Array.isArray(graphData.nodes) || !graphData.nodes.length) {
            return null;
        }

        const matches = new Set();
        const currentUrl = normalizeNodeUrl(window.location.href);
        const currentPath = normalizeNodeUrl(window.location.pathname || '/');
        if (currentUrl) {
            matches.add(currentUrl);
        }
        if (currentPath) {
            matches.add(currentPath);
            matches.add((window.location.origin || '') + currentPath);
        }

        const candidates = Array.from(matches);
        for (const node of graphData.nodes) {
            const candidateUrls = [node.url, node.permalink, node.href, node.link].filter(Boolean);
            for (const candidateUrl of candidateUrls) {
                const normalized = normalizeNodeUrl(candidateUrl);
                if (!normalized) {
                    continue;
                }

                if (candidates.includes(normalized) || normalized.endsWith(currentPath) || currentUrl.endsWith(normalized)) {
                    return String(node.id);
                }
            }
        }

        if (state && state.selection && state.selection.nodeId) {
            return String(state.selection.nodeId);
        }

        return null;
    }

    function layoutNetwork(nodes, links, width, height, selectedNodeId) {
        const positioned = nodes.map((node) => Object.assign({}, node));
        assignNetworkTargets(positioned, links, {
            viewport: { worldWidth: width, worldHeight: height },
        }, selectedNodeId);
        positioned.forEach((node) => {
            node.x = node.tx;
            node.y = node.ty;
        });
        return positioned;
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

        const width = effectiveState.viewport.worldWidth || effectiveState.viewport.width || 800;
        const height = effectiveState.viewport.worldHeight || effectiveState.viewport.height || 420;
        const selectedNodeId = effectiveState.selection.nodeId || resolveCurrentPageNodeId(graphData, effectiveState);
        const positionedNodes = layoutNetwork(visible.nodes, visible.links, width, height, selectedNodeId);
        return Object.assign({}, visible, { nodes: positionedNodes });
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

        if (!container.dataset.themisdbInitialCentered) {
            centerGraphViewport(container, state);
            container.dataset.themisdbInitialCentered = 'true';
        }

        const canvas = window.ThemisDBGraphV2.GraphView && window.ThemisDBGraphV2.GraphView.ensureCanvas
            ? window.ThemisDBGraphV2.GraphView.ensureCanvas(container, state)
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
            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
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
                const dx = event.clientX - lastPanPoint.x;
                const dy = event.clientY - lastPanPoint.y;
                const nextScrollLeft = Math.max(0, (container.scrollLeft || 0) - dx);
                const nextScrollTop = Math.max(0, (container.scrollTop || 0) - dy);
                container.scrollLeft = nextScrollLeft;
                container.scrollTop = nextScrollTop;
                state.viewport.panX = nextScrollLeft;
                state.viewport.panY = nextScrollTop;
                lastPanPoint = { x: event.clientX, y: event.clientY };
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
                lastPanPoint = { x: event.clientX, y: event.clientY };
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
                const navigation = container.__themisdbGraphNavigation;
                if (navigation && typeof navigation.select === 'function') {
                    navigation.select(hitNode.id);
                    return;
                } else {
                    updateNodeInfoPanel(container.closest('[data-graph-v2-shell]'), hitNode, rawGraph);
                }
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

            const nextWorldWidth = Math.max(state.viewport.width, Math.max(state.viewport.width * 2.2, 1600) * nextZoom);
            const nextWorldHeight = Math.max(state.viewport.height, Math.max(state.viewport.height * 2.2, 1100) * nextZoom);
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

        const containers = getContainers();
        const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };

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
            shell.style.display = shell.getAttribute('data-graph-v2-expanded') === '1' ? 'flex' : 'none';
        });

        if (!containers.length) {
            return;
        }

        containers.forEach((container) => {
            const state = getContainerState(container);
            const activePageNodeId = resolveCurrentPageNodeId(graphData, state);
            if (activePageNodeId) {
                state.selection.nodeId = String(activePageNodeId);
                state.selection.hoverNodeId = String(activePageNodeId);
            }
            syncViewportToContainer(container, state);

            const shell = container.closest('[data-graph-v2-shell]');
            if (shell) {
                ensureGraphNavigationTools(shell, container, graphData, state);
                bindToggleButton(shell, container);
                injectHeaderBrandTrigger(shell);
                if (shell.getAttribute('data-graph-v2-expanded') === '1') {
                    startGraphAnimation(container, graphData, state, true);
                    bindInteraction(container, graphData, state);
                }
                return;
            }

            startGraphAnimation(container, graphData, state, true);
            bindInteraction(container, graphData, state);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapGraph, { once: true });
    } else {
        bootstrapGraph();
    }
})();
