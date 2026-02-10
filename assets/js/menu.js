/**
 * ParkourONE Fullscreen Menu
 */
(function() {
    'use strict';

    const toggle = document.getElementById('po-menu-toggle');
    const overlay = document.getElementById('po-menu-overlay');
    const header = document.getElementById('po-header');

    if (!toggle || !overlay) return;

    let isOpen = false;

    function isMobile() {
        return window.innerWidth <= 768;
    }

    function openMenu() {
        isOpen = true;
        overlay.classList.add('is-open');
        toggle.classList.add('is-active');
        toggle.setAttribute('aria-expanded', 'true');
        overlay.setAttribute('aria-hidden', 'false');
        // Only lock scroll on mobile (fullscreen overlay)
        if (isMobile()) {
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMenu() {
        isOpen = false;
        overlay.classList.remove('is-open');
        toggle.classList.remove('is-active');
        toggle.setAttribute('aria-expanded', 'false');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function toggleMenu() {
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    // Toggle Button Click
    toggle.addEventListener('click', toggleMenu);

    // Close on Escape + Focus Trap (WCAG 2.1)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
            toggle.focus();
        }

        // Focus trap when menu is open
        if (e.key === 'Tab' && isOpen) {
            var focusable = overlay.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
            var visible = Array.prototype.filter.call(focusable, function(el) {
                return el.offsetParent !== null;
            });
            if (visible.length === 0) return;
            var first = visible[0];
            var last = visible[visible.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

    // Close on Link Click
    overlay.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            closeMenu();
        });
    });

    // Close on Click Outside Menu Content
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeMenu();
        }
    });
})();
