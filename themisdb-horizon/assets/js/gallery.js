/**
 * ThemisDB Theme – gallery.js
 * Scoped gallery controller with count badges, focus-safe lightbox and touch support.
 */
(function () {
    'use strict';

    var FOCUSABLE = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

    function formatCount(count) {
        return count === 1 ? count + ' Eintrag' : count + ' Einträge';
    }

    function preloadImage(item) {
        var img = item && item.querySelector('img');
        if (!img || !img.src) {
            return;
        }

        var preloader = new Image();
        preloader.src = img.src;
    }

    function initSection(section) {
        var grid = section.querySelector('[data-gallery-grid]') || section.querySelector('.themisdb-gallery-grid');
        var filters = section.querySelector('#themisdb-gallery-filters');
        var results = section.querySelector('[data-gallery-results]');
        var lightbox = section.querySelector('[data-gallery-lightbox]');
        var lbInner = lightbox ? lightbox.querySelector('.themisdb-lb-inner') : null;
        var lbImg = lightbox ? lightbox.querySelector('[data-gallery-image]') : null;
        var lbTitle = lightbox ? lightbox.querySelector('[data-gallery-title]') : null;
        var lbDesc = lightbox ? lightbox.querySelector('[data-gallery-desc]') : null;
        var lbClose = lightbox ? lightbox.querySelector('[data-gallery-close]') : null;
        var lbPrev = lightbox ? lightbox.querySelector('[data-gallery-prev]') : null;
        var lbNext = lightbox ? lightbox.querySelector('[data-gallery-next]') : null;
        var lbPosition = lightbox ? lightbox.querySelector('[data-gallery-position]') : null;

        if (!grid) {
            return;
        }

        var items = Array.prototype.slice.call(grid.querySelectorAll('.themisdb-gallery-item'));
        var visibleItems = items.slice();
        var currentIdx = 0;
        var lastFocusedItem = null;
        var touchX = null;

        function updateResults() {
            if (results) {
                results.textContent = formatCount(visibleItems.length);
            }
        }

        function updateLightbox(item) {
            var img = item ? item.querySelector('img') : null;

            if (lbImg) {
                lbImg.src = img ? img.src : '';
                lbImg.alt = img ? img.alt : '';
            }

            if (lbTitle) {
                lbTitle.textContent = item ? (item.dataset.title || '') : '';
            }

            if (lbDesc) {
                lbDesc.textContent = item ? (item.dataset.desc || '') : '';
            }

            if (lbPosition) {
                lbPosition.textContent = visibleItems.length ? (currentIdx + 1) + ' / ' + visibleItems.length : '';
            }

            preloadImage(visibleItems[(currentIdx + 1) % visibleItems.length]);
            preloadImage(visibleItems[(currentIdx - 1 + visibleItems.length) % visibleItems.length]);
        }

        function trapFocus(event) {
            if (!lightbox || !lightbox.classList.contains('is-open') || event.key !== 'Tab') {
                return;
            }

            var focusables = Array.prototype.slice.call(lightbox.querySelectorAll(FOCUSABLE)).filter(function (el) {
                return el.offsetParent !== null;
            });

            if (!focusables.length) {
                return;
            }

            var first = focusables[0];
            var last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function openLightbox(item) {
            if (!lightbox || !visibleItems.length) {
                return;
            }

            lastFocusedItem = item;
            currentIdx = Math.max(0, visibleItems.indexOf(item));
            updateLightbox(visibleItems[currentIdx]);

            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            if (lbInner) {
                lbInner.focus();
            } else if (lbClose) {
                lbClose.focus();
            }
        }

        function closeLightbox() {
            if (!lightbox) {
                return;
            }

            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            if (lastFocusedItem) {
                lastFocusedItem.focus();
            }
        }

        function navigate(dir) {
            if (!visibleItems.length) {
                return;
            }

            currentIdx = (currentIdx + dir + visibleItems.length) % visibleItems.length;
            updateLightbox(visibleItems[currentIdx]);
        }

        function applyFilter(filter) {
            items.forEach(function (item) {
                var match = filter === 'all' || item.dataset.category === filter;
                item.classList.toggle('is-hidden', !match);
                item.setAttribute('aria-hidden', match ? 'false' : 'true');
                item.tabIndex = match ? 0 : -1;
            });

            visibleItems = items.filter(function (item) {
                return !item.classList.contains('is-hidden');
            });

            updateResults();
        }

        if (filters) {
            filters.addEventListener('click', function (event) {
                var btn = event.target.closest('.themisdb-gallery-filter-btn');
                if (!btn) {
                    return;
                }

                filters.querySelectorAll('.themisdb-gallery-filter-btn').forEach(function (node) {
                    node.classList.remove('is-active');
                    node.setAttribute('aria-selected', 'false');
                });

                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');
                applyFilter(btn.dataset.filter || 'all');
            });
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                openLightbox(item);
            });

            item.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openLightbox(item);
                }
            });
        });

        if (lbClose) {
            lbClose.addEventListener('click', closeLightbox);
        }

        if (lbPrev) {
            lbPrev.addEventListener('click', function () {
                navigate(-1);
            });
        }

        if (lbNext) {
            lbNext.addEventListener('click', function () {
                navigate(1);
            });
        }

        if (lightbox) {
            lightbox.addEventListener('click', function (event) {
                if (event.target === lightbox) {
                    closeLightbox();
                }
            });

            lightbox.addEventListener('touchstart', function (event) {
                touchX = event.touches[0].clientX;
            }, { passive: true });

            lightbox.addEventListener('touchend', function (event) {
                if (touchX === null) {
                    return;
                }

                var delta = touchX - event.changedTouches[0].clientX;
                if (Math.abs(delta) > 50) {
                    navigate(delta > 0 ? 1 : -1);
                }
                touchX = null;
            }, { passive: true });
        }

        document.addEventListener('keydown', function (event) {
            if (!lightbox || !lightbox.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                closeLightbox();
            } else if (event.key === 'ArrowLeft') {
                navigate(-1);
            } else if (event.key === 'ArrowRight') {
                navigate(1);
            } else {
                trapFocus(event);
            }
        });

        applyFilter('all');
    }

    document.querySelectorAll('.themisdb-gallery-section').forEach(initSection);
}());
