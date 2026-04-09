(function() {
	'use strict';

	// AND across axes, OR within an axis. Cards without any term on an axis are
	// treated as "unkategorisiert" and are hidden when that axis has active filters.
	function matchesAxis(card, key, selected) {
		if (!selected.length) return true;
		var raw = card.getAttribute('data-' + (key === 'abWann' ? 'ab-wann' : key)) || '';
		var values = raw.split(',').map(function(v) { return v.trim(); }).filter(Boolean);
		if (!values.length) return false;
		return selected.some(function(s) { return values.indexOf(s) !== -1; });
	}

	function matchesDate(card, fromDate) {
		if (!fromDate) return true;
		var ab = card.getAttribute('data-ab-wann') || '';
		if (!ab) return true; // Jobs ohne Datum bleiben sichtbar (flexibler Start)
		return ab >= fromDate;
	}

	function initFilter(section) {
		var filterEl = section.querySelector('.po-jobs__filter');
		if (!filterEl) return;

		var grid = section.querySelector('.po-jobs__grid');
		var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.po-job-card')) : [];
		var emptyState = section.querySelector('.po-jobs__empty-state');
		var resetBtn = filterEl.querySelector('.po-jobs__filter-reset');
		var dateInput = filterEl.querySelector('.po-jobs__filter-date');

		var state = {}; // { key: ['slug1', 'slug2', ...] }
		var fromDate = '';

		function getSelected(group) {
			return Array.prototype.slice.call(group.querySelectorAll('input[type="checkbox"]:checked'))
				.map(function(cb) { return cb.value; });
		}

		function applyFilters() {
			var anyActive = !!fromDate;
			Object.keys(state).forEach(function(k) { if (state[k].length) anyActive = true; });

			var visibleCount = 0;
			cards.forEach(function(card) {
				var show = true;
				Object.keys(state).forEach(function(k) {
					if (!matchesAxis(card, k, state[k])) show = false;
				});
				if (show && !matchesDate(card, fromDate)) show = false;
				card.hidden = !show;
				if (show) visibleCount++;
			});

			if (emptyState) emptyState.hidden = visibleCount > 0;
			if (resetBtn) resetBtn.hidden = !anyActive;
		}

		function updateGroupLabel(group) {
			var key = group.getAttribute('data-filter-key');
			var countEl = group.querySelector('.po-jobs__filter-count');
			var selected = state[key] || [];
			if (countEl) {
				if (!selected.length) {
					countEl.textContent = 'Alle';
					countEl.classList.remove('is-active');
				} else {
					countEl.textContent = selected.length + ' ausgewählt';
					countEl.classList.add('is-active');
				}
			}
		}

		// Checkbox-Gruppen verdrahten
		filterEl.querySelectorAll('.po-jobs__filter-group').forEach(function(group) {
			var key = group.getAttribute('data-filter-key');
			if (!key || key === 'abWann') return;
			state[key] = [];

			var toggle = group.querySelector('.po-jobs__filter-toggle');
			var options = group.querySelector('.po-jobs__filter-options');
			if (toggle && options) {
				toggle.addEventListener('click', function(e) {
					e.stopPropagation();
					var isOpen = !options.hidden;
					// Alle anderen schließen
					filterEl.querySelectorAll('.po-jobs__filter-options').forEach(function(o) {
						if (o !== options) o.hidden = true;
					});
					filterEl.querySelectorAll('.po-jobs__filter-toggle').forEach(function(t) {
						if (t !== toggle) t.setAttribute('aria-expanded', 'false');
					});
					options.hidden = isOpen;
					toggle.setAttribute('aria-expanded', String(!isOpen));
				});
			}

			group.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
				cb.addEventListener('change', function() {
					state[key] = getSelected(group);
					updateGroupLabel(group);
					applyFilters();
				});
			});
		});

		// Datum-Input
		if (dateInput) {
			dateInput.addEventListener('change', function() {
				fromDate = dateInput.value || '';
				applyFilters();
			});
		}

		// Reset
		if (resetBtn) {
			resetBtn.addEventListener('click', function() {
				filterEl.querySelectorAll('input[type="checkbox"]').forEach(function(cb) { cb.checked = false; });
				if (dateInput) dateInput.value = '';
				Object.keys(state).forEach(function(k) { state[k] = []; });
				fromDate = '';
				filterEl.querySelectorAll('.po-jobs__filter-group').forEach(updateGroupLabel);
				applyFilters();
			});
		}

		// Klick außerhalb → Dropdowns schließen
		document.addEventListener('click', function(e) {
			if (!filterEl.contains(e.target)) {
				filterEl.querySelectorAll('.po-jobs__filter-options').forEach(function(o) { o.hidden = true; });
				filterEl.querySelectorAll('.po-jobs__filter-toggle').forEach(function(t) { t.setAttribute('aria-expanded', 'false'); });
			}
		});
	}

	function initCarousel(section) {
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
	}

	function init() {
		document.querySelectorAll('.po-jobs').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';
			initFilter(section);
			initCarousel(section);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
