/**
 * ThemisDB Theme – legal-accordion.js
 * Öffnen/Schließen der Impressum- und Datenschutz-Sektionen
 */
(function () {
    'use strict';

    /* data-legal-toggle öffnet die Sektion */
    document.querySelectorAll('[data-legal-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id      = btn.dataset.legalToggle;
            const section = document.getElementById(id);
            if (!section) return;

            const isOpen = section.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', String(isOpen));
            section.setAttribute('aria-hidden', String(!isOpen));

            if (isOpen) {
                /* Sanftes Scrollen zur Sektion */
                setTimeout(function () {
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 50);
                /* Schließ-Button fokussieren */
                section.querySelector('[data-legal-close]')?.focus();
            }
        });
    });

    /* data-legal-close schließt die eigene Sektion */
    document.querySelectorAll('[data-legal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id      = btn.dataset.legalClose;
            const section = document.getElementById(id);
            if (!section) return;

            section.classList.remove('is-open');
            section.setAttribute('aria-hidden', 'true');

            /* Zugehörigen Toggle-Button zurücksetzen */
            document.querySelectorAll('[data-legal-toggle="' + id + '"]').forEach(function (t) {
                t.setAttribute('aria-expanded', 'false');
                t.focus();
            });
        });
    });

    /* Escape schließt offene Rechts-Sektionen */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.themisdb-legal-block.is-open').forEach(function (section) {
            section.querySelector('[data-legal-close]')?.click();
        });
    });
})();
