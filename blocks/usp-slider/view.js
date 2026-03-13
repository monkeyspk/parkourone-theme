(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.po-usp').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';

			var wrapper = section.querySelector('.po-usp__wrapper');
			var track = section.querySelector('.po-usp__track');
			var cards = section.querySelectorAll('.po-usp__card');
			var prevBtn = section.querySelector('.po-usp__nav-prev');
			var nextBtn = section.querySelector('.po-usp__nav-next');

			if (!wrapper || !track || !prevBtn || !nextBtn || cards.length === 0) return;

			function getCardWidth() {
				return cards[0].offsetWidth + 24;
			}

			function getVisibleCards() {
				return Math.floor(wrapper.offsetWidth / getCardWidth());
			}

			function updateNav() {
				var scrollLeft = wrapper.scrollLeft;
				var maxScroll = track.scrollWidth - wrapper.offsetWidth;
				prevBtn.disabled = scrollLeft <= 0;
				nextBtn.disabled = scrollLeft >= maxScroll - 10;
			}

			prevBtn.addEventListener('click', function() {
				wrapper.scrollBy({ left: -getCardWidth() * getVisibleCards(), behavior: 'smooth' });
			});

			nextBtn.addEventListener('click', function() {
				wrapper.scrollBy({ left: getCardWidth() * getVisibleCards(), behavior: 'smooth' });
			});

			wrapper.addEventListener('scroll', updateNav);
			window.addEventListener('resize', updateNav);
			updateNav();

			// Card click → modal open is handled by shared overlay-handler.js (data-modal-target)
			// Anchor links inside modals are handled by scroll-animations.js
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
