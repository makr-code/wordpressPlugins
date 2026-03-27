/**
 * ThemisDB Theme – faq.js
 * Akkordeon für den FAQ-Bereich
 */
(function () {
    'use strict';

    const container = document.getElementById('themisdb-faq-container');
    if (!container) return;

    container.addEventListener('click', function (e) {
        const toggle = e.target.closest('.themisdb-faq-toggle');
        if (!toggle) return;

        const item   = toggle.closest('.themisdb-faq-item');
        const answer = item?.querySelector('.themisdb-faq-answer');
        if (!answer) return;

        const isOpen = answer.classList.contains('is-open');

        /* Alle anderen schließen */
        container.querySelectorAll('.themisdb-faq-answer.is-open').forEach(function (a) {
            a.classList.remove('is-open');
            a.closest('.themisdb-faq-item')?.classList.remove('is-open');
            a.previousElementSibling?.setAttribute('aria-expanded', 'false');
        });

        /* Dieses öffnen / schließen */
        if (!isOpen) {
            answer.classList.add('is-open');
            item.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        }
    });

    /* Tastatur: Enter/Space auf Toggle */
    container.addEventListener('keydown', function (e) {
        const toggle = e.target.closest('.themisdb-faq-toggle');
        if (!toggle) return;
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggle.click();
        }
    });
})();
