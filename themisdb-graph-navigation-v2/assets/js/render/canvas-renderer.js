(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function truncateLabel(label, maxLength) {
        const text = String(label || '').trim();
        if (!text) {
            return '';
        }
        return text.length > maxLength ? text.slice(0, maxLength - 1) + '…' : text;
    }

    function edgeColorForType(type) {
        const normalized = String(type || 'related').toLowerCase();
        const colors = {
            related: { stroke: 'rgba(148, 163, 184, 0.82)', glow: 'rgba(148, 163, 184, 0.25)' },
            contains_type: { stroke: 'rgba(96, 165, 250, 0.9)', glow: 'rgba(96, 165, 250, 0.35)' },
            has_item: { stroke: 'rgba(52, 211, 153, 0.9)', glow: 'rgba(52, 211, 153, 0.3)' },
            parent: { stroke: 'rgba(251, 191, 36, 0.9)', glow: 'rgba(251, 191, 36, 0.3)' },
            taxonomy: { stroke: 'rgba(244, 114, 182, 0.9)', glow: 'rgba(244, 114, 182, 0.28)' },
            category: { stroke: 'rgba(45, 212, 191, 0.9)', glow: 'rgba(45, 212, 191, 0.28)' },
            tag: { stroke: 'rgba(251, 146, 60, 0.9)', glow: 'rgba(251, 146, 60, 0.28)' },
            content: { stroke: 'rgba(59, 130, 246, 0.9)', glow: 'rgba(59, 130, 246, 0.28)' },
            has_link: { stroke: 'rgba(165, 180, 252, 0.9)', glow: 'rgba(165, 180, 252, 0.3)' },
            default: { stroke: 'rgba(167, 139, 250, 0.8)', glow: 'rgba(167, 139, 250, 0.2)' },
        };

        return colors[normalized] || colors.default;
    }

    function roundedRect(ctx, x, y, width, height, radius) {
        const r = Math.min(radius, width / 2, height / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + width - r, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + r);
        ctx.lineTo(x + width, y + height - r);
        ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
        ctx.lineTo(x + r, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function createSceneGraph(graph, view, state) {
        const nodes = Array.isArray(graph && graph.nodes) ? graph.nodes : [];
        const links = Array.isArray(graph && graph.links) ? graph.links : [];

        const centerX = view.width / 2;
        const centerY = view.height / 2;
        const anchorRadius = Math.min(view.width, view.height) * 0.24;

        const nodeMap = new Map(nodes.map((node, index) => {
            const hasPosition = Number.isFinite(Number(node.x)) && Number.isFinite(Number(node.y));
            const angle = (index / Math.max(1, nodes.length)) * Math.PI * 2;
            const radius = anchorRadius + (Number(node.level || 0) * 20);
            const x = hasPosition ? Number(node.x) : centerX + Math.cos(angle) * radius;
            const y = hasPosition ? Number(node.y) : centerY + Math.sin(angle) * radius;
            return [String(node.id), Object.assign({}, node, { x, y })];
        }));

        const edgeGroup = links.map((link) => {
            const source = nodeMap.get(String(link.source));
            const target = nodeMap.get(String(link.target));
            if (!source || !target) {
                return null;
            }

            const edgeStyle = edgeColorForType(link.type);
            return {
                type: 'edge',
                z: 1,
                points: [{ x: source.x, y: source.y }, { x: target.x, y: target.y }],
                style: edgeStyle,
                weight: Math.max(1, Number(link.weight || 1)),
            };
        }).filter(Boolean);

        const nodeGroup = nodes.map((node) => {
            const entry = nodeMap.get(String(node.id)) || { x: centerX, y: centerY };
            return {
                type: 'node',
                z: 2,
                node,
                x: entry.x,
                y: entry.y,
                radius: Math.max(9, Number(node.size || 14)),
                color: node.color || '#7c4dff',
            };
        });

        return {
            view,
            groups: [{ name: 'edges', items: edgeGroup }, { name: 'nodes', items: nodeGroup }],
            transform: {
                x: 0,
                y: 0,
                scale: 1,
            },
        };
    }

    function drawSceneGraph(ctx, scene, canvas, state) {
        const ratio = window.devicePixelRatio || 1;
        const selectedNodeId = state && state.selection ? state.selection.nodeId : null;
        const hoverNodeId = state && state.selection ? state.selection.hoverNodeId : null;
        const panX = Number(state && state.viewport ? (state.viewport.panX || 0) : 0);
        const panY = Number(state && state.viewport ? (state.viewport.panY || 0) : 0);

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = 'rgba(15, 23, 42, 0.24)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        scene.groups.forEach((group) => {
            group.items.forEach((item) => {
                if (item.type === 'edge') {
                    const style = item.style || edgeColorForType('related');
                    const startX = (item.points[0].x - panX) * ratio;
                    const startY = (item.points[0].y - panY) * ratio;
                    const endX = (item.points[1].x - panX) * ratio;
                    const endY = (item.points[1].y - panY) * ratio;

                    ctx.beginPath();
                    ctx.moveTo(startX, startY);
                    ctx.lineTo(endX, endY);
                    ctx.strokeStyle = style.stroke;
                    ctx.shadowColor = style.glow;
                    ctx.shadowBlur = 10;
                    ctx.lineWidth = clamp(1.2 + (item.weight * 1.1), 1.2, 3.6);
                    ctx.stroke();
                    ctx.shadowBlur = 0;
                    return;
                }

                const node = item.node;
                const isSelected = String(selectedNodeId || '') === String(node.id || '');
                const isHovered = String(hoverNodeId || '') === String(node.id || '');
                const radius = item.radius + (isSelected ? 4 : isHovered ? 3 : 0);
                const centerX = (item.x - panX) * ratio;
                const centerY = (item.y - panY) * ratio;

                ctx.beginPath();
                ctx.fillStyle = item.color;
                ctx.shadowColor = isHovered || isSelected ? 'rgba(255, 255, 255, 0.4)' : 'rgba(0, 0, 0, 0.18)';
                ctx.shadowBlur = isHovered || isSelected ? 18 : 8;
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.fill();
                ctx.shadowBlur = 0;

                if (isSelected || isHovered) {
                    ctx.beginPath();
                    ctx.strokeStyle = isSelected ? '#ffffff' : '#a5f3fc';
                    ctx.lineWidth = isSelected ? 2.8 : 2.2;
                    ctx.arc(centerX, centerY, radius + 8, 0, Math.PI * 2);
                    ctx.stroke();
                }

                const showLabels = state && state.filters && state.filters.showLabels !== false;
                if (showLabels) {
                    const label = truncateLabel(node.label || node.type || 'Node', 14);
                    const textX = (item.x - panX) * ratio;
                    const textY = ((item.y - panY) + item.radius + 20) * ratio;
                    const badgeColor = isSelected || isHovered ? 'rgba(15, 23, 42, 0.8)' : 'rgba(15, 23, 42, 0.7)';
                    ctx.font = '600 10px sans-serif';
                    const textMetrics = ctx.measureText(label);
                    const badgeWidth = Math.max(52, textMetrics.width + 14);
                    const badgeHeight = 20;
                    const badgeX = textX - (badgeWidth / 2);
                    const badgeY = textY - badgeHeight / 2;
                    roundedRect(ctx, badgeX, badgeY, badgeWidth, badgeHeight, 10);
                    ctx.fillStyle = badgeColor;
                    ctx.fill();
                    ctx.strokeStyle = (node.color || '#7c4dff') + 'cc';
                    ctx.stroke();
                    ctx.fillStyle = '#f8fafc';
                    ctx.textAlign = 'center';
                    ctx.fillText(label, textX, textY + 3.5);
                }
            });
        });
    }

    function createCanvasRenderer() {
        return {
            draw(graph, canvas, state) {
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return false;
                }

                const ratio = window.devicePixelRatio || 1;
                const width = Math.max(320, canvas.width / ratio);
                const height = Math.max(260, canvas.height / ratio);
                const scene = createSceneGraph(graph, { width, height }, state);
                drawSceneGraph(ctx, scene, canvas, state);
                return true;
            },
            drawScene(graph, canvas, state) {
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return false;
                }

                const ratio = window.devicePixelRatio || 1;
                const width = Math.max(320, canvas.width / ratio);
                const height = Math.max(260, canvas.height / ratio);
                const scene = createSceneGraph(graph, { width, height }, state);
                drawSceneGraph(ctx, scene, canvas, state);
                return true;
            },
        };
    }

    window.ThemisDBGraphV2.CanvasRenderer = createCanvasRenderer();
})();
