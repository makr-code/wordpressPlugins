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

    function gatherAuthor(root) {
        var grid = document.querySelector('.tv3-author-one-card-grid');
        if (!grid) {
            return { nodes: createAuthorFallbackNodes(), source: null };
        }

        var body = grid.querySelector('.tv3-author-one-card .tv3-post-card-body');
        if (!body) {
            return { nodes: createAuthorFallbackNodes(), source: grid };
        }

        var nodes = Array.prototype.slice.call(body.children || []);
        if (!nodes.length) {
            nodes = createAuthorFallbackNodes();
        }
        return { nodes: nodes, source: grid };
    }

    function createAuthorFallbackNodes() {
        var heading = document.createElement('h4');
        heading.textContent = 'Autor';

        var paragraph = document.createElement('p');
        var authorLink = document.querySelector('.tv3-single-post-meta a[href*="/author/"]');
        var authorName = authorLink ? (authorLink.textContent || '').trim() : '';
        paragraph.textContent = authorName ? authorName : 'Autor nicht hinterlegt.';

        return [heading, paragraph];
    }

    function createFallbackNodes(title, text) {
        var heading = document.createElement('h4');
        heading.textContent = title;

        var paragraph = document.createElement('p');
        paragraph.textContent = text;

        return [heading, paragraph];
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
            if (tag === 'p' && normalizeLabel(node.textContent || '') !== '') {
                result.push(node);
                if (result.length >= 3) {
                    break;
                }
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

    function buildReferencesPanel(nodes) {
        var section = document.createElement('section');
        section.className = 'tv3-compact-meta-panel tv3-compact-meta-panel-references is-collapsed';

        // Heading always visible
        section.appendChild(nodes[0]);

        // Collapsible body (full content)
        var body = document.createElement('div');
        body.className = 'tv3-panel-body';

        for (var i = 1; i < nodes.length; i += 1) {
            body.appendChild(nodes[i]);
        }

        section.appendChild(body);

        // Text toggle button
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'tv3-panel-toggle-text';
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.textContent = 'Alle Referenzen anzeigen';
        section.appendChild(toggleBtn);

        toggleBtn.addEventListener('click', function () {
            var collapsed = section.classList.toggle('is-collapsed');
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggleBtn.textContent = collapsed ? 'Alle Referenzen anzeigen' : 'Weniger anzeigen';
        });

        return section;
    }

    function applyCompactLayout(root) {
        if (!root || root.dataset.tv3CompactMetaApplied === '1') {
            return;
        }

        var isSingle = document.body.classList.contains('single') || document.body.classList.contains('single-post');
        if (!isSingle) {
            return;
        }

        var nodes = Array.prototype.slice.call(root.children || []);
        if (!nodes.length) {
            return;
        }

        var referencesIndex = findIndexByLabel(nodes, function (label) {
            return label.indexOf('referenzen') !== -1 || label.indexOf('referenz') !== -1;
        });
        var podcastIndex = findIndexByLabel(nodes, function (label) {
            return label.indexOf('podcast') !== -1 || label.indexOf('episode') !== -1;
        });
        var authorBundle = gatherAuthor(root);

        if (!authorBundle.nodes.length) {
            return;
        }

        var referenceNodes = referencesIndex >= 0
            ? gatherReferences(nodes, referencesIndex, -1, podcastIndex)
            : createFallbackNodes('Referenzen', 'Keine Referenzen hinterlegt.');
        var authorNodes = authorBundle.nodes;
        var podcastNodes = podcastIndex >= 0
            ? gatherPodcast(nodes, podcastIndex)
            : createFallbackNodes('Podcast', 'Keine Podcast-Episode hinterlegt.');

        if (podcastNodes.length < 2) {
            podcastNodes = createFallbackNodes('Podcast', 'Keine Podcast-Episode hinterlegt.');
        }

        if (!referenceNodes.length || !authorNodes.length || !podcastNodes.length) {
            return;
        }

        var anchorNode = null;
        if (referencesIndex >= 0) {
            anchorNode = nodes[referencesIndex];
        } else if (podcastIndex >= 0) {
            anchorNode = nodes[podcastIndex];
        }

        var grid = document.createElement('div');
        grid.className = 'tv3-compact-meta-grid';

        if (anchorNode) {
            root.insertBefore(grid, anchorNode);
        } else {
            root.appendChild(grid);
        }

        grid.appendChild(buildReferencesPanel(referenceNodes));
        grid.appendChild(buildPanel('tv3-compact-meta-panel-author', authorNodes));
        grid.appendChild(buildPanel('tv3-compact-meta-panel-podcast', podcastNodes));

        if (authorBundle.source && authorBundle.source.parentNode) {
            authorBundle.source.parentNode.removeChild(authorBundle.source);
        }
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

    function initWithFallbacks() {
        var attemptCount = 0;
        var maxAttempts = 4;

        function attemptBoot() {
            boot();
            attemptCount += 1;

            if (attemptCount >= maxAttempts) {
                return;
            }

            if (!document.querySelector('.tv3-compact-meta-grid')) {
                var delay = attemptCount === 1 ? 120 : 360;
                window.setTimeout(attemptBoot, delay);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', attemptBoot, { once: true });
        } else {
            attemptBoot();
        }

        window.addEventListener('load', attemptBoot, { once: true });
    }

    initWithFallbacks();
})();
