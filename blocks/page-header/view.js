/**
 * Page Header Block - Frontend JavaScript
 * Stats Counter Animation & Parallax Effects
 */
(function() {
	'use strict';

	// Stats Counter Animation
	function animateCounters() {
		const counters = document.querySelectorAll('.po-ph__stat-number[data-target]');

		if (!counters.length) return;

		const observerOptions = {
			threshold: 0.5,
			rootMargin: '0px'
		};

		const observer = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					const counter = entry.target;
					const target = parseInt(counter.dataset.target, 10);

					if (isNaN(target)) return;

					animateNumber(counter, target);
					observer.unobserve(counter);
				}
			});
		}, observerOptions);

		counters.forEach(function(counter) {
			observer.observe(counter);
		});
	}

	function animateNumber(element, target) {
		const duration = 2000;
		const startTime = performance.now();
		const startValue = 0;

		function easeOutQuart(t) {
			return 1 - Math.pow(1 - t, 4);
		}

		function update(currentTime) {
			const elapsed = currentTime - startTime;
			const progress = Math.min(elapsed / duration, 1);
			const easedProgress = easeOutQuart(progress);
			const currentValue = Math.round(startValue + (target - startValue) * easedProgress);

			element.textContent = formatNumber(currentValue);

			if (progress < 1) {
				requestAnimationFrame(update);
			}
		}

		requestAnimationFrame(update);
	}

	function formatNumber(num) {
		if (num >= 1000) {
			return num.toLocaleString('de-CH');
		}
		return num.toString();
	}

	// Parallax for Fullscreen Background
	function initParallax() {
		const fullscreenHeaders = document.querySelectorAll('.po-ph--fullscreen');

		if (!fullscreenHeaders.length) return;

		let ticking = false;

		function updateParallax() {
			fullscreenHeaders.forEach(function(header) {
				const bgImage = header.querySelector('.po-ph__bg-image');
				if (!bgImage) return;

				const rect = header.getBoundingClientRect();
				const windowHeight = window.innerHeight;

				if (rect.bottom < 0 || rect.top > windowHeight) return;

				const scrollPercent = (windowHeight - rect.top) / (windowHeight + rect.height);
				const translateY = (scrollPercent - 0.5) * 50;

				bgImage.style.transform = 'translateY(' + translateY + 'px) scale(1.1)';
			});

			ticking = false;
		}

		window.addEventListener('scroll', function() {
			if (!ticking) {
				requestAnimationFrame(updateParallax);
				ticking = true;
			}
		}, { passive: true });

		updateParallax();
	}

	// Fade-in Animation on Scroll
	function initScrollAnimations() {
		const animatedElements = document.querySelectorAll(
			'.po-ph__title, .po-ph__description, .po-ph__actions, .po-ph__stats, .po-ph__image-wrapper'
		);

		if (!animatedElements.length) return;

		const observer = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, {
			threshold: 0.1,
			rootMargin: '0px 0px -50px 0px'
		});

		animatedElements.forEach(function(el) {
			observer.observe(el);
		});
	}

	// Initialize when DOM is ready
	function init() {
		animateCounters();
		initParallax();
		initScrollAnimations();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
