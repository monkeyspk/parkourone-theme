(function() {
	'use strict';

	function animateNumber(el) {
		var targetStr = el.getAttribute('data-target') || '0';
		var cleanNum = targetStr.replace(/[^\d.,]/g, '').replace(',', '.');
		var target = parseFloat(cleanNum) || 0;
		var isInteger = Number.isInteger(target);
		var duration = 2000;
		var startTime = null;

		function easeOutQuart(t) {
			return 1 - Math.pow(1 - t, 4);
		}

		function step(timestamp) {
			if (!startTime) startTime = timestamp;
			var progress = Math.min((timestamp - startTime) / duration, 1);
			var easedProgress = easeOutQuart(progress);
			var current = easedProgress * target;

			if (isInteger) {
				el.textContent = Math.floor(current).toLocaleString('de-DE');
			} else {
				el.textContent = current.toFixed(1).replace('.', ',');
			}

			if (progress < 1) {
				requestAnimationFrame(step);
			} else {
				el.textContent = isInteger ? target.toLocaleString('de-DE') : target.toFixed(1).replace('.', ',');
			}
		}

		requestAnimationFrame(step);
	}

	function init() {
		document.querySelectorAll('.po-stats').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';

			var animated = false;

			// Remove no-js class, set numbers to 0 for animation
			section.classList.remove('po-stats--no-js');
			section.querySelectorAll('.po-stats__number').forEach(function(el) {
				el.textContent = '0';
			});

			function startAnimation() {
				if (animated) return;
				animated = true;
				section.classList.add('is-visible');
				section.querySelectorAll('.po-stats__number').forEach(animateNumber);
			}

			if ('IntersectionObserver' in window) {
				var observer = new IntersectionObserver(function(entries) {
					entries.forEach(function(entry) {
						if (entry.isIntersecting && !animated) {
							startAnimation();
							observer.disconnect();
						}
					});
				}, {
					threshold: 0.1,
					rootMargin: '0px 0px -50px 0px'
				});

				observer.observe(section);

				// Fallback: if observer hasn't fired after 3s and element is visible
				setTimeout(function() {
					if (!animated) {
						var rect = section.getBoundingClientRect();
						if (rect.top < window.innerHeight && rect.bottom > 0) {
							startAnimation();
							observer.disconnect();
						}
					}
				}, 3000);
			} else {
				function checkVisibility() {
					if (animated) return;
					var rect = section.getBoundingClientRect();
					if (rect.top < window.innerHeight * 0.9 && rect.bottom > 0) {
						startAnimation();
						window.removeEventListener('scroll', checkVisibility);
					}
				}
				window.addEventListener('scroll', checkVisibility, { passive: true });
				checkVisibility();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
