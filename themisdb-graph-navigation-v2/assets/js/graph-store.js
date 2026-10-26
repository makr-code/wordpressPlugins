(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    const STORAGE_PREFIX = 'themisdbGraphV2';
    const GRAPH_CACHE_TTL_MS = 30 * 60 * 1000;
    const preparedGraphCache = new Map();

    function getStorage() {
        try {
            if (!window.localStorage) {
                return null;
            }
            const testKey = '__themisdb_graph_storage_test__';
            window.localStorage.setItem(testKey, '1');
            window.localStorage.removeItem(testKey);
            return window.localStorage;
        } catch (error) {
            return null;
        }
    }

    function getGraphCacheKey(rawGraph) {
        const config = window.themisdbGraphV2 || {};
        const graphFingerprint = rawGraph && rawGraph.nodes && rawGraph.nodes.length
            ? `${rawGraph.nodes.length}:${rawGraph.links ? rawGraph.links.length : 0}`
            : 'empty';
        const versionKey = config.graphVersion || config.cacheKey || 'default';
        const siteKey = (config.cacheKey || config.graphVersion || window.location.hostname || 'themisdb-graph') + ':' + versionKey + ':' + graphFingerprint;
        return `${STORAGE_PREFIX}:graph:${siteKey}`;
    }

    function readGraphFromStorage(rawGraph) {
        const storage = getStorage();
        if (!storage) {
            return rawGraph || null;
        }

        try {
            const cacheKey = getGraphCacheKey(rawGraph || {});
            const cached = storage.getItem(cacheKey);
            if (!cached) {
                return rawGraph || null;
            }

            const payload = JSON.parse(cached);
            const hasValidPayload = payload && payload.graph && Array.isArray(payload.graph.nodes);
            if (!hasValidPayload) {
                return rawGraph || null;
            }

            const currentVersion = (window.themisdbGraphV2 && (window.themisdbGraphV2.graphVersion || window.themisdbGraphV2.cacheKey)) || null;
            if (currentVersion && payload.version && String(payload.version) !== String(currentVersion)) {
                storage.removeItem(cacheKey);
                return rawGraph || null;
            }

            if (Number(payload.expiresAt || 0) < Date.now()) {
                storage.removeItem(cacheKey);
                return rawGraph || null;
            }

            return payload.graph;
        } catch (error) {
            return rawGraph || null;
        }
    }

    function writeGraphToStorage(graph) {
        const storage = getStorage();
        if (!storage || !graph || !Array.isArray(graph.nodes)) {
            return;
        }

        try {
            storage.setItem(getGraphCacheKey(graph), JSON.stringify({
                graph,
                version: (window.themisdbGraphV2 && (window.themisdbGraphV2.graphVersion || window.themisdbGraphV2.cacheKey)) || null,
                expiresAt: Date.now() + GRAPH_CACHE_TTL_MS,
            }));
        } catch (error) {
            // Storage may be unavailable or full; fail silently and keep the runtime graph in memory.
        }
    }

    function resolveRuntimeGraph(graph) {
        if (!graph || !Array.isArray(graph.nodes)) {
            return readGraphFromStorage(null) || { nodes: [], links: [] };
        }

        const cachedGraph = readGraphFromStorage(graph);
        const activeGraph = cachedGraph || graph;
        writeGraphToStorage(activeGraph);
        return activeGraph;
    }

    function getPreparedGraph(graph, options) {
        const cacheKey = JSON.stringify({
            nodes: Array.isArray(graph && graph.nodes) ? graph.nodes.length : 0,
            links: Array.isArray(graph && graph.links) ? graph.links.length : 0,
            options: Object.assign({ maxVisibleNodes: 120, minDegree: 0, showExternal: true }, options || {}),
        });

        if (preparedGraphCache.has(cacheKey)) {
            return preparedGraphCache.get(cacheKey);
        }

        const prepared = buildGraphModel(graph, options);
        preparedGraphCache.set(cacheKey, prepared);
        return prepared;
    }

    function getReductionProfile(nodeCount, options) {
        const localOptions = Object.assign({
            maxVisibleNodes: 120,
            minDegree: 0,
            showExternal: true,
        }, options || {});

        const maxVisibleNodes = Math.max(30, Number(localOptions.maxVisibleNodes) || 120);
        const minDegree = Math.max(0, Number(localOptions.minDegree) || 0);

        if (nodeCount <= 120) {
            return {
                active: false,
                strategy: 'full',
                maxVisibleNodes,
                minDegree,
            };
        }

        if (nodeCount > 1200) {
            return {
                active: true,
                strategy: 'aggressive',
                maxVisibleNodes: Math.min(maxVisibleNodes, 120),
                minDegree: Math.max(minDegree, 2),
            };
        }

        if (nodeCount > 600) {
            return {
                active: true,
                strategy: 'moderate',
                maxVisibleNodes: Math.min(maxVisibleNodes, 180),
                minDegree: Math.max(minDegree, 1),
            };
        }

        return {
            active: true,
            strategy: 'light',
            maxVisibleNodes: Math.min(maxVisibleNodes, 220),
            minDegree: Math.max(minDegree, 0),
        };
    }

    function calculateVisibleGraph(graph, options) {
        const nodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
        const links = Array.isArray(graph && graph.links) ? graph.links : [];
        const localOptions = Object.assign({
            maxVisibleNodes: 120,
            minDegree: 0,
            showExternal: true,
            selectedNodeId: null,
        }, options || {});

        const reduction = getReductionProfile(nodes.length, localOptions);
        const selectedNodeId = localOptions.selectedNodeId ? String(localOptions.selectedNodeId) : '';
        const nodeMap = new Map(nodes.map((node) => [String(node.id), Object.assign({}, node)]));
        const adjacency = new Map();

        links.forEach((link) => {
            const sourceId = String(link.source);
            const targetId = String(link.target);
            if (!nodeMap.has(sourceId) || !nodeMap.has(targetId)) {
                return;
            }

            if (!adjacency.has(sourceId)) {
                adjacency.set(sourceId, []);
            }
            if (!adjacency.has(targetId)) {
                adjacency.set(targetId, []);
            }
            adjacency.get(sourceId).push(targetId);
            adjacency.get(targetId).push(sourceId);
        });

        const degreeScore = (node) => (Number(node.degree || 0) * 2) + (String(node.type) === 'home' ? 10 : 0);

        const preferred = new Set();
        if (selectedNodeId) {
            preferred.add(selectedNodeId);
            (adjacency.get(selectedNodeId) || []).forEach((neighborId) => preferred.add(String(neighborId)));
        }

        const filtered = nodes.filter((node) => {
            const nodeId = String(node.id);
            const isExternal = String(node.type) === 'external_resource';
            if (!localOptions.showExternal && isExternal) {
                return false;
            }
            if (selectedNodeId && preferred.has(nodeId)) {
                return true;
            }
            if ((node.degree || 0) < reduction.minDegree) {
                return false;
            }
            return !selectedNodeId || preferred.has(nodeId);
        });

        const ranked = filtered
            .map((node) => ({ node, score: degreeScore(node) }))
            .sort((a, b) => b.score - a.score || (b.node.degree || 0) - (a.node.degree || 0))
            .map((entry) => entry.node);

        const rootNodes = ranked.filter((node) => String(node.type) === 'home' || String(node.type) === 'category' || String(node.type) === 'tag');
        const contentNodes = ranked.filter((node) => !rootNodes.includes(node));
        const topNodes = rootNodes.concat(contentNodes).slice(0, reduction.maxVisibleNodes);
        const topNodeSet = new Set(topNodes.map((node) => String(node.id)));

        if (selectedNodeId) {
            const selectedNeighbors = new Set([selectedNodeId]);
            (adjacency.get(selectedNodeId) || []).forEach((neighborId) => selectedNeighbors.add(String(neighborId)));
            const expanded = nodes.filter((node) => selectedNeighbors.has(String(node.id)));
            expanded.sort((a, b) => (b.degree || 0) - (a.degree || 0));
            expanded.slice(0, reduction.maxVisibleNodes).forEach((node) => topNodeSet.add(String(node.id)));
        }

        const visibleNodes = nodes.filter((node) => {
            const nodeId = String(node.id);
            if (!topNodeSet.has(nodeId)) {
                return false;
            }
            if (!localOptions.showExternal && String(node.type) === 'external_resource') {
                return false;
            }
            return true;
        });

        const visibleNodeSet = new Set(visibleNodes.map((node) => String(node.id)));
        const visibleLinks = links.filter((link) => {
            const sourceId = String(link.source);
            const targetId = String(link.target);
            return visibleNodeSet.has(sourceId) && visibleNodeSet.has(targetId);
        });

        return {
            nodes: visibleNodes,
            links: visibleLinks,
            stats: {
                totalNodes: nodes.length,
                totalLinks: links.length,
                visibleNodes: visibleNodes.length,
                visibleLinks: visibleLinks.length,
                reduction,
            },
        };
    }

    function resolveHitNode(graph, pointerX, pointerY, canvas) {
        const nodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
        const activeCanvas = canvas
            || (pointerY && typeof pointerY.getBoundingClientRect === 'function' ? pointerY : null)
            || (pointerX && typeof pointerX.getBoundingClientRect === 'function' ? pointerX : null);

        if (!activeCanvas || !nodes.length) {
            return null;
        }

        let clientX = Number.NaN;
        let clientY = Number.NaN;

        if (pointerX && typeof pointerX === 'object') {
            if ('clientX' in pointerX && 'clientY' in pointerX) {
                clientX = Number(pointerX.clientX);
                clientY = Number(pointerX.clientY);
            } else if (pointerX instanceof Element && typeof pointerX.getBoundingClientRect === 'function') {
                const rect = pointerX.getBoundingClientRect();
                clientX = rect.left + rect.width / 2;
                clientY = rect.top + rect.height / 2;
            } else if (pointerY && typeof pointerY === 'object' && 'clientX' in pointerY && 'clientY' in pointerY) {
                clientX = Number(pointerY.clientX);
                clientY = Number(pointerY.clientY);
            }
        } else if (typeof pointerX === 'number' || typeof pointerY === 'number') {
            clientX = Number(pointerX);
            clientY = Number(pointerY);
        }

        const rect = activeCanvas.getBoundingClientRect();
        const hasViewportCoords = Number.isFinite(clientX) && Number.isFinite(clientY)
            && Number.isFinite(rect.left) && Number.isFinite(rect.top)
            && Number.isFinite(rect.width) && Number.isFinite(rect.height);

        const container = activeCanvas && activeCanvas.parentElement ? activeCanvas.parentElement : null;
        const scrollLeft = container && typeof container.scrollLeft === 'number' ? container.scrollLeft : 0;
        const scrollTop = container && typeof container.scrollTop === 'number' ? container.scrollTop : 0;

        const localX = hasViewportCoords ? (clientX - rect.left + scrollLeft) : (Number.isFinite(clientX) ? clientX : 0);
        const localY = hasViewportCoords ? (clientY - rect.top + scrollTop) : (Number.isFinite(clientY) ? clientY : 0);

        let bestNode = null;
        let bestDistance = Number.POSITIVE_INFINITY;

        nodes.forEach((node) => {
            const dx = Number(node.x || 0) - localX;
            const dy = Number(node.y || 0) - localY;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const hitRadius = (Number(node.size || 14) + 10);
            if (distance <= hitRadius && distance < bestDistance) {
                bestDistance = distance;
                bestNode = node;
            }
        });

        return bestNode;
    }

    function buildGraphModel(graph) {
        const rawNodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
        const rawLinks = Array.isArray(graph && graph.links) ? graph.links : [];
        const adjacencySource = graph && graph.adjacency && typeof graph.adjacency === 'object' ? graph.adjacency : {};
        const adjacencyMap = new Map(Object.entries(adjacencySource).map(([nodeId, neighbors]) => [String(nodeId), Array.isArray(neighbors) ? neighbors.map((neighbor) => String(neighbor)) : []]));

        const nodeMap = new Map(rawNodes.map((node) => [String(node.id), Object.assign({}, node, {
            degree: 0,
            x: 0,
            y: 0,
            featureVector: Array.isArray(node.featureVector) ? node.featureVector : [],
            neighbors: adjacencyMap.get(String(node.id)) || [],
        })]));

        rawLinks.forEach((link) => {
            const source = String(link.source);
            const target = String(link.target);
            if (nodeMap.has(source)) {
                nodeMap.get(source).degree += 1;
                if (!nodeMap.get(source).neighbors.includes(target)) {
                    nodeMap.get(source).neighbors.push(target);
                }
            }
            if (nodeMap.has(target)) {
                nodeMap.get(target).degree += 1;
                if (!nodeMap.get(target).neighbors.includes(source)) {
                    nodeMap.get(target).neighbors.push(source);
                }
            }
        });

        const nodes = Array.from(nodeMap.values()).map((node) => Object.assign({}, node, {
            size: Math.max(12, (node.size || 14) + Math.min(14, (node.degree || 0) * 0.7)),
        }));

        return {
            nodes,
            links: rawLinks,
            adjacency: Object.fromEntries(Array.from(nodeMap.keys()).map((nodeId) => [nodeId, nodeMap.get(nodeId).neighbors || []])),
            nodeIndex: graph && graph.nodeIndex && typeof graph.nodeIndex === 'object' ? graph.nodeIndex : Object.fromEntries(nodes.map((node, index) => [String(node.id), index])),
            stats: graph && graph.stats ? graph.stats : {
                nodeCount: nodes.length,
                linkCount: rawLinks.length,
            },
        };
    }

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};
    window.ThemisDBGraphV2.GraphStore = {
        buildGraphModel,
        calculateVisibleGraph,
        getReductionProfile,
        resolveHitNode,
        resolveRuntimeGraph,
        readGraphFromStorage,
        writeGraphToStorage,
        getPreparedGraph,
    };
})();
