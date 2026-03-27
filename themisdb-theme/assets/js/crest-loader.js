/**
 * ThemisDB Theme – crest-loader.js
 * Setzt den src-Pfad aller Wappen-Bilder aus themisdbThemeData.crestBaseUrl
 */
(function () {
    'use strict';

    const baseUrl = (window.themisdbThemeData && window.themisdbThemeData.crestBaseUrl)
        ? window.themisdbThemeData.crestBaseUrl.replace(/\/?$/, '/')
        : '';

    if (!baseUrl) return;

    document.querySelectorAll('img.themisdb-crest-img[data-crest]').forEach(function (img) {
        const placeholder = img.parentElement
            ? img.parentElement.querySelector('.themisdb-crest-placeholder')
            : null;

        img.addEventListener('load', function () {
            img.style.display = 'block';
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        });

        img.addEventListener('error', function () {
            img.style.display = 'none';
            if (placeholder) {
                placeholder.style.display = 'flex';
            }
        });

        img.src = baseUrl + img.dataset.crest;
    });
})();
