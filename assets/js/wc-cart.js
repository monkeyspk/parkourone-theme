/**
 * WooCommerce Cart - Quantity Buttons
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const cart = document.querySelector('.wc-cart');
		if (!cart) return;

		// Quantity buttons
		cart.addEventListener('click', function(e) {
			const btn = e.target.closest('.wc-cart__qty-btn');
			if (!btn) return;

			const wrapper = btn.closest('.wc-cart__qty-wrapper');
			const input = wrapper.querySelector('.wc-cart__qty-input');
			let value = parseInt(input.value, 10) || 1;
			const min = parseInt(input.min, 10) || 0;
			const max = parseInt(input.max, 10) || 999;

			if (btn.classList.contains('wc-cart__qty-minus')) {
				value = Math.max(min, value - 1);
			} else if (btn.classList.contains('wc-cart__qty-plus')) {
				value = Math.min(max, value + 1);
			}

			input.value = value;

			// Show update button
			const updateBtn = cart.querySelector('.wc-cart__update');
			if (updateBtn) {
				updateBtn.style.display = 'inline-block';
			}
		});

		// Show update button on manual input change
		cart.querySelectorAll('.wc-cart__qty-input').forEach(function(input) {
			input.addEventListener('change', function() {
				const updateBtn = cart.querySelector('.wc-cart__update');
				if (updateBtn) {
					updateBtn.style.display = 'inline-block';
				}
			});
		});
	});
})();
