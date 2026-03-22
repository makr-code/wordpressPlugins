/**
 * ThemisDB Wiki Integration - JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize Mermaid for diagram rendering
        if (typeof mermaid !== 'undefined') {
            mermaid.initialize({
                startOnLoad: true,
                theme: 'default',
                securityLevel: 'strict',
                fontFamily: 'inherit'
            });
        }

        ThemisDBWikiIntegration.init();
        ThemisDBWikiNav.init();
        ThemisDBWikiSearch.init();
    });
    
    /**
     * Wiki Navigation object
     */
    var ThemisDBWikiNav = {
        
        /**
         * Initialize navigation
         */
        init: function() {
            this.initAccordion();
            this.highlightCurrentPage();
        },
        
        /**
         * Initialize accordion functionality
         */
        initAccordion: function() {
            $('.themisdb-wiki-nav-accordion .themisdb-nav-section-title').on('click', function() {
                var $title = $(this);
                var $items = $title.next('.themisdb-nav-section-items');
                
                // Toggle collapsed state
                $title.toggleClass('collapsed');
                
                // Toggle visibility
                if ($title.hasClass('collapsed')) {
                    $items.slideUp(300);
                } else {
                    $items.slideDown(300);
                }
            });
            
            // Start with first section expanded, others collapsed
            $('.themisdb-wiki-nav-accordion .themisdb-nav-section').each(function(index) {
                if (index > 0) {
                    $(this).find('.themisdb-nav-section-title').addClass('collapsed');
                    $(this).find('.themisdb-nav-section-items').hide();
                }
            });
        },
        
        /**
         * Highlight current page in navigation
         */
        highlightCurrentPage: function() {
            var currentUrl = window.location.href;
            var currentPath = window.location.pathname;
            
            $('.themisdb-nav-item a').each(function() {
                var $link = $(this);
                var href = $link.attr('href');
                
                // Check if this link matches current page
                if (href === currentUrl || href === currentPath) {
                    $link.addClass('current-page');
                    $link.attr('aria-current', 'page');
                    
                    // Expand parent section if accordion
                    var $section = $link.closest('.themisdb-nav-section');
                    if ($section.length) {
                        var $title = $section.find('.themisdb-nav-section-title');
                        $title.removeClass('collapsed');
                        $title.next('.themisdb-nav-section-items').slideDown(300);
                    }
                }
            });
        }
    };
    
    /**
     * Main object
     */
    var ThemisDBWikiIntegration = {
        
        /**
         * Initialize
         */
        init: function() {
            this.initSmoothScroll();
            this.initCodeCopy();
            this.initTOCHighlight();
        },
        
        /**
         * Smooth scroll for TOC links
         */
        initSmoothScroll: function() {
            $('.themisdb-wiki-toc a').on('click', function(e) {
                var target = $(this).attr('href');
                
                if (target.indexOf('#') === 0) {
                    e.preventDefault();
                    
                    var $target = $(target);
                    
                    if ($target.length) {
                        $('html, body').animate({
                            scrollTop: $target.offset().top - 100
                        }, 500);
                    }
                }
            });
        },
        
        /**
         * Add copy button to code blocks
         */
        initCodeCopy: function() {
            $('.themisdb-wiki-content pre code').each(function() {
                var $code = $(this);
                var $pre = $code.parent();
                
                // Create copy button
                var $button = $('<button>')
                    .addClass('themisdb-copy-code')
                    .html('📋 Copy')
                    .css({
                        'position': 'absolute',
                        'top': '0.5rem',
                        'right': '0.5rem',
                        'padding': '0.25rem 0.5rem',
                        'font-size': '0.875rem',
                        'background': '#0969da',
                        'color': '#ffffff',
                        'border': 'none',
                        'border-radius': '3px',
                        'cursor': 'pointer',
                        'opacity': '0',
                        'transition': 'opacity 0.2s ease'
                    });
                
                // Make pre relative for absolute positioning
                $pre.css('position', 'relative');
                
                // Append button
                $pre.append($button);
                
                // Show button on hover
                $pre.hover(
                    function() {
                        $button.css('opacity', '1');
                    },
                    function() {
                        $button.css('opacity', '0');
                    }
                );
                
                // Copy on click
                $button.on('click', function(e) {
                    e.preventDefault();
                    
                    var text = $code.text();
                    
                    // Try modern Clipboard API first
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function() {
                            // Update button text
                            $button.html('✅ Copied!');
                            
                            setTimeout(function() {
                                $button.html('📋 Copy');
                            }, 2000);
                        }).catch(function() {
                            // Fallback to legacy method
                            fallbackCopy(text);
                        });
                    } else {
                        // Fallback for older browsers
                        fallbackCopy(text);
                    }
                    
                    function fallbackCopy(text) {
                        // Create temporary textarea
                        var $temp = $('<textarea>')
                            .val(text)
                            .css({
                                'position': 'absolute',
                                'left': '-9999px'
                            })
                            .appendTo('body');
                        
                        // Select and copy
                        $temp.select();
                        try {
                            document.execCommand('copy');
                            // Update button text
                            $button.html('✅ Copied!');
                        } catch (err) {
                            $button.html('❌ Failed');
                        }
                        $temp.remove();
                        
                        setTimeout(function() {
                            $button.html('📋 Copy');
                        }, 2000);
                    }
                });
            });
        },
        
        /**
         * Highlight current section in TOC
         */
        initTOCHighlight: function() {
            var $toc = $('.themisdb-wiki-toc');
            
            if (!$toc.length) {
                return;
            }
            
            var $links = $toc.find('a');
            var $sections = $('.themisdb-wiki-content h2, .themisdb-wiki-content h3');
            
            if (!$sections.length) {
                return;
            }
            
            // Highlight on scroll
            $(window).on('scroll', function() {
                var scrollPos = $(window).scrollTop() + 150;
                
                $sections.each(function() {
                    var $section = $(this);
                    var sectionTop = $section.offset().top;
                    var sectionBottom = sectionTop + $section.outerHeight();
                    var sectionId = '#' + $section.attr('id');
                    
                    if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                        $links.removeClass('active');
                        $links.filter('[href="' + sectionId + '"]').addClass('active');
                    }
                });
            });
            
            // Add CSS for active state
            $('<style>')
                .html('.themisdb-wiki-toc a.active { font-weight: bold; color: #0550ae; }')
                .appendTo('head');
        }
    };
    
    /**
     * Wiki Live-Search
     *
     * Watches #wiki-search-input and populates #search-suggestions with AJAX results.
     * Requires themisdbWiki.search_nonce to be set via wp_localize_script.
     */
    var ThemisDBWikiSearch = {

        $input: null,
        $suggestions: null,
        debounceTimer: null,
        minLength: 2,

        init: function() {
            this.$input       = $('#wiki-search-input');
            this.$suggestions = $('#search-suggestions');

            if (!this.$input.length || !this.$suggestions.length) {
                return;
            }

            var self = this;

            this.$input.on('input', function() {
                clearTimeout(self.debounceTimer);
                var query = $(this).val().trim();

                if (query.length < self.minLength) {
                    self.hide();
                    return;
                }

                self.debounceTimer = setTimeout(function() {
                    self.fetch(query);
                }, 300);
            });

            // Close suggestions when focus leaves the search area.
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.wiki-search').length) {
                    self.hide();
                }
            });

            // Keyboard navigation inside suggestions.
            this.$input.on('keydown', function(e) {
                var $items = self.$suggestions.find('li');
                var $active = $items.filter('.suggestion-active');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!$active.length) {
                        $items.first().addClass('suggestion-active').focus();
                    } else {
                        $active.removeClass('suggestion-active')
                               .next('li').addClass('suggestion-active').focus();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    $active.removeClass('suggestion-active')
                           .prev('li').addClass('suggestion-active').focus();
                } else if (e.key === 'Escape') {
                    self.hide();
                }
            });
        },

        fetch: function(query) {
            var self = this;
            var nonce = (typeof themisdbWiki !== 'undefined') ? themisdbWiki.search_nonce : '';
            var ajaxUrl = (typeof themisdbWiki !== 'undefined') ? themisdbWiki.ajaxurl : '';

            if (!ajaxUrl) {
                return;
            }

            $.post(ajaxUrl, {
                action: 'themisdb_wiki_search',
                query:  query,
                nonce:  nonce
            })
            .done(function(response) {
                if (response.success && response.data && response.data.results) {
                    self.render(response.data.results);
                } else {
                    self.hide();
                }
            })
            .fail(function() {
                self.hide();
            });
        },

        render: function(results) {
            var self = this;

            if (!results.length) {
                this.hide();
                return;
            }

            var $list = $('<ul>');

            $.each(results, function(i, item) {
                // item.title and item.url come from server — escape before inserting into DOM.
                var $li = $('<li>')
                    .attr('tabindex', '0')
                    .on('click keydown', function(e) {
                        if (e.type === 'click' || e.key === 'Enter' || e.key === ' ') {
                            window.location.href = item.url;
                        }
                    });

                var $link = $('<a>')
                    .attr('href', item.url)
                    .text(item.title);

                $li.append($link);

                if (item.excerpt) {
                    // excerpt may contain <mark> tags from server — use html() only for that node.
                    var $excerpt = $('<span>').addClass('suggestion-excerpt').html(item.excerpt);
                    $li.append($excerpt);
                }

                $list.append($li);
            });

            this.$suggestions.empty().append($list).show();
        },

        hide: function() {
            this.$suggestions.hide().empty();
        }
    };

})(jQuery);
