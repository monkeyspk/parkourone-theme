document.addEventListener('DOMContentLoaded', function() {
	var stundenplan = document.querySelector('.po-stundenplan-detail');
	if (!stundenplan) return;

	var filters = stundenplan.querySelectorAll('.po-filter__select');
	var events = stundenplan.querySelectorAll('.po-schedule-event');
	var dayCounts = stundenplan.querySelectorAll('.po-stundenplan-day__count');

	var activeFilters = {
		age: '',
		location: ''
	};

	// Initialize from data attributes if set
	activeFilters.age = stundenplan.dataset.filterAge || '';
	activeFilters.location = stundenplan.dataset.filterLocation || '';

	// Set initial filter values
	filters.forEach(function(filter) {
		var filterType = filter.dataset.filter;
		if (activeFilters[filterType]) {
			filter.value = activeFilters[filterType];
		}
	});

	// Filter events
	function filterEvents() {
		events.forEach(function(event) {
			var ageMatch = !activeFilters.age || event.dataset.age === activeFilters.age;
			var locationMatch = !activeFilters.location || event.dataset.location === activeFilters.location;

			if (ageMatch && locationMatch) {
				event.classList.remove('is-hidden');
			} else {
				event.classList.add('is-hidden');
			}
		});

		updateDayCounts();
	}

	// Update day counts
	function updateDayCounts() {
		var days = stundenplan.querySelectorAll('.po-stundenplan-day');
		days.forEach(function(day) {
			var visibleEvents = day.querySelectorAll('.po-schedule-event:not(.is-hidden)');
			var countEl = day.querySelector('.po-stundenplan-day__count');
			if (countEl) {
				countEl.textContent = visibleEvents.length;
			}

			// Show/hide empty state
			var emptyEl = day.querySelector('.po-stundenplan-day__empty');
			var eventsContainer = day.querySelector('.po-stundenplan-day__events');

			if (visibleEvents.length === 0) {
				if (!emptyEl && eventsContainer) {
					var empty = document.createElement('div');
					empty.className = 'po-stundenplan-day__empty';
					empty.textContent = 'Keine Kurse';
					eventsContainer.appendChild(empty);
				} else if (emptyEl) {
					emptyEl.style.display = 'flex';
				}
			} else if (emptyEl) {
				emptyEl.style.display = 'none';
			}
		});
	}

	// Listen to filter changes
	filters.forEach(function(filter) {
		filter.addEventListener('change', function() {
			var filterType = this.dataset.filter;
			activeFilters[filterType] = this.value;
			filterEvents();

			// Update URL without reload
			var url = new URL(window.location);
			if (this.value) {
				url.searchParams.set(filterType, this.value);
			} else {
				url.searchParams.delete(filterType);
			}
			window.history.replaceState({}, '', url);
		});
	});

	// Initialize from URL params
	var urlParams = new URLSearchParams(window.location.search);
	var urlAge = urlParams.get('age');
	var urlLocation = urlParams.get('location');

	if (urlAge) {
		activeFilters.age = urlAge;
		var ageFilter = stundenplan.querySelector('[data-filter="age"]');
		if (ageFilter) ageFilter.value = urlAge;
	}

	if (urlLocation) {
		activeFilters.location = urlLocation;
		var locationFilter = stundenplan.querySelector('[data-filter="location"]');
		if (locationFilter) locationFilter.value = urlLocation;
	}

	// Initial filter
	if (activeFilters.age || activeFilters.location) {
		filterEvents();
	}

	// Mobile: Horizontal scroll for days
	if (window.innerWidth <= 600) {
		var grid = stundenplan.querySelector('.po-stundenplan-detail__grid');
		if (grid) {
			// Find current day and scroll to it
			var today = new Date().getDay();
			var todayColumn = grid.querySelector('[data-day="' + today + '"]');
			if (todayColumn) {
				setTimeout(function() {
					todayColumn.scrollIntoView({ behavior: 'smooth', inline: 'center' });
				}, 300);
			}
		}
	}
});
