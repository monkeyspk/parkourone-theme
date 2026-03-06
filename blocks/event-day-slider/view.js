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

			applyFilters();
		}

		if (loadMoreBtn) {
			loadMoreBtn.addEventListener('click', showMoreItems);
		}

		// ========================================
		// FILTER
		// ========================================

		var currentAgeFilter = 'all';
		var currentLocationFilter = 'all';
		var currentWeekdayFilter = 'all';

		function applyFilters() {
			var hasActiveFilter = currentAgeFilter !== 'all' || currentLocationFilter !== 'all' || currentWeekdayFilter !== 'all';

			if (hasActiveFilter) {
				allCards.forEach(function(card) {
					var filters = card.getAttribute('data-filters') || '';
					var weekday = card.getAttribute('data-weekday') || '';
					var matchAge = currentAgeFilter === 'all' || filters.indexOf(currentAgeFilter) !== -1;
					var matchLoc = currentLocationFilter === 'all' || filters.indexOf(currentLocationFilter) !== -1;
					var matchDay = currentWeekdayFilter === 'all' || weekday === currentWeekdayFilter;
					if (matchAge && matchLoc && matchDay) {
						card.classList.remove('is-hidden');
						card.style.display = '';
					} else {
						card.style.display = 'none';
					}
				});
				if (loadMoreWrap) loadMoreWrap.style.display = 'none';
			} else {
				allCards.forEach(function(card, index) {
					if (index >= visibleUpTo) {
						card.classList.add('is-hidden');
						card.style.display = '';
					} else {
						card.style.display = '';
					}
				});
				if (loadMoreWrap) loadMoreWrap.style.display = (visibleUpTo >= totalCount) ? 'none' : '';
			}
		}

		// ========================================
		// DROPDOWN FILTERS
		// ========================================

		var dropdowns = section.querySelectorAll('.po-eds__dropdown');

		dropdowns.forEach(function(dropdown) {
			var trigger = dropdown.querySelector('.po-eds__dropdown-trigger');
			var panel = dropdown.querySelector('.po-eds__dropdown-panel');
			var valueEl = dropdown.querySelector('.po-eds__dropdown-value');
			var options = dropdown.querySelectorAll('.po-eds__dropdown-option');
			var filterType = dropdown.getAttribute('data-filter-type');

			trigger.addEventListener('click', function(e) {
				e.stopPropagation();
				var isOpen = dropdown.classList.contains('is-open');

				// Alle anderen Dropdowns schliessen
				dropdowns.forEach(function(d) {
					d.classList.remove('is-open');
					d.querySelector('.po-eds__dropdown-trigger').setAttribute('aria-expanded', 'false');
					d.querySelector('.po-eds__dropdown-panel').setAttribute('aria-hidden', 'true');
				});

				if (!isOpen) {
					dropdown.classList.add('is-open');
					trigger.setAttribute('aria-expanded', 'true');
					panel.setAttribute('aria-hidden', 'false');
				}
			});

			options.forEach(function(option) {
				option.addEventListener('click', function(e) {
					e.stopPropagation();
					var value = option.getAttribute('data-value');

					options.forEach(function(o) { o.classList.remove('is-selected'); });
					option.classList.add('is-selected');
					valueEl.textContent = option.textContent.trim();

					if (filterType === 'age') {
						currentAgeFilter = value;
					} else if (filterType === 'location') {
						currentLocationFilter = value;
					} else if (filterType === 'weekday') {
						currentWeekdayFilter = value;
					}

					applyFilters();
					dropdown.classList.remove('is-open');
					trigger.setAttribute('aria-expanded', 'false');
					panel.setAttribute('aria-hidden', 'true');
				});
			});
		});

		// ========================================
		// URL-PARAMETER FILTER (?alter=kids&standort=berlin-mitte&tag=1)
		// ========================================

		var urlParams = new URLSearchParams(window.location.search);
		var paramMap = { age: 'alter', location: 'standort', weekday: 'tag' };

		dropdowns.forEach(function(dropdown) {
			var filterType = dropdown.getAttribute('data-filter-type');
			var paramName = paramMap[filterType];
			var paramValue = urlParams.get(paramName);
			if (!paramValue) return;

			var options = dropdown.querySelectorAll('.po-eds__dropdown-option');
			var valueEl = dropdown.querySelector('.po-eds__dropdown-value');
			var matched = false;

			options.forEach(function(option) {
				if (option.getAttribute('data-value') === paramValue) {
					options.forEach(function(o) { o.classList.remove('is-selected'); });
					option.classList.add('is-selected');
					valueEl.textContent = option.textContent.trim();
					matched = true;
				}
			});

			if (matched) {
				if (filterType === 'age') currentAgeFilter = paramValue;
				else if (filterType === 'location') currentLocationFilter = paramValue;
				else if (filterType === 'weekday') currentWeekdayFilter = paramValue;
			}
		});

		if (urlParams.has('alter') || urlParams.has('standort') || urlParams.has('tag')) {
			applyFilters();
		}

		// Dropdowns schliessen bei Klick ausserhalb
		document.addEventListener('click', function() {
			dropdowns.forEach(function(d) {
				d.classList.remove('is-open');
				d.querySelector('.po-eds__dropdown-trigger').setAttribute('aria-expanded', 'false');
				d.querySelector('.po-eds__dropdown-panel').setAttribute('aria-hidden', 'true');
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
