(function() {
	'use strict';

	var sections = document.querySelectorAll('.po-eds');
	if (!sections.length) return;

	sections.forEach(function(section) {
		initEventDaySlider(section);
	});

	function initEventDaySlider(section) {
		var events = section.querySelectorAll('[data-filters]');
		var dayCards = section.querySelectorAll('.po-eds__day-card');

		// ========================================
		// FILTER
		// ========================================

		function applyFilter(filterValue) {
			events.forEach(function(event) {
				var filters = event.getAttribute('data-filters') || '';
				var match = filterValue === 'all' || filters.indexOf(filterValue) !== -1;
				event.style.display = match ? '' : 'none';
			});

			// Day-Cards ohne sichtbare Events ausblenden, Count aktualisieren
			dayCards.forEach(function(card) {
				var visibleItems = card.querySelectorAll('.po-eds__card-item:not([style*="display: none"])');
				var countEl = card.querySelector('.po-eds__day-card-count');
				if (visibleItems.length === 0) {
					card.style.display = 'none';
				} else {
					card.style.display = '';
					if (countEl) {
						countEl.textContent = visibleItems.length;
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
	}
})();
