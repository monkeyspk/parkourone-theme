/**
 * ParkourONE Promo Popup
 * Slide-in mit Delay, Dismiss & localStorage
 */

(function() {
	'use strict';

	var STORAGE_PREFIX = 'po_promo_dismissed_v';

	console.log('[Promo Popup] Script loaded');

	var popup = document.querySelector('.po-promo-popup');
	if (!popup) {
		console.log('[Promo Popup] No .po-promo-popup element found in DOM');
		return;
	}

	var version = popup.getAttribute('data-version') || '1';
	var delay = parseInt(popup.getAttribute('data-delay'), 10) || 5;
	var storageKey = STORAGE_PREFIX + version;

	console.log('[Promo Popup] Found popup — version:', version, 'delay:', delay + 's', 'storageKey:', storageKey);

	// Alte Versionen aufräumen
	try {
		for (var i = 0; i < localStorage.length; i++) {
			var key = localStorage.key(i);
			if (key && key.indexOf(STORAGE_PREFIX) === 0 && key !== storageKey) {
				console.log('[Promo Popup] Removing old key:', key);
				localStorage.removeItem(key);
				i--; // Index verschiebt sich nach removeItem
			}
		}
	} catch (e) {
		console.warn('[Promo Popup] localStorage cleanup failed:', e);
	}

	// Bereits dismissed?
	try {
		if (localStorage.getItem(storageKey)) {
			console.log('[Promo Popup] Already dismissed (localStorage key exists)');
			return;
		}
	} catch (e) {
		console.warn('[Promo Popup] localStorage read failed:', e);
	}

	console.log('[Promo Popup] Will show in', delay, 'seconds');

	// Nach Delay anzeigen
	var showTimer = setTimeout(function() {
		console.log('[Promo Popup] Showing now');
		popup.style.display = 'block';
		// Layout erzwingen vor der Animation
		popup.offsetHeight; // eslint-disable-line no-unused-expressions
		popup.classList.add('is-visible');
	}, delay * 1000);

	// Schliessen-Funktion
	function dismiss() {
		console.log('[Promo Popup] Dismissed');
		popup.classList.remove('is-visible');
		setTimeout(function() {
			popup.style.display = 'none';
		}, 400);

		try {
			localStorage.setItem(storageKey, '1');
		} catch (e) {
			// localStorage voll oder nicht verfügbar
		}

		// Timer aufräumen falls Popup vor Delay geschlossen wird
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
})();
