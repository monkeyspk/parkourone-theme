(function() {
	'use strict';

	function init() {
		document.querySelectorAll('.po-tg').forEach(function(section) {
			if (section.dataset.poInit) return;
			section.dataset.poInit = 'true';

			var fab = section.querySelector('.po-tg__filter-fab');
			var trigger = section.querySelector('.po-tg__filter-trigger');
			var filterText = section.querySelector('.po-tg__filter-text');
			var options = section.querySelectorAll('.po-tg__filter-option');
			var cards = section.querySelectorAll('.po-tg__card');

			// ========================================
			// LOCATION FILTER (FAB)
			// ========================================
			if (fab && trigger) {
				var observer = new IntersectionObserver(function(entries) {
					entries.forEach(function(entry) {
						if (entry.isIntersecting) {
							fab.classList.add('is-visible');
						} else {
							fab.classList.remove('is-visible');
							fab.classList.remove('is-open');
						}
					});
				}, { threshold: 0.05, rootMargin: '-20% 0px -20% 0px' });

				observer.observe(section);

				trigger.addEventListener('click', function(e) {
					e.stopPropagation();
					fab.classList.toggle('is-open');
				});

				document.addEventListener('click', function(e) {
					if (!fab.contains(e.target)) {
						fab.classList.remove('is-open');
					}
				});

				options.forEach(function(option) {
					option.addEventListener('click', function() {
						options.forEach(function(o) { o.classList.remove('is-active'); });
						this.classList.add('is-active');

						var filterValue = this.getAttribute('data-filter');
						var filterName = this.textContent;

						if (filterValue === 'all') {
							filterText.textContent = 'Standort filtern';
						} else {
							filterText.textContent = filterName;
						}

						cards.forEach(function(card) {
							var locations = card.getAttribute('data-locations') || '';
							if (filterValue === 'all' || locations.indexOf(filterValue) !== -1) {
								card.style.display = '';
							} else {
								card.style.display = 'none';
							}
						});

						fab.classList.remove('is-open');
					});
				});
			}

			// ========================================
			// BOOKING TRIGGERS (inside coach modals → open booking overlay)
			// Modal open/close is handled by shared overlay-handler.
			// We only need to wire up the booking triggers inside coach modals.
			// ========================================
			var sectionId = section.id;
			var buttons = section.querySelectorAll('[data-modal-target]');

			buttons.forEach(function(btn) {
				var modalId = btn.getAttribute('data-modal-target');
				var modal = document.getElementById(modalId);
				if (!modal) return;

				modal.querySelectorAll('.po-tg-coach__booking-trigger').forEach(function(bookingTrigger) {
					bookingTrigger.addEventListener('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
						var index = this.getAttribute('data-training-index');
						var coachId = modal.id.split('-modal-')[1];
						var bookingModalId = sectionId.replace('team-grid', 'team-grid') + '-booking-' + coachId + '-' + index;

						// The booking modal ID follows the pattern: {unique_id}-booking-{coachId}-{tindex}
						// unique_id is the section's ID (or the anchor)
						var prefix = modal.id.split('-modal-')[0];
						bookingModalId = prefix + '-booking-' + coachId + '-' + index;

						var bookingModal = document.getElementById(bookingModalId);
						if (bookingModal) {
							bookingModal.classList.add('is-active');
							bookingModal.setAttribute('aria-hidden', 'false');
							document.body.classList.add('po-no-scroll');
						}
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
