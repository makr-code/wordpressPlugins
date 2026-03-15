/**
 * Modern enhancements for ThemisDB theme
 * Includes: Reading progress, lazy loading, animations, and interactive features
 */
(function() {
    'use strict';

    /**
     * Reading Progress Indicator
     */
    function initReadingProgress() {
        // Create progress bar element
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        document.body.appendChild(progressBar);

        // Update progress on scroll
        function updateProgress() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            progressBar.style.width = scrolled + '%';
        }

        // Throttle scroll events
        let ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    updateProgress();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    /**
     * Lazy Loading for Images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            // Observe all images with data-src attribute
            const lazyImages = document.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => imageObserver.observe(img));
        }
    }

    /**
     * Accordion functionality
     */
    function initAccordion() {
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isActive = this.classList.contains('active');
                
                // Close all accordions
                document.querySelectorAll('.accordion-header').forEach(h => {
                    h.classList.remove('active');
                    if (h.nextElementSibling) {
                        h.nextElementSibling.classList.remove('active');
                    }
                });
                
                // Toggle current accordion
                if (!isActive) {
                    this.classList.add('active');
                    if (content) {
                        content.classList.add('active');
                    }
                }
            });
        });
    }

    /**
     * Smooth reveal animations on scroll
     */
    function initScrollAnimations() {
        if ('IntersectionObserver' in window) {
            const revealElements = document.querySelectorAll('.reveal-on-scroll');
            
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                    }
                });
            }, {
                threshold: 0.15
            });

            revealElements.forEach(el => revealObserver.observe(el));
        }
    }

    /**
     * Image Gallery Lightbox
     */
    function initImageGallery() {
        const galleryImages = document.querySelectorAll('.gallery img, .wp-block-gallery img');
        
        if (galleryImages.length === 0) return;

        // Create lightbox overlay
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox-overlay';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <button class="lightbox-close" aria-label="Close">✕</button>
                <button class="lightbox-prev" aria-label="Previous">‹</button>
                <img src="" alt="" class="lightbox-image">
                <button class="lightbox-next" aria-label="Next">›</button>
            </div>
        `;
        document.body.appendChild(lightbox);

        const lightboxImg = lightbox.querySelector('.lightbox-image');
        const closeBtn = lightbox.querySelector('.lightbox-close');
        const prevBtn = lightbox.querySelector('.lightbox-prev');
        const nextBtn = lightbox.querySelector('.lightbox-next');
        let currentIndex = 0;
        let images = [];

        function showLightbox(index) {
            currentIndex = index;
            lightboxImg.src = images[currentIndex].src;
            lightboxImg.alt = images[currentIndex].alt;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function hideLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        function showNext() {
            currentIndex = (currentIndex + 1) % images.length;
            lightboxImg.src = images[currentIndex].src;
            lightboxImg.alt = images[currentIndex].alt;
        }

        function showPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            lightboxImg.src = images[currentIndex].src;
            lightboxImg.alt = images[currentIndex].alt;
        }

        // Store all gallery images
        images = Array.from(galleryImages);

        // Add click events to gallery images
        galleryImages.forEach((img, index) => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', () => showLightbox(index));
        });

        // Lightbox controls
        closeBtn.addEventListener('click', hideLightbox);
        prevBtn.addEventListener('click', showPrev);
        nextBtn.addEventListener('click', showNext);
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) hideLightbox();
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            
            if (e.key === 'Escape') hideLightbox();
            if (e.key === 'ArrowRight') showNext();
            if (e.key === 'ArrowLeft') showPrev();
        });
    }

    /**
     * Animated counter for stats
     */
    function initCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        if (counters.length === 0) return;

        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.dataset.count || counter.textContent);
                    const duration = 2000; // 2 seconds
                    const increment = target / (duration / 16); // 60fps
                    let current = 0;

                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            counter.textContent = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target;
                        }
                    };

                    updateCounter();
                    observer.unobserve(counter);
                }
            });
        }, observerOptions);

        counters.forEach(counter => observer.observe(counter));
    }

    /**
     * Back to top button
     */
    function initBackToTop() {
        const backToTop = document.createElement('button');
        backToTop.className = 'back-to-top';
        backToTop.innerHTML = '↑';
        backToTop.setAttribute('aria-label', 'Back to top');
        backToTop.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent-purple, #7c4dff);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 12px rgba(124, 77, 255, 0.4);
        `;
        document.body.appendChild(backToTop);

        // Show/hide button based on scroll position
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });

        // Scroll to top on click with fallback for older browsers
        backToTop.addEventListener('click', () => {
            // Try modern smooth scroll first
            if ('scrollBehavior' in document.documentElement.style) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                // Fallback for older browsers - animated scroll
                const scrollToTop = () => {
                    const c = document.documentElement.scrollTop || document.body.scrollTop;
                    if (c > 0) {
                        window.requestAnimationFrame(scrollToTop);
                        window.scrollTo(0, c - c / 8);
                    }
                };
                scrollToTop();
            }
        });
    }

    /**
     * Tooltips initialization
     */
    function initTooltips() {
        // Automatically add tooltips to abbreviations
        const abbrs = document.querySelectorAll('abbr[title]');
        abbrs.forEach(abbr => {
            abbr.classList.add('tooltip');
            const tooltipText = document.createElement('span');
            tooltipText.className = 'tooltiptext';
            tooltipText.textContent = abbr.getAttribute('title');
            abbr.appendChild(tooltipText);
        });
    }

    /**
     * Copy code blocks to clipboard
     */
    function initCodeCopy() {
        const codeBlocks = document.querySelectorAll('pre code');
        
        codeBlocks.forEach(code => {
            const pre = code.parentElement;
            const copyBtn = document.createElement('button');
            copyBtn.className = 'copy-code-btn';
            copyBtn.innerHTML = '📋 Copy';
            copyBtn.style.cssText = `
                position: absolute;
                top: 8px;
                right: 8px;
                padding: 4px 12px;
                background: rgba(255, 255, 255, 0.2);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: all 0.3s ease;
            `;
            
            pre.style.position = 'relative';
            pre.appendChild(copyBtn);

            copyBtn.addEventListener('click', async () => {
                const text = code.textContent;
                try {
                    // Modern clipboard API with fallback
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                        copyBtn.innerHTML = '✅ Copied!';
                    } else {
                        // Fallback for older browsers
                        const textArea = document.createElement('textarea');
                        textArea.value = text;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        document.body.appendChild(textArea);
                        textArea.select();
                        try {
                            document.execCommand('copy');
                            copyBtn.innerHTML = '✅ Copied!';
                        } catch (err) {
                            copyBtn.innerHTML = '❌ Failed';
                        }
                        document.body.removeChild(textArea);
                    }
                    setTimeout(() => {
                        copyBtn.innerHTML = '📋 Copy';
                    }, 2000);
                } catch (err) {
                    copyBtn.innerHTML = '❌ Failed';
                    setTimeout(() => {
                        copyBtn.innerHTML = '📋 Copy';
                    }, 2000);
                }
            });
        });
    }

    /**
     * Copy social share link via data attributes (no inline handlers)
     */
    function initShareCopyButton() {
        const copyButtons = document.querySelectorAll('.share-button.share-copy[data-url]');
        if (copyButtons.length === 0) return;

        copyButtons.forEach((button) => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                themisdbCopyUrl(button);
            });
        });
    }

    /**
     * External links - open in new tab with icon
     */
    function initExternalLinks() {
        const links = document.querySelectorAll('a[href^="http"]');
        links.forEach(link => {
            const url = new URL(link.href);
            if (url.hostname !== window.location.hostname) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
                if (!link.querySelector('.external-icon')) {
                    const icon = document.createElement('span');
                    icon.className = 'external-icon';
                    icon.innerHTML = ' 🔗';
                    icon.style.fontSize = '0.8em';
                    link.appendChild(icon);
                }
            }
        });
    }

    /**
     * Featured Image Contrast Detection
     * Analyses the brightness of the featured image and adds a CSS class
     * to the article element so that CSS can apply appropriate text contrast.
     */
    function initFeaturedImageContrast() {
        var thumbnail = document.querySelector( '.entry-thumbnail img' );
        if ( ! thumbnail ) {
            return;
        }
        var article = thumbnail.closest( 'article' );
        if ( ! article ) {
            return;
        }

        function applyContrastClass( img ) {
            try {
                var sampleSize = 50;
                var canvas     = document.createElement( 'canvas' );
                canvas.width   = sampleSize;
                canvas.height  = sampleSize;
                var ctx = canvas.getContext( '2d' );
                ctx.drawImage( img, 0, 0, sampleSize, sampleSize );
                var data       = ctx.getImageData( 0, 0, sampleSize, sampleSize ).data;
                var brightness = 0;
                var pixels     = sampleSize * sampleSize;
                for ( var i = 0; i < data.length; i += 4 ) {
                    // Perceived brightness (WCAG luminance formula)
                    brightness += data[ i ] * 0.299 + data[ i + 1 ] * 0.587 + data[ i + 2 ] * 0.114;
                }
                brightness = brightness / pixels;
                if ( brightness < 128 ) {
                    article.classList.add( 'has-dark-featured-image' );
                } else {
                    article.classList.add( 'has-light-featured-image' );
                }
            } catch ( e ) {
                // Canvas read blocked by CORS – fall back to CSS-only treatment
            }
        }

        if ( thumbnail.complete && thumbnail.naturalWidth > 0 ) {
            applyContrastClass( thumbnail );
        } else {
            thumbnail.addEventListener( 'load', function () {
                applyContrastClass( thumbnail );
            } );
        }
    }

    /**
     * Initialize all enhancements
     */
    function init() {
        try {
            initReadingProgress();
            initLazyLoading();
            initAccordion();
            initScrollAnimations();
            initImageGallery();
            initCounters();
            initBackToTop();
            initTooltips();
            initCodeCopy();
            initShareCopyButton();
            initExternalLinks();
            initFeaturedImageContrast();
            initStickyHeader();
            initDarkMode();
            initTableOfContents();
            initSearchOverlay();
        } catch (error) {
            console.warn('ThemisDB enhancements initialization error:', error);
            // Continue execution even if some features fail
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ------------------------------------------------------------------ */
    /* Sticky Header – shrink on scroll                                     */
    /* ------------------------------------------------------------------ */
    function initStickyHeader() {
        var header = document.querySelector('.site-header');
        if (!header) return;

        function update() {
            header.classList.toggle('is-scrolled', window.scrollY > 80);
        }

        window.addEventListener('scroll', update, { passive: true });
        update(); // apply correct state on first render
    }

    /* ------------------------------------------------------------------ */
    /* Dark Mode – toggle + localStorage                                    */
    /* ------------------------------------------------------------------ */
    function initDarkMode() {
        var toggle = document.querySelector('.dark-mode-toggle');
        if (!toggle) return;

        var html  = document.documentElement;
        var KEY   = 'themisdb-color-scheme';
        var stored = localStorage.getItem(KEY);

        // Apply stored preference (system preference already handled by CSS media query;
        // the class is needed for the manual override only).
        if (stored === 'dark') {
            html.classList.add('dark-mode');
        } else if (stored === 'light') {
            html.classList.remove('dark-mode');
        }
        // If nothing stored we leave it to prefers-color-scheme via CSS

        function syncToggle() {
            var isDark = html.classList.contains('dark-mode') ||
                         (!html.classList.contains('dark-mode') &&
                          !localStorage.getItem(KEY) &&
                          window.matchMedia('(prefers-color-scheme: dark)').matches);
            toggle.textContent = isDark ? '☀️' : '🌙';
            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }

        syncToggle();

        toggle.addEventListener('click', function () {
            html.classList.toggle('dark-mode');
            var nowDark = html.classList.contains('dark-mode');

            // If user explicitly chose dark and system is also dark, store 'dark';
            // if user removes dark mode while system is dark, store 'light' to override.
            localStorage.setItem(KEY, nowDark ? 'dark' : 'light');
            syncToggle();
        });

        // Keep button in sync when system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', syncToggle);
    }

    /* ------------------------------------------------------------------ */
    /* Table of Contents – auto-generated from h2/h3 in .entry-content     */
    /* ------------------------------------------------------------------ */
    function initTableOfContents() {
        var content = document.querySelector('.entry-content');
        if (!content) return;

        var headings = content.querySelectorAll('h2, h3');
        if (headings.length < 3) return;

        var tocList = document.createElement('ol');
        tocList.className = 'toc-list';

        var counter = 0;
        headings.forEach(function (h) {
            if (!h.id) {
                counter++;
                h.id = 'toc-' + counter;
            }
            var li  = document.createElement('li');
            var tag = h.tagName.toLowerCase();
            if (tag === 'h3') li.className = 'toc-h3';

            var a     = document.createElement('a');
            a.href    = '#' + h.id;
            a.textContent = h.textContent.replace(/^[§▸]\s*/, ''); // strip leading markers
            li.appendChild(a);
            tocList.appendChild(li);
        });

        var tocTitle = document.createElement('div');
        tocTitle.className = 'toc-title';
        tocTitle.setAttribute('role', 'button');
        tocTitle.setAttribute('tabindex', '0');
        tocTitle.setAttribute('aria-expanded', 'true');
        tocTitle.innerHTML = '📋 Contents';

        var toc = document.createElement('nav');
        toc.className = 'toc-container';
        toc.setAttribute('aria-label', 'Table of contents');
        toc.appendChild(tocTitle);
        toc.appendChild(tocList);

        function toggleToc() {
            var collapsed = tocTitle.classList.toggle('collapsed');
            tocList.style.display = collapsed ? 'none' : '';
            tocTitle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }

        tocTitle.addEventListener('click', toggleToc);
        tocTitle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleToc();
            }
        });

        // Insert the TOC right before the entry-content div
        content.parentNode.insertBefore(toc, content);
    }

    /* ------------------------------------------------------------------ */
    /* Search Overlay – full-screen keyboard-accessible search              */
    /* ------------------------------------------------------------------ */
    function initSearchOverlay() {
        var toggleBtn = document.querySelector('.search-toggle');
        if (!toggleBtn) return;

        // Use the PHP-escaped home URL from the body data attribute.
        // This value is written with WordPress's esc_url() in header.php and is
        // therefore safe to use as a form action via setAttribute().
        // The '/' fallback is a safe default if the attribute is absent.
        var homeUrl = document.body.dataset.homeUrl || '/';

        // Build overlay DOM safely via DOM API (avoid innerHTML for the action URL)
        var overlay = document.createElement('div');
        overlay.className = 'search-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Site search');

        var closeBtn = document.createElement('button');
        closeBtn.className = 'search-overlay-close';
        closeBtn.setAttribute('aria-label', 'Close search');
        closeBtn.textContent = '\u00d7'; // ×

        var inner = document.createElement('div');
        inner.className = 'search-overlay-inner';

        var form = document.createElement('form');
        form.className = 'search-overlay-form';
        form.setAttribute('role', 'search');
        form.setAttribute('method', 'get');
        form.setAttribute('action', homeUrl); // safe: set via setAttribute, value from esc_url()

        var field = document.createElement('input');
        field.type = 'search';
        field.name = 's';
        field.className = 'search-overlay-field';
        field.placeholder = 'Search\u2026';
        field.setAttribute('autocomplete', 'off');
        field.setAttribute('aria-label', 'Search the site');

        var submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.className = 'search-overlay-submit';
        submitBtn.setAttribute('aria-label', 'Submit search');
        submitBtn.textContent = '\uD83D\uDD0D'; // 🔍

        form.appendChild(field);
        form.appendChild(submitBtn);

        var hint = document.createElement('p');
        hint.className = 'search-overlay-hint';

        var kbdEsc   = document.createElement('kbd');
        kbdEsc.textContent = 'Esc';
        var kbdEnter = document.createElement('kbd');
        kbdEnter.textContent = 'Enter';
        var mid      = document.createTextNode('\u00a0\u00b7\u00a0 '); // · 

        hint.appendChild(document.createTextNode('Press '));
        hint.appendChild(kbdEsc);
        hint.appendChild(document.createTextNode(' to close \u00a0'));
        hint.appendChild(mid);
        hint.appendChild(kbdEnter);
        hint.appendChild(document.createTextNode(' to search'));

        inner.appendChild(form);
        inner.appendChild(hint);
        overlay.appendChild(closeBtn);
        overlay.appendChild(inner);
        document.body.appendChild(overlay);

        function open() {
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            toggleBtn.setAttribute('aria-expanded', 'true');
            setTimeout(function () { field.focus(); }, 80);
        }

        function close() {
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.focus();
        }

        toggleBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
        });
    }

})();

/**
 * Copy URL to clipboard function for social share
 */
function themisdbCopyUrl(button) {
    const url = button.getAttribute('data-url');
    
    if (navigator.clipboard && window.isSecureContext) {
        // Modern async clipboard API
        navigator.clipboard.writeText(url).then(() => {
            showCopySuccess(button);
        }).catch(() => {
            fallbackCopyUrl(url, button);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyUrl(url, button);
    }
}

function fallbackCopyUrl(url, button) {
    const textArea = document.createElement('textarea');
    textArea.value = url;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(button);
    } catch (err) {
        console.error('Failed to copy URL:', err);
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '✅ ' + (button.getAttribute('data-copied-text') || 'Copied!');
    button.classList.add('copied');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('copied');
    }, 2000);
}

/* ===== Tabbed Posts Widget tabs ===== */
(function () {
    function initTabbedPostsWidgets() {
        document.querySelectorAll('.themisdb-tabbed-posts').forEach(function (widget) {
            var tabs   = widget.querySelectorAll('.tpw-tab');
            var panels = widget.querySelectorAll('.tpw-panel');

            function activate(index) {
                tabs.forEach(function (t, i) {
                    var active = (i === index);
                    t.classList.toggle('is-active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                    t.setAttribute('tabindex',       active ? '0'    : '-1');
                });
                panels.forEach(function (p, i) {
                    p.classList.toggle('is-active', i === index);
                });
            }

            tabs.forEach(function (tab, i) {
                tab.setAttribute('tabindex', i === 0 ? '0' : '-1');

                tab.addEventListener('click', function () {
                    activate(i);
                });

                // Arrow key navigation per WAI-ARIA tablist pattern
                tab.addEventListener('keydown', function (e) {
                    var idx = i;
                    if (e.key === 'ArrowRight') { idx = (i + 1) % tabs.length; }
                    else if (e.key === 'ArrowLeft')  { idx = (i - 1 + tabs.length) % tabs.length; }
                    else if (e.key === 'Home')        { idx = 0; }
                    else if (e.key === 'End')         { idx = tabs.length - 1; }
                    else { return; }
                    e.preventDefault();
                    activate(idx);
                    tabs[idx].focus();
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabbedPostsWidgets);
    } else {
        initTabbedPostsWidgets();
    }
})();
