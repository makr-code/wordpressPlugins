/**
 * ThemisDB Theme – hero-slider.js
 * Unified slider controller for:
 * - Theme hero markup (.themisdb-hero-*)
 * - ThemisDB Front Slider plugin markup (.themisdb-fs-*)
 */
(function () {
    'use strict';

    const DEFAULT_INTERVAL = (window.themisdbThemeData && parseInt(window.themisdbThemeData.sliderInterval, 10) > 0)
        ? parseInt(window.themisdbThemeData.sliderInterval, 10)
        : 5000;

    function initSlider(cfg) {
        const root = cfg.root;
        const track = root.querySelector(cfg.trackSel);
        if (!track) return;

        const slides = Array.from(track.querySelectorAll(cfg.slideSel));
        if (!slides.length) return;

        const dotsWrap = cfg.dotsWrapSel ? root.querySelector(cfg.dotsWrapSel) : null;
        const dots = dotsWrap
            ? Array.from(dotsWrap.querySelectorAll(cfg.dotSel))
            : Array.from(root.querySelectorAll(cfg.dotSel));

        const prevBtn = root.querySelector(cfg.prevSel);
        const nextBtn = root.querySelector(cfg.nextSel);
        const counter = cfg.counterSel ? root.querySelector(cfg.counterSel) : null;
        const progress = cfg.progressSel ? root.querySelector(cfg.progressSel) : null;

        let interval = DEFAULT_INTERVAL;
        if (cfg.intervalFromDataAttr) {
            const raw = parseInt(root.getAttribute(cfg.intervalFromDataAttr), 10);
            if (raw > 0) interval = raw;
        }

        let autoplay = true;
        if (cfg.autoplayFromDataAttr) {
            autoplay = root.getAttribute(cfg.autoplayFromDataAttr) !== '0';
        }

        const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) autoplay = false;

        const total = slides.length;
        let current = Math.max(0, slides.findIndex(function (s) { return s.classList.contains('is-active'); }));
        if (current < 0) current = 0;

        let timer = null;
        let paused = false;
        let progStart = null;
        let progRaf = null;

        function applyPosition(index) {
            if (cfg.translateTrack) {
                track.style.transform = 'translateX(-' + (index * 100) + '%)';
            }
        }

        function updateAccessibility(activeIdx) {
            slides.forEach(function (slide, idx) {
                const active = idx === activeIdx;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                slide.querySelectorAll('a, button').forEach(function (el) {
                    el.setAttribute('tabindex', active ? '0' : '-1');
                });
            });
        }

        function updateDots(activeIdx) {
            dots.forEach(function (dot, idx) {
                const active = idx === activeIdx;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function resetProgress() {
            if (!progress) return;
            cancelAnimationFrame(progRaf);
            progress.style.transition = 'none';
            progress.style.width = '0%';
            if (!paused && autoplay) startProgress();
        }

        function startProgress() {
            if (!progress) return;
            progStart = null;
            progRaf = requestAnimationFrame(function tick(ts) {
                if (!progStart) progStart = ts;
                const elapsed = ts - progStart;
                const pct = Math.min((elapsed / interval) * 100, 100);
                progress.style.transition = 'none';
                progress.style.width = pct + '%';
                if (elapsed < interval && !paused && autoplay) {
                    progRaf = requestAnimationFrame(tick);
                }
            });
        }

        function goTo(idx) {
            current = (idx + total) % total;
            applyPosition(current);
            updateAccessibility(current);
            updateDots(current);
            if (counter) counter.textContent = (current + 1) + ' / ' + total;
            track.setAttribute('aria-live', autoplay ? 'off' : 'polite');
            resetProgress();
        }

        function goNext() { goTo(current + 1); }
        function goPrev() { goTo(current - 1); }

        function startTimer() {
            if (!autoplay || total < 2) return;
            clearInterval(timer);
            timer = setInterval(goNext, interval);
        }

        function stopTimer() {
            clearInterval(timer);
            cancelAnimationFrame(progRaf);
        }

        function pause() {
            paused = true;
            stopTimer();
        }

        function resume() {
            if (!autoplay) return;
            paused = false;
            startTimer();
            resetProgress();
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goPrev(); startTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goNext(); startTimer(); });

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () {
                const dataIdx = dot.getAttribute('data-idx') || dot.getAttribute('data-index');
                const idx = dataIdx !== null ? parseInt(dataIdx, 10) : i;
                goTo(Number.isNaN(idx) ? i : idx);
                startTimer();
            });
        });

        root.setAttribute('tabindex', '0');
        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  { e.preventDefault(); goPrev(); startTimer(); }
            if (e.key === 'ArrowRight') { e.preventDefault(); goNext(); startTimer(); }
        });

        root.addEventListener('mouseenter', pause);
        root.addEventListener('mouseleave', resume);
        root.addEventListener('focusin', pause);
        root.addEventListener('focusout', function (e) {
            if (!root.contains(e.relatedTarget)) resume();
        });

        let touchX = null;
        track.addEventListener('touchstart', function (e) {
            touchX = e.touches[0].clientX;
        }, { passive: true });
        track.addEventListener('touchend', function (e) {
            if (touchX === null) return;
            const delta = touchX - e.changedTouches[0].clientX;
            if (Math.abs(delta) > 50) {
                delta > 0 ? goNext() : goPrev();
                startTimer();
            }
            touchX = null;
        }, { passive: true });

        goTo(current);
        startTimer();
    }

    document.querySelectorAll('.themisdb-hero-slider').forEach(function (root) {
        initSlider({
            root: root,
            trackSel: '#themisdb-hero-track, .themisdb-hero-track',
            slideSel: '.themisdb-hero-slide',
            dotsWrapSel: '#themisdb-hero-dots, .themisdb-slider-dots',
            dotSel: '.themisdb-slider-dot',
            prevSel: '#themisdb-hero-prev, .themisdb-slider-arrow-prev',
            nextSel: '#themisdb-hero-next, .themisdb-slider-arrow-next',
            counterSel: '#themisdb-hero-counter',
            progressSel: '#themisdb-hero-progress, .themisdb-hero-progress-bar',
            translateTrack: false,
        });
    });

    document.querySelectorAll('.themisdb-fs-wrapper').forEach(function (root) {
        initSlider({
            root: root,
            trackSel: '.themisdb-fs-track',
            slideSel: '.themisdb-fs-slide',
            dotSel: '.themisdb-fs-dot',
            prevSel: '.themisdb-fs-prev',
            nextSel: '.themisdb-fs-next',
            progressSel: '.themisdb-fs-timer-fill',
            intervalFromDataAttr: 'data-interval',
            autoplayFromDataAttr: 'data-autoplay',
            translateTrack: true,
        });
    });
})();
