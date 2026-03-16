/**
 * Neo4j Bloom-inspired Graph Navigation Overlay for ThemisDB Theme
 * 
 * Creates an interactive force-directed graph with physics-based layout
 * Displays as a transparent overlay on the page with zoom, pan, and drag
 */
(function() {
    'use strict';

    // Configuration
    const config = {
        mermaidCDN: 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs',
        d3CDN: 'https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js',
        fallbackEnabled: true,
        animationDuration: 300,
        physics: {
            charge: -800,
            linkDistance: 120,
            linkStrength: 1,
            collisionRadius: 60,
            centerStrength: 0.05
        }
    };

    let d3Loaded = false;
    let d3 = null;
    let simulation = null;
    let transform = null;

    /**
     * Load D3.js dynamically for force-directed graph
     */
    async function loadD3() {
        if (d3Loaded && d3) return true;

        try {
            // Create script element for D3
            const script = document.createElement('script');
            script.src = config.d3CDN;
            script.async = true;
            
            await new Promise((resolve, reject) => {
                script.onload = () => {
                    d3 = window.d3;
                    d3Loaded = true;
                    console.log('D3.js loaded successfully');
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            });

            return true;
        } catch (error) {
            console.warn('Failed to load D3.js:', error);
            
            if (config.fallbackEnabled) {
                console.log('Using fallback navigation tree');
                return false;
            }
            return false;
        }
    }

    /**
     * Get theme color based on menu item title
     */
    function getThemeColor(title, index) {
        const lowerTitle = title.toLowerCase();
        
        // Thematic color mapping
        if (lowerTitle.includes('doc') || lowerTitle.includes('guide') || lowerTitle.includes('tutorial')) {
            return { fill: '#3498db', stroke: '#2980b9', class: 'docNode', label: '📚' };
        } else if (lowerTitle.includes('feature') || lowerTitle.includes('product')) {
            return { fill: '#27ae60', stroke: '#229954', class: 'featureNode', label: '⚡' };
        } else if (lowerTitle.includes('about') || lowerTitle.includes('contact') || lowerTitle.includes('team')) {
            return { fill: '#e74c3c', stroke: '#c0392b', class: 'aboutNode', label: '👥' };
        } else if (lowerTitle.includes('blog') || lowerTitle.includes('news') || lowerTitle.includes('article')) {
            return { fill: '#f39c12', stroke: '#e67e22', class: 'blogNode', label: '📝' };
        } else if (lowerTitle.includes('api') || lowerTitle.includes('code') || lowerTitle.includes('develop')) {
            return { fill: '#9b59b6', stroke: '#8e44ad', class: 'apiNode', label: '💻' };
        } else if (lowerTitle.includes('support') || lowerTitle.includes('help') || lowerTitle.includes('faq')) {
            return { fill: '#16a085', stroke: '#138d75', class: 'supportNode', label: '🛟' };
        } else {
            // Cycle through Themis colors for other items
            const colors = [
                { fill: '#7c4dff', stroke: '#651fff', class: 'primaryNode', label: '🔷' },
                { fill: '#3498db', stroke: '#2980b9', class: 'secondaryNode', label: '🔹' },
                { fill: '#27ae60', stroke: '#229954', class: 'successNode', label: '✅' }
            ];
            return colors[index % colors.length];
        }
    }

    /**
     * Get edge label based on relationship
     */
    function getEdgeLabel(parentTitle, childTitle, level) {
        const labels = [
            'contains', 'includes', 'leads to', 'opens', 'shows',
            'displays', 'presents', 'explores', 'details', 'explains'
        ];
        
        if (level === 0) return 'navigate to';
        if (level === 1) return 'view';
        
        return labels[Math.floor(Math.random() * labels.length)];
    }

    /**
     * Validate WordPress graph data structure
     */
    function isValidWordPressData(data) {
        if (!data || typeof data !== 'object') {
            return false;
        }
        
        // Check if data has required properties
        if (!data.nodes || !Array.isArray(data.nodes)) {
            return false;
        }
        
        if (!data.links || !Array.isArray(data.links)) {
            return false;
        }
        
        // Check if nodes array has at least one node
        if (data.nodes.length === 0) {
            return false;
        }
        
        // Validate that each node has required properties
        const hasValidNodes = data.nodes.every(node => 
            node.id != null && 
            typeof node.label === 'string' && 
            typeof node.type === 'string'
        );
        
        if (!hasValidNodes) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Build graph data structure for force-directed layout
     * Uses WordPress posts, pages, categories, and tags data passed from PHP
     */
    function buildGraphData() {
        // Check if WordPress data is available with thorough validation
        if (isValidWordPressData(window.themisdbGraphData)) {
            console.log('Using WordPress content data for graph');
            return processWordPressGraphData(window.themisdbGraphData);
        }
        
        // Fallback to menu-based graph if no WordPress data
        console.log('Falling back to menu-based graph');
        return buildMenuGraphData();
    }
    
    /**
     * Process WordPress content data into graph format
     */
    function processWordPressGraphData(wpData) {
        const nodes = [];
        const links = [];
        
        // Process nodes and add visual properties
        wpData.nodes.forEach((node, index) => {
            const themeColor = getThemeColorByType(node.type, index);
            
            nodes.push({
                id: node.id,
                label: node.label,
                url: node.url,
                level: node.level,
                type: node.type,
                color: themeColor.fill,
                icon: themeColor.label,
                size: getNodeSize(node.type, node.level),
                excerpt: node.excerpt || '',
                count: node.count || 0,
                date: node.date || ''
            });
        });
        
        // Process links
        wpData.links.forEach(link => {
            links.push({
                source: link.source,
                target: link.target,
                label: getLinkLabel(link.type),
                strength: getLinkStrength(link.type)
            });
        });
        
        return { nodes, links };
    }
    
    /**
     * Get node size based on type and level
     */
    function getNodeSize(type, level) {
        const sizes = {
            'home': 35,
            'category': 28,
            'tag': 25,
            'page': 26,
            'post': 22
        };
        return sizes[type] || Math.max(20, 30 - level * 5);
    }
    
    /**
     * Get theme color based on node type
     */
    function getThemeColorByType(type, index) {
        const typeColors = {
            'home': { fill: '#7c4dff', stroke: '#651fff', class: 'homeNode', label: '🏠' },
            'category': { fill: '#3498db', stroke: '#2980b9', class: 'categoryNode', label: '📁' },
            'tag': { fill: '#f39c12', stroke: '#e67e22', class: 'tagNode', label: '🏷️' },
            'page': { fill: '#27ae60', stroke: '#229954', class: 'pageNode', label: '📄' },
            'post': { fill: '#e74c3c', stroke: '#c0392b', class: 'postNode', label: '📝' }
        };
        
        return typeColors[type] || { fill: '#95a5a6', stroke: '#7f8c8d', class: 'defaultNode', label: '●' };
    }
    
    /**
     * Get link label based on relationship type
     */
    function getLinkLabel(type) {
        const labels = {
            'contains': 'contains',
            'tagged': 'tagged with',
            'has_post': 'has post',
            'has_tag': 'tagged',
            'page_of': 'page of'
        };
        return labels[type] || 'related to';
    }
    
    /**
     * Get link strength based on relationship type
     */
    function getLinkStrength(type) {
        const strengths = {
            'contains': 0.8,
            'tagged': 0.7,
            'has_post': 1.0,
            'has_tag': 0.9,
            'page_of': 0.9
        };
        return strengths[type] || 0.5;
    }
    
    /**
     * Build graph data from navigation menu (fallback)
     */
    function buildMenuGraphData() {
        const menu = document.querySelector('#primary-menu');
        if (!menu) return null;

        const nodes = [];
        const links = [];

        // Add home node (center)
        nodes.push({
            id: 'home',
            label: 'Home',
            url: '/',
            level: 0,
            type: 'home',
            color: '#7c4dff',
            icon: '🏠',
            size: 30
        });

        let nodeIdCounter = 0;

        /**
         * Recursive function to process menu items
         */
        function processMenuItem(item, parentId, level) {
            const link = item.querySelector(':scope > a');
            if (!link) return null;

            const nodeId = `node_${nodeIdCounter++}`;
            const title = link.textContent.trim();
            const url = link.getAttribute('href');
            const themeColor = getThemeColor(title, nodeIdCounter);

            // Add node
            nodes.push({
                id: nodeId,
                label: title,
                url: url,
                level: level,
                type: themeColor.class,
                color: themeColor.fill,
                icon: themeColor.label,
                size: Math.max(20, 30 - level * 5)
            });

            // Add link
            links.push({
                source: parentId,
                target: nodeId,
                label: getEdgeLabel('', title, level),
                strength: 1 / (level + 1)
            });

            // Process children recursively
            const submenu = item.querySelector(':scope > ul');
            if (submenu) {
                const children = submenu.querySelectorAll(':scope > li');
                children.forEach(child => {
                    processMenuItem(child, nodeId, level + 1);
                });
            }

            return nodeId;
        }

        // Process all top-level menu items
        const topItems = menu.querySelectorAll(':scope > li');
        topItems.forEach(item => {
            processMenuItem(item, 'home', 1);
        });

        return { nodes, links };
    }

    /**
     * Create Neo4j Bloom-inspired force-directed graph
     */
    function createForceDirectedGraph(container, graphData, panelContainer) {
        if (!d3) {
            console.error('D3.js not loaded');
            return;
        }

        const width = container.clientWidth;
        const height = container.clientHeight;

        // Clear container
        container.innerHTML = '';

        // Create SVG
        const svg = d3.select(container)
            .append('svg')
            .attr('width', width)
            .attr('height', height)
            .attr('class', 'bloom-graph');

        // Add zoom behavior
        const zoom = d3.zoom()
            .scaleExtent([0.1, 4])
            .on('zoom', (event) => {
                g.attr('transform', event.transform);
                transform = event.transform;
            });

        svg.call(zoom);

        // Create group for zoom/pan
        const g = svg.append('g');

        // Add definitions for gradients and glows
        const defs = svg.append('defs');

        // Glow filter
        const filter = defs.append('filter')
            .attr('id', 'glow')
            .attr('x', '-50%')
            .attr('y', '-50%')
            .attr('width', '200%')
            .attr('height', '200%');

        filter.append('feGaussianBlur')
            .attr('stdDeviation', '3')
            .attr('result', 'coloredBlur');

        const feMerge = filter.append('feMerge');
        feMerge.append('feMergeNode').attr('in', 'coloredBlur');
        feMerge.append('feMergeNode').attr('in', 'SourceGraphic');

        // Create arrow markers for links
        defs.append('marker')
            .attr('id', 'arrowhead')
            .attr('viewBox', '0 -5 10 10')
            .attr('refX', 20)
            .attr('refY', 0)
            .attr('markerWidth', 8)
            .attr('markerHeight', 8)
            .attr('orient', 'auto')
            .append('path')
            .attr('d', 'M0,-5L10,0L0,5')
            .attr('fill', '#3498db');

        // Create force simulation
        simulation = d3.forceSimulation(graphData.nodes)
            .force('link', d3.forceLink(graphData.links)
                .id(d => d.id)
                .distance(d => config.physics.linkDistance * (1 + d.source.level * 0.2))
                .strength(d => d.strength))
            .force('charge', d3.forceManyBody()
                .strength(config.physics.charge))
            .force('center', d3.forceCenter(width / 2, height / 2)
                .strength(config.physics.centerStrength))
            .force('collision', d3.forceCollide()
                .radius(d => d.size + config.physics.collisionRadius))
            .force('x', d3.forceX(width / 2).strength(0.02))
            .force('y', d3.forceY(height / 2).strength(0.02));

        // Create links
        const link = g.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(graphData.links)
            .enter()
            .append('line')
            .attr('class', 'link')
            .attr('stroke', '#3498db')
            .attr('stroke-width', 2)
            .attr('stroke-opacity', 0.6)
            .attr('marker-end', 'url(#arrowhead)');

        // Create link labels
        const linkLabel = g.append('g')
            .attr('class', 'link-labels')
            .selectAll('text')
            .data(graphData.links)
            .enter()
            .append('text')
            .attr('class', 'link-label')
            .attr('text-anchor', 'middle')
            .attr('dy', -5)
            .style('font-size', '10px')
            .style('fill', '#ecf0f1')
            .style('opacity', 0)
            .text(d => d.label);

        // Create node groups
        const node = g.append('g')
            .attr('class', 'nodes')
            .selectAll('g')
            .data(graphData.nodes)
            .enter()
            .append('g')
            .attr('class', 'node')
            .call(d3.drag()
                .on('start', dragstarted)
                .on('drag', dragged)
                .on('end', dragended));

        // Add circles to nodes
        node.append('circle')
            .attr('r', d => d.size)
            .attr('fill', d => d.color)
            .attr('stroke', d => d3.rgb(d.color).darker(1))
            .attr('stroke-width', 2)
            .style('filter', 'url(#glow)')
            .style('cursor', 'pointer');

        // Add icons to nodes
        node.append('text')
            .attr('class', 'node-icon')
            .attr('text-anchor', 'middle')
            .attr('dy', 5)
            .style('font-size', d => `${d.size * 0.7}px`)
            .style('pointer-events', 'none')
            .text(d => d.icon);

        // Add labels below nodes
        node.append('text')
            .attr('class', 'node-label')
            .attr('text-anchor', 'middle')
            .attr('dy', d => d.size + 15)
            .style('font-size', '12px')
            .style('font-weight', '600')
            .style('fill', '#fff')
            .style('pointer-events', 'none')
            .text(d => d.label);

        // Create tooltip element
        const tooltip = d3.select(container)
            .append('div')
            .attr('class', 'graph-tooltip')
            .attr('id', 'themisdb-graph-tooltip')
            .attr('role', 'tooltip')
            .attr('aria-hidden', 'true')
            .style('position', 'absolute')
            .style('visibility', 'hidden')
            .style('opacity', '0')
            .style('background', 'linear-gradient(135deg, rgba(44, 62, 80, 0.98) 0%, rgba(52, 73, 94, 0.98) 100%)')
            .style('color', '#fff')
            .style('padding', '16px')
            .style('border-radius', '12px')
            .style('font-size', '14px')
            .style('max-width', '320px')
            .style('min-width', '200px')
            .style('box-shadow', '0 8px 24px rgba(0,0,0,0.4)')
            .style('pointer-events', 'none')
            .style('z-index', '10000')
            .style('border', '2px solid rgba(124, 77, 255, 0.5)')
            .style('line-height', '1.6')
            .style('transform', 'translateY(4px)')
            .style('transition', 'opacity 140ms ease, transform 140ms ease');

        node
            .attr('tabindex', 0)
            .attr('role', 'button')
            .attr('aria-label', d => `Open ${d.label}`)
            .attr('aria-describedby', 'themisdb-graph-tooltip');

        const nodeElementById = new Map();
        const nodeDataById = new Map(graphData.nodes.map(n => [n.id, n]));
        node.each(function(d) {
            nodeElementById.set(d.id, this);
        });

        function resolveNodeId(endpoint) {
            return typeof endpoint === 'object' && endpoint !== null ? endpoint.id : endpoint;
        }

        const connectionCount = new Map(graphData.nodes.map(n => [n.id, 0]));
        graphData.links.forEach(l => {
            const sourceId = resolveNodeId(l.source);
            const targetId = resolveNodeId(l.target);
            if (connectionCount.has(sourceId)) {
                connectionCount.set(sourceId, connectionCount.get(sourceId) + 1);
            }
            if (connectionCount.has(targetId)) {
                connectionCount.set(targetId, connectionCount.get(targetId) + 1);
            }
        });

        const panelState = {
            search: '',
            type: 'all',
            sort: 'label_asc',
            selectedIds: new Set(),
            selectedOnly: false,
            filterMode: 'hide',
            helpVisible: false,
            minCount: 0,
            dateFrom: '',
            dateTo: '',
            visibleIds: new Set(graphData.nodes.map(n => n.id)),
            sortedVisibleNodes: [],
            listRenderLimit: 120,
            listRenderStep: 120,
            searchDebounceTimer: null,
            isListScrollBound: false
        };

        const panelStorageKey = 'themisdbGraphPanelStateV2';
        const panelPresetStorageKey = 'themisdbGraphPanelPresetsV1';
        const panelPresetMetaStorageKey = 'themisdbGraphPanelPresetMetaV1';
        let panelPresets = {};
        let panelPresetMeta = {
            autoApplyPreset: false,
            defaultPresetName: '',
            history: []
        };

        const panel = panelContainer ? {
            root: panelContainer,
            typeChips: panelContainer.querySelector('#graph-type-chips'),
            searchInput: panelContainer.querySelector('#graph-filter-search'),
            typeSelect: panelContainer.querySelector('#graph-filter-type'),
            sortSelect: panelContainer.querySelector('#graph-sort-select'),
            selectedOnlyCheckbox: panelContainer.querySelector('#graph-selected-only'),
            highlightOnlyCheckbox: panelContainer.querySelector('#graph-highlight-only'),
            minCountInput: panelContainer.querySelector('#graph-filter-min-count'),
            dateFromInput: panelContainer.querySelector('#graph-filter-date-from'),
            dateToInput: panelContainer.querySelector('#graph-filter-date-to'),
            presetSelect: panelContainer.querySelector('#graph-preset-select'),
            savePresetBtn: panelContainer.querySelector('#graph-save-preset'),
            deletePresetBtn: panelContainer.querySelector('#graph-delete-preset'),
            renamePresetBtn: panelContainer.querySelector('#graph-rename-preset'),
            duplicatePresetBtn: panelContainer.querySelector('#graph-duplicate-preset'),
            exportPresetBtn: panelContainer.querySelector('#graph-export-presets'),
            importPresetBtn: panelContainer.querySelector('#graph-import-presets'),
            importPresetInput: panelContainer.querySelector('#graph-import-presets-input'),
            importModeSelect: panelContainer.querySelector('#graph-import-mode'),
            importConflictModal: panelContainer.querySelector('#graph-import-conflict-modal'),
            importConflictLabel: panelContainer.querySelector('#graph-import-conflict-label'),
            toggleHelpBtn: panelContainer.querySelector('#graph-toggle-help'),
            inlineHelp: panelContainer.querySelector('#graph-inline-help'),
            autoApplyPresetCheckbox: panelContainer.querySelector('#graph-auto-apply-preset'),
            presetAuditList: panelContainer.querySelector('#graph-preset-audit'),
            selectVisibleBtn: panelContainer.querySelector('#graph-select-visible'),
            clearSelectionBtn: panelContainer.querySelector('#graph-clear-selection'),
            resetFiltersBtn: panelContainer.querySelector('#graph-reset-filters'),
            shareViewBtn: panelContainer.querySelector('#graph-share-view'),
            selectionCount: panelContainer.querySelector('#graph-selection-count'),
            list: panelContainer.querySelector('#graph-node-list')
        } : null;

        const tooltipState = {
            showTimer: null,
            hideTimer: null,
            rafPending: false,
            lastEvent: null,
            isVisible: false,
            activeTouchNodeId: null,
            suppressNextClick: false,
            isPinned: false,
            pinnedNodeId: null,
            currentNodeId: null
        };

        function clearTooltipTimer(timerKey) {
            if (tooltipState[timerKey]) {
                clearTimeout(tooltipState[timerKey]);
                tooltipState[timerKey] = null;
            }
        }

        function placeTooltip(event) {
            const tooltipNode = tooltip.node();
            if (!tooltipNode || !event) return;

            const offset = 16;
            const margin = 12;
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
            const scrollX = window.scrollX || window.pageXOffset || 0;
            const scrollY = window.scrollY || window.pageYOffset || 0;

            const rect = tooltipNode.getBoundingClientRect();
            const width = rect.width || 240;
            const height = rect.height || 120;

            let left = event.pageX + offset;
            let top = event.pageY - offset;

            const minLeft = scrollX + margin;
            const maxLeft = scrollX + viewportWidth - width - margin;
            if (left > maxLeft) {
                left = event.pageX - width - offset;
            }
            left = Math.max(minLeft, Math.min(left, maxLeft));

            const minTop = scrollY + margin;
            const maxTop = scrollY + viewportHeight - height - margin;
            if (top > maxTop) {
                top = event.pageY - height - offset;
            }
            top = Math.max(minTop, Math.min(top, maxTop));

            tooltip
                .style('left', `${left}px`)
                .style('top', `${top}px`);
        }

        function queueTooltipPosition(event) {
            if (tooltipState.isPinned) {
                return;
            }

            tooltipState.lastEvent = event;
            if (tooltipState.rafPending) return;

            tooltipState.rafPending = true;
            window.requestAnimationFrame(() => {
                tooltipState.rafPending = false;
                placeTooltip(tooltipState.lastEvent);
            });
        }

        function showTooltip(event, interactive = false) {
            clearTooltipTimer('hideTimer');
            tooltip
                .style('visibility', 'visible')
                .style('opacity', '1')
                .style('transform', 'translateY(0)')
                .style('pointer-events', interactive ? 'auto' : 'none')
                .attr('aria-hidden', 'false');
            tooltipState.isVisible = true;
            queueTooltipPosition(event);
        }

        function scheduleTooltipShow(event, interactive = false) {
            clearTooltipTimer('showTimer');
            if (tooltipState.isVisible) {
                showTooltip(event, interactive);
                return;
            }

            tooltipState.showTimer = setTimeout(() => {
                showTooltip(event, interactive);
            }, 120);
        }

        function scheduleTooltipHide() {
            if (tooltipState.isPinned) {
                return;
            }

            clearTooltipTimer('showTimer');
            clearTooltipTimer('hideTimer');
            tooltipState.hideTimer = setTimeout(() => {
                tooltip
                    .style('opacity', '0')
                    .style('transform', 'translateY(4px)')
                    .style('visibility', 'hidden')
                    .attr('aria-hidden', 'true');
                tooltipState.isVisible = false;
            }, 180);
        }

        function setTooltipPinned(nodeId, pinned) {
            tooltipState.isPinned = pinned;
            tooltipState.pinnedNodeId = pinned ? nodeId : null;
            tooltip.style('pointer-events', pinned ? 'auto' : 'none');

            if (pinned && nodeId && nodeDataById.has(nodeId)) {
                renderTooltipContent(nodeDataById.get(nodeId));
            }
        }

        function getEventPosition(event, element) {
            if (event && Number.isFinite(event.pageX) && Number.isFinite(event.pageY)) {
                return { pageX: event.pageX, pageY: event.pageY };
            }

            if (event && event.touches && event.touches[0]) {
                return { pageX: event.touches[0].pageX, pageY: event.touches[0].pageY };
            }

            const rect = element.getBoundingClientRect();
            const scrollX = window.scrollX || window.pageXOffset || 0;
            const scrollY = window.scrollY || window.pageYOffset || 0;
            return {
                pageX: rect.left + scrollX + rect.width / 2,
                pageY: rect.top + scrollY + rect.height / 2
            };
        }

        function highlightNodeAndLinks(nodeElement, d) {
            d3.select(nodeElement).select('circle')
                .transition()
                .duration(200)
                .attr('r', d.size * 1.3)
                .attr('stroke-width', 4);

            link.style('stroke-opacity', l =>
                (l.source.id === d.id || l.target.id === d.id) ? 1 : 0.2
            ).style('stroke-width', l =>
                (l.source.id === d.id || l.target.id === d.id) ? 3 : 2
            );

            linkLabel.style('opacity', l =>
                (l.source.id === d.id || l.target.id === d.id) ? 1 : 0
            );
        }

        function resetNodeAndLinks(nodeElement, d) {
            d3.select(nodeElement).select('circle')
                .transition()
                .duration(200)
                .attr('r', d.size)
                .attr('stroke-width', panelState.selectedIds.has(d.id) ? 4 : 2)
                .attr('stroke', panelState.selectedIds.has(d.id) ? '#ffd166' : d3.rgb(d.color).darker(1));

            link.style('stroke-opacity', 0.6)
                .style('stroke-width', 2);

            linkLabel.style('opacity', 0);
        }

        function renderTooltipContent(d) {
            tooltipState.currentNodeId = d.id;

            let tooltipHTML = '<div class="graph-tooltip-header">';
            tooltipHTML += `<span class="graph-tooltip-title">${d.icon} ${d.label}</span>`;
            tooltipHTML += '</div>';

            const typeColors = {
                'home': '#7c4dff',
                'category': '#3498db',
                'tag': '#f39c12',
                'page': '#27ae60',
                'post': '#e74c3c'
            };

            const typeColor = typeColors[d.type] || '#95a5a6';
            tooltipHTML += `<div style="margin-bottom: 12px;"><span class="graph-tooltip-badge" style="background: ${typeColor};">${d.type}</span></div>`;

            if (d.excerpt && d.excerpt.trim()) {
                tooltipHTML += `<div class="graph-tooltip-excerpt">${d.excerpt}</div>`;
            }

            if (d.count && d.count > 0) {
                tooltipHTML += `<div class="graph-tooltip-meta">📊 <strong>${d.count}</strong> ${d.type === 'category' ? 'posts' : 'items'}</div>`;
            }

            const connectedCount = connectionCount.get(d.id) || 0;
            tooltipHTML += `<div class="graph-tooltip-meta">🕸️ <strong>${connectedCount}</strong> Verbindungen</div>`;

            if (d.date) {
                const date = new Date(d.date);
                const formattedDate = date.toLocaleDateString('de-DE', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                tooltipHTML += `<div class="graph-tooltip-meta">📅 ${formattedDate}</div>`;
            }

            tooltipHTML += '<div class="graph-tooltip-footer">';
            tooltipHTML += '<button type="button" class="graph-tooltip-button" data-tooltip-action="open">🔗 Oeffnen</button>';
            tooltipHTML += `<button type="button" class="graph-tooltip-button" data-tooltip-action="pin">${tooltipState.isPinned && tooltipState.pinnedNodeId === d.id ? '📌 Loesen' : '📌 Anheften'}</button>`;
            tooltipHTML += '<button type="button" class="graph-tooltip-button" data-tooltip-action="close">✕ Schliessen</button>';
            tooltipHTML += '</div>';

            tooltip.html(tooltipHTML);
            tooltip.select('.graph-tooltip-excerpt')
                .style('display', '-webkit-box')
                .style('-webkit-line-clamp', '4')
                .style('-webkit-box-orient', 'vertical')
                .style('overflow', 'hidden');
        }

        function focusNodeById(nodeId, pinTooltip = true) {
            const targetNode = nodeDataById.get(nodeId);
            const targetNodeElement = nodeElementById.get(nodeId);

            if (!targetNode || !targetNodeElement) {
                return;
            }

            if (Number.isFinite(targetNode.x) && Number.isFinite(targetNode.y)) {
                const focusScale = 1.25;
                const focusTransform = d3.zoomIdentity
                    .translate(width / 2 - targetNode.x * focusScale, height / 2 - targetNode.y * focusScale)
                    .scale(focusScale);
                svg.transition().duration(350).call(zoom.transform, focusTransform);
            }

            highlightNodeAndLinks(targetNodeElement, targetNode);
            renderTooltipContent(targetNode);
            showTooltip(getEventPosition(null, targetNodeElement), pinTooltip);
            setTooltipPinned(nodeId, pinTooltip);
        }

        function updateSelectionStyles() {
            node.classed('node-selected', d => panelState.selectedIds.has(d.id));
            node.select('circle')
                .attr('stroke', d => panelState.selectedIds.has(d.id) ? '#ffd166' : d3.rgb(d.color).darker(1))
                .attr('stroke-width', d => panelState.selectedIds.has(d.id) ? 4 : 2);

            const highlightMode = panelState.filterMode === 'highlight';

            if (panelState.selectedIds.size > 0) {
                link.style('stroke-opacity', l => {
                    const sourceId = resolveNodeId(l.source);
                    const targetId = resolveNodeId(l.target);
                    if (panelState.selectedIds.has(sourceId) || panelState.selectedIds.has(targetId)) {
                        return 0.9;
                    }

                    if (highlightMode) {
                        const edgeVisible = panelState.visibleIds.has(sourceId) && panelState.visibleIds.has(targetId);
                        return edgeVisible ? 0.2 : 0.04;
                    }

                    return 0.25;
                });
            } else {
                if (highlightMode) {
                    link.style('stroke-opacity', l => {
                        const sourceId = resolveNodeId(l.source);
                        const targetId = resolveNodeId(l.target);
                        return panelState.visibleIds.has(sourceId) && panelState.visibleIds.has(targetId) ? 0.6 : 0.08;
                    });
                } else {
                    link.style('stroke-opacity', 0.6);
                }
            }
        }

        function getSortedNodes(nodesToSort) {
            const sorted = [...nodesToSort];
            const collator = new Intl.Collator('de-DE', { sensitivity: 'base' });

            sorted.sort((a, b) => {
                switch (panelState.sort) {
                    case 'label_desc':
                        return collator.compare(b.label || '', a.label || '');
                    case 'type_asc': {
                        const typeCompare = collator.compare(a.type || '', b.type || '');
                        return typeCompare !== 0 ? typeCompare : collator.compare(a.label || '', b.label || '');
                    }
                    case 'size_desc':
                        return (b.size || 0) - (a.size || 0);
                    case 'count_desc':
                        return (b.count || 0) - (a.count || 0);
                    case 'label_asc':
                    default:
                        return collator.compare(a.label || '', b.label || '');
                }
            });

            return sorted;
        }

        function getDayTimestamp(value, endOfDay = false) {
            if (!value) {
                return null;
            }

            const suffix = endOfDay ? 'T23:59:59' : 'T00:00:00';
            const parsed = new Date(`${value}${suffix}`).getTime();
            return Number.isFinite(parsed) ? parsed : null;
        }

        function persistPanelState() {
            try {
                localStorage.setItem(panelStorageKey, JSON.stringify({
                    search: panelState.search,
                    type: panelState.type,
                    sort: panelState.sort,
                    selectedOnly: panelState.selectedOnly,
                    filterMode: panelState.filterMode,
                    helpVisible: panelState.helpVisible,
                    minCount: panelState.minCount,
                    dateFrom: panelState.dateFrom,
                    dateTo: panelState.dateTo,
                    selectedIds: [...panelState.selectedIds]
                }));
            } catch (error) {
                console.warn('Could not persist graph panel state', error);
            }
        }

        function hydratePanelState() {
            try {
                const raw = localStorage.getItem(panelStorageKey);
                if (!raw) {
                    return;
                }

                const parsed = JSON.parse(raw);
                panelState.search = typeof parsed.search === 'string' ? parsed.search : '';
                panelState.type = typeof parsed.type === 'string' ? parsed.type : 'all';
                panelState.sort = typeof parsed.sort === 'string' ? parsed.sort : 'label_asc';
                panelState.selectedOnly = Boolean(parsed.selectedOnly);
                panelState.filterMode = parsed.filterMode === 'highlight' ? 'highlight' : 'hide';
                panelState.helpVisible = Boolean(parsed.helpVisible);

                const minCount = Number(parsed.minCount);
                panelState.minCount = Number.isFinite(minCount) && minCount > 0 ? minCount : 0;

                panelState.dateFrom = typeof parsed.dateFrom === 'string' ? parsed.dateFrom : '';
                panelState.dateTo = typeof parsed.dateTo === 'string' ? parsed.dateTo : '';

                if (Array.isArray(parsed.selectedIds)) {
                    const validIds = parsed.selectedIds.filter(id => nodeDataById.has(id));
                    panelState.selectedIds = new Set(validIds);
                }
            } catch (error) {
                console.warn('Could not restore graph panel state', error);
            }
        }

        function hydratePanelStateFromUrl(validTypes) {
            try {
                const params = new URLSearchParams(window.location.search);
                if (params.get('gn') !== '1') {
                    return;
                }

                const search = params.get('gq');
                const type = params.get('gt');
                const sort = params.get('gs');
                const selectedOnly = params.get('gso');
                const filterMode = params.get('gfm');
                const minCount = params.get('gmc');
                const dateFrom = params.get('gdf');
                const dateTo = params.get('gdt');
                const selected = params.get('gsel');

                if (search !== null) panelState.search = search;
                if (type !== null) panelState.type = type;
                if (sort !== null) panelState.sort = sort;
                if (selectedOnly !== null) panelState.selectedOnly = selectedOnly === '1';
                if (filterMode !== null) panelState.filterMode = filterMode === 'highlight' ? 'highlight' : 'hide';
                if (dateFrom !== null) panelState.dateFrom = dateFrom;
                if (dateTo !== null) panelState.dateTo = dateTo;

                if (minCount !== null) {
                    const parsed = Number(minCount);
                    panelState.minCount = Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
                }

                if (selected !== null) {
                    const ids = selected
                        .split(',')
                        .map(value => value.trim())
                        .filter(value => nodeDataById.has(value));
                    panelState.selectedIds = new Set(ids);
                }

                if (panelState.type !== 'all' && !validTypes.includes(panelState.type)) {
                    panelState.type = 'all';
                }
            } catch (error) {
                console.warn('Could not parse graph URL state', error);
            }
        }

        function buildPanelUrl() {
            const url = new URL(window.location.href);
            const params = url.searchParams;

            const isDefaultState =
                panelState.search === '' &&
                panelState.type === 'all' &&
                panelState.sort === 'label_asc' &&
                panelState.selectedOnly === false &&
                panelState.filterMode === 'hide' &&
                panelState.minCount === 0 &&
                panelState.dateFrom === '' &&
                panelState.dateTo === '' &&
                panelState.selectedIds.size === 0;

            const managedKeys = ['gn', 'gq', 'gt', 'gs', 'gso', 'gfm', 'gmc', 'gdf', 'gdt', 'gsel'];
            managedKeys.forEach(key => params.delete(key));

            if (!isDefaultState) {
                params.set('gn', '1');
                if (panelState.search) params.set('gq', panelState.search);
                if (panelState.type && panelState.type !== 'all') params.set('gt', panelState.type);
                if (panelState.sort && panelState.sort !== 'label_asc') params.set('gs', panelState.sort);
                if (panelState.selectedOnly) params.set('gso', '1');
                if (panelState.filterMode === 'highlight') params.set('gfm', 'highlight');
                if (panelState.minCount > 0) params.set('gmc', String(panelState.minCount));
                if (panelState.dateFrom) params.set('gdf', panelState.dateFrom);
                if (panelState.dateTo) params.set('gdt', panelState.dateTo);
                if (panelState.selectedIds.size > 0) {
                    params.set('gsel', [...panelState.selectedIds].slice(0, 80).join(','));
                }
            }

            return url;
        }

        function syncUrlState() {
            try {
                const url = buildPanelUrl();
                window.history.replaceState(null, '', url.toString());
            } catch (error) {
                console.warn('Could not sync graph URL state', error);
            }
        }

        async function copyShareLink() {
            const shareUrl = buildPanelUrl().toString();
            if (!panel || !panel.shareViewBtn) {
                return;
            }

            try {
                await navigator.clipboard.writeText(shareUrl);
                const previous = panel.shareViewBtn.textContent;
                panel.shareViewBtn.textContent = 'Link kopiert';
                setTimeout(() => {
                    panel.shareViewBtn.textContent = previous;
                }, 1400);
            } catch (error) {
                console.warn('Clipboard copy failed, using prompt fallback', error);
                window.prompt('Link dieser Ansicht:', shareUrl);
            }
        }

        function syncPanelControls() {
            if (!panel) {
                return;
            }

            if (panel.searchInput) panel.searchInput.value = panelState.search;
            if (panel.typeSelect) panel.typeSelect.value = panelState.type;
            if (panel.sortSelect) panel.sortSelect.value = panelState.sort;
            if (panel.selectedOnlyCheckbox) panel.selectedOnlyCheckbox.checked = panelState.selectedOnly;
            if (panel.highlightOnlyCheckbox) panel.highlightOnlyCheckbox.checked = panelState.filterMode === 'highlight';
            if (panel.minCountInput) panel.minCountInput.value = panelState.minCount > 0 ? String(panelState.minCount) : '';
            if (panel.dateFromInput) panel.dateFromInput.value = panelState.dateFrom;
            if (panel.dateToInput) panel.dateToInput.value = panelState.dateTo;
            renderInlineHelpState();
        }

        function renderInlineHelpState() {
            if (!panel?.inlineHelp || !panel?.toggleHelpBtn) {
                return;
            }

            const isVisible = panelState.helpVisible;
            panel.inlineHelp.classList.toggle('visible', isVisible);
            panel.inlineHelp.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            panel.toggleHelpBtn.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            panel.toggleHelpBtn.textContent = isVisible ? 'Hilfe ausblenden' : 'Hilfe anzeigen';
        }

        function serializeCurrentPanelState() {
            return {
                search: panelState.search,
                type: panelState.type,
                sort: panelState.sort,
                selectedOnly: panelState.selectedOnly,
                filterMode: panelState.filterMode,
                minCount: panelState.minCount,
                dateFrom: panelState.dateFrom,
                dateTo: panelState.dateTo,
                selectedIds: [...panelState.selectedIds]
            };
        }

        function applySerializedPanelState(serialized) {
            panelState.search = typeof serialized.search === 'string' ? serialized.search : '';
            panelState.type = typeof serialized.type === 'string' ? serialized.type : 'all';
            panelState.sort = typeof serialized.sort === 'string' ? serialized.sort : 'label_asc';
            panelState.selectedOnly = Boolean(serialized.selectedOnly);
            panelState.filterMode = serialized.filterMode === 'highlight' ? 'highlight' : 'hide';

            const parsedMinCount = Number(serialized.minCount);
            panelState.minCount = Number.isFinite(parsedMinCount) && parsedMinCount > 0 ? parsedMinCount : 0;
            panelState.dateFrom = typeof serialized.dateFrom === 'string' ? serialized.dateFrom : '';
            panelState.dateTo = typeof serialized.dateTo === 'string' ? serialized.dateTo : '';
            panelState.selectedIds = new Set(Array.isArray(serialized.selectedIds)
                ? serialized.selectedIds.filter(id => nodeDataById.has(id))
                : []);
        }

        function loadPanelPresets() {
            try {
                const raw = localStorage.getItem(panelPresetStorageKey);
                panelPresets = raw ? JSON.parse(raw) : {};
                if (!panelPresets || typeof panelPresets !== 'object') {
                    panelPresets = {};
                }
            } catch (error) {
                panelPresets = {};
                console.warn('Could not load graph panel presets', error);
            }
        }

        function persistPanelPresets() {
            try {
                localStorage.setItem(panelPresetStorageKey, JSON.stringify(panelPresets));
            } catch (error) {
                console.warn('Could not persist graph panel presets', error);
            }
        }

        function loadPanelPresetMeta() {
            try {
                const raw = localStorage.getItem(panelPresetMetaStorageKey);
                if (!raw) {
                    panelPresetMeta = { autoApplyPreset: false, defaultPresetName: '', history: [] };
                    return;
                }

                const parsed = JSON.parse(raw);
                panelPresetMeta = {
                    autoApplyPreset: Boolean(parsed?.autoApplyPreset),
                    defaultPresetName: typeof parsed?.defaultPresetName === 'string' ? parsed.defaultPresetName : '',
                    history: Array.isArray(parsed?.history) ? parsed.history.slice(0, 20) : []
                };
            } catch (error) {
                panelPresetMeta = { autoApplyPreset: false, defaultPresetName: '', history: [] };
                console.warn('Could not load graph panel preset meta', error);
            }
        }

        function persistPanelPresetMeta() {
            try {
                localStorage.setItem(panelPresetMetaStorageKey, JSON.stringify(panelPresetMeta));
            } catch (error) {
                console.warn('Could not persist graph panel preset meta', error);
            }
        }

        function renderPresetAudit() {
            if (!panel || !panel.presetAuditList) {
                return;
            }

            const items = Array.isArray(panelPresetMeta.history) ? panelPresetMeta.history : [];
            if (items.length === 0) {
                panel.presetAuditList.innerHTML = '<div class="graph-panel-empty">Noch keine Preset-Aktivitaet.</div>';
                return;
            }

            const html = items.slice(0, 6).map(entry => {
                const action = entry.action || 'Aktion';
                const name = entry.name || '-';
                const timestamp = entry.timestamp
                    ? new Date(entry.timestamp).toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' })
                    : '';
                return `<div class="graph-preset-audit-item"><strong>${action}</strong>: ${name}<span>${timestamp}</span></div>`;
            }).join('');

            panel.presetAuditList.innerHTML = html;
        }

        function logPresetEvent(action, name) {
            if (!Array.isArray(panelPresetMeta.history)) {
                panelPresetMeta.history = [];
            }

            panelPresetMeta.history.unshift({
                action,
                name: name || '-',
                timestamp: new Date().toISOString()
            });
            panelPresetMeta.history = panelPresetMeta.history.slice(0, 20);
            persistPanelPresetMeta();
            renderPresetAudit();
        }

        function applyPresetByName(name) {
            if (!name || !panelPresets[name]) {
                return false;
            }

            applySerializedPanelState(panelPresets[name]);
            if (panel?.presetSelect) {
                panel.presetSelect.value = name;
            }
            return true;
        }

        function renamePreset(oldName, newName) {
            if (!oldName || !newName || !panelPresets[oldName]) {
                return false;
            }

            panelPresets[newName] = panelPresets[oldName];
            if (newName !== oldName) {
                delete panelPresets[oldName];
            }

            if (panelPresetMeta.defaultPresetName === oldName) {
                panelPresetMeta.defaultPresetName = newName;
            }

            persistPanelPresets();
            persistPanelPresetMeta();
            renderPresetOptions();
            if (panel?.presetSelect) {
                panel.presetSelect.value = newName;
            }
            return true;
        }

        function duplicatePreset(name, duplicateName) {
            if (!name || !duplicateName || !panelPresets[name]) {
                return false;
            }

            panelPresets[duplicateName] = JSON.parse(JSON.stringify(panelPresets[name]));
            persistPanelPresets();
            renderPresetOptions();
            if (panel?.presetSelect) {
                panel.presetSelect.value = duplicateName;
            }
            return true;
        }

        function exportPresetsToFile() {
            const selectedName = panel?.presetSelect?.value || '';
            const payload = {
                version: 1,
                exportedAt: new Date().toISOString(),
                presets: selectedName && panelPresets[selectedName]
                    ? { [selectedName]: panelPresets[selectedName] }
                    : panelPresets
            };

            const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = selectedName
                ? `graph-preset-${selectedName.replace(/[^a-zA-Z0-9_-]+/g, '-')}.json`
                : 'graph-presets.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        function askConflictDecisionInPanel(name) {
            return new Promise((resolve) => {
                if (!panel?.importConflictModal) {
                    resolve('skip');
                    return;
                }

                const modal = panel.importConflictModal;
                const label = panel.importConflictLabel;
                const buttons = Array.from(modal.querySelectorAll('[data-conflict-decision]'));
                const previousFocus = document.activeElement;
                let resolved = false;

                if (label) {
                    label.textContent = `Preset "${name}" existiert bereits. Wie soll der Konflikt behandelt werden?`;
                }

                modal.classList.add('visible');
                modal.setAttribute('aria-hidden', 'false');

                const cleanup = () => {
                    buttons.forEach(btn => btn.removeEventListener('click', onDecision));
                    modal.removeEventListener('keydown', onKeydown);
                    modal.classList.remove('visible');
                    modal.setAttribute('aria-hidden', 'true');
                    if (previousFocus && typeof previousFocus.focus === 'function') {
                        previousFocus.focus();
                    }
                };

                const finalize = (decision) => {
                    if (resolved) {
                        return;
                    }
                    resolved = true;
                    cleanup();
                    resolve(decision);
                };

                const onDecision = (event) => {
                    const button = event.target.closest('[data-conflict-decision]');
                    if (!button) {
                        return;
                    }

                    const decision = button.getAttribute('data-conflict-decision') || 'skip';
                    finalize(decision);
                };

                const onKeydown = (event) => {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        finalize('cancel');
                        return;
                    }

                    if (event.key === 'Enter' && !event.target.closest('[data-conflict-decision]')) {
                        event.preventDefault();
                        finalize('skip');
                        return;
                    }

                    if (buttons.length > 0 && (event.key === 'ArrowRight' || event.key === 'ArrowLeft' || event.key === 'Home' || event.key === 'End')) {
                        event.preventDefault();
                        const activeIndex = Math.max(0, buttons.indexOf(document.activeElement));

                        if (event.key === 'Home') {
                            buttons[0].focus();
                            return;
                        }

                        if (event.key === 'End') {
                            buttons[buttons.length - 1].focus();
                            return;
                        }

                        const direction = event.key === 'ArrowRight' ? 1 : -1;
                        const nextIndex = (activeIndex + direction + buttons.length) % buttons.length;
                        buttons[nextIndex].focus();
                        return;
                    }

                    if (event.key !== 'Tab' || buttons.length === 0) {
                        return;
                    }

                    const first = buttons[0];
                    const last = buttons[buttons.length - 1];
                    const active = document.activeElement;

                    if (event.shiftKey && active === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && active === last) {
                        event.preventDefault();
                        first.focus();
                    }
                };

                buttons.forEach(btn => btn.addEventListener('click', onDecision));
                modal.addEventListener('keydown', onKeydown);
                buttons[0]?.focus();
            });
        }

        async function mergeImportedPresets(rawText, mode = 'overwrite') {
            const parsed = JSON.parse(rawText);
            const incoming = parsed?.presets && typeof parsed.presets === 'object'
                ? parsed.presets
                : parsed;

            if (!incoming || typeof incoming !== 'object') {
                throw new Error('Invalid preset format');
            }

            let importedCount = 0;
            let skippedCount = 0;
            let cancelled = false;
            let bulkDecision = null;

            for (const [name, value] of Object.entries(incoming)) {
                if (cancelled) {
                    break;
                }

                if (!name || typeof value !== 'object' || value === null) {
                    continue;
                }

                const exists = Boolean(panelPresets[name]);
                if (mode === 'new-only' && exists) {
                    skippedCount += 1;
                    continue;
                }
                if (mode === 'skip-existing' && exists) {
                    skippedCount += 1;
                    continue;
                }

                if (mode === 'ask-each' && exists) {
                    let decision = bulkDecision;
                    if (!decision) {
                        decision = await askConflictDecisionInPanel(name);
                    }

                    if (decision === 'cancel') {
                        cancelled = true;
                        break;
                    }

                    if (decision === 'overwrite-all') {
                        bulkDecision = 'overwrite';
                        decision = 'overwrite';
                    }

                    if (decision === 'skip-all') {
                        bulkDecision = 'skip';
                        decision = 'skip';
                    }

                    if (decision === 'skip') {
                        skippedCount += 1;
                        continue;
                    }
                }

                panelPresets[name] = value;
                importedCount += 1;
            }

            if (panel?.importConflictModal) {
                panel.importConflictModal.classList.remove('visible');
            }

            persistPanelPresets();
            renderPresetOptions();
            return { importedCount, skippedCount, cancelled };
        }

        function renderPresetOptions() {
            if (!panel || !panel.presetSelect) {
                return;
            }

            panel.presetSelect.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Preset laden...';
            panel.presetSelect.appendChild(placeholder);

            Object.keys(panelPresets)
                .sort((a, b) => a.localeCompare(b, 'de-DE'))
                .forEach(name => {
                    const option = document.createElement('option');
                    option.value = name;
                    option.textContent = name;
                    panel.presetSelect.appendChild(option);
                });

            if (panel.autoApplyPresetCheckbox) {
                panel.autoApplyPresetCheckbox.checked = panelPresetMeta.autoApplyPreset;
            }

            renderPresetAudit();
        }

        function renderTypeChips(typeCounts) {
            if (!panel || !panel.typeChips) {
                return;
            }

            const fragment = document.createDocumentFragment();
            const allCount = graphData.nodes.length;

            const allChip = document.createElement('button');
            allChip.type = 'button';
            allChip.className = 'graph-type-chip';
            if (panelState.type === 'all') {
                allChip.classList.add('active');
            }
            allChip.textContent = `Alle (${allCount})`;
            allChip.addEventListener('click', () => {
                panelState.type = 'all';
                syncPanelControls();
                applyPanelState();
            });
            fragment.appendChild(allChip);

            typeCounts.forEach((count, type) => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'graph-type-chip';
                if (panelState.type === type) {
                    chip.classList.add('active');
                }

                chip.textContent = `${type} (${count})`;
                chip.addEventListener('click', () => {
                    panelState.type = type;
                    syncPanelControls();
                    applyPanelState();
                });
                fragment.appendChild(chip);
            });

            panel.typeChips.innerHTML = '';
            panel.typeChips.appendChild(fragment);
        }

        function updateSelectionCount() {
            if (!panel || !panel.selectionCount) {
                return;
            }

            panel.selectionCount.textContent = `${panelState.selectedIds.size} ausgewaehlt | ${panelState.visibleIds.size} sichtbar`;
        }

        function createPanelNodeItem(n) {
            const item = document.createElement('div');
            item.className = 'graph-node-item';
            if (panelState.selectedIds.has(n.id)) {
                item.classList.add('selected');
            }

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = panelState.selectedIds.has(n.id);
            checkbox.setAttribute('aria-label', `Select ${n.label}`);
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    panelState.selectedIds.add(n.id);
                } else {
                    panelState.selectedIds.delete(n.id);
                }

                applyPanelState();
            });

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = `${n.icon || '•'} ${n.label} (${n.type})`;
            button.addEventListener('click', () => {
                focusNodeById(n.id, true);
                panelState.selectedIds.add(n.id);
                applyPanelState();
            });

            item.appendChild(checkbox);
            item.appendChild(button);
            return item;
        }

        function updateListMoreIndicator() {
            if (!panel || !panel.list) {
                return;
            }

            const existing = panel.list.querySelector('.graph-node-list-more');
            if (existing) {
                existing.remove();
            }

            const renderedCount = panel.list.querySelectorAll('.graph-node-item').length;
            const total = panelState.sortedVisibleNodes.length;

            if (renderedCount >= total) {
                return;
            }

            const more = document.createElement('div');
            more.className = 'graph-node-list-more';
            more.textContent = `${renderedCount} von ${total} angezeigt. Scrollen fuer mehr...`;
            panel.list.appendChild(more);
        }

        function renderPanelListChunk(reset = false) {
            if (!panel || !panel.list) {
                return;
            }

            if (reset) {
                panel.list.innerHTML = '';
            }

            const startIndex = panel.list.querySelectorAll('.graph-node-item').length;
            const endIndex = Math.min(panelState.listRenderLimit, panelState.sortedVisibleNodes.length);

            if (startIndex >= endIndex) {
                updateListMoreIndicator();
                return;
            }

            const fragment = document.createDocumentFragment();
            for (let i = startIndex; i < endIndex; i += 1) {
                fragment.appendChild(createPanelNodeItem(panelState.sortedVisibleNodes[i]));
            }

            const existingMore = panel.list.querySelector('.graph-node-list-more');
            if (existingMore) {
                existingMore.remove();
            }

            panel.list.appendChild(fragment);
            updateListMoreIndicator();
        }

        function renderPanelList() {
            if (!panel || !panel.list) {
                return;
            }

            panel.list.innerHTML = '';

            const visibleNodes = graphData.nodes.filter(n => panelState.visibleIds.has(n.id));
            if (visibleNodes.length === 0) {
                panel.list.innerHTML = '<div class="graph-panel-empty">Keine Knoten fuer die aktuelle Filterung gefunden.</div>';
                updateSelectionCount();
                return;
            }

            panelState.sortedVisibleNodes = getSortedNodes(visibleNodes);
            panelState.listRenderLimit = panelState.listRenderStep;
            renderPanelListChunk(true);
            updateSelectionCount();
        }

        function applyPanelState() {
            const searchValue = panelState.search.trim().toLowerCase();
            const fromTimestamp = getDayTimestamp(panelState.dateFrom, false);
            const toTimestamp = getDayTimestamp(panelState.dateTo, true);
            const nextVisible = new Set();

            graphData.nodes.forEach(n => {
                const typeMatch = panelState.type === 'all' || n.type === panelState.type;
                const searchMatch = searchValue.length === 0 || (n.label || '').toLowerCase().includes(searchValue);
                const selectionMatch = !panelState.selectedOnly || panelState.selectedIds.has(n.id);
                const countMatch = panelState.minCount <= 0 || (Number(n.count) || 0) >= panelState.minCount;

                let dateMatch = true;
                if (fromTimestamp !== null || toTimestamp !== null) {
                    const nodeTime = n.date ? new Date(n.date).getTime() : NaN;
                    if (!Number.isFinite(nodeTime)) {
                        dateMatch = false;
                    } else {
                        if (fromTimestamp !== null && nodeTime < fromTimestamp) {
                            dateMatch = false;
                        }
                        if (toTimestamp !== null && nodeTime > toTimestamp) {
                            dateMatch = false;
                        }
                    }
                }

                if (typeMatch && searchMatch && selectionMatch && countMatch && dateMatch) {
                    nextVisible.add(n.id);
                }
            });

            panelState.visibleIds = nextVisible;

            const highlightMode = panelState.filterMode === 'highlight';

            if (highlightMode) {
                node
                    .style('display', null)
                    .style('pointer-events', 'auto')
                    .style('opacity', d => panelState.visibleIds.has(d.id) ? 1 : 0.18);

                link
                    .style('display', null)
                    .style('pointer-events', 'none');

                linkLabel
                    .style('display', null)
                    .style('opacity', l => {
                        const sourceId = resolveNodeId(l.source);
                        const targetId = resolveNodeId(l.target);
                        return panelState.visibleIds.has(sourceId) && panelState.visibleIds.has(targetId) ? 0.25 : 0.04;
                    });
            } else {
                node
                    .style('display', d => panelState.visibleIds.has(d.id) ? null : 'none')
                    .style('pointer-events', d => panelState.visibleIds.has(d.id) ? 'auto' : 'none')
                    .style('opacity', 1);

                link.style('display', l => {
                    const sourceId = resolveNodeId(l.source);
                    const targetId = resolveNodeId(l.target);
                    return panelState.visibleIds.has(sourceId) && panelState.visibleIds.has(targetId) ? null : 'none';
                });

                linkLabel.style('display', l => {
                    const sourceId = resolveNodeId(l.source);
                    const targetId = resolveNodeId(l.target);
                    return panelState.visibleIds.has(sourceId) && panelState.visibleIds.has(targetId) ? null : 'none';
                });
            }

            if (!highlightMode && tooltipState.currentNodeId && !panelState.visibleIds.has(tooltipState.currentNodeId)) {
                setTooltipPinned(null, false);
                scheduleTooltipHide();
            }

            updateSelectionStyles();
            renderPanelList();
            const typeCounts = new Map();
            graphData.nodes.forEach(n => {
                typeCounts.set(n.type, (typeCounts.get(n.type) || 0) + 1);
            });
            renderTypeChips(typeCounts);
            persistPanelState();
            syncUrlState();
        }

        function initializePanel() {
            if (!panel) {
                return;
            }

            const uniqueTypes = [...new Set(graphData.nodes.map(n => n.type).filter(Boolean))]
                .sort((a, b) => a.localeCompare(b, 'de-DE'));
            uniqueTypes.forEach(type => {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = `Typ: ${type}`;
                panel.typeSelect?.appendChild(option);
            });

            hydratePanelState();
            loadPanelPresets();
            loadPanelPresetMeta();
            if (panelPresetMeta.autoApplyPreset && panelPresetMeta.defaultPresetName) {
                applyPresetByName(panelPresetMeta.defaultPresetName);
            }
            hydratePanelStateFromUrl(uniqueTypes);
            renderPresetOptions();

            if (panelState.type !== 'all' && !uniqueTypes.includes(panelState.type)) {
                panelState.type = 'all';
            }

            syncPanelControls();

            panel.toggleHelpBtn?.addEventListener('click', () => {
                panelState.helpVisible = !panelState.helpVisible;
                renderInlineHelpState();
                persistPanelState();
            });

            panel.searchInput?.addEventListener('input', (e) => {
                panelState.search = e.target.value || '';
                if (panelState.searchDebounceTimer) {
                    clearTimeout(panelState.searchDebounceTimer);
                }

                panelState.searchDebounceTimer = setTimeout(() => {
                    panelState.searchDebounceTimer = null;
                    applyPanelState();
                }, 180);
            });

            panel.typeSelect?.addEventListener('change', (e) => {
                panelState.type = e.target.value || 'all';
                applyPanelState();
            });

            panel.sortSelect?.addEventListener('change', (e) => {
                panelState.sort = e.target.value || 'label_asc';
                applyPanelState();
            });

            panel.selectedOnlyCheckbox?.addEventListener('change', (e) => {
                panelState.selectedOnly = Boolean(e.target.checked);
                applyPanelState();
            });

            panel.highlightOnlyCheckbox?.addEventListener('change', (e) => {
                panelState.filterMode = e.target.checked ? 'highlight' : 'hide';
                applyPanelState();
            });

            panel.minCountInput?.addEventListener('input', (e) => {
                const parsed = Number(e.target.value);
                panelState.minCount = Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
                applyPanelState();
            });

            panel.dateFromInput?.addEventListener('change', (e) => {
                panelState.dateFrom = e.target.value || '';
                applyPanelState();
            });

            panel.dateToInput?.addEventListener('change', (e) => {
                panelState.dateTo = e.target.value || '';
                applyPanelState();
            });

            panel.selectVisibleBtn?.addEventListener('click', () => {
                panelState.visibleIds.forEach(id => panelState.selectedIds.add(id));
                applyPanelState();
            });

            panel.clearSelectionBtn?.addEventListener('click', () => {
                panelState.selectedIds.clear();
                applyPanelState();
            });

            panel.resetFiltersBtn?.addEventListener('click', () => {
                panelState.search = '';
                panelState.type = 'all';
                panelState.sort = 'label_asc';
                panelState.selectedOnly = false;
                panelState.filterMode = 'hide';
                panelState.minCount = 0;
                panelState.dateFrom = '';
                panelState.dateTo = '';
                syncPanelControls();
                applyPanelState();
            });

            panel.presetSelect?.addEventListener('change', (e) => {
                const presetName = e.target.value;
                if (!presetName || !panelPresets[presetName]) {
                    return;
                }

                applyPresetByName(presetName);
                panelPresetMeta.defaultPresetName = presetName;
                persistPanelPresetMeta();
                logPresetEvent('Laden', presetName);
                syncPanelControls();
                applyPanelState();
            });

            panel.savePresetBtn?.addEventListener('click', () => {
                const currentName = panel.presetSelect?.value || '';
                const presetName = window.prompt('Name fuer Preset:', currentName);
                if (!presetName) {
                    return;
                }

                const name = presetName.trim();
                if (!name) {
                    return;
                }

                panelPresets[name] = serializeCurrentPanelState();
                persistPanelPresets();
                renderPresetOptions();
                panelPresetMeta.defaultPresetName = name;
                persistPanelPresetMeta();
                logPresetEvent('Speichern', name);
                if (panel.presetSelect) {
                    panel.presetSelect.value = name;
                }
            });

            panel.deletePresetBtn?.addEventListener('click', () => {
                const selectedName = panel.presetSelect?.value || '';
                if (!selectedName || !panelPresets[selectedName]) {
                    return;
                }

                if (!window.confirm(`Preset \"${selectedName}\" wirklich loeschen?`)) {
                    return;
                }

                delete panelPresets[selectedName];
                persistPanelPresets();
                if (panelPresetMeta.defaultPresetName === selectedName) {
                    panelPresetMeta.defaultPresetName = '';
                    persistPanelPresetMeta();
                }
                logPresetEvent('Loeschen', selectedName);
                renderPresetOptions();
                if (panel.presetSelect) {
                    panel.presetSelect.value = '';
                }
            });

            panel.renamePresetBtn?.addEventListener('click', () => {
                const selectedName = panel.presetSelect?.value || '';
                if (!selectedName || !panelPresets[selectedName]) {
                    return;
                }

                const newName = window.prompt('Neuer Preset-Name:', selectedName);
                if (!newName) {
                    return;
                }

                const trimmed = newName.trim();
                if (!trimmed) {
                    return;
                }

                if (trimmed !== selectedName && panelPresets[trimmed]) {
                    if (!window.confirm(`Preset \"${trimmed}\" existiert bereits. Ueberschreiben?`)) {
                        return;
                    }
                }

                renamePreset(selectedName, trimmed);
                logPresetEvent('Umbenennen', `${selectedName} -> ${trimmed}`);
                applyPanelState();
            });

            panel.duplicatePresetBtn?.addEventListener('click', () => {
                const selectedName = panel.presetSelect?.value || '';
                if (!selectedName || !panelPresets[selectedName]) {
                    return;
                }

                const suggested = `${selectedName}-copy`;
                const duplicateName = window.prompt('Name fuer Duplikat:', suggested);
                if (!duplicateName) {
                    return;
                }

                const trimmed = duplicateName.trim();
                if (!trimmed) {
                    return;
                }

                if (panelPresets[trimmed] && !window.confirm(`Preset \"${trimmed}\" existiert bereits. Ueberschreiben?`)) {
                    return;
                }

                duplicatePreset(selectedName, trimmed);
                logPresetEvent('Duplizieren', `${selectedName} -> ${trimmed}`);
                applyPanelState();
            });

            panel.exportPresetBtn?.addEventListener('click', () => {
                exportPresetsToFile();
                const selectedName = panel.presetSelect?.value || 'alle';
                logPresetEvent('Export', selectedName);
            });

            panel.importPresetBtn?.addEventListener('click', () => {
                panel.importPresetInput?.click();
            });

            panel.importPresetInput?.addEventListener('change', async (e) => {
                const file = e.target.files?.[0];
                if (!file) {
                    return;
                }

                try {
                    const fileText = await file.text();
                    const mode = panel.importModeSelect?.value || 'overwrite';
                    const result = await mergeImportedPresets(fileText, mode);
                    if (result.cancelled) {
                        const partialInfo = result.importedCount > 0 ? ` (${result.importedCount} bereits importiert)` : '';
                        window.alert(`Import abgebrochen${partialInfo}.`);
                    } else {
                        if (result.importedCount <= 0) {
                            window.alert('Keine gueltigen Presets importiert.');
                        } else {
                            const skipInfo = result.skippedCount > 0 ? `, ${result.skippedCount} uebersprungen` : '';
                            window.alert(`${result.importedCount} Preset(s) importiert${skipInfo}.`);
                        }
                    }

                    const detail = [
                        `${result.importedCount} importiert`,
                        result.skippedCount > 0 ? `${result.skippedCount} uebersprungen` : null,
                        result.cancelled ? 'abgebrochen' : null
                    ].filter(Boolean).join(', ');
                    logPresetEvent('Import', detail);
                } catch (error) {
                    console.warn('Preset import failed', error);
                    window.alert('Preset-Import fehlgeschlagen. Bitte JSON-Datei pruefen.');
                } finally {
                    e.target.value = '';
                }
            });

            panel.autoApplyPresetCheckbox?.addEventListener('change', (e) => {
                panelPresetMeta.autoApplyPreset = Boolean(e.target.checked);
                panelPresetMeta.defaultPresetName = panel.presetSelect?.value || panelPresetMeta.defaultPresetName;
                persistPanelPresetMeta();
                logPresetEvent('Auto-Apply', panelPresetMeta.autoApplyPreset ? 'aktiv' : 'inaktiv');
            });

            panel.shareViewBtn?.addEventListener('click', () => {
                copyShareLink();
            });

            if (panel.list && !panelState.isListScrollBound) {
                panel.list.addEventListener('scroll', () => {
                    const nearBottom = panel.list.scrollTop + panel.list.clientHeight >= panel.list.scrollHeight - 80;
                    if (!nearBottom) {
                        return;
                    }

                    if (panelState.listRenderLimit >= panelState.sortedVisibleNodes.length) {
                        return;
                    }

                    panelState.listRenderLimit += panelState.listRenderStep;
                    renderPanelListChunk(false);
                });
                panelState.isListScrollBound = true;
            }

            applyPanelState();
        }

        tooltip.on('click', function(event) {
            const target = event.target.closest('[data-tooltip-action]');
            if (!target) {
                return;
            }

            const action = target.getAttribute('data-tooltip-action');
            const activeNodeId = tooltipState.currentNodeId;
            const activeNode = activeNodeId ? nodeDataById.get(activeNodeId) : null;
            if (!activeNode) {
                return;
            }

            if (action === 'open' && activeNode.url) {
                window.location.href = activeNode.url;
                return;
            }

            if (action === 'pin') {
                const nextPinned = !(tooltipState.isPinned && tooltipState.pinnedNodeId === activeNode.id);
                setTooltipPinned(activeNode.id, nextPinned);
                if (!nextPinned) {
                    scheduleTooltipHide();
                }
                return;
            }

            if (action === 'close') {
                setTooltipPinned(null, false);
                scheduleTooltipHide();
            }
        });

        initializePanel();

        // Add hover effects
        node.on('mouseover', function(event, d) {
            if (tooltipState.isPinned && tooltipState.pinnedNodeId !== d.id) {
                return;
            }

            clearTooltipTimer('hideTimer');
            tooltipState.activeTouchNodeId = null;
            highlightNodeAndLinks(this, d);
            renderTooltipContent(d);
            scheduleTooltipShow(getEventPosition(event, this));
        })
        .on('mousemove', function(event) {
            if (tooltipState.isVisible && !tooltipState.isPinned) {
                queueTooltipPosition(event);
            }
        })
        .on('mouseout', function(event, d) {
            if (tooltipState.isPinned && tooltipState.pinnedNodeId === d.id) {
                return;
            }

            resetNodeAndLinks(this, d);
            scheduleTooltipHide();
        })
        .on('focus', function(event, d) {
            if (tooltipState.isPinned && tooltipState.pinnedNodeId !== d.id) {
                return;
            }

            highlightNodeAndLinks(this, d);
            renderTooltipContent(d);
            scheduleTooltipShow(getEventPosition(event, this), true);
        })
        .on('blur', function(event, d) {
            if (tooltipState.isPinned && tooltipState.pinnedNodeId === d.id) {
                return;
            }

            resetNodeAndLinks(this, d);
            scheduleTooltipHide();
        })
        .on('keydown', function(event, d) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (d.url) {
                    window.location.href = d.url;
                }
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                resetNodeAndLinks(this, d);
                setTooltipPinned(null, false);
                scheduleTooltipHide();
            }
        })
        .on('touchstart', function(event, d) {
            const isSameNode = tooltipState.activeTouchNodeId === d.id;

            if (!isSameNode || !tooltipState.isVisible) {
                event.preventDefault();
                tooltipState.activeTouchNodeId = d.id;
                tooltipState.suppressNextClick = true;
                highlightNodeAndLinks(this, d);
                renderTooltipContent(d);
                showTooltip(getEventPosition(event, this), true);
                setTooltipPinned(d.id, true);
                return;
            }

            if (d.url) {
                window.location.href = d.url;
            }
        })
        .on('click', function(event, d) {
            if (tooltipState.suppressNextClick) {
                event.preventDefault();
                event.stopPropagation();
                tooltipState.suppressNextClick = false;
                return;
            }

            if (d.url) {
                window.location.href = d.url;
            }
        });

        // Update positions on simulation tick
        simulation.on('tick', () => {
            link
                .attr('x1', d => d.source.x)
                .attr('y1', d => d.source.y)
                .attr('x2', d => d.target.x)
                .attr('y2', d => d.target.y);

            linkLabel
                .attr('x', d => (d.source.x + d.target.x) / 2)
                .attr('y', d => (d.source.y + d.target.y) / 2);

            node.attr('transform', d => `translate(${d.x},${d.y})`);
        });

        // Drag functions
        function dragstarted(event, d) {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        }

        function dragged(event, d) {
            d.fx = event.x;
            d.fy = event.y;
        }

        function dragended(event, d) {
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        }

        // Initial zoom to fit
        const bounds = g.node().getBBox();
        const fullWidth = bounds.width;
        const fullHeight = bounds.height;
        const midX = bounds.x + fullWidth / 2;
        const midY = bounds.y + fullHeight / 2;

        if (fullWidth > 0 && fullHeight > 0) {
            const scale = 0.8 / Math.max(fullWidth / width, fullHeight / height);
            const translate = [width / 2 - scale * midX, height / 2 - scale * midY];

            svg.call(zoom.transform, d3.zoomIdentity
                .translate(translate[0], translate[1])
                .scale(scale));
        }

        return { simulation, svg, zoom };
    }

    /**
     * Create fallback HTML navigation tree (for browsers without D3)
     */
    function createFallbackTree(nodes) {
        let html = '<div class="fallback-nav-tree">';
        
        // Group nodes by level for hierarchical display
        const levels = new Map();
        nodes.forEach(node => {
            if (!levels.has(node.level)) {
                levels.set(node.level, []);
            }
            levels.get(node.level).push(node);
        });

        // Start node (home)
        const homeNode = nodes.find(n => n.id === 'home');
        if (homeNode) {
            html += '<div class="nav-level level-0">';
            html += `<div class="nav-node start-node ${homeNode.type}" data-url="${homeNode.url}" onclick="window.location.href='${homeNode.url}'">`;
            html += `<span class="node-icon">${homeNode.icon}</span>`;
            html += `<span class="node-label">${homeNode.label}</span>`;
            html += '</div>';
            html += '</div>';
        }

        // Process other levels
        levels.forEach((nodeList, level) => {
            if (level === 0) return; // Skip home level
            
            html += `<div class="nav-level level-${level}">`;
            
            nodeList.forEach((node, index) => {
                if (node.id === 'home') return;
                
                const indent = level * 40;
                
                // Edge connector
                if (index > 0 || level > 1) {
                    html += `<div class="nav-connector" style="margin-left: ${indent}px" data-level="${level}">`;
                    html += '<span class="connector-label">└─</span>';
                    html += '</div>';
                }
                
                html += `<div class="nav-node ${node.type}" data-url="${node.url}" data-level="${level}" style="margin-left: ${indent}px" onclick="window.location.href='${node.url}'">`;
                html += `<span class="node-icon">${node.icon}</span>`;
                html += `<span class="node-label">${node.label}</span>`;
                html += '</div>';
            });
            
            html += '</div>';
        });

        html += '</div>';
        return html;
    }

    /**
     * Render navigation overlay
     */
    async function renderNavigationOverlay() {
        const graphData = buildGraphData();
        if (!graphData || graphData.nodes.length === 0) {
            console.warn('No navigation menu found');
            return;
        }

        const overlay = document.getElementById('themisdb-nav-overlay');
        const container = overlay.querySelector('.nav-graph-container');
        const panelContainer = overlay.querySelector('.graph-side-panel');

        if (d3Loaded && d3) {
            // Use D3.js force-directed graph (Neo4j Bloom style)
            createForceDirectedGraph(container, graphData, panelContainer);
        } else {
            // Use fallback
            container.innerHTML = createFallbackTree(graphData.nodes);
            animateFallbackTree(container);
            if (panelContainer) {
                panelContainer.innerHTML = '<div class="graph-panel-empty">Filter und Selektion sind im erweiterten D3-Modus verfuegbar.</div>';
            }
        }
    }

    function ensureOverlayStyles() {
        if (document.getElementById('themisdb-graph-panel-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'themisdb-graph-panel-styles';
        style.textContent = `
            .themisdb-graph-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 340px;
                gap: 14px;
                width: min(1320px, calc(100vw - 40px));
                height: min(84vh, 860px);
                margin: 0 auto;
            }

            .graph-side-panel {
                background: linear-gradient(180deg, rgba(16, 20, 30, 0.95) 0%, rgba(24, 29, 42, 0.95) 100%);
                border: 1px solid rgba(107, 139, 255, 0.32);
                border-radius: 14px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
                padding: 14px;
                color: #e8efff;
                display: flex;
                flex-direction: column;
                min-height: 0;
            }

            .graph-panel-title {
                margin: 0 0 10px;
                font-size: 1rem;
                font-weight: 700;
                letter-spacing: 0.02em;
            }

            .graph-panel-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .graph-panel-grid input,
            .graph-panel-grid select {
                width: 100%;
                border-radius: 8px;
                border: 1px solid rgba(124, 150, 255, 0.35);
                background: rgba(11, 16, 25, 0.8);
                color: #f2f6ff;
                padding: 8px 10px;
                outline: none;
            }

            .graph-panel-grid button {
                border: 1px solid rgba(107, 139, 255, 0.45);
                background: rgba(68, 90, 176, 0.35);
                color: #f2f6ff;
                border-radius: 8px;
                padding: 7px 10px;
                cursor: pointer;
                font-size: 12px;
            }

            .graph-panel-grid input[type="checkbox"] {
                width: auto;
                accent-color: #8fb3ff;
            }

            .graph-panel-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }

            .graph-panel-row select {
                flex: 1;
            }

            .graph-panel-row span.small {
                font-size: 11px;
                color: #aac0ee;
                white-space: nowrap;
            }

            .graph-panel-row.wrap {
                flex-wrap: wrap;
            }

            .graph-panel-row.wrap button {
                flex: 1;
                min-width: 92px;
            }

            .graph-visually-hidden {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .graph-preset-audit {
                margin-top: 6px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                max-height: 96px;
                overflow: auto;
                padding: 6px;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(130, 152, 233, 0.22);
            }

            .graph-preset-audit-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                font-size: 11px;
                color: #c8d8fa;
            }

            .graph-preset-audit-item span {
                color: #91abdc;
                font-variant-numeric: tabular-nums;
            }

            .graph-inline-help {
                margin-top: 6px;
                display: none;
                border: 1px solid rgba(120, 150, 235, 0.3);
                background: rgba(9, 14, 24, 0.92);
                border-radius: 10px;
                padding: 10px;
                font-size: 12px;
                line-height: 1.45;
                color: #d7e3ff;
            }

            .graph-inline-help.visible {
                display: block;
            }

            .graph-inline-help strong {
                color: #f4f8ff;
            }

            .graph-inline-help ul {
                margin: 6px 0 0;
                padding-left: 16px;
            }

            .graph-inline-help li {
                margin: 4px 0;
            }

            .graph-import-conflict-modal {
                margin-top: 8px;
                border: 1px solid rgba(120, 150, 235, 0.35);
                background: rgba(10, 16, 28, 0.95);
                border-radius: 10px;
                padding: 10px;
                display: none;
            }

            .graph-import-conflict-modal.visible {
                display: block;
            }

            .graph-import-conflict-label {
                font-size: 12px;
                color: #dbe7ff;
                margin-bottom: 8px;
                line-height: 1.4;
            }

            .graph-import-conflict-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .graph-import-conflict-actions button {
                flex: 1;
                min-width: 96px;
            }

            .graph-import-conflict-actions button:focus-visible {
                outline: 2px solid rgba(255, 215, 120, 0.95);
                outline-offset: 1px;
            }

            .graph-panel-row button {
                border: 1px solid rgba(107, 139, 255, 0.45);
                background: rgba(68, 90, 176, 0.35);
                color: #f2f6ff;
                border-radius: 8px;
                padding: 7px 10px;
                cursor: pointer;
                font-size: 12px;
            }

            .graph-type-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .graph-type-chip {
                border: 1px solid rgba(124, 150, 255, 0.45);
                background: rgba(23, 33, 56, 0.85);
                color: #dce7ff;
                border-radius: 999px;
                padding: 4px 10px;
                cursor: pointer;
                font-size: 12px;
                line-height: 1.4;
            }

            .graph-type-chip.active {
                background: rgba(255, 204, 97, 0.26);
                border-color: rgba(255, 215, 120, 0.8);
                color: #fff6d9;
            }

            .graph-selection-count {
                font-size: 12px;
                color: #b8c8f8;
            }

            .graph-node-list {
                margin-top: 10px;
                border-top: 1px solid rgba(130, 152, 233, 0.3);
                padding-top: 10px;
                overflow: auto;
                min-height: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .graph-node-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.04);
            }

            .graph-node-item button {
                border: 0;
                background: transparent;
                color: #e8efff;
                text-align: left;
                width: 100%;
                cursor: pointer;
                font-size: 13px;
            }

            .graph-node-item.selected {
                background: rgba(255, 202, 93, 0.22);
                box-shadow: inset 0 0 0 1px rgba(255, 215, 120, 0.6);
            }

            .graph-node-list-more {
                padding: 8px;
                text-align: center;
                font-size: 12px;
                color: #a9bde8;
                background: rgba(255, 255, 255, 0.03);
                border-radius: 8px;
            }

            .graph-panel-empty {
                color: #d8e4ff;
                font-size: 13px;
                line-height: 1.5;
            }

            @media (max-width: 960px) {
                .themisdb-graph-layout {
                    grid-template-columns: 1fr;
                    height: min(90vh, 920px);
                }

                .graph-side-panel {
                    max-height: 36vh;
                }
            }
        `;

        document.head.appendChild(style);
    }

    /**
     * Animate fallback tree
     */
    function animateFallbackTree(container) {
        const nodes = container.querySelectorAll('.nav-node');
        const connectors = container.querySelectorAll('.nav-connector');

        nodes.forEach((node, index) => {
            setTimeout(() => {
                node.classList.add('visible');
            }, index * 100);
        });

        setTimeout(() => {
            connectors.forEach((connector, index) => {
                setTimeout(() => {
                    connector.classList.add('visible');
                }, index * 50);
            });
        }, nodes.length * 100);
    }

    /**
     * Toggle overlay visibility
     */
    function toggleOverlay() {
        const overlay = document.getElementById('themisdb-nav-overlay');
        const button = document.getElementById('nav-graph-toggle');
        
        if (overlay.classList.contains('visible')) {
            overlay.classList.remove('visible');
            button.setAttribute('aria-expanded', 'false');
            button.innerHTML = `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H3v2.5a.5.5 0 0 1-1 0v-3zm12 12a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H13v-2.5a.5.5 0 0 1 1 0v3zM2 9.5a.5.5 0 0 1 .5.5v2.5H5a.5.5 0 0 1 0 1H2.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5zm12-7a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V3h-2.5a.5.5 0 0 1 0-1h3a.5.5 0 0 1 .5.5z"/>
            </svg>`;
        } else {
            overlay.classList.add('visible');
            button.setAttribute('aria-expanded', 'true');
            button.innerHTML = `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
            </svg>`;
        }
    }

    /**
     * Initialize navigation graph overlay
     */
    async function init() {
        ensureOverlayStyles();

        // Create overlay HTML
        const overlay = document.createElement('div');
        overlay.id = 'themisdb-nav-overlay';
        overlay.className = 'nav-graph-overlay';
        overlay.innerHTML = `
            <div class="themisdb-graph-layout">
                <div class="nav-graph-container">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p>Loading interactive graph navigation...</p>
                    </div>
                </div>
                <aside class="graph-side-panel" aria-label="Graph controls">
                    <h3 class="graph-panel-title">Navigation Explorer</h3>
                    <div class="graph-panel-grid">
                        <div class="graph-type-chips" id="graph-type-chips" aria-label="Schnellfilter Typ"></div>
                        <input id="graph-filter-search" type="search" placeholder="Suchen nach Titel..." />
                        <select id="graph-filter-type">
                            <option value="all">Alle Typen</option>
                        </select>
                        <select id="graph-sort-select">
                            <option value="label_asc">Sortierung: Titel A-Z</option>
                            <option value="label_desc">Sortierung: Titel Z-A</option>
                            <option value="type_asc">Sortierung: Typ</option>
                            <option value="size_desc">Sortierung: Groesse</option>
                            <option value="count_desc">Sortierung: Count</option>
                        </select>
                        <label class="graph-panel-row" for="graph-highlight-only">
                            <span>Highlight-only Modus</span>
                            <input id="graph-highlight-only" type="checkbox" />
                        </label>
                        <input id="graph-filter-min-count" type="number" min="0" step="1" placeholder="Mindest-Count (z. B. 3)" />
                        <input id="graph-filter-date-from" type="date" aria-label="Datum von" />
                        <input id="graph-filter-date-to" type="date" aria-label="Datum bis" />
                        <label class="graph-panel-row" for="graph-selected-only">
                            <span>Nur Selektion anzeigen</span>
                            <input id="graph-selected-only" type="checkbox" />
                        </label>
                        <button id="graph-toggle-help" type="button" aria-expanded="false">Hilfe anzeigen</button>
                        <div class="graph-inline-help" id="graph-inline-help" aria-hidden="true">
                            <strong>Schnellhilfe Bedienung</strong>
                            <ul>
                                <li>Maus: Hover zeigt Details, Klick auf Node oeffnet Zielseite.</li>
                                <li>Tastatur: Tab auf Node, Enter/Leertaste oeffnet, Escape schliesst Tooltip.</li>
                                <li>Panel: Suche, Typ, Sortierung und Datum filtern den Graph live.</li>
                                <li>Modus: Highlight-only dimmt unpassende Knoten statt sie auszublenden.</li>
                                <li>Presets: Speichern/Laden fuer wiederkehrende Ansichten.</li>
                                <li>Import: Konflikte ueber den Modus waehlen oder je Preset entscheiden.</li>
                            </ul>
                        </div>
                        <label class="graph-panel-row" for="graph-auto-apply-preset">
                            <span>Preset automatisch laden</span>
                            <input id="graph-auto-apply-preset" type="checkbox" />
                        </label>
                        <div class="graph-panel-row">
                            <button id="graph-select-visible" type="button">Sichtbare auswaehlen</button>
                            <button id="graph-clear-selection" type="button">Selektion loeschen</button>
                        </div>
                        <div class="graph-panel-row">
                            <select id="graph-preset-select" aria-label="Preset Auswahl">
                                <option value="">Preset laden...</option>
                            </select>
                            <button id="graph-save-preset" type="button">Speichern</button>
                            <button id="graph-delete-preset" type="button">Loeschen</button>
                        </div>
                        <div class="graph-panel-row wrap">
                            <button id="graph-rename-preset" type="button">Umbenennen</button>
                            <button id="graph-duplicate-preset" type="button">Duplizieren</button>
                            <button id="graph-export-presets" type="button">Export</button>
                            <button id="graph-import-presets" type="button">Import</button>
                            <input id="graph-import-presets-input" class="graph-visually-hidden" type="file" accept="application/json,.json" />
                        </div>
                        <label class="graph-panel-row" for="graph-import-mode">
                            <span class="small">Import-Konflikte</span>
                            <select id="graph-import-mode" aria-label="Import Konfliktmodus">
                                <option value="overwrite">Ueberschreiben</option>
                                <option value="skip-existing">Bestehende ueberspringen</option>
                                <option value="new-only">Nur neue Presets</option>
                                <option value="ask-each">Fragen je Preset</option>
                            </select>
                        </label>
                        <div class="graph-preset-audit" id="graph-preset-audit" aria-label="Preset Historie"></div>
                        <div class="graph-import-conflict-modal" id="graph-import-conflict-modal" role="dialog" aria-modal="true" aria-labelledby="graph-import-conflict-label" aria-live="polite" aria-hidden="true" tabindex="-1">
                            <div class="graph-import-conflict-label" id="graph-import-conflict-label"></div>
                            <div class="graph-import-conflict-actions">
                                <button type="button" data-conflict-decision="overwrite">Ueberschreiben</button>
                                <button type="button" data-conflict-decision="skip">Ueberspringen</button>
                                <button type="button" data-conflict-decision="overwrite-all">Alle ueberschreiben</button>
                                <button type="button" data-conflict-decision="skip-all">Alle ueberspringen</button>
                                <button type="button" data-conflict-decision="cancel">Abbrechen</button>
                            </div>
                        </div>
                        <button id="graph-reset-filters" type="button">Filter zuruecksetzen</button>
                        <button id="graph-share-view" type="button">Ansicht-Link kopieren</button>
                        <div class="graph-selection-count" id="graph-selection-count">0 ausgewaehlt</div>
                    </div>
                    <div class="graph-node-list" id="graph-node-list" role="listbox" aria-label="Graph nodes"></div>
                </aside>
            </div>
            <div class="graph-controls">
                <button class="control-btn" id="zoom-in" title="Zoom In">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                </button>
                <button class="control-btn" id="zoom-out" title="Zoom Out">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/>
                    </svg>
                </button>
                <button class="control-btn" id="reset-view" title="Reset View">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                    </svg>
                </button>
            </div>
        `;
        document.body.appendChild(overlay);

        // Create toggle button
        const button = document.createElement('button');
        button.id = 'nav-graph-toggle';
        button.className = 'nav-graph-toggle';
        button.setAttribute('aria-label', 'Toggle Navigation Graph');
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = `<svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H3v2.5a.5.5 0 0 1-1 0v-3zm12 12a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H13v-2.5a.5.5 0 0 1 1 0v3zM2 9.5a.5.5 0 0 1 .5.5v2.5H5a.5.5 0 0 1 0 1H2.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5zm12-7a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V3h-2.5a.5.5 0 0 1 0-1h3a.5.5 0 0 1 .5.5z"/>
        </svg>`;
        
        // Insert button in header (far left)
        const header = document.querySelector('.header-inner');
        if (header) {
            header.insertAdjacentElement('afterbegin', button);
        }

        // Add event listeners
        button.addEventListener('click', toggleOverlay);

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('visible')) {
                toggleOverlay();
            }
        });

        // Close when clicking outside
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                toggleOverlay();
            }
        });

        // Load D3.js
        await loadD3();

        // Render navigation
        await renderNavigationOverlay();

        // Setup zoom controls if D3 is loaded
        if (d3Loaded && simulation) {
            document.getElementById('zoom-in')?.addEventListener('click', () => {
                const svg = d3.select('.bloom-graph');
                svg.transition().call(svg.__zoom.scaleBy, 1.3);
            });

            document.getElementById('zoom-out')?.addEventListener('click', () => {
                const svg = d3.select('.bloom-graph');
                svg.transition().call(svg.__zoom.scaleBy, 0.7);
            });

            document.getElementById('reset-view')?.addEventListener('click', () => {
                const svg = d3.select('.bloom-graph');
                svg.transition().call(svg.__zoom.transform, d3.zoomIdentity);
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
