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

    function initContextAnchorNav() {
        var nav = document.querySelector('[data-tv3-anchor-nav]');
        if (!nav) {
            return false;
        }

        if (nav.getAttribute('data-tv3-anchor-ready') === '1') {
            return true;
        }

        var list = nav.querySelector('[data-tv3-anchor-list]');
        if (!list) {
            return false;
        }

        var limit = clamp(toInt(nav.getAttribute('data-tv3-anchor-limit'), 8), 1, 12);
        var sections = collectSectionAnchors(limit);
        if (!sections.length) {
            nav.hidden = true;
            return false;
        }

        nav.hidden = false;

        list.innerHTML = '';

        var items = sections.map(function (section) {
            var item = document.createElement('li');
            var link = document.createElement('a');
            link.setAttribute('href', '#' + section.id);
            link.setAttribute('data-tv3-anchor-link', 'true');
            link.textContent = section.label;
            item.appendChild(link);
            list.appendChild(item);
            return {
                id: section.id,
                node: section.node,
                item: item,
                link: link
            };
        });

        function getStickyOffset() {
            var stickyPart = document.querySelector('.tv3-hero-context-nav-part');
            if (!stickyPart) {
                return 0;
            }

            var top = parseFloat(window.getComputedStyle(stickyPart).top || '0');
            return Number.isFinite(top) ? top : 0;
        }

        items.forEach(function (entry) {
            entry.link.addEventListener('click', function (event) {
                var target = document.getElementById(entry.id);
                if (!target) {
                    return;
                }

                event.preventDefault();
                var y = window.scrollY + target.getBoundingClientRect().top - getStickyOffset() - 12;
                window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });

                if (window.history && typeof window.history.replaceState === 'function') {
                    window.history.replaceState(null, '', '#' + entry.id);
                }
            });
        });

        var raf = false;
        function updateActiveAnchor() {
            raf = false;
            var marker = window.scrollY + getStickyOffset() + 28;
            var current = items[0].id;

            for (var i = 0; i < items.length; i++) {
                var top = window.scrollY + items[i].node.getBoundingClientRect().top;
                if (top <= marker) {
                    current = items[i].id;
                } else {
                    break;
                }
            }

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

        var bars = Array.prototype.slice.call(document.querySelectorAll('[data-tv3-reading-progress-bar]'));
        if (!bars.length) {
            return false;
        }

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
                label.textContent = labelPattern
                    .replace('%1$d', String(percent))
                    .replace('%2$d', String(minutesRemaining));
                return;
            }

            label.textContent = labelPatternSimple
                .replace('%1$d', String(percent));
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
