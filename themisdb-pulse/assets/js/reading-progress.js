(function () {
    var progressSettings = window.themisdbV3ReadingProgress || {};

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function resolveAnchorLimit(nav) {
        var value = nav && nav.getAttribute('data-tv3-anchor-limit');
        if (value !== null && value !== undefined && value !== '') {
            var parsed = toInt(value, Number.MAX_SAFE_INTEGER);
            if (Number.isFinite(parsed) && parsed > 0) {
                return parsed;
            }
        }

        return Number.MAX_SAFE_INTEGER;
    }

    function toInt(value, fallback) {
        var parsed = parseInt(value, 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function isVisibleNode(node) {
        if (!node) {
            return false;
        }

        var style = window.getComputedStyle(node);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return false;
        }

        var rect = node.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function slugify(text) {
        return String(text || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function createUniqueId(base, seen) {
        var candidate = base || 'section';
        var index = 2;

        while (seen[candidate] || document.getElementById(candidate)) {
            candidate = (base || 'section') + '-' + String(index);
            index += 1;
        }

        seen[candidate] = true;
        return candidate;
    }

    function pickReadingTarget() {
        var body = document.body;
        if (body && isVisibleNode(body)) {
            return body;
        }

        var siteBlocks = document.querySelector('.wp-site-blocks');
        if (siteBlocks && isVisibleNode(siteBlocks)) {
            return siteBlocks;
        }

        var frontMain = document.querySelector('.tv3-front-main');
        if (frontMain && isVisibleNode(frontMain)) {
            return frontMain;
        }

        var selectors = [
            'body',
            '.wp-site-blocks',
            '.tv3-page-content-card .wp-block-post-content',
            '.wp-block-post-content',
            '.tv3-front-main',
            'main'
        ];

        var bestNode = null;
        var bestScore = 0;

        for (var i = 0; i < selectors.length; i++) {
            var nodes = Array.prototype.slice.call(document.querySelectorAll(selectors[i]));
            for (var j = 0; j < nodes.length; j++) {
                var node = nodes[j];
                if (!isVisibleNode(node)) {
                    continue;
                }

                var textLength = (node.textContent || '').trim().length;
                if (textLength <= 280) {
                    continue;
                }

                var score = textLength + Math.max(0, node.scrollHeight);
                if (!bestNode || score > bestScore) {
                    bestNode = node;
                    bestScore = score;
                }
            }
        }

        return bestNode;
    }

    function isCardGridTitle(node) {
        if (!node) {
            return false;
        }

        var overlayAncestor = node.closest(
            '.themisdb-benchmark-overlay, .themisdb-benchmark-focus, .themisdb-visualizer, [class*="benchmark-overlay"], [class*="benchmark-focus"]'
        );
        if (overlayAncestor) {
            return true;
        }

        var className = node.className || '';
        if (typeof className === 'string' && /(^|\s)[a-z0-9_-]*(?:card|tile)(?:[a-z0-9_-]*)(?=\s|$)/i.test(className)) {
            return true;
        }

        var cardAncestor = node.closest(
            '.tv3-docs-grid-card, .tv3-feature-card, .tv3-product-card, .tv3-pricing-card, .tv3-download-card, .tv3-card, .tv3-blog-card'
        );
        return !!cardAncestor;
    }

    function isAnchorTargetHeading(node) {
        if (!node || !node.textContent || !node.tagName) {
            return false;
        }

        if (node.tagName && node.tagName.toLowerCase() !== 'h2') {
            return false;
        }

        if (node.closest('nav, header, footer, .tv3-breadcrumbs, .wp-block-navigation, .widget, .sidebar, .menu, .pagination, .wp-block-post-template, .wp-block-query, .wp-block-posts, .wp-block-latest-posts, .wp-block-post-title')) {
            return false;
        }

        if (node.closest('.themisdb-front-slider, .themisdb-fs-slide-content, .tv3-hero-slider-shell, .tv3-frontpage-hero-shell, .themisdb-benchmark-headline, .themisdb-benchmark-overlay, .themisdb-benchmark-focus, .themisdb-visualizer, [class*="benchmark-overlay"], [class*="benchmark-focus"], [class*="benchmark-headline"]')) {
            return false;
        }

        if (isCardGridTitle(node)) {
            return false;
        }

        var className = node.className || '';
        if (typeof className === 'string' && /(^|\s)(tv3-[a-z-]*card-title|card-title)(?=\s|$)/i.test(className)) {
            return false;
        }

        if (typeof className === 'string' && /(^|\s)(themisdb-fs-title|la-section-title|benchmark-headline)(?=\s|$)/i.test(className)) {
            return false;
        }

        var text = (node.textContent || '').replace(/\s+/g, ' ').trim();
        if (!text || text.length < 3) {
            return false;
        }

        var rect = node.getBoundingClientRect ? node.getBoundingClientRect() : null;
        if (rect && rect.width === 0 && rect.height === 0) {
            return false;
        }

        return true;
    }

    function pickAnchorScope() {
        var selectors = [
            'body',
            '.wp-site-blocks',
            '.tv3-front-main',
            '.tv3-page-content-card .wp-block-post-content',
            '.entry-content',
            '.wp-block-post-content',
            'main'
        ];

        var bestNode = null;
        var bestScore = -1;

        for (var i = 0; i < selectors.length; i++) {
            var node = document.querySelector(selectors[i]);
            if (!node) {
                continue;
            }

            var headings = Array.prototype.slice.call(node.querySelectorAll('h2')).filter(isAnchorTargetHeading);
            if (!headings.length) {
                continue;
            }

            var score = headings.length + (node === document.body ? 25 : 0) + (node.classList && node.classList.contains('wp-site-blocks') ? 10 : 0) + (node.classList && node.classList.contains('wp-block-post-content') ? 4 : 0);
            if (score > bestScore) {
                bestNode = node;
                bestScore = score;
            }
        }

        return bestNode || document.querySelector('.tv3-front-main') || document.querySelector('main');
    }

    function collectSectionAnchors(limit) {
        var scope = pickAnchorScope();
        if (!scope) {
            return [];
        }

        var headings = Array.prototype.slice.call(scope.querySelectorAll('h2'));
        var visibleHeadings = headings.filter(isAnchorTargetHeading);

        var prioritized = visibleHeadings.filter(function (node) {
            return node.tagName && node.tagName.toLowerCase() === 'h2';
        });
        var source = prioritized.length ? prioritized.concat(visibleHeadings.filter(function (node) {
            return !prioritized.includes(node);
        })) : visibleHeadings;

        if (!source.length) {
            var paragraphNodes = Array.prototype.slice.call(scope.querySelectorAll('p'));
            source = paragraphNodes.filter(function (node) {
                if (!node || !node.textContent) {
                    return false;
                }

                if (node.querySelector('img, figure, table, ul, ol, blockquote, pre')) {
                    return false;
                }

                if (node.classList && (node.classList.contains('tv3-article-lead') || node.classList.contains('tv3-page-hero-excerpt'))) {
                    return false;
                }

                var text = node.textContent.replace(/\s+/g, ' ').trim();
                if (text.length < 20 || text.length > 140) {
                    return false;
                }

                var words = text.split(/\s+/).filter(Boolean).length;
                if (words > 18) {
                    return false;
                }

                if (!/[.:!?…]$/.test(text)) {
                    return false;
                }

                return true;
            });
        }

        var seen = {};
        var anchors = [];
        for (var i = 0; i < source.length; i++) {
            if (anchors.length >= limit) {
                break;
            }

            var heading = source[i];
            var text = (heading.textContent || '').replace(/\s+/g, ' ').trim();
            if (text.length < 3) {
                continue;
            }

            var id = (heading.id || '').trim();
            if (!id) {
                id = createUniqueId(slugify(text), seen);
                heading.id = id;
            }

            if (id && seen[id]) {
                continue;
            }

            seen[id] = true;
            anchors.push({ id: id, label: text, node: heading });
        }

        return anchors;
    }

    function setActiveAnchor(items, activeId) {
        items.forEach(function (item) {
            var active = item.id === activeId;
            item.item.classList.toggle('is-current', active);
            if (active) {
                item.link.setAttribute('aria-current', 'location');
            } else {
                item.link.removeAttribute('aria-current');
            }
        });
    }

    function dedupeContextNavShells() {
        var shells = Array.prototype.slice.call(document.querySelectorAll('.tv3-hero-context-nav-shell'));
        if (shells.length <= 1) {
            return;
        }

        var preferred = shells.filter(function (shell) {
            return !!shell.querySelector('[data-tv3-anchor-nav], .tv3-hero-context-nav');
        });
        var primary = preferred.length ? preferred[0] : shells[0];

        shells.forEach(function (shell) {
            if (shell !== primary) {
                shell.remove();
            }
        });
    }

    function normalizeFrontpageBreadcrumbPlacement() {
        var shell = document.querySelector('.tv3-breadcrumbs-shell.tv3-breadcrumbs-frontpage');
        if (!shell) {
            return;
        }

        var hero = shell.closest('.tv3-frontpage-hero-shell.tv3-hero-slider-shell');
        if (!hero) {
            return;
        }

        hero.insertAdjacentElement('afterend', shell);
    }

    function dedupeBreadcrumbShells() {
        var shells = Array.prototype.slice.call(document.querySelectorAll('.tv3-breadcrumbs-shell'));
        if (shells.length <= 1) {
            return;
        }

        var frontpageShells = shells.filter(function (shell) {
            return shell.classList && shell.classList.contains('tv3-breadcrumbs-frontpage');
        });

        if (frontpageShells.length) {
            shells.forEach(function (shell) {
                if (!shell.classList || !shell.classList.contains('tv3-breadcrumbs-frontpage')) {
                    if (shell.parentNode) {
                        shell.parentNode.removeChild(shell);
                    }
                }
            });
            return;
        }

        var primary = shells.find(function (shell) {
            return !!shell.querySelector('[data-tv3-anchor-nav], .tv3-breadcrumbs, [data-tv3-anchor-list]');
        }) || shells[0];

        shells.forEach(function (shell) {
            if (shell !== primary && shell.parentNode) {
                shell.parentNode.removeChild(shell);
            }
        });
    }

    function dedupeBreadcrumbItems(list) {
        if (!list) {
            return;
        }

        var seen = {};
        Array.prototype.slice.call(list.children).forEach(function (item) {
            var link = item.querySelector('a[href]');
            var href = link ? (link.getAttribute('href') || '').trim() : '';
            var text = (item.textContent || '').replace(/\s+/g, ' ').trim();
            var key = (href || text || '').toLowerCase();

            if (!key) {
                item.remove();
                return;
            }

            if (seen[key]) {
                item.remove();
                return;
            }

            seen[key] = true;
        });
    }

    function getBreadcrumbWindow(nav) {
        var beforeCount = 1;
        var afterCount = 3;
        var value = (nav && nav.getAttribute('data-tv3-breadcrumb-window')) || '';
        var parsed = value.split(',');

        if (parsed.length >= 2) {
            beforeCount = toInt(parsed[0], beforeCount);
            afterCount = toInt(parsed[1], afterCount);
        }

        var attrBefore = nav && nav.getAttribute('data-tv3-breadcrumb-before');
        var attrAfter = nav && nav.getAttribute('data-tv3-breadcrumb-after');
        if (attrBefore !== null && attrBefore !== '') {
            beforeCount = toInt(attrBefore, beforeCount);
        }
        if (attrAfter !== null && attrAfter !== '') {
            afterCount = toInt(attrAfter, afterCount);
        }

        return {
            before: Math.max(0, beforeCount),
            after: Math.max(0, afterCount)
        };
    }

    function collapseBreadcrumbTrail(list, items, activeId, beforeCount, afterCount) {
        if (!list || !items || !items.length) {
            return;
        }

        Array.prototype.slice.call(list.children).forEach(function (itemNode) {
            if (itemNode && itemNode.style) {
                itemNode.style.display = '';
                itemNode.hidden = false;
            }
        });
    }

    function syncProgressBreadcrumbText(value) {
        var progressEntry = document.querySelector('[data-tv3-reading-progress-breadcrumb="1"]');
        if (!progressEntry) {
            return;
        }

        var textTarget = progressEntry.querySelector('span') || progressEntry;
        textTarget.textContent = (value && value.trim()) ? value.trim() : '0% gelesen';
    }

    function initContextAnchorNav() {
        // Find the nav element with anchor data, with a fallback for template-based breadcrumbs.
        var nav = document.querySelector('[data-tv3-anchor-nav]') || document.querySelector('.tv3-breadcrumbs');
        if (!nav) {
            console.log('TV3 Anchors: nav not found');
            return false;
        }

        var list;
        var currentSectionCount = collectSectionAnchors(resolveAnchorLimit(nav)).length;
        if (nav.getAttribute('data-tv3-anchor-ready') === '1') {
            list = nav.querySelector('[data-tv3-anchor-list]') || nav.querySelector('.tv3-breadcrumbs-list');
            var generatedCount = list ? list.querySelectorAll('li[data-tv3-anchor-generated="1"]').length : 0;
            if (generatedCount >= currentSectionCount && currentSectionCount > 0) {
                console.log('TV3 Anchors: already ready with ' + currentSectionCount + ' sections');
                return true;
            }
        }

        // Get the list to populate (either .tv3-breadcrumbs-list or [data-tv3-anchor-list])
        list = nav.querySelector('[data-tv3-anchor-list]') || nav.querySelector('.tv3-breadcrumbs-list');
        if (!list) {
            if (nav.hasAttribute('data-tv3-anchor-list')) {
                list = nav;
            }
        }

        if (!list) {
            console.log('TV3 Anchors: list not found');
            return false;
        }

        var limit = resolveAnchorLimit(nav);
        var sections = collectSectionAnchors(limit);
        console.log('TV3 Anchors: found ' + sections.length + ' sections');
        nav.hidden = false;

        // Remove repeated breadcrumb entries before rebuilding anchor tracking.
        dedupeBreadcrumbItems(list);

        // Rebuild dynamic anchors once per nav to avoid duplicate anchors on repeated init passes.
        Array.prototype.slice.call(list.querySelectorAll('li[data-tv3-anchor-generated="1"]')).forEach(function (node) {
            node.remove();
        });

        var existingItems = Array.prototype.slice.call(list.querySelectorAll('li'));
        var items = [];
        var seenIds = {};

        // Add existing hardcoded breadcrumbs to items tracking.
        existingItems.forEach(function (liItem) {
            if (liItem.getAttribute('data-tv3-anchor-generated') === '1') {
                return;
            }

            var link = liItem.querySelector('a, span');
            if (!link) {
                return;
            }

            var href = (liItem.querySelector('a') || link).getAttribute('href') || '';
            var label = (liItem.textContent || '').replace(/\s+/g, ' ').trim();
            if (href === '/' && label.toLowerCase() === 'start') {
                liItem.setAttribute('data-tv3-home-breadcrumb', '1');
            }

            var entryId = (href === '/' && label.toLowerCase() === 'start') ? '__start__' : ('breadcrumb:' + (href || label || 'item'));
            if (seenIds[entryId]) {
                liItem.remove();
                return;
            }

            seenIds[entryId] = true;
            items.push({
                id: entryId,
                node: null,
                item: liItem,
                link: link
            });
        });

        var progressLabel = document.querySelector('[data-tv3-reading-progress-label]');
        var progressItem = list.querySelector('[data-tv3-reading-progress-breadcrumb="1"]');

        if (progressItem) {
            var progressText = progressItem.querySelector('span') || progressItem;
            var progressValue = (progressLabel ? progressLabel.textContent : '').trim();
            progressText.textContent = progressValue || '0% gelesen';
            progressItem.style.marginLeft = 'auto';
            progressItem.style.marginInlineStart = 'auto';
            progressItem.style.flex = '0 0 auto';
            if (!seenIds.__progress__) {
                seenIds.__progress__ = true;
                items.push({
                    id: '__progress__',
                    node: null,
                    item: progressItem,
                    link: progressText
                });
            }
        }

        var existingAnchorIds = {};
        existingItems.forEach(function (liItem) {
            var anchor = liItem.querySelector('a[href^="#"]');
            if (!anchor) {
                return;
            }

            var hash = anchor.getAttribute('href') || '';
            if (hash.length > 1) {
                existingAnchorIds[hash.slice(1)] = true;
            }
        });

        // Add dynamically generated in-page anchor links for the actual headings on this page.
        var dynamicItems = sections.map(function (section) {
            if (existingAnchorIds[section.id]) {
                return null;
            }

            var item = document.createElement('li');
            var link = document.createElement('a');
            var href = '#' + section.id;

            link.setAttribute('href', href);
            link.setAttribute('data-tv3-anchor-link', 'true');
            link.textContent = section.label;
            item.appendChild(link);
            item.className = 'tv3-breadcrumbs-item';
            item.setAttribute('data-tv3-anchor-generated', '1');

            var progressItem = list.querySelector('[data-tv3-reading-progress-breadcrumb="1"]');

            if (progressItem) {
                list.insertBefore(item, progressItem);
            } else {
                list.appendChild(item);
            }

            console.log('TV3 Anchors: added link "' + section.label + '" (' + href + ')');

            return {
                id: section.id,
                node: section.node,
                item: item,
                link: link
            };
        }).filter(Boolean);
        
        items = items.concat(dynamicItems);

        function getAnchorActivationLine() {
            var nav = document.querySelector('.tv3-hero-context-nav, [data-tv3-anchor-nav], .tv3-breadcrumbs-shell');
            if (nav) {
                var rect = nav.getBoundingClientRect();
                return rect.bottom - 18;
            }

            return window.innerHeight * 0.28;
        }

        items.forEach(function (entry) {
            entry.link.addEventListener('click', function (event) {
                // Handle "Start" breadcrumb specially
                if (entry.id === '__start__') {
                    event.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    if (window.history && typeof window.history.replaceState === 'function') {
                        window.history.replaceState(null, '', window.location.pathname);
                    }
                    return;
                }
                
                // Check if this is a link to a landing page (external) or an anchor (internal)
                var href = entry.link.getAttribute('href');
                if (href && !href.startsWith('#')) {
                    // This is a landing page link - allow normal navigation
                    return;
                }
                
                // This is an internal anchor link - handle scrolling with the sticky header offset.
                var target = document.getElementById(entry.id);
                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                if (window.history && typeof window.history.replaceState === 'function') {
                    window.history.replaceState(null, '', '#' + entry.id);
                }
            });
        });

        var raf = false;
        function updateActiveAnchor() {
            raf = false;
            var activationLine = getAnchorActivationLine();
            var current = '__start__';
            var generatedAnchorItems = items.filter(function (entry) {
                return entry && entry.id && entry.id !== '__start__' && entry.id !== '__progress__' && !!entry.node;
            });

            if (window.scrollY <= 50) {
                var isFrontpageBreadcrumbContext = !!document.querySelector('.tv3-breadcrumbs-frontpage') || document.body.classList.contains('home') || document.body.classList.contains('front-page');
                if (isFrontpageBreadcrumbContext && generatedAnchorItems.length) {
                    current = generatedAnchorItems[generatedAnchorItems.length - 1].id;
                } else {
                    current = '__start__';
                }
            } else {
                for (var i = 1; i < items.length; i++) {
                    var item = items[i];
                    if (!item.node) continue;

                    var top = item.node.getBoundingClientRect().top;
                    if (top <= activationLine) {
                        current = item.id;
                    } else {
                        break;
                    }
                }
            }

            var breadcrumbWindow = getBreadcrumbWindow(nav);
            collapseBreadcrumbTrail(list, items, current, breadcrumbWindow.before, breadcrumbWindow.after);
            setActiveAnchor(items, current);
        }

        function scheduleUpdate() {
            if (raf) {
                return;
            }
            raf = true;
            window.requestAnimationFrame(updateActiveAnchor);
        }

        window.addEventListener('scroll', scheduleUpdate, { passive: true });
        window.addEventListener('resize', scheduleUpdate);
        scheduleUpdate();

        nav.setAttribute('data-tv3-anchor-ready', '1');
        return true;
    }

    function initReadingProgress() {
        if (window.__tv3ReadingProgressReady) {
            return true;
        }

        var breadcrumbProgress = document.querySelector('[data-tv3-reading-progress-breadcrumb="1"]');
        var bars = Array.prototype.slice.call(document.querySelectorAll('[data-tv3-reading-progress-bar]'));
        if (!bars.length) {
            return false;
        }

        bars.forEach(function (bar) {
            var shell = bar.parentElement;
            if (!shell) {
                return;
            }

            if (breadcrumbProgress) {
                shell.classList.add('is-breadcrumb-fallback-hidden');
                shell.style.display = 'none';
                shell.setAttribute('aria-hidden', 'true');
            } else {
                shell.classList.remove('is-breadcrumb-fallback-hidden');
                shell.style.display = '';
                shell.removeAttribute('aria-hidden');
            }
        });

        var contextBars = bars.filter(function (candidate) {
            return !!candidate.closest('.tv3-hero-context-nav');
        });

        var bar = contextBars.find(isVisibleNode) || contextBars[0] || bars.find(isVisibleNode) || bars[0];

        var progressShell = bar.parentElement;
        if (!progressShell) {
            return false;
        }

        var label = progressShell.querySelector('[data-tv3-reading-progress-label]');

        bars.forEach(function (otherBar) {
            if (otherBar === bar) {
                return;
            }

            var otherShell = otherBar.parentElement;
            if (!otherShell) {
                return;
            }

            otherShell.classList.remove('is-active');
            otherBar.style.width = '0%';

            var otherLabel = otherShell.querySelector('[data-tv3-reading-progress-label]');
            if (otherLabel) {
                otherLabel.textContent = '';
            }
        });

        var target = pickReadingTarget();
        if (!target) {
            progressShell.classList.remove('is-active');
            bar.style.width = '0%';
            if (label) {
                label.textContent = '';
            }
            return false;
        }

        var minutesTotal = Math.max(0, toInt(progressSettings.minutesTotal, 0));
        var labelPattern = String(progressSettings.labelPattern || '%1$d% · %2$d Min. uebrig');
        var labelPatternSimple = String(progressSettings.labelPatternSimple || '%1$d% gelesen');

        function updateLabel(progressFraction) {
            if (!label) {
                return;
            }

            var percent = clamp(Math.round(progressFraction * 100), 0, 100);
            if (minutesTotal > 0) {
                var minutesRemaining = Math.max(0, Math.ceil(minutesTotal * (1 - progressFraction)));
                var text = labelPattern
                    .replace('%1$d', String(percent))
                    .replace('%2$d', String(minutesRemaining));
                label.textContent = text;
                syncProgressBreadcrumbText(text);
                return;
            }

            var text = labelPatternSimple
                .replace('%1$d', String(percent));
            label.textContent = text;
            syncProgressBreadcrumbText(text);
        }

        var rafScheduled = false;

        function recalc() {
            rafScheduled = false;

            var rect = target.getBoundingClientRect();
            var scrollY = window.scrollY || window.pageYOffset || 0;
            var viewportHeight = window.innerHeight || 1;

            var start = rect.top + scrollY;
            var end = start + Math.max(1, target.scrollHeight - viewportHeight * 0.55);

            if (end <= start + 1) {
                progressShell.classList.remove('is-active');
                bar.style.width = '0%';
                return;
            }

            var progress = clamp((scrollY - start) / (end - start), 0, 1);
            progressShell.classList.add('is-active');
            bar.style.width = String((progress * 100).toFixed(2)) + '%';
            updateLabel(progress);
        }

        function scheduleRecalc() {
            if (rafScheduled) {
                return;
            }
            rafScheduled = true;
            window.requestAnimationFrame(recalc);
        }

        window.addEventListener('scroll', scheduleRecalc, { passive: true });
        window.addEventListener('resize', scheduleRecalc);
        window.addEventListener('orientationchange', scheduleRecalc);

        scheduleRecalc();
        window.__tv3ReadingProgressReady = true;
        return true;
    }

    function initContextSystems() {
        normalizeFrontpageBreadcrumbPlacement();
        dedupeBreadcrumbShells();
        dedupeContextNavShells();

        var attempts = 0;
        function tryInitAnchors() {
            attempts += 1;
            var ok = initContextAnchorNav();
            if (!ok && attempts < 7) {
                window.setTimeout(tryInitAnchors, attempts * 220);
            }
        }

        tryInitAnchors();
        window.addEventListener('load', tryInitAnchors, { once: true });
        window.addEventListener('load', normalizeFrontpageBreadcrumbPlacement, { once: true });

        var progressAttempts = 0;
        function tryInitReadingProgress() {
            progressAttempts += 1;
            var ok = initReadingProgress();
            if (!ok && progressAttempts < 7) {
                window.setTimeout(tryInitReadingProgress, progressAttempts * 220);
            }
        }

        tryInitReadingProgress();
        window.addEventListener('load', tryInitReadingProgress, { once: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContextSystems);
    } else {
        initContextSystems();
    }
})();
