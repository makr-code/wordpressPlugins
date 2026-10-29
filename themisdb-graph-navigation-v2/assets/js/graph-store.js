(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

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
            if ((node.degree || 0) < reduction.minDegree && nodeId !== selectedNodeId) {
                return false;
            }
            return true;
        });

        const ranked = filtered
            .map((node) => ({
                node,
                score: degreeScore(node),
                priority: String(node.id) === selectedNodeId ? 2 : (preferred.has(String(node.id)) ? 1 : 0),
            }))
            .sort((a, b) => b.priority - a.priority || b.score - a.score || (b.node.degree || 0) - (a.node.degree || 0))
            .map((entry) => entry.node);

        const preferredNodes = ranked.filter((node) => preferred.has(String(node.id)));
        const remainingNodes = ranked.filter((node) => !preferred.has(String(node.id)));
        const rootNodes = remainingNodes.filter((node) => ['home', 'taxonomy', 'post_type', 'category', 'tag'].includes(String(node.type)));
        const contentNodes = remainingNodes.filter((node) => !rootNodes.includes(node));
        const topNodes = preferredNodes.concat(rootNodes, contentNodes).slice(0, reduction.maxVisibleNodes);
        const topNodeSet = new Set(topNodes.map((node) => String(node.id)));

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

        const localX = hasViewportCoords ? (clientX - rect.left) : (Number.isFinite(clientX) ? clientX : 0);
        const localY = hasViewportCoords ? (clientY - rect.top) : (Number.isFinite(clientY) ? clientY : 0);

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
    };
})();
