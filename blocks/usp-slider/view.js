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

			// Cards are fully clickable → open modal via data-modal-target
			cards.forEach(function(card) {
				var modalId = card.getAttribute('data-modal-target');
				if (!modalId) return;

				card.addEventListener('click', function(e) {
					e.preventDefault();
					var modal = document.getElementById(modalId);
					if (!modal) return;
					modal.classList.add('is-active');
					modal.setAttribute('aria-hidden', 'false');
					document.body.classList.add('po-no-scroll');
				});

				// Close modal on anchor link click inside modal
				var modal = document.getElementById(modalId);
				if (modal) {
					modal.querySelectorAll('a[href^="#"]').forEach(function(link) {
						link.addEventListener('click', function() {
							modal.classList.remove('is-active');
							modal.setAttribute('aria-hidden', 'true');
							var stillActive = document.querySelectorAll('.po-overlay.is-active');
							if (stillActive.length === 0) {
								document.body.classList.remove('po-no-scroll');
							}
						});
					});
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
