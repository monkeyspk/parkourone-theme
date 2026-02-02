/**
 * ParkourONE Header - Scroll Enhancement
 */
(function() {
    'use strict';

    const header = document.querySelector('.po-header');
    if (!header) return;

    let lastScroll = 0;
    const scrollThreshold = 50;

    function handleScroll() {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

        // Add shadow when scrolled
        if (currentScroll > scrollThreshold) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }

        lastScroll = currentScroll;
    }

    // Throttle scroll events
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Initial check
    handleScroll();
})();
