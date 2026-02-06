/**
 * Checkout Accordion – toggle sections open/closed
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var toggles = document.querySelectorAll('[data-accordion-toggle]');

		toggles.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var section = btn.closest('[data-accordion-section]');
				if (!section) return;

				var isOpen = section.classList.contains('po-accordion-section--open');

				if (isOpen) {
					section.classList.remove('po-accordion-section--open');
				} else {
					section.classList.add('po-accordion-section--open');
				}
			});
		});
	});
})();
