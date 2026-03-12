(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.po-sp').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';

			var events = section.querySelectorAll('[data-filters]');
			var rows = section.querySelectorAll('.po-sp__row');
			var dayCards = section.querySelectorAll('.po-sp__day-card');

			// Shared filter function
			function applyFilters(ageFilter, locationFilter) {
				events.forEach(function(event) {
					var filters = event.getAttribute('data-filters') || '';
					var matchAge = ageFilter === 'all' || filters.indexOf(ageFilter) !== -1;
					var matchLocation = locationFilter === 'all' || filters.indexOf(locationFilter) !== -1;

					if (matchAge && matchLocation) {
						event.style.display = '';
					} else {
						event.style.display = 'none';
					}
				});

				rows.forEach(function(row) {
					var visibleEvents = row.querySelectorAll('.po-sp__event:not([style*="display: none"])');
					if (visibleEvents.length === 0) {
						row.classList.add('po-sp__row--hidden');
					} else {
						row.classList.remove('po-sp__row--hidden');
					}
				});

				dayCards.forEach(function(card) {
					var visibleItems = card.querySelectorAll('.po-sp__card-item:not([style*="display: none"])');
					var countEl = card.querySelector('.po-sp__day-card-count');
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
			// INLINE FILTER (Custom Dropdowns)
			// ========================================
			var inlineFilters = section.querySelector('.po-sp__inline-filters');
			if (inlineFilters) {
				var customDropdowns = section.querySelectorAll('.po-sp__custom-dropdown');
				var currentAgeFilter = 'all';
				var currentLocationFilter = 'all';

				customDropdowns.forEach(function(dropdown) {
					var trigger = dropdown.querySelector('.po-sp__dropdown-trigger');
					var panel = dropdown.querySelector('.po-sp__dropdown-panel');
					var valueEl = dropdown.querySelector('.po-sp__dropdown-value');
					var options = dropdown.querySelectorAll('.po-sp__dropdown-option');
					var filterType = dropdown.getAttribute('data-filter-type');

					trigger.addEventListener('click', function(e) {
						e.stopPropagation();
						var isOpen = dropdown.classList.contains('is-open');

						customDropdowns.forEach(function(d) {
							d.classList.remove('is-open');
							d.querySelector('.po-sp__dropdown-trigger').setAttribute('aria-expanded', 'false');
							d.querySelector('.po-sp__dropdown-panel').setAttribute('aria-hidden', 'true');
						});

						if (!isOpen) {
							dropdown.classList.add('is-open');
							trigger.setAttribute('aria-expanded', 'true');
							panel.setAttribute('aria-hidden', 'false');
						}
					});

					options.forEach(function(option) {
						option.addEventListener('click', function() {
							var filterValue = this.getAttribute('data-value');
							var filterName = this.textContent.trim();

							options.forEach(function(o) { o.classList.remove('is-selected'); });
							this.classList.add('is-selected');
							valueEl.textContent = filterName;

							if (filterType === 'age') {
								currentAgeFilter = filterValue;
							} else if (filterType === 'location') {
								currentLocationFilter = filterValue;
							}

							applyFilters(currentAgeFilter, currentLocationFilter);

							dropdown.classList.remove('is-open');
							trigger.setAttribute('aria-expanded', 'false');
							panel.setAttribute('aria-hidden', 'true');
						});
					});
				});

				document.addEventListener('click', function(e) {
					if (!inlineFilters.contains(e.target)) {
						customDropdowns.forEach(function(d) {
							d.classList.remove('is-open');
							d.querySelector('.po-sp__dropdown-trigger').setAttribute('aria-expanded', 'false');
							d.querySelector('.po-sp__dropdown-panel').setAttribute('aria-hidden', 'true');
						});
					}
				});
			}

			// ========================================
			// FAB FILTER (Floating Button)
			// ========================================
			var fab = section.querySelector('.po-sp__filter-fab');
			var fabTrigger = section.querySelector('.po-sp__filter-trigger');
			var filterText = section.querySelector('.po-sp__filter-text');
			var fabOptions = section.querySelectorAll('.po-sp__filter-option');

			if (fab && fabTrigger) {
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

				fabTrigger.addEventListener('click', function(e) {
					e.stopPropagation();
					fab.classList.toggle('is-open');
				});

				document.addEventListener('click', function(e) {
					if (!fab.contains(e.target)) {
						fab.classList.remove('is-open');
					}
				});

				fabOptions.forEach(function(option) {
					option.addEventListener('click', function() {
						fabOptions.forEach(function(o) { o.classList.remove('is-active'); });
						this.classList.add('is-active');

						var filterValue = this.getAttribute('data-filter');
						var filterName = this.textContent.trim();

						if (filterValue === 'all') {
							filterText.textContent = 'Filtern';
						} else {
							filterText.textContent = filterName;
						}

						// FAB uses simple filter (not combined)
						applyFilters(filterValue, filterValue);

						fab.classList.remove('is-open');
					});
				});
			}

			// ========================================
			// MODAL OPEN/CLOSE handled by shared overlay-handler
			// ========================================

			// ========================================
			// COACH-SLIDE NAVIGATION
			// ========================================
			section.querySelectorAll('[data-modal-target]').forEach(function(btn) {
				var modalId = btn.getAttribute('data-modal-target');
				var modal = document.getElementById(modalId);
				if (!modal) return;

				modal.querySelectorAll('.po-sp__coach-link, .po-sp__coach-link-inline').forEach(function(link) {
					link.addEventListener('click', function(e) {
						e.preventDefault();
						e.stopPropagation();

						var stepsContainer = this.closest('.po-steps');
						if (!stepsContainer) return;

						var slides = stepsContainer.querySelectorAll('.po-steps__slide');
						var coachSlide = stepsContainer.querySelector('[data-slide="coach"]');

						if (coachSlide) {
							slides.forEach(function(s) {
								s.classList.remove('is-active');
								s.classList.add('is-next');
							});
							coachSlide.classList.remove('is-next');
							coachSlide.classList.add('is-active');
						}
					});
				});

				modal.querySelectorAll('.po-sp__back-to-overview').forEach(function(backBtn) {
					backBtn.addEventListener('click', function() {
						var stepsContainer = this.closest('.po-steps');
						if (!stepsContainer) return;

						var slides = stepsContainer.querySelectorAll('.po-steps__slide:not(.po-sp__coach-slide)');
						var coachSlide = stepsContainer.querySelector('.po-sp__coach-slide');

						if (coachSlide) {
							coachSlide.classList.remove('is-active');
						}

						slides.forEach(function(s, i) {
							s.classList.remove('is-active', 'is-prev', 'is-next');
							if (i === 0) {
								s.classList.add('is-active');
							} else {
								s.classList.add('is-next');
							}
						});

						stepsContainer.setAttribute('data-step', '0');
					});
				});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
