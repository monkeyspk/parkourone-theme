(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.po-jobs').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';

			var grid = section.querySelector('.po-jobs__grid');
			var prevBtn = section.querySelector('.po-jobs__nav-prev');
			var nextBtn = section.querySelector('.po-jobs__nav-next');
			var nav = section.querySelector('.po-jobs__nav');

			if (!grid || !prevBtn || !nextBtn) return;

			function getCardWidth() {
				var firstCard = grid.querySelector('.po-job-card');
				if (!firstCard) return 340;
				return firstCard.offsetWidth + 24;
			}

			function getVisibleCards() {
				return Math.max(1, Math.floor(grid.offsetWidth / getCardWidth()));
			}

			function updateNav() {
				var sl = grid.scrollLeft;
				var maxScroll = grid.scrollWidth - grid.offsetWidth;
				prevBtn.disabled = sl <= 0;
				nextBtn.disabled = sl >= maxScroll - 10;
				if (nav) nav.style.display = maxScroll <= 0 ? 'none' : '';
			}

			prevBtn.addEventListener('click', function() {
				grid.scrollBy({ left: -getCardWidth() * getVisibleCards(), behavior: 'smooth' });
			});

			nextBtn.addEventListener('click', function() {
				grid.scrollBy({ left: getCardWidth() * getVisibleCards(), behavior: 'smooth' });
			});

			grid.addEventListener('scroll', updateNav);
			window.addEventListener('resize', updateNav);
			updateNav();
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
