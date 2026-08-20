(function (api) {
    'use strict';

    if (!api) {
        return;
    }

    var latestSection = document.getElementById('homepage-latest-articles');
    var latestCta = document.getElementById('homepage-latest-cta');
    var heroKicker = document.getElementById('homepage-hero-kicker');
    var heroSubtitle = document.getElementById('homepage-hero-subtitle');
    var blogCta = document.getElementById('homepage-blog-cta');
    var statsSection = document.getElementById('homepage-stats-section');
    var introSection = document.getElementById('homepage-intro-section');
    var introEyebrow = document.getElementById('homepage-intro-eyebrow');
    var introTitle = document.getElementById('homepage-intro-title');
    var latestEyebrow = document.getElementById('homepage-latest-eyebrow');
    var latestTitle = document.getElementById('homepage-latest-title');
    var latestLink = document.getElementById('homepage-latest-link');
    var latestReadmores = Array.prototype.slice.call(document.querySelectorAll('.homepage-readmore, .slider-readmore'));

    function clampCount(value) {
        var parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed)) {
            return 4;
        }

        if (parsed < 3) {
            return 3;
        }

        if (parsed > 8) {
            return 8;
        }

        return parsed;
    }

    function applyShowLatest(showLatest) {
        if (latestSection) {
            latestSection.classList.toggle('homepage-hidden', !showLatest);
        }

        if (latestCta) {
            latestCta.classList.toggle('homepage-hidden', !showLatest);
        }
    }

    function applyLatestCount(count) {
        void clampCount(count);
    }

    function clampLeadWords(value) {
        var parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed)) {
            return 34;
        }

        if (parsed < 20) {
            return 20;
        }

        if (parsed > 60) {
            return 60;
        }

        return parsed;
    }

    function clampCompactWords(value) {
        var parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed)) {
            return 14;
        }

        if (parsed < 8) {
            return 8;
        }

        if (parsed > 30) {
            return 30;
        }

        return parsed;
    }

    function trimWords(text, words) {
        var source = (text || '').toString().trim();
        if (!source) {
            return '';
        }

        var parts = source.split(/\s+/);
        if (parts.length <= words) {
            return source;
        }

        return parts.slice(0, words).join(' ') + '...';
    }

    function applyLeadExcerptWords(words) {
        void clampLeadWords(words);
    }

    function applyCompactExcerptWords(words) {
        void clampCompactWords(words);
    }

    function setElementText(el, value) {
        if (!el) {
            return;
        }

        var nextValue = (value || '').toString().trim();
        if (!nextValue) {
            return;
        }

        el.textContent = nextValue;
    }

    function setElementTextWithDefault(el, value) {
        if (!el) {
            return;
        }

        var nextValue = (value || '').toString().trim();
        var fallbackValue = (el.getAttribute('data-default') || '').toString().trim();

        el.textContent = nextValue || fallbackValue;
    }

    function applyBlogCtaUrl(rawUrl) {
        if (!blogCta) {
            return;
        }

        var nextUrl = (rawUrl || '').toString().trim();
        var defaultUrl = (blogCta.getAttribute('data-default-href') || '').trim();
        var finalUrl = nextUrl || defaultUrl;

        blogCta.classList.toggle('homepage-hidden', !finalUrl);
        if (finalUrl) {
            blogCta.setAttribute('href', finalUrl);
        }
    }

    api('themisdb_home_show_latest_articles', function (value) {
        applyShowLatest(!!value.get());

        value.bind(function (newValue) {
            applyShowLatest(!!newValue);
        });
    });

    api('themisdb_home_latest_articles_count', function (value) {
        applyLatestCount(value.get());

        value.bind(function (newValue) {
            applyLatestCount(newValue);
        });
    });

    api('themisdb_home_hero_kicker', function (value) {
        setElementText(heroKicker, value.get());

        value.bind(function (newValue) {
            setElementText(heroKicker, newValue);
        });
    });

    api('themisdb_home_hero_subtitle', function (value) {
        value.bind(function (newValue) {
            if (!heroSubtitle) {
                return;
            }

            var text = (newValue || '').toString().trim();
            heroSubtitle.textContent = text;
            heroSubtitle.classList.toggle('homepage-hidden', !text);
        });
    });

    api('themisdb_home_latest_cta_label', function (value) {
        setElementText(latestCta, value.get());

        value.bind(function (newValue) {
            setElementText(latestCta, newValue);
        });
    });

    api('themisdb_home_blog_cta_label', function (value) {
        setElementText(blogCta, value.get());

        value.bind(function (newValue) {
            setElementText(blogCta, newValue);
        });
    });

    api('themisdb_home_blog_cta_url', function (value) {
        applyBlogCtaUrl(value.get());

        value.bind(function (newValue) {
            applyBlogCtaUrl(newValue);
        });
    });

    api('themisdb_home_show_stats', function (value) {
        if (statsSection) {
            statsSection.classList.toggle('homepage-hidden', !value.get());
        }

        value.bind(function (newValue) {
            if (statsSection) {
                statsSection.classList.toggle('homepage-hidden', !newValue);
            }
        });
    });

    api('themisdb_home_show_intro_section', function (value) {
        if (introSection) {
            introSection.classList.toggle('homepage-hidden', !value.get());
        }

        value.bind(function (newValue) {
            if (introSection) {
                introSection.classList.toggle('homepage-hidden', !newValue);
            }
        });
    });

    api('themisdb_home_intro_eyebrow', function (value) {
        setElementTextWithDefault(introEyebrow, value.get());

        value.bind(function (newValue) {
            setElementTextWithDefault(introEyebrow, newValue);
        });
    });

    api('themisdb_home_intro_title', function (value) {
        setElementTextWithDefault(introTitle, value.get());

        value.bind(function (newValue) {
            setElementTextWithDefault(introTitle, newValue);
        });
    });

    api('themisdb_home_latest_eyebrow', function (value) {
        setElementTextWithDefault(latestEyebrow, value.get());

        value.bind(function (newValue) {
            setElementTextWithDefault(latestEyebrow, newValue);
        });
    });

    api('themisdb_home_latest_title', function (value) {
        setElementTextWithDefault(latestTitle, value.get());

        value.bind(function (newValue) {
            setElementTextWithDefault(latestTitle, newValue);
        });
    });

    api('themisdb_home_latest_link_label', function (value) {
        setElementTextWithDefault(latestLink, value.get());

        value.bind(function (newValue) {
            setElementTextWithDefault(latestLink, newValue);
        });
    });

    api('themisdb_home_latest_lead_cta_label', function (value) {
        latestReadmores.forEach(function (el) {
            setElementTextWithDefault(el, value.get());
        });

        value.bind(function (newValue) {
            latestReadmores.forEach(function (el) {
                setElementTextWithDefault(el, newValue);
            });
        });
    });

    api('themisdb_home_latest_lead_excerpt_words', function (value) {
        applyLeadExcerptWords(value.get());

        value.bind(function (newValue) {
            applyLeadExcerptWords(newValue);
        });
    });

    api('themisdb_home_latest_compact_excerpt_words', function (value) {
        applyCompactExcerptWords(value.get());

        value.bind(function (newValue) {
            applyCompactExcerptWords(newValue);
        });
    });

    [
        ['themisdb_home_stat_posts_icon', 'homepage-stat-posts-icon'],
        ['themisdb_home_stat_pages_icon', 'homepage-stat-pages-icon'],
        ['themisdb_home_stat_categories_icon', 'homepage-stat-categories-icon'],
        ['themisdb_home_stat_tags_icon', 'homepage-stat-tags-icon'],
        ['themisdb_home_stat_posts_label', 'homepage-stat-posts-label'],
        ['themisdb_home_stat_pages_label', 'homepage-stat-pages-label'],
        ['themisdb_home_stat_categories_label', 'homepage-stat-categories-label'],
        ['themisdb_home_stat_tags_label', 'homepage-stat-tags-label']
    ].forEach(function (pair) {
        var settingKey = pair[0];
        var element = document.getElementById(pair[1]);

        api(settingKey, function (value) {
            setElementTextWithDefault(element, value.get());

            value.bind(function (newValue) {
                setElementTextWithDefault(element, newValue);
            });
        });
    });

    // ── Header live preview ───────────────────────────────────────────────────

    var siteHeader = document.getElementById('masthead');
    var navLinks   = document.querySelectorAll('.main-navigation a');
    var siteNameLink = document.querySelector('.site-name-link');
    var siteTagline  = document.querySelector('.site-tagline');
    var logoInitial  = document.querySelector('.site-logo-initial');
    var customLogo   = document.querySelector('.site-branding-group .custom-logo');
    var headerIconBtns = document.querySelectorAll('.header-icon-btn');
    var searchToggleBtn   = document.querySelector('.search-toggle');
    var darkModeToggleBtn = document.querySelector('.dark-mode-toggle');

    function applyHeaderStyle(style) {
        if (!siteHeader) { return; }
        siteHeader.classList.toggle('header-style-dark', style === 'dark');
        siteHeader.classList.toggle('header-style-glass', style !== 'dark');
    }

    function applyNavAccent(color) {
        var safeColor = (color || '').trim();
        if (!safeColor) { return; }
        document.querySelectorAll('.main-navigation a:hover, .main-navigation .current-menu-item > a').forEach(function (el) {
            el.style.color = safeColor;
        });
        // Inject a style tag for pseudo-selectors (can't set via .style)
        var styleId = 'themisdb-preview-nav-accent';
        var existing = document.getElementById(styleId);
        if (!existing) {
            existing = document.createElement('style');
            existing.id = styleId;
            document.head.appendChild(existing);
        }
        existing.textContent =
            '.main-navigation a:hover,' +
            '.main-navigation .current-menu-item > a,' +
            '.main-navigation .current_page_item > a { color: ' + safeColor + ' !important; }';
    }

    function applyLogoWidth(width) {
        var w = parseInt(width, 10);
        if (!Number.isFinite(w) || w < 40) { w = 40; }
        if (w > 300) { w = 300; }
        if (customLogo) {
            customLogo.style.maxWidth = w + 'px';
        }
    }

    function applyHeaderSticky(sticky) {
        if (!siteHeader) { return; }
        siteHeader.style.position = sticky ? 'sticky' : 'relative';
    }

    function applyHeaderShowSearch(show) {
        if (!searchToggleBtn) { return; }
        searchToggleBtn.style.display = show ? '' : 'none';
    }

    function applyHeaderShowDarkmode(show) {
        if (!darkModeToggleBtn) { return; }
        darkModeToggleBtn.style.display = show ? '' : 'none';
    }

    api('themisdb_header_style', function (value) {
        applyHeaderStyle(value.get());
        value.bind(applyHeaderStyle);
    });

    api('themisdb_header_accent_color', function (value) {
        applyNavAccent(value.get());
        value.bind(applyNavAccent);
    });

    api('themisdb_logo_width', function (value) {
        applyLogoWidth(value.get());
        value.bind(applyLogoWidth);
    });

    api('themisdb_header_sticky', function (value) {
        applyHeaderSticky(value.get());
        value.bind(applyHeaderSticky);
    });

    api('themisdb_header_show_search', function (value) {
        applyHeaderShowSearch(!!value.get());
        value.bind(function (v) { applyHeaderShowSearch(!!v); });
    });

    api('themisdb_header_show_darkmode', function (value) {
        applyHeaderShowDarkmode(!!value.get());
        value.bind(function (v) { applyHeaderShowDarkmode(!!v); });
    });

})(window.wp && window.wp.customize);
