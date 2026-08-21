/**
 * ThemisDB Front Slider – Vanilla JS controller
 *
 * Features:
 *  - Timer-driven autoplay (configurable interval via data-interval)
 *  - Animated progress bar that fills over each interval
 *  - Previous / Next buttons
 *  - Dot navigation
 *  - Touch / pointer swipe (50 px threshold)
 *  - Keyboard navigation (←/→ on focused wrapper)
 *  - Pause on hover / focus-within
 *  - Respects prefers-reduced-motion (disables autoplay & animations)
 *  - Accessible: ARIA live region, aria-hidden on inactive slides,
 *    tabindex management for off-screen links
 */
(function () {
    'use strict';

    var SWIPE_THRESHOLD = 50; // px

    /**
     * Initialise a single slider wrapper element.
     * @param {HTMLElement} wrapper
     */
    function initSlider(wrapper) {
        if (!wrapper || wrapper.dataset.tfsInit === '1') {
            return;
        }
        wrapper.dataset.tfsInit = '1';

        var track       = wrapper.querySelector('.themisdb-fs-track');
        var slides      = Array.prototype.slice.call(wrapper.querySelectorAll('.themisdb-fs-slide'));
        var dots        = Array.prototype.slice.call(wrapper.querySelectorAll('.themisdb-fs-dot'));
        var prevBtn     = wrapper.querySelector('.themisdb-fs-prev');
        var nextBtn     = wrapper.querySelector('.themisdb-fs-next');
        var timerFill   = wrapper.querySelector('.themisdb-fs-timer-fill');

        if (!track || slides.length < 2) {
            return; // nothing to slide
        }

        var total       = slides.length;
        var current     = 0;
        var interval    = parseInt(wrapper.getAttribute('data-interval'), 10) || 5000;
        var autoplay    = wrapper.getAttribute('data-autoplay') !== '0';
        var respectReduced = wrapper.getAttribute('data-respect-reduced-motion') !== '0';
        var prefersReduced = respectReduced && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        var timerID        = null;
        var timerStartedAt = null;
        var timerRemaining = interval;
        var rafID          = null;
        var imageBlendTimers = [];
        var imageBlendInterval = Math.max(2400, Math.round(interval * 0.58));

        if (prefersReduced) {
            wrapper.classList.add('is-reduced-motion');
            autoplay = false;
        } else {
            wrapper.classList.remove('is-reduced-motion');
        }

        function assignSlideImageEffects() {
            var variants = ['has-image-effect-a', 'has-image-effect-b', 'has-image-effect-c'];
            slides.forEach(function (slide) {
                slide.classList.remove('has-image-effect-a', 'has-image-effect-b', 'has-image-effect-c');
                if (!slide.querySelector('.themisdb-fs-image-card')) {
                    return;
                }
                var pick = variants[Math.floor(Math.random() * variants.length)];
                slide.classList.add(pick);
            });
        }

        function fitActiveSlide() {
            var active = slides[current];
            if (!active) {
                return;
            }

            var inner = active.querySelector('.themisdb-fs-slide-inner');
            var content = active.querySelector('.themisdb-fs-slide-content');
            if (!inner || !content) {
                return;
            }

            active.classList.remove('is-fit-tight', 'is-fit-compact');

            var available = inner.clientHeight;
            var needed = content.scrollHeight;

            if (needed > available) {
                active.classList.add('is-fit-tight');
                needed = content.scrollHeight;
            }

            if (needed > available) {
                active.classList.add('is-fit-compact');
            }
        }

        function resetSlideImageLayers(slide) {
            if (!slide) {
                return;
            }
            var layers = slide.querySelectorAll('.themisdb-fs-layer-image');
            if (!layers.length) {
                return;
            }
            Array.prototype.forEach.call(layers, function (layer, idx) {
                layer.classList.toggle('is-layer-active', idx === 0);
            });
            slide.setAttribute('data-image-layer-index', '0');
        }

        function stopImageBlendTimers() {
            imageBlendTimers.forEach(function (id) {
                clearInterval(id);
            });
            imageBlendTimers = [];
        }

        function startActiveSlideImageBlend() {
            stopImageBlendTimers();
            if (prefersReduced) {
                return;
            }

            var slide = slides[current];
            if (!slide) {
                return;
            }

            var layers = Array.prototype.slice.call(slide.querySelectorAll('.themisdb-fs-layer-image'));
            if (layers.length < 2) {
                return;
            }

            var currentLayer = parseInt(slide.getAttribute('data-image-layer-index') || '0', 10);
            if (isNaN(currentLayer) || currentLayer < 0 || currentLayer >= layers.length) {
                currentLayer = 0;
            }

            var blendId = setInterval(function () {
                var nextLayer = (currentLayer + 1) % layers.length;
                layers[currentLayer].classList.remove('is-layer-active');
                layers[nextLayer].classList.add('is-layer-active');
                currentLayer = nextLayer;
                slide.setAttribute('data-image-layer-index', String(currentLayer));
            }, imageBlendInterval);

            imageBlendTimers.push(blendId);
        }

        /* ------------------------------------------------------------------
         * Height: mirror the CSS custom property for slide height.
         * The PHP template injects an inline style on the slide itself;
         * here we just ensure the wrapper variable is in sync.
         * ---------------------------------------------------------------- */
        var imageHeight = parseInt(
            getComputedStyle(slides[0]).getPropertyValue('--tfs-slide-height') || '420',
            10
        );
        // If no CSS variable, fall back to the actual rendered height.
        if (!imageHeight) {
            imageHeight = slides[0].offsetHeight || 420;
        }

        /* ------------------------------------------------------------------
         * Go to a specific slide index.
         * ---------------------------------------------------------------- */
        function goTo(index) {
            var prev = current;

            // Clamp / wrap
            if (index < 0)      { index = total - 1; }
            if (index >= total) { index = 0; }

            // Update track position
            track.style.transform = 'translateX(-' + (index * 100) + '%)';

            // Update slide classes + ARIA
            slides[prev].classList.remove('is-active');
            slides[prev].setAttribute('aria-hidden', 'true');
            setTabIndex(slides[prev], '-1');

            slides[index].classList.add('is-active');
            slides[index].setAttribute('aria-hidden', 'false');
            setTabIndex(slides[index], '0');

            // Update dots
            if (dots[prev]) {
                dots[prev].classList.remove('is-active');
                dots[prev].setAttribute('aria-selected', 'false');
            }
            if (dots[index]) {
                dots[index].classList.add('is-active');
                dots[index].setAttribute('aria-selected', 'true');
            }

            current = index;
            fitActiveSlide();
            startActiveSlideImageBlend();
        }

        /**
         * Set tabindex on all interactive children of a slide.
         * @param {HTMLElement} slide
         * @param {string} value  '0' or '-1'
         */
        function setTabIndex(slide, value) {
            var focusable = slide.querySelectorAll('a, button, [tabindex]');
            Array.prototype.forEach.call(focusable, function (el) {
                el.setAttribute('tabindex', value);
            });
        }

        /* ------------------------------------------------------------------
         * Autoplay timer
         * ---------------------------------------------------------------- */
        function startTimer() {
            if (!autoplay) { return; }
            if (timerID !== null) {
                clearTimeout(timerID);
                timerID = null;
            }
            if (timerRemaining <= 0 || timerRemaining > interval) {
                timerRemaining = interval;
            }
            timerStartedAt = Date.now();
            timerID = setTimeout(function () {
                goTo(current + 1);
                timerRemaining = interval;
                resetProgressBar();
                startTimer();
            }, timerRemaining);
            animateProgressBar(timerRemaining);
        }

        function stopTimer() {
            var hadTimer = timerID !== null;
            if (timerID !== null) {
                clearTimeout(timerID);
                timerID = null;
            }
            var elapsed   = timerStartedAt ? Date.now() - timerStartedAt : 0;
            if (hadTimer) {
                timerRemaining = Math.max(0, timerRemaining - elapsed);
            }
            timerStartedAt = null;
            cancelProgressAnimation();
        }

        function resetProgressBar() {
            timerRemaining = interval;
            cancelProgressAnimation();
            if (timerFill) {
                timerFill.style.transition = 'none';
                timerFill.style.width      = '0%';
                // Read offsetWidth to force a reflow so the browser applies
                // the transition:none before we re-enable the animation.
                // Without this, the CSS engine batches both style writes and
                // the "none" transition is never actually committed.
                timerFill.offsetWidth; // eslint-disable-line no-unused-expressions
            }
        }

        function animateProgressBar(duration) {
            if (!timerFill || prefersReduced) { return; }
            timerFill.style.transition = 'width ' + duration + 'ms linear';
            timerFill.style.width      = '100%';
        }

        function cancelProgressAnimation() {
            if (rafID) {
                cancelAnimationFrame(rafID);
                rafID = null;
            }
            if (timerFill) {
                // Freeze bar at its current painted position.
                var currentWidth = timerFill.getBoundingClientRect().width;
                var parentWidth  = timerFill.parentNode
                    ? timerFill.parentNode.getBoundingClientRect().width
                    : 1;
                var pct = parentWidth > 0
                    ? Math.round((currentWidth / parentWidth) * 100)
                    : 0;
                timerFill.style.transition = 'none';
                timerFill.style.width      = pct + '%';
            }
        }

        /* ------------------------------------------------------------------
         * Pause / Resume on hover and focus-within
         * ---------------------------------------------------------------- */
        wrapper.addEventListener('mouseenter', stopTimer);
        wrapper.addEventListener('mouseleave', function () {
            startTimer();
        });
        wrapper.addEventListener('focusin', stopTimer);
        wrapper.addEventListener('focusout', function (e) {
            // Resume only when focus leaves the slider entirely.
            if (!wrapper.contains(e.relatedTarget)) {
                startTimer();
            }
        });

        /* ------------------------------------------------------------------
         * Prev / Next buttons
         * ---------------------------------------------------------------- */
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                stopTimer();
                resetProgressBar();
                goTo(current - 1);
                startTimer();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                stopTimer();
                resetProgressBar();
                goTo(current + 1);
                startTimer();
            });
        }

        /* ------------------------------------------------------------------
         * Dot navigation
         * ---------------------------------------------------------------- */
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var idx = parseInt(dot.getAttribute('data-index'), 10);
                if (!isNaN(idx) && idx !== current) {
                    stopTimer();
                    resetProgressBar();
                    goTo(idx);
                    startTimer();
                }
            });
        });

        /* ------------------------------------------------------------------
         * Keyboard navigation (arrow keys on focused wrapper)
         * ---------------------------------------------------------------- */
        wrapper.setAttribute('tabindex', '0');
        wrapper.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                stopTimer();
                resetProgressBar();
                goTo(current + (e.key === 'ArrowRight' ? 1 : -1));
                startTimer();
            }
        });

        /* ------------------------------------------------------------------
         * Touch / Pointer swipe
         * ---------------------------------------------------------------- */
        var touchStartX   = null;
        var touchStartY   = null;
        var isDragging    = false;

        wrapper.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'mouse' && e.button !== 0) { return; }
            touchStartX = e.clientX;
            touchStartY = e.clientY;
            isDragging  = false;
        }, { passive: true });

        wrapper.addEventListener('pointermove', function (e) {
            if (touchStartX === null) { return; }
            var dx = Math.abs(e.clientX - touchStartX);
            var dy = Math.abs(e.clientY - touchStartY);
            if (dx > dy && dx > 8) { isDragging = true; }
        }, { passive: true });

        wrapper.addEventListener('pointerup', function (e) {
            if (touchStartX === null) { return; }
            var deltaX = e.clientX - touchStartX;
            touchStartX = null;
            touchStartY = null;
            if (!isDragging) { return; }
            isDragging = false;
            if (Math.abs(deltaX) < SWIPE_THRESHOLD) { return; }
            stopTimer();
            resetProgressBar();
            goTo(current + (deltaX < 0 ? 1 : -1));
            startTimer();
        });

        wrapper.addEventListener('pointercancel', function () {
            touchStartX = null;
            touchStartY = null;
            isDragging  = false;
        });

        window.addEventListener('resize', function () {
            fitActiveSlide();
        });

        /* ------------------------------------------------------------------
         * Init
         * ---------------------------------------------------------------- */
        // Ensure the first slide is fully visible (no leftover transform).
        track.style.transform = 'translateX(0%)';
        assignSlideImageEffects();
        slides.forEach(function (slide) {
            resetSlideImageLayers(slide);
        });
        fitActiveSlide();
        startActiveSlideImageBlend();
        resetProgressBar();
        startTimer();
    }

    /* ----------------------------------------------------------------------
     * Boot: find all slider wrappers on the page.
     * -------------------------------------------------------------------- */
    function boot() {
        var wrappers = document.querySelectorAll('.themisdb-fs-wrapper');
        Array.prototype.forEach.call(wrappers, function (wrapper) {
            initSlider(wrapper);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());
