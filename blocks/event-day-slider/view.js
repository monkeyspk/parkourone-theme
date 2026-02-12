(function() {
	'use strict';

	var sections = document.querySelectorAll('.po-eds');
	if (!sections.length) return;

	sections.forEach(function(section) {
		initEventWeekCalendar(section);
	});

	function initEventWeekCalendar(section) {
		var weekGrids = section.querySelectorAll('.po-eds__week-grid');
		var prevBtn = section.querySelector('.po-eds__week-prev');
		var nextBtn = section.querySelector('.po-eds__week-next');
		var weekLabel = section.querySelector('.po-eds__week-label');
		var currentWeek = 0;
		var totalWeeks = weekGrids.length;

		// ========================================
		// WOCHENNAVIGATION
		// ========================================

		function showWeek(index) {
			if (index < 0 || index >= totalWeeks) return;

			weekGrids.forEach(function(grid) {
				grid.classList.remove('is-active');
			});

			weekGrids[index].classList.add('is-active');
			currentWeek = index;

			// Label aktualisieren
			weekLabel.textContent = weekGrids[index].getAttribute('data-week-label');

			// Button-States
			prevBtn.disabled = (currentWeek === 0);
			nextBtn.disabled = (currentWeek >= totalWeeks - 1);

			// Filter nach Wochenwechsel erneut anwenden
			applyFilter(activeFilter);
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function() {
				showWeek(currentWeek - 1);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function() {
				showWeek(currentWeek + 1);
			});
		}

		// ========================================
		// FILTER
		// ========================================

		var activeFilter = 'all';

		function applyFilter(filterValue) {
			activeFilter = filterValue;
			var activeGrid = section.querySelector('.po-eds__week-grid.is-active');
			if (!activeGrid) return;

			var events = activeGrid.querySelectorAll('[data-filters]');
			var dayCols = activeGrid.querySelectorAll('.po-eds__day-col:not(.is-past)');

			events.forEach(function(event) {
				var filters = event.getAttribute('data-filters') || '';
				var match = filterValue === 'all' || filters.indexOf(filterValue) !== -1;
				event.style.display = match ? '' : 'none';
			});

			// Day-Count aktualisieren
			dayCols.forEach(function(col) {
				var visibleItems = col.querySelectorAll('.po-eds__card-item:not([style*="display: none"])');
				var countEl = col.querySelector('.po-eds__day-count');
				var emptyText = col.querySelector('.po-eds__day-empty-text');

				if (countEl) {
					if (visibleItems.length > 0) {
						countEl.textContent = visibleItems.length;
						countEl.style.display = '';
					} else {
						countEl.style.display = 'none';
					}
				}

				// "Keine Trainings" Text zeigen wenn alle gefiltert
				if (emptyText) {
					var allItems = col.querySelectorAll('.po-eds__card-item');
					if (allItems.length > 0 && visibleItems.length === 0) {
						emptyText.style.display = '';
					} else if (allItems.length > 0) {
						emptyText.style.display = 'none';
					}
				}
			});
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
