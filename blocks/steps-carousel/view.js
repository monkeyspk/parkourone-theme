(function() {
	'use strict';

	function initStepsCarousel() {
		const carousels = document.querySelectorAll('.po-steps-carousel');

		carousels.forEach(function(carousel) {
			const wrapper = carousel.querySelector('.po-steps-carousel__wrapper');
			const track = carousel.querySelector('.po-steps-carousel__track');
			const cards = carousel.querySelectorAll('.po-steps-carousel__card');
			const dots = carousel.querySelectorAll('.po-steps-carousel__dot');

			if (!wrapper || cards.length === 0) return;

			let currentIndex = 0;

			function updateDots(index) {
				dots.forEach(function(dot, i) {
					if (i === index) {
						dot.classList.add('is-active');
					} else {
						dot.classList.remove('is-active');
					}
				});
			}

			function scrollToCard(index) {
				const card = cards[index];
				if (!card) return;

				const cardRect = card.getBoundingClientRect();
				const wrapperRect = wrapper.getBoundingClientRect();
				const cardCenter = card.offsetLeft + card.offsetWidth / 2;
				const wrapperCenter = wrapper.offsetWidth / 2;
				const scrollLeft = cardCenter - wrapperCenter;

				wrapper.scrollTo({
					left: scrollLeft,
					behavior: 'smooth'
				});

				currentIndex = index;
				updateDots(index);
			}

			// Dot click handlers
			dots.forEach(function(dot, index) {
				dot.addEventListener('click', function() {
					scrollToCard(index);
				});
			});

			// Track scroll to update dots
			let scrollTimeout;
			wrapper.addEventListener('scroll', function() {
				clearTimeout(scrollTimeout);
				scrollTimeout = setTimeout(function() {
					// Find which card is most centered
					const wrapperCenter = wrapper.scrollLeft + wrapper.offsetWidth / 2;
					let closestIndex = 0;
					let closestDistance = Infinity;

					cards.forEach(function(card, index) {
						const cardCenter = card.offsetLeft + card.offsetWidth / 2;
						const distance = Math.abs(cardCenter - wrapperCenter);
						if (distance < closestDistance) {
							closestDistance = distance;
							closestIndex = index;
						}
					});

					if (closestIndex !== currentIndex) {
						currentIndex = closestIndex;
						updateDots(currentIndex);
					}
				}, 50);
			}, { passive: true });

			// Touch/swipe handling for better mobile UX
			let touchStartX = 0;
			let touchEndX = 0;

			wrapper.addEventListener('touchstart', function(e) {
				touchStartX = e.changedTouches[0].screenX;
			}, { passive: true });

			wrapper.addEventListener('touchend', function(e) {
				touchEndX = e.changedTouches[0].screenX;
				handleSwipe();
			}, { passive: true });

			function handleSwipe() {
				const swipeThreshold = 50;
				const diff = touchStartX - touchEndX;

				if (Math.abs(diff) > swipeThreshold) {
					if (diff > 0 && currentIndex < cards.length - 1) {
						// Swipe left - next card
						scrollToCard(currentIndex + 1);
					} else if (diff < 0 && currentIndex > 0) {
						// Swipe right - previous card
						scrollToCard(currentIndex - 1);
					}
				}
			}

			// Initial state
			updateDots(0);
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initStepsCarousel);
	} else {
		initStepsCarousel();
	}
})();
