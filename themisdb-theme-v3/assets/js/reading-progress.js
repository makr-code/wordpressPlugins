(function () {
    var progressSettings = window.themisdbV3ReadingProgress || {};

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function toInt(value, fallback) {
        var parsed = parseInt(value, 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function pickReadingTarget() {
        var selectors = [
            '.tv3-page-content-card .wp-block-post-content',
            '.wp-block-post-content',
            '.tv3-front-main',
            'main'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var node = document.querySelector(selectors[i]);
            if (!node) {
                continue;
            }

            var textLength = (node.textContent || '').trim().length;
            if (textLength > 280) {
                return node;
            }
        }

        return null;
    }

    function initReadingProgress() {
        var bar = document.querySelector('[data-tv3-reading-progress-bar]');
        var label = document.querySelector('[data-tv3-reading-progress-label]');
        if (!bar) {
            return;
        }

        var progressShell = bar.parentElement;
        if (!progressShell) {
            return;
        }

        var target = pickReadingTarget();
        if (!target) {
            progressShell.classList.remove('is-active');
            bar.style.width = '0%';
            if (label) {
                label.textContent = '';
            }
            return;
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReadingProgress);
    } else {
        initReadingProgress();
    }
})();
