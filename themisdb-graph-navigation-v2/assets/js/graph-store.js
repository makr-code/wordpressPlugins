(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function calculateVisibleGraph(graph, options) {
        const nodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
        const links = Array.isArray(graph && graph.links) ? graph.links : [];
        const localOptions = Object.assign({
            maxVisibleNodes: 250,
            minDegree: 0,
            showExternal: true,
            selectedNodeId: null,
        }, options || {});

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
            if ((node.degree || 0) < localOptions.minDegree) {
                return false;
            }
            if (selectedNodeId) {
                return preferred.has(nodeId);
            }
            return true;
        });

        const ranked = filtered
            .map((node) => ({ node, score: degreeScore(node) }))
            .sort((a, b) => b.score - a.score || (b.node.degree || 0) - (a.node.degree || 0))
            .map((entry) => entry.node);

        const rootNodes = ranked.filter((node) => String(node.type) === 'home' || String(node.type) === 'category' || String(node.type) === 'tag');
        const contentNodes = ranked.filter((node) => !rootNodes.includes(node));
        const topNodes = rootNodes.concat(contentNodes).slice(0, localOptions.maxVisibleNodes);
        const topNodeSet = new Set(topNodes.map((node) => String(node.id)));

        if (selectedNodeId) {
            const selectedNeighbors = new Set([selectedNodeId]);
            (adjacency.get(selectedNodeId) || []).forEach((neighborId) => selectedNeighbors.add(String(neighborId)));
            const expanded = nodes.filter((node) => selectedNeighbors.has(String(node.id)));
            expanded.sort((a, b) => (b.degree || 0) - (a.degree || 0));
            expanded.slice(0, localOptions.maxVisibleNodes).forEach((node) => topNodeSet.add(String(node.id)));
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
        };
    }

    function resolveHitNode(graph, pointerX, pointerY, canvas) {
        const nodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
        if (!canvas || !nodes.length) {
            return null;
        }

        let clientX = pointerX;
        let clientY = pointerY;
        if (pointerX && typeof pointerX === 'object') {
            clientX = Number(pointerX.clientX);
            clientY = Number(pointerX.clientY);
        }

        const rect = canvas.getBoundingClientRect();
        const isClientPointer = Number.isFinite(clientX) && Number.isFinite(clientY)
            && Number.isFinite(rect.left) && Number.isFinite(rect.top)
            && clientX >= rect.left && clientX <= rect.right
            && clientY >= rect.top && clientY <= rect.bottom;

        const localX = isClientPointer ? (clientX - rect.left) : (Number.isFinite(clientX) ? clientX : 0);
        const localY = isClientPointer ? (clientY - rect.top) : (Number.isFinite(clientY) ? clientY : 0);

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

        const nodeMap = new Map(rawNodes.map((node) => [String(node.id), Object.assign({}, node, {
            degree: 0,
            x: 0,
            y: 0,
        })]));

        rawLinks.forEach((link) => {
            const source = String(link.source);
            const target = String(link.target);
            if (nodeMap.has(source)) {
                nodeMap.get(source).degree += 1;
            }
            if (nodeMap.has(target)) {
                nodeMap.get(target).degree += 1;
            }
        });

        const nodes = Array.from(nodeMap.values()).map((node) => Object.assign({}, node, {
            size: Math.max(12, (node.size || 14) + Math.min(14, (node.degree || 0) * 0.7)),
        }));

        return {
            nodes,
            links: rawLinks,
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
        resolveHitNode,
    };
})();
