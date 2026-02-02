(function() {
	'use strict';

	// Prevent double initialization
	if (window.poTextRevealInitialized) return;
	window.poTextRevealInitialized = true;

	console.log('[Text-Reveal] Script loaded v2');

	// Inject critical styles to ensure they're always loaded
	function injectStyles() {
		if (document.getElementById('po-text-reveal-styles')) return;

		const style = document.createElement('style');
		style.id = 'po-text-reveal-styles';
		style.textContent = `
			.po-text-reveal__word {
				display: inline;
				color: rgba(29, 29, 31, 0.15) !important;
				transition: color 0.35s ease-out !important;
			}
			.po-text-reveal__word.is-visible {
				color: #1d1d1f !important;
			}
			@media (prefers-color-scheme: dark) {
				.po-text-reveal__word {
					color: rgba(245, 245, 247, 0.15) !important;
				}
				.po-text-reveal__word.is-visible {
					color: #f5f5f7 !important;
				}
			}
		`;
		document.head.appendChild(style);
		console.log('[Text-Reveal] Styles injected');
	}

	function initTextReveal() {
		// Inject styles first
		injectStyles();

		const sections = document.querySelectorAll('.po-text-reveal');
		console.log('[Text-Reveal] Found sections:', sections.length);

		if (sections.length === 0) return;

		sections.forEach(function(section) {
			// Skip if already initialized
			if (section.dataset.trInitialized === 'true') return;
			section.dataset.trInitialized = 'true';

			const words = section.querySelectorAll('.po-text-reveal__word');
			if (words.length === 0) return;

			const totalWords = words.length;
			console.log('[Text-Reveal] Initialized with', totalWords, 'words');

			function updateWords() {
				const rect = section.getBoundingClientRect();
				const windowHeight = window.innerHeight;
				const sectionTop = rect.top;
				const sectionBottom = rect.bottom;

				// Calculate progress based on section position
				const startTrigger = windowHeight * 0.85;
				const endTrigger = windowHeight * 0.15;
				const range = startTrigger - endTrigger;

				let progress = 0;

				if (sectionBottom < 0) {
					// Section scrolled past - all visible
					progress = 1;
				} else if (sectionTop > windowHeight) {
					// Section below viewport - none visible
					progress = 0;
				} else if (sectionTop <= startTrigger) {
					if (sectionTop <= endTrigger) {
						progress = 1;
					} else {
						progress = (startTrigger - sectionTop) / range;
					}
				}

				progress = Math.max(0, Math.min(1, progress));
				const visibleCount = Math.ceil(progress * totalWords);

				words.forEach(function(word, index) {
					if (index < visibleCount) {
						word.classList.add('is-visible');
					} else {
						word.classList.remove('is-visible');
					}
				});
			}

			// Initial update
			updateWords();

			// Scroll handler with throttle
			let ticking = false;
			function onScroll() {
				if (!ticking) {
					requestAnimationFrame(function() {
						updateWords();
						ticking = false;
					});
					ticking = true;
				}
			}

			window.addEventListener('scroll', onScroll, { passive: true });
			window.addEventListener('resize', updateWords, { passive: true });
		});
	}

	// Initialize
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initTextReveal);
	} else {
		initTextReveal();
	}
})();
