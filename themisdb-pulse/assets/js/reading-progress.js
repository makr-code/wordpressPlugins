(function () {
    var progressSettings = window.themisdbV3ReadingProgress || {};

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
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
        var selectors = [
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

    function pickAnchorScope() {
        var selectors = [
            '.tv3-front-main',
            '.tv3-page-content-card .wp-block-post-content',
            '.wp-block-post-content',
            'main'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var node = document.querySelector(selectors[i]);
            if (!node) {
                continue;
            }

            if ((node.textContent || '').trim().length > 120) {
                return node;
            }
        }

        return null;
    }

    function collectSectionAnchors(limit) {
        var scope = pickAnchorScope();
        if (!scope) {
            return [];
        }

        var headings = Array.prototype.slice.call(scope.querySelectorAll('h2, h3, h4'));
        var filteredH2 = headings.filter(function (node) {
            return node.tagName && node.tagName.toLowerCase() === 'h2';
        });
        var source = filteredH2.length >= 3 ? filteredH2 : headings;

        if (!source.length) {
            var paragraphNodes = Array.prototype.slice.call(scope.querySelectorAll('p'));
            source = paragraphNodes.filter(function (node) {
                if (!node || !node.textContent) {
                    return false;
                }

                if (node.querySelector('img, figure, table, ul, ol, blockquote, pre')) {
                    return false;
                }

                if (node.classList.contains('tv3-article-lead') || node.classList.contains('tv3-page-hero-excerpt')) {
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

            if (!id || seen[id]) {
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

        var primary = null;
        shells.forEach(function (shell) {
            if (!primary && shell.querySelector('[data-tv3-anchor-nav], .tv3-hero-context-nav')) {
                primary = shell;
            }
        });

        if (!primary) {
            primary = shells[0];
        }

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

        var primary = shells[0];
        shells.slice(1).forEach(function (shell) {
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

        var requestedBefore = Number.isFinite(beforeCount) ? Math.max(0, beforeCount) : 1;
        var requestedAfter = Number.isFinite(afterCount) ? Math.max(0, afterCount) : 3;
        var ordered = items.filter(function (entry) {
            return entry && entry.item && list.contains(entry.item);
        });

        if (!ordered.length) {
            return;
        }

        var progressEntry = ordered.find(function (entry) {
            return entry.id === '__progress__';
        });
        var baseItems = ordered.filter(function (entry) {
            return entry.id !== '__progress__';
        });

        var homeEntry = baseItems.find(function (entry) {
            return entry.id === '__start__';
        }) || baseItems[0];
        var generatedItems = baseItems.filter(function (entry) {
            return entry.id !== '__start__';
        });

        if (!generatedItems.length) {
            if (homeEntry && homeEntry.item) {
                homeEntry.item.style.display = '';
                homeEntry.item.hidden = false;
            }
            if (progressEntry && progressEntry.item) {
                progressEntry.item.style.display = '';
                progressEntry.item.hidden = false;
            }
            return;
        }

        var listWidth = list.clientWidth || list.getBoundingClientRect().width || 1200;
        var estimatedItemWidth = 150;
        var reservedWidth = 320;
        var adaptiveVisible = Math.max(
            1,
            Math.min(
                generatedItems.length,
                Math.floor(Math.max(listWidth - reservedWidth, 220) / estimatedItemWidth)
            )
        );

        var activeIndex = generatedItems.findIndex(function (entry) {
            return entry.id === activeId;
        });
        if (activeIndex === -1) {
            activeIndex = 0;
        }

        var minVisible = Math.max(1, requestedBefore + requestedAfter + 1);
        var totalVisible = Math.max(minVisible, Math.min(adaptiveVisible, generatedItems.length));
        var beforeVisible = Math.min(requestedBefore, activeIndex);
        var afterVisible = Math.min(requestedAfter, Math.max(0, generatedItems.length - activeIndex - 1));

        while (beforeVisible + afterVisible + 1 > totalVisible) {
            if (afterVisible > 1) {
                afterVisible -= 1;
            } else if (beforeVisible > 0) {
                beforeVisible -= 1;
            } else {
                break;
            }
        }

        if (beforeVisible + afterVisible + 1 < totalVisible && generatedItems.length > totalVisible) {
            var remainingAfter = Math.max(0, generatedItems.length - (activeIndex + 1));
            if (remainingAfter > requestedAfter) {
                afterVisible = Math.min(requestedAfter, totalVisible - beforeVisible - 1);
            }
        }

        var startIndex = clamp(activeIndex - beforeVisible, 0, Math.max(0, generatedItems.length - (beforeVisible + afterVisible + 1)));
        var visibleIds = {};
        generatedItems.slice(startIndex, startIndex + beforeVisible + afterVisible + 1).forEach(function (entry) {
            visibleIds[entry.id] = true;
        });

        var leadingEllipsis = list.querySelector('.tv3-breadcrumbs-item--ellipsis[data-tv3-breadcrumbs-leading="1"]');
        var trailingEllipsis = list.querySelector('.tv3-breadcrumbs-item--ellipsis[data-tv3-breadcrumbs-trailing="1"]');
        var shouldShowLeadingEllipsis = startIndex > 0;
        var shouldShowTrailingEllipsis = startIndex + beforeVisible + afterVisible + 1 < generatedItems.length || (activeId === '__start__' && generatedItems.length > Math.max(2, requestedAfter + 1));

        Array.prototype.slice.call(list.children).forEach(function (itemNode) {
            var isHome = homeEntry && homeEntry.item && itemNode === homeEntry.item;
            var isProgress = progressEntry && progressEntry.item && itemNode === progressEntry.item;

            if (isHome || isProgress) {
                itemNode.style.display = '';
                itemNode.hidden = false;
                return;
            }

            if (itemNode.classList && itemNode.classList.contains('tv3-breadcrumbs-item--ellipsis')) {
                var showLeading = itemNode === leadingEllipsis && shouldShowLeadingEllipsis;
                var showTrailing = itemNode === trailingEllipsis && shouldShowTrailingEllipsis;
                itemNode.style.display = showLeading || showTrailing ? '' : 'none';
                itemNode.hidden = !(showLeading || showTrailing);
                return;
            }

            var isVisibleGenerated = !!generatedItems.find(function (entry) {
                return entry.item === itemNode && visibleIds[entry.id];
            });

            itemNode.style.display = isVisibleGenerated ? '' : 'none';
            itemNode.hidden = !isVisibleGenerated;
        });

        if (leadingEllipsis) {
            leadingEllipsis.style.display = startIndex > 0 ? '' : 'none';
            leadingEllipsis.hidden = startIndex <= 0;
        }

        if (trailingEllipsis) {
            var trailingVisible = startIndex + beforeVisible + afterVisible + 1 < generatedItems.length;
            trailingEllipsis.style.display = trailingVisible ? '' : 'none';
            trailingEllipsis.hidden = !trailingVisible;
        }

        if (homeEntry && homeEntry.item) {
            homeEntry.item.style.display = '';
            homeEntry.item.hidden = false;
        }

        if (progressEntry && progressEntry.item) {
            progressEntry.item.style.display = '';
            progressEntry.item.hidden = false;
        }
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

        var currentSectionCount = collectSectionAnchors(clamp(toInt(nav.getAttribute('data-tv3-anchor-limit'), 8), 1, 12)).length;
        var generatedCount = list ? list.querySelectorAll('li[data-tv3-anchor-generated="1"]').length : 0;
        if (nav.getAttribute('data-tv3-anchor-ready') === '1' && generatedCount >= currentSectionCount && currentSectionCount > 0) {
            console.log('TV3 Anchors: already ready with ' + currentSectionCount + ' sections');
            return true;
        }

        // Get the list to populate (either .tv3-breadcrumbs-list or [data-tv3-anchor-list])
        var list = nav.querySelector('[data-tv3-anchor-list]') || nav.querySelector('.tv3-breadcrumbs-list');
        if (!list) {
            if (nav.hasAttribute('data-tv3-anchor-list')) {
                list = nav;
            }
        }

        if (!list) {
            console.log('TV3 Anchors: list not found');
            return false;
        }

        var limit = clamp(toInt(nav.getAttribute('data-tv3-anchor-limit'), 8), 1, 12);
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

            var leadingEllipsis = list.querySelector('[data-tv3-breadcrumbs-leading="1"]');
            var trailingEllipsis = list.querySelector('[data-tv3-breadcrumbs-trailing="1"]');
            var progressItem = list.querySelector('[data-tv3-reading-progress-breadcrumb="1"]');
            var insertBeforeNode = trailingEllipsis || progressItem || null;

            if (insertBeforeNode) {
                list.insertBefore(item, insertBeforeNode);
            } else if (leadingEllipsis) {
                list.insertBefore(item, leadingEllipsis.nextSibling || null);
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

            if (window.scrollY <= 50) {
                current = '__start__';
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
