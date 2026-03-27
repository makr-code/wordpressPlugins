/**
 * ThemisDB Theme – motion.js
 * Shared reveal animations for dynamic theme blocks.
 */
(function () {
    'use strict';

    var SELECTORS = {
        sectionHeaders: '.themisdb-section-shell > .themisdb-section-header',
        galleryItems: '.themisdb-gallery-item',
        sectionCards: '.themisdb-section-card',
        timelineEntries: '.themisdb-timeline > article, .themisdb-timeline-entry',
        stateLinks: '.themisdb-land-link',
        faqItems: '.themisdb-faq-item',
        buttonGridChildren: '.themisdb-button-box-grid > *',
        featureChildren: '.themisdb-feature > *'
    };

    var hasReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var root = document.documentElement;

    function setReveal(el, motionType, delay) {
        if (!el || el.classList.contains('themisdb-motion-target')) {
            return;
        }

        el.classList.add('themisdb-motion-target');
        el.setAttribute('data-motion', motionType || 'fade-up');
        el.style.setProperty('--themisdb-motion-delay', (delay || 0) + 'ms');
    }

    function assignSequence(selector, motionType, step, limit) {
        var nodes = Array.prototype.slice.call(document.querySelectorAll(selector));

        nodes.forEach(function (node, index) {
            var delay = Math.min(index * (step || 70), limit || 420);
            setReveal(node, motionType, delay);
        });
    }

    function assignFeatureChildren() {
        var rows = Array.prototype.slice.call(document.querySelectorAll('.themisdb-feature'));

        rows.forEach(function (row) {
            var children = Array.prototype.slice.call(row.children || []);
            if (!children.length) {
                return;
            }

            if (children[0]) {
                setReveal(children[0], 'slide-right', 0);
            }

            if (children[1]) {
                setReveal(children[1], 'slide-left', 110);
            }
        });
    }

    function revealAll() {
        document.querySelectorAll('.themisdb-motion-target').forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    function initObserver() {
        if (hasReducedMotion || !('IntersectionObserver' in window)) {
            revealAll();
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -12% 0px',
            threshold: 0.12
        });

        document.querySelectorAll('.themisdb-motion-target').forEach(function (el) {
            observer.observe(el);
        });
    }

    function initMediaLoading() {
        var mediaNodes = Array.prototype.slice.call(document.querySelectorAll(
            '.themisdb-gallery-item img, .themisdb-section-card img, .themisdb-feature img'
        ));

        mediaNodes.forEach(function (img) {
            var host = img.closest('.themisdb-gallery-item, .themisdb-section-card, .themisdb-feature');
            if (!host) {
                return;
            }

            function markReady() {
                host.classList.remove('is-media-loading');
                host.classList.add('is-media-ready');
                img.classList.add('is-loaded');
            }

            if (img.complete && img.naturalWidth > 0) {
                markReady();
                return;
            }

            img.addEventListener('load', markReady, { once: true });
            img.addEventListener('error', function () {
                host.classList.remove('is-media-loading');
                host.classList.add('is-media-ready', 'has-media-error');
            }, { once: true });
        });
    }

    root.classList.add('themisdb-motion-ready');
    if (hasReducedMotion) {
        root.classList.add('themisdb-reduced-motion');
    }

    assignSequence(SELECTORS.sectionHeaders, 'fade-up', 0, 0);
    assignSequence(SELECTORS.galleryItems, 'zoom-in', 70, 350);
    assignSequence(SELECTORS.sectionCards, 'fade-up', 80, 360);
    assignSequence(SELECTORS.timelineEntries, 'slide-right', 90, 360);
    assignSequence(SELECTORS.stateLinks, 'pop-in', 60, 360);
    assignSequence(SELECTORS.faqItems, 'fade-up', 55, 260);
    assignSequence(SELECTORS.buttonGridChildren, 'fade-up', 75, 300);
    assignFeatureChildren();
    initMediaLoading();
    initObserver();
}());