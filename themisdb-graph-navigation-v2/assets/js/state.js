(function () {
    'use strict';

    window.ThemisDBGraphV2 = window.ThemisDBGraphV2 || {};

    const state = {
        viewport: {
            width: 0,
            height: 0,
            worldWidth: 0,
            worldHeight: 0,
            zoom: 1,
            offsetX: 0,
            offsetY: 0,
            panX: 0,
            panY: 0,
        },
        selection: {
            nodeId: null,
            hoverNodeId: null,
        },
        filters: {
            minDegree: 0,
            maxVisibleNodes: (window.themisdbGraphV2 && window.themisdbGraphV2.settings && window.themisdbGraphV2.settings.maxVisibleNodes) || 120,
            showLabels: true,
            showExternal: (window.themisdbGraphV2 && window.themisdbGraphV2.settings && window.themisdbGraphV2.settings.showExternal !== undefined)
                ? Boolean(window.themisdbGraphV2.settings.showExternal)
                : true,
        },
        layout: {
            mode: 'network',
            world: {
                centerX: 0,
                centerY: 0,
            },
        },
    };

    window.ThemisDBGraphV2.state = state;
})();
