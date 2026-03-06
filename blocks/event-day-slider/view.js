(function() {
	'use strict';

	var sections = document.querySelectorAll('.po-eds');
	if (!sections.length) return;

	sections.forEach(function(section) {
		initEventList(section);
	});

	function initEventList(section) {
		var list = section.querySelector('.po-eds__list');
		if (!list) return;

		var allCards = list.querySelectorAll('.po-eds__card');
		var initialCount = parseInt(list.getAttribute('data-initial') || '0', 10);
		var totalCount = parseInt(list.getAttribute('data-total') || '0', 10);
		var loadMoreWrap = section.querySelector('.po-eds__load-more-wrap');
		var loadMoreBtn = section.querySelector('.po-eds__load-more');
		var loadBatch = 15; // 15 Events pro Klick

		// ========================================
		// LOAD MORE
		// ========================================

		var visibleUpTo = initialCount; // Index bis wohin sichtbar

		function showMoreItems() {
			var newVisibleUpTo = Math.min(visibleUpTo + loadBatch, allCards.length);

			for (var i = visibleUpTo; i < newVisibleUpTo; i++) {
				allCards[i].classList.remove('is-hidden');
			}

			visibleUpTo = newVisibleUpTo;

			if (visibleUpTo >= totalCount && loadMoreWrap) {
				loadMoreWrap.style.display = 'none';
			}

			applyFilter(activeFilter);
		}

		if (loadMoreBtn) {
			loadMoreBtn.addEventListener('click', showMoreItems);
		}

		// ========================================
		// FILTER
		// ========================================

		var activeFilter = 'all';

		function applyFilter(filterValue) {
			activeFilter = filterValue;

			allCards.forEach(function(card, index) {
				// Hidden Cards (load-more) nicht anfassen
				if (index >= visibleUpTo) return;

				var filters = card.getAttribute('data-filters') || '';
				var match = filterValue === 'all' || filters.indexOf(filterValue) !== -1;
				card.style.display = match ? '' : 'none';
			});

			// Load-More Button anpassen:
			// Bei aktivem Filter verstecken wir den Button und zeigen alle passenden
			if (loadMoreWrap) {
				if (filterValue !== 'all') {
					// Bei Filter: alle Cards zeigen die passen
					allCards.forEach(function(card) {
						var filters = card.getAttribute('data-filters') || '';
						var match = filters.indexOf(filterValue) !== -1;
						if (match) {
							card.classList.remove('is-hidden');
							card.style.display = '';
						} else {
							card.style.display = 'none';
						}
					});
					loadMoreWrap.style.display = 'none';
				} else {
					// Bei "Alle": hidden/visible State wiederherstellen
					allCards.forEach(function(card, index) {
						if (index >= visibleUpTo) {
							card.classList.add('is-hidden');
							card.style.display = '';
						} else {
							card.style.display = '';
						}
					});
					loadMoreWrap.style.display = (visibleUpTo >= totalCount) ? 'none' : '';
				}
			}
		}

		// ========================================
		// INLINE FILTER PILLS
		// ========================================

		var filterBtns = section.querySelectorAll('.po-eds__filter-btn');

		filterBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				filterBtns.forEach(function(b) { b.classList.remove('is-active'); });
				this.classList.add('is-active');
				applyFilter(this.getAttribute('data-filter'));
			});
		});

		// ========================================
		// MODAL OEFFNEN/SCHLIESSEN
		// ========================================

		var eventButtons = section.querySelectorAll('[data-modal-target]');

		eventButtons.forEach(function(btn) {
			var modalId = btn.getAttribute('data-modal-target');
			var modal = document.getElementById(modalId);
			if (!modal) return;

			var closeBtn = modal.querySelector('.po-overlay__close');
			var backdrop = modal.querySelector('.po-overlay__backdrop');

			function openModal() {
				modal.classList.add('is-active');
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('po-no-scroll');
				setTimeout(function() {
					var focusEl = modal.querySelector('.po-overlay__close');
					if (focusEl) focusEl.focus();
				}, 100);
			}

			function closeModal() {
				modal.classList.remove('is-active');
				modal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('po-no-scroll');
			}

			btn.addEventListener('click', openModal);
			if (closeBtn) closeBtn.addEventListener('click', closeModal);
			if (backdrop) backdrop.addEventListener('click', closeModal);

			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && modal.classList.contains('is-active')) {
					closeModal();
				}
			});
		});
	}
})();
