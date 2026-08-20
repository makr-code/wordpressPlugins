/**
 * ThemisDB Theme – navigation.js
 * Mobile-Menu-Toggle + Smooth-Scroll + Aktiver Abschnitt-Tracking
 */
(function () {
    'use strict';

    const NAV     = document.getElementById('themisdb-navbar');
    const BURGER  = document.getElementById('themisdb-hamburger');
    const MOBILE  = document.getElementById('themisdb-mobile-menu');
    const REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Mobile Menu ── */
    if (BURGER && MOBILE) {
        BURGER.addEventListener('click', function () {
            const open = MOBILE.classList.toggle('is-open');
            BURGER.setAttribute('aria-expanded', String(open));
            BURGER.querySelector('[data-icon]')?.setAttribute('data-icon', open ? 'bars-staggered' : 'bars');
        });

        /* Schließen bei Klick außerhalb */
        document.addEventListener('click', function (e) {
            if (!NAV.contains(e.target)) {
                MOBILE.classList.remove('is-open');
                BURGER.setAttribute('aria-expanded', 'false');
            }
        });

        /* Schließen, wenn ein Menüpunkt geklickt wird */
        MOBILE.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function () {
                MOBILE.classList.remove('is-open');
                BURGER.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ── Smooth Scroll für alle Anker-Links ── */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            const href   = a.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (!target) return;
            e.preventDefault();

            const offset = (NAV ? NAV.offsetHeight : 0) + 16;
            const top    = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top: top, behavior: REDUCED ? 'auto' : 'smooth' });
        });
    });

    /* ── Navbar Scroll-Schatten ── */
    if (NAV) {
        window.addEventListener('scroll', function () {
            NAV.classList.toggle('is-scrolled', window.scrollY > 40);
        }, { passive: true });
    }

    /* ── Aktiven Nav-Link anhand des sichtbaren Abschnitts markieren ── */
    const sections = document.querySelectorAll('section[id]');
    if (sections.length && 'IntersectionObserver' in window) {
        const desktopLinks = document.querySelectorAll('.themisdb-nav-links a[href^="#"]');
        const mobileLinks  = document.querySelectorAll('#themisdb-mobile-menu a[href^="#"]');

        const obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const id = '#' + entry.target.id;
                const markActive = function (links) {
                    links.forEach(function (l) {
                        l.classList.toggle('is-active', l.getAttribute('href') === id);
                    });
                };
                markActive(desktopLinks);
                markActive(mobileLinks);
            });
        }, { rootMargin: '-40% 0px -55% 0px' });

        sections.forEach(function (s) { obs.observe(s); });
    }
})();
