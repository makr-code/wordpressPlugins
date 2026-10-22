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
        const orbitRadius = Math.max(90, Math.min(viewportWidth, viewportHeight) * 0.28);

        const clusters = new Map();
        nodes.forEach((node) => {
            const type = String(node.type || 'node');
            if (!clusters.has(type)) {
                clusters.set(type, []);
            }
            clusters.get(type).push(node);
        });

        const clusterKeys = Array.from(clusters.keys())
            .filter((key) => key !== 'home')
            .sort();

        const centerCluster = clusters.get('home') || [];
        centerCluster.forEach((node, index) => {
            node.tx = centerX + Math.cos(index * 0.9) * 12;
            node.ty = centerY + Math.sin(index * 0.9) * 12;
        });

        clusterKeys.forEach((clusterKey, clusterIndex) => {
            const group = clusters.get(clusterKey) || [];
            const angle = (clusterIndex / Math.max(1, clusterKeys.length)) * Math.PI * 2;
            const anchorX = centerX + Math.cos(angle) * orbitRadius;
            const anchorY = centerY + Math.sin(angle) * (orbitRadius * 0.8);
            const spread = Math.max(26, Math.min(88, Math.sqrt(group.length) * 12));

            group.forEach((node, index) => {
                const localAngle = (index / Math.max(1, group.length)) * Math.PI * 2;
                const localRadius = spread + ((index % 3) * 8);
                node.tx = anchorX + Math.cos(localAngle) * localRadius;
                node.ty = anchorY + Math.sin(localAngle) * localRadius;
            });
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
        const links = simulation.links || [];
        const effectiveDt = Math.max(0.6, Math.min(1.6, dt / 16.67));
        simulation.alpha = Math.max(
            Number(simulation.alphaTarget || 0.08),
            Number(simulation.alpha || 0.9) * Math.pow(Number(simulation.cooling || 0.985), effectiveDt)
        );
        const alpha = Number(simulation.alpha || 0.08);
        const repulsion = 2100 * (0.55 + alpha);
        const springStrength = 0.015 + (0.028 * alpha);
        const centerGravity = 0.11 + (0.15 * alpha);
        const velocityDamping = 0.9 + ((1 - alpha) * 0.06);
        const minDistance = 24;
        const nodeById = new Map(nodes.map((node) => [String(node.id), node]));

        for (let i = 0; i < nodes.length; i += 1) {
            const node = nodes[i];
            const level = Math.max(0, Number(node.level || 0));
            const anchorStrength = (level <= 1 ? 0.008 : 0.0025) * alpha;
            let fx = (Number(node.tx || 0) - Number(node.x || 0)) * anchorStrength;
            let fy = (Number(node.ty || 0) - Number(node.y || 0)) * anchorStrength;

            for (let j = 0; j < nodes.length; j += 1) {
                if (i === j) {
                    continue;
                }
                const other = nodes[j];
                const dx = Number(node.x || 0) - Number(other.x || 0);
                const dy = Number(node.y || 0) - Number(other.y || 0);
                const distanceSq = Math.max(minDistance * minDistance, (dx * dx) + (dy * dy));
                const force = (repulsion / distanceSq) * effectiveDt;
                fx += (dx / Math.sqrt(distanceSq)) * force;
                fy += (dy / Math.sqrt(distanceSq)) * force;
            }

            node.vx = (Number(node.vx || 0) + (fx * effectiveDt)) * velocityDamping;
            node.vy = (Number(node.vy || 0) + (fy * effectiveDt)) * velocityDamping;
        }

        links.forEach((link) => {
            const source = nodeById.get(String(link.source));
            const target = nodeById.get(String(link.target));
            if (!source || !target) {
                return;
            }

            const dx = Number(target.x || 0) - Number(source.x || 0);
            const dy = Number(target.y || 0) - Number(source.y || 0);
            const distance = Math.max(1, Math.sqrt((dx * dx) + (dy * dy)));
            const desired = 94 + ((Number(source.level || 0) + Number(target.level || 0)) * 12);
            const stretch = (distance - desired) * springStrength * effectiveDt;
            const nx = dx / distance;
            const ny = dy / distance;

            source.vx += nx * stretch;
            source.vy += ny * stretch;
            target.vx -= nx * stretch;
            target.vy -= ny * stretch;
        });

        const viewportWidth = Number(state.viewport.width || 800);
        const viewportHeight = Number(state.viewport.height || 420);
        const centerX = viewportWidth / 2;
        const centerY = viewportHeight / 2;
        const padding = 28;
        const maxX = viewportWidth - padding;
        const maxY = viewportHeight - padding;

        for (let i = 0; i < nodes.length; i += 1) {
            for (let j = i + 1; j < nodes.length; j += 1) {
                const a = nodes[i];
                const b = nodes[j];
                const dx = Number(a.x || 0) - Number(b.x || 0);
                const dy = Number(a.y || 0) - Number(b.y || 0);
                const dist = Math.sqrt((dx * dx) + (dy * dy)) || 0.0001;
                const ra = Math.max(9, Number(a.size || 12) * 0.55);
                const rb = Math.max(9, Number(b.size || 12) * 0.55);
                const minGap = ra + rb + 5;
                if (dist >= minGap) {
                    continue;
                }

                const overlap = (minGap - dist) / minGap;
                const push = overlap * (0.9 + alpha) * effectiveDt;
                const nx = dx / dist;
                const ny = dy / dist;
                a.vx += nx * push;
                a.vy += ny * push;
                b.vx -= nx * push;
                b.vy -= ny * push;
            }
        }

        nodes.forEach((node) => {
            node.vx += (centerX - Number(node.x || 0)) * centerGravity * 0.0018 * effectiveDt;
            node.vy += (centerY - Number(node.y || 0)) * centerGravity * 0.0018 * effectiveDt;
            node.vx = Math.max(-9, Math.min(9, Number(node.vx || 0)));
            node.vy = Math.max(-9, Math.min(9, Number(node.vy || 0)));

            node.x = Number(node.x || 0) + (Number(node.vx || 0) * effectiveDt);
            node.y = Number(node.y || 0) + (Number(node.vy || 0) * effectiveDt);

            if (node.x < padding) {
                node.x = padding + ((padding - node.x) * 0.4);
                node.vx = Math.abs(Number(node.vx || 0)) * 0.45;
            } else if (node.x > maxX) {
                node.x = maxX - ((node.x - maxX) * 0.4);
                node.vx = -Math.abs(Number(node.vx || 0)) * 0.45;
            }

            if (node.y < padding) {
                node.y = padding + ((padding - node.y) * 0.4);
                node.vy = Math.abs(Number(node.vy || 0)) * 0.45;
            } else if (node.y > maxY) {
                node.y = maxY - ((node.y - maxY) * 0.4);
                node.vy = -Math.abs(Number(node.vy || 0)) * 0.45;
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

    function bindToggleButton(shell, canvas, toggleOverride) {
        const toggle = toggleOverride || (shell ? shell.querySelector('[data-graph-v2-toggle]') : null);
        const closeButton = shell ? shell.querySelector('[data-graph-v2-close]') : null;
        if (!toggle || toggle.dataset.bound === 'true') {
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
                shell.setAttribute('data-graph-v2-expanded', shouldOpen ? '1' : '0');
                shell.classList.toggle('is-open', shouldOpen);
                shell.style.display = shouldOpen ? 'block' : 'none';
                if (canvas) {
                    canvas.hidden = !shouldOpen;
                }
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

        const handleToggle = () => {
            const shouldOpen = shell ? shell.getAttribute('data-graph-v2-expanded') !== '1' : true;
            setOpenState(shouldOpen);

            if (!canvas) {
                return;
            }

            if (!shouldOpen) {
                stopGraphAnimation(canvas);
                return;
            }

            requestAnimationFrame(() => {
                const state = window.ThemisDBGraphV2.state;
                const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };
                state.viewport.width = Math.max(320, canvas.clientWidth || canvas.getBoundingClientRect().width || 720);
                state.viewport.height = Math.max(320, canvas.clientHeight || canvas.getBoundingClientRect().height || 420);

                startGraphAnimation(canvas, graphData, state, true);
                bindInteraction(canvas, graphData, state);
            });
        };

        toggle.addEventListener('click', handleToggle);

        if (closeButton) {
            closeButton.addEventListener('click', () => {
                setOpenState(false);
                if (canvas) {
                    stopGraphAnimation(canvas);
                }
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
            shell.setAttribute('data-graph-v2-expanded', shouldOpen ? '1' : '0');
            shell.classList.toggle('is-open', shouldOpen);
            shell.style.display = shouldOpen ? 'block' : 'none';
            if (canvas) {
                canvas.hidden = !shouldOpen;
            }
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

        anchor.addEventListener('click', () => {
            const shouldOpen = shell.getAttribute('data-graph-v2-expanded') !== '1';
            setOpenState(shouldOpen);

            if (!shouldOpen) {
                if (canvas) {
                    stopGraphAnimation(canvas);
                }
                return;
            }

            requestAnimationFrame(() => {
                const state = window.ThemisDBGraphV2.state;
                const graphData = window.themisdbGraphV2 && window.themisdbGraphV2.graph ? window.themisdbGraphV2.graph : { nodes: [], links: [] };
                state.viewport.width = Math.max(320, canvas.clientWidth || canvas.getBoundingClientRect().width || 720);
                state.viewport.height = Math.max(320, canvas.clientHeight || canvas.getBoundingClientRect().height || 420);

                startGraphAnimation(canvas, graphData, state, true);
                bindInteraction(canvas, graphData, state);
            });
        });
    }

    function layoutRadial(nodes, width, height) {
        const centerX = width / 2;
        const centerY = height / 2;
        const radiusBase = Math.min(width, height) * 0.22;

        return nodes.map((node, index) => {
            const count = Math.max(1, nodes.length);
            const angle = (index / count) * Math.PI * 2;
            const radius = radiusBase + (Number(node.level || 0) * 18);

            return Object.assign({}, node, {
                x: centerX + Math.cos(angle) * radius,
                y: centerY + Math.sin(angle) * radius,
            });
        });
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
                filters: { minDegree: 0, maxVisibleNodes: 250, showExternal: true },
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

        const canvas = container.querySelector('canvas');
        if (!canvas) {
            return;
        }
        if (canvas.dataset.bound === 'true') {
            return;
        }
        canvas.dataset.bound = 'true';
        canvas.style.cursor = 'grab';
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

        canvas.style.pointerEvents = 'auto';

        canvas.addEventListener('pointermove', (event) => {
            event.preventDefault();
            const point = toCanvasPoint(event);
            const nextGraph = getAnimationGraph(container, rawGraph, state);
            const hitNode = window.ThemisDBGraphV2.GraphStore && window.ThemisDBGraphV2.GraphStore.resolveHitNode
                ? window.ThemisDBGraphV2.GraphStore.resolveHitNode(nextGraph, event, canvas)
                : null;
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
                    renderGraph(container, activeGraph, state);
                }
                return;
            }

            if (panPointerId === event.pointerId && lastPanPoint) {
                const dx = point.x - lastPanPoint.x;
                const dy = point.y - lastPanPoint.y;
                const activeGraph = getAnimationGraph(container, rawGraph, state);
                (activeGraph.nodes || []).forEach((node) => {
                    node.x = Number(node.x || 0) + dx;
                    node.y = Number(node.y || 0) + dy;
                    if (Number.isFinite(node.tx) && Number.isFinite(node.ty)) {
                        node.tx += dx;
                        node.ty += dy;
                    }
                });
                lastPanPoint = point;
                hasPanned = true;
                renderGraph(container, activeGraph, state);
                return;
            }

            if (state.selection.hoverNodeId !== nextHoverNodeId) {
                state.selection.hoverNodeId = nextHoverNodeId;
                canvas.style.cursor = nextHoverNodeId ? 'pointer' : 'grab';
                renderGraph(container, nextGraph, state);
            } else if (nextHoverNodeId) {
                canvas.style.cursor = 'pointer';
            }
        });

        canvas.addEventListener('pointerdown', (event) => {
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
                return;
            }
            dragNodeId = String(hitNode.id);
            canvas.setPointerCapture(event.pointerId);
            state.selection.nodeId = dragNodeId;
            state.selection.hoverNodeId = dragNodeId;
            isDragging = false;
            renderGraph(container, nextGraph, state);
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

        canvas.addEventListener('pointerup', finishDrag);
        canvas.addEventListener('pointercancel', finishDrag);

        canvas.addEventListener('pointerleave', () => {
            if (dragNodeId) {
                return;
            }
            if (state.selection.hoverNodeId !== null) {
                state.selection.hoverNodeId = null;
                canvas.style.cursor = 'grab';
                renderGraph(container, getAnimationGraph(container, rawGraph, state), state);
            }
        });

        canvas.addEventListener('click', (event) => {
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
            renderGraph(container, nextGraph, state);
            startGraphAnimation(container, rawGraph, state, true);
        });

        canvas.addEventListener('dblclick', (event) => {
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

        canvas.addEventListener('wheel', (event) => {
            event.preventDefault();
            const activeGraph = getAnimationGraph(container, rawGraph, state);
            const zoomFactor = event.deltaY < 0 ? 1.08 : 0.92;
            const point = toCanvasPoint(event);
            state.viewport.zoom = Math.max(0.35, Math.min(2.8, Number(state.viewport.zoom || 1) * zoomFactor));

            (activeGraph.nodes || []).forEach((node) => {
                const x = Number(node.x || 0);
                const y = Number(node.y || 0);
                node.x = point.x + ((x - point.x) * zoomFactor);
                node.y = point.y + ((y - point.y) * zoomFactor);
                if (Number.isFinite(node.tx) && Number.isFinite(node.ty)) {
                    node.tx = point.x + ((Number(node.tx) - point.x) * zoomFactor);
                    node.ty = point.y + ((Number(node.ty) - point.y) * zoomFactor);
                }
                node.vx = Number(node.vx || 0) * 0.75;
                node.vy = Number(node.vy || 0) * 0.75;
            });

            startGraphAnimation(container, rawGraph, state, false);
        }, { passive: false });
    }

    function bootstrapGraph() {
        normalizeGraphOverlayPlacement();
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
                if (!toggle.dataset.bound) {
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
