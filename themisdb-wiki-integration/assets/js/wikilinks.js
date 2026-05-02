/**
 * WikiLinks Frontend Handler
 * Handles wiki link interactions on the frontend
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Highlight broken wiki links
        highlightBrokenLinks();
        
        // Add tooltips to wiki links
        addWikiLinkTooltips();
        
        // Handle wiki link hover previews
        setupLinkPreviews();
        
        // Smooth scroll to anchors
        setupSmoothScrolling();
        
        // TOC active link highlighting
        highlightActiveTocLink();
    });
    
    /**
     * Highlight Broken Wiki Links
     */
    function highlightBrokenLinks() {
        $('a.wikilink-new').each(function() {
            var $link = $(this);
            var pageName = $link.data('wiki-page');
            
            // Add create link action
            $link.on('click', function(e) {
                if (confirm('The page "' + pageName + '" does not exist. Do you want to create it?')) {
                    // Redirect to create new wiki page with pre-filled title
                    var createUrl = (typeof themisdbWiki !== 'undefined' && themisdbWiki.adminUrl) ? 
                        themisdbWiki.adminUrl + 'post-new.php?post_type=themisdb_wiki&post_title=' + encodeURIComponent(pageName) :
                        '/wp-admin/post-new.php?post_type=themisdb_wiki&post_title=' + encodeURIComponent(pageName);
                    window.location.href = createUrl;
                }
                e.preventDefault();
            });
        });
    }
    
    /**
     * Add Tooltips to Wiki Links
     */
    function addWikiLinkTooltips() {
        $('a.wikilink').each(function() {
            var $link = $(this);
            var pageName = $link.data('wiki-page');
            
            if (!$link.attr('title')) {
                $link.attr('title', 'Go to: ' + pageName);
            }
        });
        
        $('a.wikilink-new').each(function() {
            var $link = $(this);
            var pageName = $link.data('wiki-page');
            
            if (!$link.attr('title')) {
                $link.attr('title', 'Page does not exist. Click to create: ' + pageName);
            }
        });
    }
    
    /**
     * Setup Link Previews (hover cards)
     */
    function setupLinkPreviews() {
        var previewTimeout;
        var $preview = null;
        var previewCache = {};

        function renderPreview(pageName, payload) {
            var title = String((payload && payload.title) || pageName || '');
            var excerpt = String((payload && payload.excerpt) || 'No preview available.');

            return '<div class="preview-content"><strong>' + title + '</strong><p>' + excerpt + '</p></div>';
        }

        function fetchPreview(pageName, done) {
            var nonce = (typeof themisdbWiki !== 'undefined') ? themisdbWiki.search_nonce : '';
            var ajaxUrl = (typeof themisdbWiki !== 'undefined') ? themisdbWiki.ajaxurl : '';

            if (!ajaxUrl) {
                done({ title: pageName, excerpt: 'Preview unavailable.' });
                return;
            }

            $.post(ajaxUrl, {
                action: 'themisdb_wiki_search',
                query: pageName,
                nonce: nonce
            }).done(function(response) {
                var payload = { title: pageName, excerpt: 'No preview available.' };

                if (response && response.success && response.data && response.data.results && response.data.results.length) {
                    var results = response.data.results;
                    var normalized = pageName.toLowerCase();
                    var best = results[0];

                    $.each(results, function(_, item) {
                        if (String(item.title || '').toLowerCase() === normalized) {
                            best = item;
                            return false;
                        }
                        return true;
                    });

                    payload = {
                        title: best.title || pageName,
                        excerpt: best.excerpt || 'No preview excerpt found.'
                    };
                }

                done(payload);
            }).fail(function() {
                done({ title: pageName, excerpt: 'Preview unavailable.' });
            });
        }
        
        $('a.wikilink').on('mouseenter', function() {
            var $link = $(this);
            var pageName = String($link.data('wiki-page') || $link.text() || '').trim();
            
            clearTimeout(previewTimeout);

            if (!pageName) {
                return;
            }
            
            previewTimeout = setTimeout(function() {
                // Create preview element if it doesn't exist
                if (!$preview) {
                    $preview = $('<div class="wiki-link-preview"></div>').appendTo('body');
                }
                
                // Position preview near link
                var offset = $link.offset();
                $preview.css({
                    top: offset.top + $link.outerHeight() + 5,
                    left: offset.left
                });
                
                // Load preview content
                $preview.html('<div class="loading">Loading...</div>').show();

                if (previewCache[pageName]) {
                    $preview.html(renderPreview(pageName, previewCache[pageName]));
                    return;
                }

                fetchPreview(pageName, function(payload) {
                    previewCache[pageName] = payload;
                    if ($preview) {
                        $preview.html(renderPreview(pageName, payload));
                    }
                });
                
            }, 500); // 500ms delay before showing preview
        });
        
        $('a.wikilink').on('mouseleave', function() {
            clearTimeout(previewTimeout);
            
            if ($preview) {
                setTimeout(function() {
                    if ($preview && !$preview.is(':hover')) {
                        $preview.hide();
                    }
                }, 100);
            }
        });
        
        // Hide preview when mouse leaves the preview itself
        $(document).on('mouseleave', '.wiki-link-preview', function() {
            $(this).hide();
        });
    }
    
    /**
     * Setup Smooth Scrolling for TOC Links
     */
    function setupSmoothScrolling() {
        $('.wiki-toc a, a[href^="#"]').on('click', function(e) {
            var href = $(this).attr('href');
            
            // Only handle anchor links
            if (href.indexOf('#') === 0) {
                e.preventDefault();
                
                var target = $(href);
                
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 500);
                    
                    // Update URL without jumping
                    if (history.pushState) {
                        history.pushState(null, null, href);
                    }
                }
            }
        });
        
        // Scroll to anchor on page load
        if (window.location.hash) {
            setTimeout(function() {
                var target = $(window.location.hash);
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 500);
                }
            }, 100);
        }
    }
    
    /**
     * Highlight Active TOC Link
     */
    function highlightActiveTocLink() {
        if ($('.wiki-toc').length === 0) {
            return;
        }
        
        var $tocLinks = $('.wiki-toc a');
        var $sections = $('h2[id], h3[id], h4[id]');
        
        if ($sections.length === 0) {
            return;
        }
        
        $(window).on('scroll', function() {
            var scrollPosition = $(window).scrollTop() + 100;
            
            var currentSection = null;
            
            $sections.each(function() {
                var $section = $(this);
                var sectionTop = $section.offset().top;
                
                if (scrollPosition >= sectionTop) {
                    currentSection = $section.attr('id');
                }
            });
            
            if (currentSection) {
                $tocLinks.removeClass('active');
                $tocLinks.filter('[href="#' + currentSection + '"]').addClass('active');
            }
        });
        
        // Trigger once on load
        $(window).trigger('scroll');
    }
    
    /**
     * Copy Link to Clipboard
     */
    function setupCopyLink() {
        $('h2[id], h3[id], h4[id]').each(function() {
            var $heading = $(this);
            var id = $heading.attr('id');
            
            var $copyButton = $('<button class="copy-link-button" title="Copy link to this section">#</button>');
            
            $copyButton.on('click', function(e) {
                e.preventDefault();
                
                var url = window.location.origin + window.location.pathname + '#' + id;
                
                // Copy to clipboard
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function() {
                        $copyButton.text('✓').addClass('copied');
                        setTimeout(function() {
                            $copyButton.text('#').removeClass('copied');
                        }, 2000);
                    });
                }
            });
            
            $heading.append($copyButton);
        });
    }
    
})(jQuery);
