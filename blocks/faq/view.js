(function() {
	'use strict';

	console.log('[FAQ] Script v2 loaded');

	function initFAQ() {
		var questions = document.querySelectorAll('.po-faqone__question');
		console.log('[FAQ] Found questions:', questions.length);

		// Direkte Event-Listener auf alle FAQ-Fragen
		questions.forEach(function(question, index) {
			// Verhindere doppelte Listener
			if (question.hasAttribute('data-faq-init')) return;
			question.setAttribute('data-faq-init', 'true');

			question.addEventListener('click', function(e) {
				console.log('[FAQ] Click on question', index);
				e.preventDefault();
				e.stopPropagation();
				var item = this.closest('.po-faqone__item');
				console.log('[FAQ] Item found:', item);
				if (!item) return;

				var expanded = this.getAttribute('aria-expanded') === 'true';
				console.log('[FAQ] Was expanded:', expanded, '-> now:', !expanded);
				this.setAttribute('aria-expanded', String(!expanded));
				item.classList.toggle('is-open', !expanded);
			});

			console.log('[FAQ] Listener attached to question', index);
		});

		// Animations
		document.querySelectorAll('.po-faqone:not(.has-animation)').forEach(function(section) {
			section.classList.add('has-animation');

			var observer = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

			section.querySelectorAll('.po-faqone__item').forEach(function(item, index) {
				item.style.transitionDelay = (index * 0.05) + 's';
				observer.observe(item);
			});
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initFAQ);
	} else {
		initFAQ();
	}

	// Also run after any AJAX loads
	document.addEventListener('ajaxComplete', initFAQ);
})();
