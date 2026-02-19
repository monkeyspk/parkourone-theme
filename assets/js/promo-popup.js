/**
 * ParkourONE Promo Popup
 * Slide-in mit Delay, Dismiss & localStorage
 */

(function() {
	'use strict';

	var STORAGE_PREFIX = 'po_promo_dismissed_v';

	function init() {
		var popup = document.querySelector('.po-promo-popup');
		if (!popup) return;

		var version = popup.getAttribute('data-version') || '1';
		var delay = parseInt(popup.getAttribute('data-delay'), 10) || 5;
		var storageKey = STORAGE_PREFIX + version;

		// Alte Versionen aufräumen
		try {
			for (var i = 0; i < localStorage.length; i++) {
				var key = localStorage.key(i);
				if (key && key.indexOf(STORAGE_PREFIX) === 0 && key !== storageKey) {
					localStorage.removeItem(key);
					i--;
				}
			}
		} catch (e) {
			// localStorage nicht verfügbar
		}

		// Bereits dismissed?
		try {
			if (localStorage.getItem(storageKey)) return;
		} catch (e) {
			// Weiter ohne localStorage
		}

		// Nach Delay anzeigen
		var showTimer = setTimeout(function() {
			popup.style.display = 'block';
			popup.offsetHeight; // Layout erzwingen vor Animation
			popup.classList.add('is-visible');
		}, delay * 1000);

		// Schliessen-Funktion
		function dismiss() {
			popup.classList.remove('is-visible');
			setTimeout(function() {
				popup.style.display = 'none';
			}, 400);

			try {
				localStorage.setItem(storageKey, '1');
			} catch (e) {
				// localStorage voll oder nicht verfügbar
			}

			clearTimeout(showTimer);
		}

		// Close-Button
		var closeBtn = popup.querySelector('.po-promo-popup__close');
		if (closeBtn) {
			closeBtn.addEventListener('click', dismiss);
		}

		// ESC-Taste
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && popup.classList.contains('is-visible')) {
				dismiss();
			}
		});
	}

	// Warten bis DOM komplett geladen (Script lädt vor dem Popup-HTML)
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
