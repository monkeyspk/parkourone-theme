/**
 * Shared Overlay Handler
 * Generic modal handling for all .po-overlay modals via event delegation.
 * Compatible with booking.js (which only handles form-reset/step-navigation).
 */
(function() {
	'use strict';

	// Open modal via [data-modal-target] clicks
	document.addEventListener('click', function(e) {
		var trigger = e.target.closest('[data-modal-target]');
		if (!trigger) return;

		var modalId = trigger.getAttribute('data-modal-target');
		var modal = document.getElementById(modalId);
		if (!modal) return;

		e.preventDefault();
		modal.classList.add('is-active');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('po-no-scroll');
	});

	// Close via .po-overlay__close button
	document.addEventListener('click', function(e) {
		var closeBtn = e.target.closest('.po-overlay__close');
		if (!closeBtn) return;

		var modal = closeBtn.closest('.po-overlay');
		if (!modal) return;

		e.preventDefault();
		closeOverlay(modal);
	});

	// Close via .po-overlay__backdrop click
	document.addEventListener('click', function(e) {
		if (!e.target.classList.contains('po-overlay__backdrop')) return;

		var modal = e.target.closest('.po-overlay');
		if (!modal) return;

		closeOverlay(modal);
	});

	// Close topmost overlay on Escape
	document.addEventListener('keydown', function(e) {
		if (e.key !== 'Escape') return;

		var activeOverlays = document.querySelectorAll('.po-overlay.is-active');
		if (activeOverlays.length === 0) return;

		// Close the last (topmost) active overlay
		var topmost = activeOverlays[activeOverlays.length - 1];
		closeOverlay(topmost);
	});

	function closeOverlay(modal) {
		modal.classList.remove('is-active');
		modal.setAttribute('aria-hidden', 'true');

		// Only remove po-no-scroll if no other overlay is active
		var stillActive = document.querySelectorAll('.po-overlay.is-active');
		if (stillActive.length === 0) {
			document.body.classList.remove('po-no-scroll');
		}
	}

	// ========================================
	// DEEP-LINK: Auto-open modals via URL params
	// ========================================

	function openOverlay(modal) {
		if (!modal) return;
		modal.classList.add('is-active');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('po-no-scroll');
	}

	function handleDeepLinks() {
		var params = new URLSearchParams(window.location.search);

		// ?coach={post_id} — open coach/team modal
		var coachId = params.get('coach');
		if (coachId) {
			openOverlay(document.querySelector('[id$="-modal-' + coachId + '"].po-overlay'));
		}

		// ?job={index} — open job detail modal
		var jobId = params.get('job');
		if (jobId) {
			openOverlay(document.querySelector('[id$="-modal-' + jobId + '"].po-overlay .po-job-modal')
				? document.querySelector('[id$="-modal-' + jobId + '"].po-overlay')
				: null);
		}

		// ?training={event_id} — open training/probetraining modal
		var trainingId = params.get('training');
		if (trainingId) {
			// Try po-overlay modals (event-day-slider, stundenplan, team-grid booking)
			var trainingModal = document.querySelector('[id$="-modal-' + trainingId + '"].po-overlay');
			if (trainingModal) {
				openOverlay(trainingModal);
			} else {
				// Fallback: try event-booking accordion (?klasse= style)
				var bookingBtns = document.querySelectorAll('.po-eds__card[data-modal-target$="-modal-' + trainingId + '"]');
				if (bookingBtns.length) bookingBtns[0].click();
			}
		}

		// ?angebot={post_id} — open angebot modal (grid or karussell)
		var angebotId = params.get('angebot');
		if (angebotId) {
			tryOpenAngebot(angebotId, 0);
		}
	}

	// Retry mechanism for angebot deep links (cards may initialize async)
	function tryOpenAngebot(angebotId, attempt) {
		var cards = document.querySelectorAll('[data-modal]');
		for (var i = 0; i < cards.length; i++) {
			try {
				var data = JSON.parse(cards[i].dataset.modal);
				if (String(data.id) === String(angebotId)) {
					cards[i].click();
					return;
				}
			} catch(e) {}
		}
		// Retry up to 5 times with increasing delay (cards may not have click handlers yet)
		if (attempt < 5) {
			setTimeout(function() { tryOpenAngebot(angebotId, attempt + 1); }, 300);
		}
	}

	// Run after DOM is ready and blocks have initialized
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			setTimeout(handleDeepLinks, 500);
		});
	} else {
		setTimeout(handleDeepLinks, 500);
	}
})();
