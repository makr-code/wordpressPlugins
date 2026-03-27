/**
 * ThemisDB Theme – gallery.js
 * Filter-Tabs + Lightbox (Tastatur & Touch)
 */
(function () {
    'use strict';

    const grid      = document.getElementById('themisdb-gallery-grid');
    const filters   = document.getElementById('themisdb-gallery-filters');
    const lightbox  = document.getElementById('themisdb-lightbox');
    const lbImg     = document.getElementById('themisdb-lb-img');
    const lbTitle   = document.getElementById('themisdb-lb-title');
    const lbDesc    = document.getElementById('themisdb-lb-desc');
    const lbClose   = document.getElementById('themisdb-lb-close');
    const lbPrev    = document.getElementById('themisdb-lb-prev');
    const lbNext    = document.getElementById('themisdb-lb-next');

    if (!grid) return;

    const items = Array.from(grid.querySelectorAll('.themisdb-gallery-item'));
    let visibleItems = items.slice(); /* gefilterte Liste */
    let currentIdx   = 0;

    /* ── Filter ── */
    if (filters) {
        filters.addEventListener('click', function (e) {
            const btn = e.target.closest('.themisdb-gallery-filter-btn');
            if (!btn) return;

            /* Aktiv-Klasse */
            filters.querySelectorAll('.themisdb-gallery-filter-btn').forEach(function (b) {
                b.classList.remove('is-active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('is-active');
            btn.setAttribute('aria-selected', 'true');

            const filter = btn.dataset.filter;

            items.forEach(function (item) {
                const match = filter === 'all' || item.dataset.category === filter;
                item.classList.toggle('is-hidden', !match);
            });

            visibleItems = items.filter(function (item) {
                return !item.classList.contains('is-hidden');
            });
        });
    }

    /* ── Lightbox öffnen ── */
    function openLightbox(item) {
        if (!lightbox) return;
        currentIdx = visibleItems.indexOf(item);

        const img = item.querySelector('img');
        if (lbImg)   { lbImg.src = img ? img.src : ''; lbImg.alt = img ? img.alt : ''; }
        if (lbTitle) lbTitle.textContent = item.dataset.title || '';
        if (lbDesc)  lbDesc.textContent  = item.dataset.desc  || '';

        lightbox.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        lbClose?.focus();
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.classList.remove('is-open');
        document.body.style.overflow = '';
        /* Fokus zurück auf zuletzt geöffnetes Item */
        visibleItems[currentIdx]?.focus();
    }

    function navigate(dir) {
        currentIdx = (currentIdx + dir + visibleItems.length) % visibleItems.length;
        const item = visibleItems[currentIdx];
        const img  = item?.querySelector('img');
        if (lbImg)   { lbImg.src = img ? img.src : ''; lbImg.alt = img ? img.alt : ''; }
        if (lbTitle) lbTitle.textContent = item?.dataset.title || '';
        if (lbDesc)  lbDesc.textContent  = item?.dataset.desc  || '';
    }

    /* ── Event: Galerie-Items ── */
    items.forEach(function (item) {
        item.addEventListener('click',   function () { openLightbox(item); });
        item.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLightbox(item); }
        });
    });

    /* ── Event: Lightbox-Buttons ── */
    if (lbClose)  lbClose.addEventListener('click',  closeLightbox);
    if (lbPrev)   lbPrev.addEventListener('click',   function () { navigate(-1); });
    if (lbNext)   lbNext.addEventListener('click',   function () { navigate(+1); });

    /* ── Event: Hintergrund-Klick schließt ── */
    if (lightbox) {
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });
    }

    /* ── Event: Tastatur ── */
    document.addEventListener('keydown', function (e) {
        if (!lightbox?.classList.contains('is-open')) return;
        if (e.key === 'Escape')      closeLightbox();
        if (e.key === 'ArrowLeft')   navigate(-1);
        if (e.key === 'ArrowRight')  navigate(+1);
    });

    /* ── Touch Swipe ── */
    let touchX = null;
    if (lightbox) {
        lightbox.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
        lightbox.addEventListener('touchend', function (e) {
            if (touchX === null) return;
            const delta = touchX - e.changedTouches[0].clientX;
            if (Math.abs(delta) > 50) delta > 0 ? navigate(+1) : navigate(-1);
            touchX = null;
        }, { passive: true });
    }
})();
