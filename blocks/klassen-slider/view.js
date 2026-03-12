(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.po-klassen-slider').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';

			var track = section.querySelector('.po-klassen-slider__track');
			var cards = section.querySelectorAll('.po-card');
			var emptyMessage = section.querySelector('.po-klassen-slider__empty-message');

			// ========================================
			// FILTER LOGIC
			// ========================================
			var dropdowns = section.querySelectorAll('.po-klassen-slider__dropdown');
			var currentAgeFilter = 'all';
			var currentLocationFilter = 'all';

			function applyFilters() {
				var visibleCount = 0;

				cards.forEach(function(card) {
					var filters = card.getAttribute('data-filters') || '';
					var matchAge = currentAgeFilter === 'all' || filters.indexOf(currentAgeFilter) !== -1;
					var matchLocation = currentLocationFilter === 'all' || filters.indexOf(currentLocationFilter) !== -1;

					if (matchAge && matchLocation) {
						card.style.display = '';
						visibleCount++;
					} else {
						card.style.display = 'none';
					}
				});

				if (emptyMessage) {
					emptyMessage.style.display = visibleCount === 0 ? 'block' : 'none';
				}
			}

			dropdowns.forEach(function(dropdown) {
				var trigger = dropdown.querySelector('.po-klassen-slider__dropdown-trigger');
				var panel = dropdown.querySelector('.po-klassen-slider__dropdown-panel');
				var valueEl = dropdown.querySelector('.po-klassen-slider__dropdown-value');
				var options = dropdown.querySelectorAll('.po-klassen-slider__dropdown-option');
				var filterType = dropdown.getAttribute('data-filter-type');

				trigger.addEventListener('click', function(e) {
					e.stopPropagation();
					var isOpen = dropdown.classList.contains('is-open');

					dropdowns.forEach(function(d) {
						d.classList.remove('is-open');
						d.querySelector('.po-klassen-slider__dropdown-trigger').setAttribute('aria-expanded', 'false');
						d.querySelector('.po-klassen-slider__dropdown-panel').setAttribute('aria-hidden', 'true');
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
						}

						applyFilters();

						dropdown.classList.remove('is-open');
						trigger.setAttribute('aria-expanded', 'false');
						panel.setAttribute('aria-hidden', 'true');
					});
				});
			});

			document.addEventListener('click', function() {
				dropdowns.forEach(function(dropdown) {
					dropdown.classList.remove('is-open');
					dropdown.querySelector('.po-klassen-slider__dropdown-trigger').setAttribute('aria-expanded', 'false');
					dropdown.querySelector('.po-klassen-slider__dropdown-panel').setAttribute('aria-hidden', 'true');
				});
			});

			// ========================================
			// CARD CLICK → MODAL (via overlay-handler for .po-overlay)
			// Cards use data-modal-target, handled by shared overlay-handler
			// ========================================

			// ========================================
			// COACH-SLIDE NAVIGATION
			// ========================================
			section.querySelectorAll('.po-ks__coach-link-inline').forEach(function(link) {
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

			// Also handle coach links inside modals that belong to this section's cards
			cards.forEach(function(card) {
				var modalId = card.getAttribute('data-modal-target');
				if (!modalId) return;
				var modal = document.getElementById(modalId);
				if (!modal) return;

				modal.querySelectorAll('.po-ks__coach-link-inline').forEach(function(link) {
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

				modal.querySelectorAll('.po-ks__back-to-overview').forEach(function(btn) {
					btn.addEventListener('click', function() {
						var stepsContainer = this.closest('.po-steps');
						if (!stepsContainer) return;

						var slides = stepsContainer.querySelectorAll('.po-steps__slide:not(.po-ks__coach-slide)');
						var coachSlide = stepsContainer.querySelector('.po-ks__coach-slide');

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

			// ========================================
			// DRAG SCROLL
			// ========================================
			if (track) {
				var isDown = false;
				var startX;
				var scrollLeft;

				track.addEventListener('mousedown', function(e) {
					isDown = true;
					track.classList.add('is-grabbing');
					startX = e.pageX - track.offsetLeft;
					scrollLeft = track.scrollLeft;
				});

				track.addEventListener('mouseleave', function() {
					isDown = false;
					track.classList.remove('is-grabbing');
				});

				track.addEventListener('mouseup', function() {
					isDown = false;
					track.classList.remove('is-grabbing');
				});

				track.addEventListener('mousemove', function(e) {
					if (!isDown) return;
					e.preventDefault();
					var x = e.pageX - track.offsetLeft;
					var walk = (x - startX) * 1.5;
					track.scrollLeft = scrollLeft - walk;
				});
			}

			// ========================================
			// NAV BUTTONS
			// ========================================
			var prevBtn = section.querySelector('.po-klassen-slider__nav-prev');
			var nextBtn = section.querySelector('.po-klassen-slider__nav-next');

			if (prevBtn && nextBtn && track) {
				function getCardWidth() {
					var firstCard = track.querySelector('.po-card');
					if (!firstCard) return 300;
					return firstCard.offsetWidth + 20;
				}

				function getVisibleCards() {
					return Math.max(1, Math.floor(track.offsetWidth / getCardWidth()));
				}

				function updateNav() {
					var sl = track.scrollLeft;
					var maxScroll = track.scrollWidth - track.offsetWidth;
					prevBtn.disabled = sl <= 0;
					nextBtn.disabled = sl >= maxScroll - 10;
				}

				prevBtn.addEventListener('click', function() {
					track.scrollBy({ left: -getCardWidth() * getVisibleCards(), behavior: 'smooth' });
				});

				nextBtn.addEventListener('click', function() {
					track.scrollBy({ left: getCardWidth() * getVisibleCards(), behavior: 'smooth' });
				});

				track.addEventListener('scroll', updateNav);
				window.addEventListener('resize', updateNav);
				updateNav();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
