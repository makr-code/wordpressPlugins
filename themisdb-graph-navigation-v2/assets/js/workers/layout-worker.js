(function () {
    'use strict';

    self.onmessage = function (event) {
        const payload = event.data || {};
        const nodes = Array.isArray(payload.nodes) ? payload.nodes : [];
        const radiusBase = Number(payload.radiusBase || 220);
        const centerX = Number(payload.centerX || 0);
        const centerY = Number(payload.centerY || 0);

        const positioned = nodes.map((node, index) => {
            const count = Math.max(1, nodes.length);
            const angle = (index / count) * Math.PI * 2;
            const radius = radiusBase + (Number(node.level || 0) * 18);

            return Object.assign({}, node, {
                x: centerX + Math.cos(angle) * radius,
                y: centerY + Math.sin(angle) * radius,
            });
        });

        self.postMessage({
            type: 'layout-ready',
            nodes: positioned,
        });
    };
})();
