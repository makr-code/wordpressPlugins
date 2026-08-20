/**
 * Navigation scripts for ThemisDB theme
 */
(function() {
    'use strict';

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.main-navigation');

    if (menuToggle && navigation) {
        menuToggle.addEventListener('click', function() {
            const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', !expanded);
            navigation.classList.toggle('toggled');
        });
    }

    // Add dropdown toggle for mobile while keeping parent links clickable
    const menuItemsWithChildren = document.querySelectorAll('.main-navigation .menu-item-has-children');
    const topLevelLinks = navigation ? navigation.querySelectorAll(':scope > ul > li > a, :scope > .primary-menu > li > a') : [];

    function canUseHoverDropdowns() {
        return window.innerWidth > 768
            && window.matchMedia('(hover: hover)').matches
            && window.matchMedia('(pointer: fine)').matches;
    }

    function setSubmenuExpandedState(item, expanded) {
        const link = item.querySelector(':scope > a');
        const toggleButton = item.querySelector(':scope > .submenu-toggle');

        item.classList.toggle('submenu-open', expanded);

        if (link) {
            link.setAttribute('aria-expanded', String(expanded));
        }

        if (toggleButton) {
            toggleButton.setAttribute('aria-expanded', String(expanded));
        }
    }

    function closeAllSubmenus() {
        menuItemsWithChildren.forEach(function(item) {
            item.classList.remove('toggled');
            setSubmenuExpandedState(item, false);
        });
    }

    function focusTopLevelLink(currentLink, direction) {
        const links = Array.from(topLevelLinks);
        const currentIndex = links.indexOf(currentLink);
        if (currentIndex === -1 || links.length < 2) {
            return;
        }

        const nextIndex = (currentIndex + direction + links.length) % links.length;
        links[nextIndex].focus();
    }
    
    // Desktop dropdown delay to prevent accidental closing
    let dropdownTimeout;
    menuItemsWithChildren.forEach(function(item) {
        const submenu = item.querySelector('ul');
        if (!submenu) {
            return;
        }

        item.addEventListener('mouseenter', function() {
            if (!canUseHoverDropdowns()) {
                return;
            }

            clearTimeout(dropdownTimeout);
            setSubmenuExpandedState(item, true);
        });

        item.addEventListener('mouseleave', function() {
            if (!canUseHoverDropdowns()) {
                return;
            }

            setSubmenuExpandedState(item, false);
        });

        item.addEventListener('focusin', function() {
            if (window.innerWidth <= 768) {
                return;
            }

            setSubmenuExpandedState(item, true);
        });

        item.addEventListener('focusout', function() {
            if (window.innerWidth <= 768) {
                return;
            }

            window.setTimeout(function() {
                if (!item.contains(document.activeElement)) {
                    setSubmenuExpandedState(item, false);
                }
            }, 0);
        });
    });
    
    menuItemsWithChildren.forEach(function(item, index) {
        const link = item.querySelector(':scope > a');
        const submenu = item.querySelector(':scope > ul');
        if (!link || !submenu) {
            return;
        }

        const submenuId = submenu.id || 'submenu-' + index;
        submenu.id = submenuId;
        link.setAttribute('aria-haspopup', 'true');
        link.setAttribute('aria-expanded', 'false');

        let toggleButton = item.querySelector(':scope > .submenu-toggle');
        if (!toggleButton) {
            toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = 'submenu-toggle';
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleButton.setAttribute('aria-controls', submenuId);
            toggleButton.setAttribute('aria-label', 'Untermenue umschalten');
            toggleButton.innerHTML = '<span aria-hidden="true">▾</span>';
            item.insertBefore(toggleButton, submenu);
        }

        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
            item.classList.toggle('toggled', !isExpanded);
            setSubmenuExpandedState(item, !isExpanded);
        });

        link.addEventListener('keydown', function(e) {
            if (window.innerWidth <= 768) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setSubmenuExpandedState(item, true);
                const firstChildLink = submenu.querySelector('a');
                if (firstChildLink) {
                    firstChildLink.focus();
                }
                return;
            }

            if (e.key === 'ArrowRight') {
                e.preventDefault();
                setSubmenuExpandedState(item, false);
                focusTopLevelLink(link, 1);
                return;
            }

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                setSubmenuExpandedState(item, false);
                focusTopLevelLink(link, -1);
                return;
            }

            if (e.key === 'Escape') {
                e.preventDefault();
                setSubmenuExpandedState(item, false);
            }
        });

        submenu.addEventListener('keydown', function(e) {
            if (window.innerWidth <= 768) {
                return;
            }

            const submenuLinks = Array.from(submenu.querySelectorAll('a'));
            const activeIndex = submenuLinks.indexOf(document.activeElement);

            if (e.key === 'ArrowDown' && activeIndex !== -1) {
                e.preventDefault();
                submenuLinks[(activeIndex + 1) % submenuLinks.length].focus();
                return;
            }

            if (e.key === 'ArrowUp' && activeIndex !== -1) {
                e.preventDefault();
                submenuLinks[(activeIndex - 1 + submenuLinks.length) % submenuLinks.length].focus();
                return;
            }

            if (e.key === 'ArrowRight') {
                e.preventDefault();
                setSubmenuExpandedState(item, false);
                focusTopLevelLink(link, 1);
                return;
            }

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                setSubmenuExpandedState(item, false);
                focusTopLevelLink(link, -1);
                return;
            }

            if (e.key === 'Escape') {
                e.preventDefault();
                setSubmenuExpandedState(item, false);
                link.focus();
            }
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.site-header') && navigation && navigation.classList.contains('toggled')) {
            navigation.classList.remove('toggled');
            if (menuToggle) {
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        }

        if (!event.target.closest('.site-header')) {
            closeAllSubmenus();
        }
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768 && navigation && navigation.classList.contains('toggled')) {
                navigation.classList.remove('toggled');
                if (menuToggle) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                }
            }

            if (window.innerWidth > 768 || canUseHoverDropdowns()) {
                closeAllSubmenus();
            }
        }, 250);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth > 768) {
            closeAllSubmenus();
        }
    });

    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId !== '#' && targetId !== '#0') {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Hamburger menu toggle
    const hamburgerButton = document.querySelector('.hamburger-menu-button');
    const hamburgerDropdown = document.querySelector('.hamburger-dropdown');

    if (hamburgerButton && hamburgerDropdown) {
        hamburgerButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const expanded = hamburgerButton.getAttribute('aria-expanded') === 'true';
            hamburgerButton.setAttribute('aria-expanded', !expanded);
        });

        // Close hamburger menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.hamburger-menu-item')) {
                hamburgerButton.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && hamburgerButton.getAttribute('aria-expanded') === 'true') {
                hamburgerButton.setAttribute('aria-expanded', 'false');
                hamburgerButton.focus();
            }
        });
    }

})();
