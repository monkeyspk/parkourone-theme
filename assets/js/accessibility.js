/**
 * ParkourONE Accessibility Utilities (WCAG 2.1 AA)
 *
 * - Focus Trap für alle Modals/Overlays
 * - Keyboard Support für Custom Dropdowns
 * - aria-live Announcements
 */
(function() {
	'use strict';

	// ========================================
	// ARIA-LIVE Announcement Region
	// ========================================
	var liveRegion = document.createElement('div');
	liveRegion.setAttribute('role', 'status');
	liveRegion.setAttribute('aria-live', 'polite');
	liveRegion.setAttribute('aria-atomic', 'true');
	liveRegion.className = 'sr-only';
	liveRegion.id = 'po-live-region';
	document.body.appendChild(liveRegion);

	window.poAnnounce = function(message) {
		liveRegion.textContent = '';
		setTimeout(function() {
			liveRegion.textContent = message;
		}, 100);
	};

	// ========================================
	// Focus Trap Utility
	// ========================================
	function getFocusableElements(container) {
		var elements = container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);
		return Array.prototype.filter.call(elements, function(el) {
			return el.offsetParent !== null && getComputedStyle(el).visibility !== 'hidden';
		});
	}

	function trapFocus(container, e) {
		if (e.key !== 'Tab') return;
		var focusable = getFocusableElements(container);
		if (focusable.length === 0) return;
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}

	// ========================================
	// Auto Focus Trap for .po-overlay
	// ========================================
	document.addEventListener('keydown', function(e) {
		// Find active overlay
		var activeOverlay = document.querySelector('.po-overlay.is-active');
		if (activeOverlay) {
			if (e.key === 'Escape') {
				var closeBtn = activeOverlay.querySelector('.po-overlay__close, [data-close], .po-modal__close');
				if (closeBtn) closeBtn.click();
				return;
			}
			trapFocus(activeOverlay, e);
		}
	});

	// ========================================
	// Keyboard Support for Custom Dropdowns
	// ========================================
	document.addEventListener('keydown', function(e) {
		var target = e.target;

		// Handle custom dropdown buttons (role=listbox pattern)
		if (target.classList.contains('po-sp__filter-btn') ||
			target.classList.contains('po-ks__filter-btn') ||
			target.classList.contains('po-booking-panel__date-select')) {

			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				target.click();
			}
		}

		// Handle dropdown options
		if (target.classList.contains('po-sp__filter-option') ||
			target.classList.contains('po-ks__filter-option')) {

			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				target.click();
			}
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				var next = target.nextElementSibling;
				if (next) next.focus();
			}
			if (e.key === 'ArrowUp') {
				e.preventDefault();
				var prev = target.previousElementSibling;
				if (prev) prev.focus();
			}
		}
	});

	// ========================================
	// Announce dynamic content updates
	// ========================================

	// Cart updates
	var cartObserver = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			if (mutation.target.dataset && mutation.target.dataset.cartCount !== undefined) {
				var count = mutation.target.textContent.trim();
				if (count && count !== '0') {
					window.poAnnounce('Warenkorb: ' + count + ' Artikel');
				}
			}
		});
	});

	var cartCount = document.querySelector('[data-cart-count]');
	if (cartCount) {
		cartObserver.observe(cartCount, { childList: true, characterData: true, subtree: true });
	}

	// ========================================
	// Reduced Motion Support
	// ========================================
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		document.documentElement.classList.add('po-reduced-motion');
	}

})();
