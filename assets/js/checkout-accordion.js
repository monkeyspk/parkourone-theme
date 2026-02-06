/**
 * Checkout Accordion – toggle sections open/closed
 * + move plugin-rendered referrer field into Sonstiges section
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		// Accordion toggle
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

		// Move plugin-rendered #referrer_field into Sonstiges accordion
		var referrerField = document.getElementById('referrer_field');
		var placeholder = document.getElementById('po-referrer-placeholder');

		if (referrerField && placeholder) {
			placeholder.appendChild(referrerField);
			referrerField.style.display = '';
		}
	});
})();
