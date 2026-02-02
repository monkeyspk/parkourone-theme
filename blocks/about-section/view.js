(function() {
	'use strict';

	function initAboutAnimations() {
		const sections = document.querySelectorAll('.po-about:not(.has-animation)');

		sections.forEach(function(section) {
			section.classList.add('has-animation');

			const observer = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			}, {
				threshold: 0.15,
				rootMargin: '0px 0px -50px 0px'
			});

			observer.observe(section);
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAboutAnimations);
	} else {
		initAboutAnimations();
	}
})();
