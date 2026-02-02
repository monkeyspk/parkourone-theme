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

    function openMenu() {
        isOpen = true;
        overlay.classList.add('is-open');
        toggle.classList.add('is-active');
        toggle.setAttribute('aria-expanded', 'true');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
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

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
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
