(function () {
    'use strict';

    function normalizeLabel(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9 ]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function findIndexByLabel(nodes, predicate) {
        for (var i = 0; i < nodes.length; i += 1) {
            var node = nodes[i];
            var label = normalizeLabel(node.textContent || '');
            if (predicate(label, node)) {
                return i;
            }
        }

        return -1;
    }

    function gatherReferences(nodes, startIndex, qualityIndex, podcastIndex) {
        var result = [];
        if (startIndex < 0) {
            return result;
        }

        result.push(nodes[startIndex]);

        var maxIndex = nodes.length;
        if (qualityIndex > startIndex) {
            maxIndex = qualityIndex;
        } else if (podcastIndex > startIndex) {
            maxIndex = podcastIndex;
        }

        for (var i = startIndex + 1; i < maxIndex; i += 1) {
            var node = nodes[i];
            var tag = node.tagName.toLowerCase();
            if (tag === 'ol' || tag === 'ul') {
                result.push(node);
                break;
            }

            // Fallback for reference text blocks when no list was emitted.
            if (tag === 'p' && normalizeLabel(node.textContent || '') !== '') {
                result.push(node);
                if (result.length >= 3) {
                    break;
                }
            }
        }

        return result;
    }

    function gatherQuality(nodes, startIndex, podcastIndex) {
        var result = [];
        if (startIndex < 0) {
            return result;
        }

        result.push(nodes[startIndex]);

        var maxIndex = podcastIndex > startIndex ? podcastIndex : nodes.length;
        for (var i = startIndex + 1; i < maxIndex; i += 1) {
            var node = nodes[i];
            var tag = node.tagName.toLowerCase();
            if (tag === 'pre' || tag === 'code' || tag === 'table' || tag === 'p') {
                result.push(node);
                if (tag !== 'p') {
                    break;
                }
            }
        }

        return result;
    }

    function gatherPodcast(nodes, startIndex) {
        var result = [];
        if (startIndex < 0) {
            return result;
        }

        result.push(nodes[startIndex]);

        for (var i = startIndex + 1; i < nodes.length; i += 1) {
            var node = nodes[i];
            var tag = node.tagName.toLowerCase();
            var className = node.className || '';
            if (tag === 'figure' && className.indexOf('wp-block-audio') !== -1) {
                result.push(node);
                continue;
            }
            break;
        }

        return result;
    }

    function buildPanel(titleClass, nodes) {
        var section = document.createElement('section');
        section.className = 'tv3-compact-meta-panel ' + titleClass;

        for (var i = 0; i < nodes.length; i += 1) {
            section.appendChild(nodes[i]);
        }

        return section;
    }

    function applyCompactLayout(root) {
        if (!root || root.dataset.tv3CompactMetaApplied === '1') {
            return;
        }

        var nodes = Array.prototype.slice.call(root.children || []);
        if (!nodes.length) {
            return;
        }

        var referencesIndex = findIndexByLabel(nodes, function (label) {
            return label === 'referenzen' || label === 'referenzen.';
        });
        var qualityIndex = findIndexByLabel(nodes, function (label) {
            return label.indexOf('qualitaets metadaten') !== -1 || label.indexOf('qualitats metadaten') !== -1;
        });
        var podcastIndex = findIndexByLabel(nodes, function (label) {
            return label.indexOf('podcast episode') !== -1;
        });

        if (referencesIndex < 0 || qualityIndex < 0 || podcastIndex < 0) {
            return;
        }

        var referenceNodes = gatherReferences(nodes, referencesIndex, qualityIndex, podcastIndex);
        var qualityNodes = gatherQuality(nodes, qualityIndex, podcastIndex);
        var podcastNodes = gatherPodcast(nodes, podcastIndex);

        if (!referenceNodes.length || !qualityNodes.length || podcastNodes.length < 2) {
            return;
        }

        var earliestIndex = Math.min(referencesIndex, qualityIndex, podcastIndex);
        var anchorNode = nodes[earliestIndex];

        var grid = document.createElement('div');
        grid.className = 'tv3-compact-meta-grid';

        grid.appendChild(buildPanel('tv3-compact-meta-panel-references', referenceNodes));
        grid.appendChild(buildPanel('tv3-compact-meta-panel-quality', qualityNodes));
        grid.appendChild(buildPanel('tv3-compact-meta-panel-podcast', podcastNodes));

        root.insertBefore(grid, anchorNode);
        root.dataset.tv3CompactMetaApplied = '1';
    }

    function boot() {
        var roots = [];
        var formulaRoots = document.querySelectorAll('.wp-block-post-content .themisdb-formula-content');
        var directRoots = document.querySelectorAll('.wp-block-post-content');

        for (var i = 0; i < formulaRoots.length; i += 1) {
            roots.push(formulaRoots[i]);
        }

        for (var j = 0; j < directRoots.length; j += 1) {
            var directRoot = directRoots[j];
            if (directRoot.querySelector('.themisdb-formula-content')) {
                continue;
            }
            roots.push(directRoot);
        }

        if (!roots.length) {
            return;
        }

        for (var k = 0; k < roots.length; k += 1) {
            applyCompactLayout(roots[k]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
