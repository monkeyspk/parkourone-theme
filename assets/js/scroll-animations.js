/**
 * ParkourONE Scroll Animations
 * Uses Intersection Observer for performant scroll-triggered animations
 */
(function() {
	'use strict';

	// Elements that should animate on scroll
	var animatedSelectors = [
		// Generic data-attribute animations
		'[data-animate]',
		'[data-animate-stagger]',
		// Block-specific animations
		'.po-hero',
		'.po-usp',
		'.po-cg',                    // Zielgruppen Grid
		'.po-stats',
		'.po-quote-highlight',
		'.po-steps-timeline',
		'.po-klassen-slider',
		'.po-about',
		'.po-angebote-karussell',
		'.po-faqone',
		'.po-stundenplan',
		'.po-stundenplan-detail',
		'.po-testimonials'
	];

	// Configuration
	var config = {
		threshold: 0.15,      // Trigger when 15% visible
		rootMargin: '0px 0px -50px 0px'  // Trigger slightly before fully in view
	};

	/**
	 * Initialize animations when DOM is ready
	 */
	function init() {
		// Check for reduced motion preference
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			// Show all elements immediately
			document.querySelectorAll(animatedSelectors.join(', ')).forEach(function(el) {
				el.classList.add('is-visible');
			});
			return;
		}

		// Create observer
		var observer = new IntersectionObserver(handleIntersection, config);

		// Observe all animated elements
		document.querySelectorAll(animatedSelectors.join(', ')).forEach(function(el) {
			observer.observe(el);
		});

		// Handle elements that are already in view on page load
		setTimeout(function() {
			document.querySelectorAll(animatedSelectors.join(', ')).forEach(function(el) {
				var rect = el.getBoundingClientRect();
				var windowHeight = window.innerHeight || document.documentElement.clientHeight;
				if (rect.top < windowHeight * 0.85 && rect.bottom > 0) {
					el.classList.add('is-visible');
				}
			});
		}, 100);
	}

	/**
	 * Handle intersection changes
	 */
	function handleIntersection(entries, observer) {
		entries.forEach(function(entry) {
			if (entry.isIntersecting) {
				// Add visible class to trigger animation
				entry.target.classList.add('is-visible');
				
				// Stop observing after animation triggered (one-time animation)
				observer.unobserve(entry.target);
			}
		});
	}

	/**
	 * Refresh observer for dynamically added content
	 */
	function refresh() {
		init();
	}

	// Expose refresh function globally
	window.poAnimations = {
		refresh: refresh
	};

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// Re-check on load (for images that might affect layout)
	window.addEventListener('load', function() {
		setTimeout(function() {
			document.querySelectorAll(animatedSelectors.join(', ')).forEach(function(el) {
				var rect = el.getBoundingClientRect();
				var windowHeight = window.innerHeight || document.documentElement.clientHeight;
				if (rect.top < windowHeight && rect.bottom > 0 && !el.classList.contains('is-visible')) {
					el.classList.add('is-visible');
				}
			});
		}, 200);
	});
})();
