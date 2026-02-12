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
		var loadBatch = 14; // Weitere 14 Tage mit Events pro Klick

		// ========================================
		// LOAD MORE
		// ========================================

		var visibleUpTo = initialCount; // Index bis wohin sichtbar

		function showMoreItems() {
			// Naechste Batch: 14 weitere Tage mit Events
			var seenDays = {};
			var dayCount = 0;
			var newVisibleUpTo = visibleUpTo;

			for (var i = visibleUpTo; i < allCards.length; i++) {
				var card = allCards[i];
				// Datum aus der Card holen (data-index fuer Reihenfolge)
				// Wir zaehlen "Tage" indem wir den Date-Text vergleichen
				var dateEl = card.querySelector('.po-eds__card-date');
				var dateText = dateEl ? dateEl.textContent.trim() : '';

				if (dateText && !seenDays[dateText]) {
					seenDays[dateText] = true;
					dayCount++;
				}

				if (dayCount > loadBatch) break;

				card.classList.remove('is-hidden');
				newVisibleUpTo = i + 1;
			}

			visibleUpTo = newVisibleUpTo;

			// Hide button wenn alles sichtbar oder Filter aktiv
			if (visibleUpTo >= totalCount && loadMoreWrap) {
				loadMoreWrap.style.display = 'none';
			}

			// Filter erneut anwenden
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
		// FAB FILTER (Floating Action Button)
		// ========================================

		var fab = section.querySelector('.po-eds__fab');
		var trigger = fab ? fab.querySelector('.po-eds__fab-trigger') : null;
		var filterText = fab ? fab.querySelector('.po-eds__fab-text') : null;
		var options = fab ? fab.querySelectorAll('.po-eds__fab-option') : [];

		if (fab && trigger) {
			// IntersectionObserver: FAB nur anzeigen wenn Block sichtbar
			var observer = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						fab.classList.add('is-visible');
					} else {
						fab.classList.remove('is-visible');
						fab.classList.remove('is-open');
					}
				});
			}, { threshold: 0.15, rootMargin: '-10% 0px -10% 0px' });

			observer.observe(section);

			// Toggle dropdown
			trigger.addEventListener('click', function(e) {
				e.stopPropagation();
				fab.classList.toggle('is-open');
			});

			// Close on outside click
			document.addEventListener('click', function(e) {
				if (!fab.contains(e.target)) {
					fab.classList.remove('is-open');
				}
			});

			// Filter option click
			options.forEach(function(option) {
				option.addEventListener('click', function() {
					options.forEach(function(o) { o.classList.remove('is-active'); });
					this.classList.add('is-active');

					var filterValue = this.getAttribute('data-filter');
					var filterName = this.textContent.trim();

					if (filterValue === 'all') {
						filterText.textContent = 'Filtern';
					} else {
						filterText.textContent = filterName;
					}

					applyFilter(filterValue);
					fab.classList.remove('is-open');
				});
			});
		}

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
