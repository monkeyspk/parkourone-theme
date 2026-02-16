/**
 * ParkourONE Checkout
 * - Move plugin-rendered referrer field into Sonstiges section
 * - Coupon form AJAX handler
 * - Remove coupon handler
 * - Mobile collapsible order summary
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initReferrerField();
		initCouponForm();
		initMobileOrderSummary();
	});

	// Re-init mobile summary after WooCommerce updates checkout fragments
	jQuery(document.body).on('updated_checkout', function () {
		initMobileOrderSummary();
	});

	/**
	 * Move plugin-rendered #referrer_field into Sonstiges section
	 */
	function initReferrerField() {
		var referrerField = document.getElementById('referrer_field');
		var placeholder = document.getElementById('po-referrer-placeholder');

		if (referrerField && placeholder) {
			placeholder.appendChild(referrerField);
			referrerField.style.display = '';
		}
	}

	/**
	 * Inline coupon form AJAX handler
	 */
	function initCouponForm() {
		// Apply coupon
		jQuery(document).on('click', '.po-checkout-summary__coupon-btn', function () {
			var btn = jQuery(this);
			var input = btn.siblings('.po-checkout-summary__coupon-input');
			var code = input.val().trim();

			if (!code) {
				input.focus();
				return;
			}

			btn.prop('disabled', true);

			jQuery.ajax({
				type: 'POST',
				url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon'),
				data: {
					coupon_code: code,
					security: wc_checkout_params.apply_coupon_nonce
				},
				success: function (response) {
					// Show WooCommerce notices
					jQuery('.woocommerce-notices-wrapper').first().html(response);
					jQuery(document.body).trigger('update_checkout', { update_shipping_method: false });
					input.val('');
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});

		// Allow Enter key in coupon input
		jQuery(document).on('keypress', '.po-checkout-summary__coupon-input', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				jQuery(this).siblings('.po-checkout-summary__coupon-btn').trigger('click');
			}
		});

		// Remove coupon
		jQuery(document).on('click', '.po-checkout-summary__remove-coupon', function () {
			var btn = jQuery(this);
			var coupon = btn.data('coupon');

			if (!coupon) return;

			btn.prop('disabled', true);

			jQuery.ajax({
				type: 'POST',
				url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_coupon'),
				data: {
					coupon: coupon,
					security: wc_checkout_params.remove_coupon_nonce
				},
				success: function (response) {
					jQuery('.woocommerce-notices-wrapper').first().html(response);
					jQuery(document.body).trigger('update_checkout', { update_shipping_method: false });
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});
	}

	/**
	 * Mobile collapsible order summary
	 */
	function initMobileOrderSummary() {
		if (window.innerWidth > 900) {
			// Remove toggle on desktop if it exists
			var existingToggle = document.querySelector('.po-checkout-summary-toggle');
			if (existingToggle) {
				existingToggle.remove();
				var summary = document.querySelector('.po-checkout-summary');
				if (summary) {
					summary.classList.remove('po-checkout-summary--collapsed');
				}
			}
			return;
		}

		var review = document.getElementById('order_review');
		if (!review) return;

		var summary = review.querySelector('.po-checkout-summary');
		if (!summary) return;

		// Don't add toggle if it already exists
		if (review.querySelector('.po-checkout-summary-toggle')) return;

		// Get total from order table
		var totalEl = review.querySelector('.order-total td');
		var totalText = totalEl ? totalEl.textContent.trim() : '';

		// Create toggle button
		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'po-checkout-summary-toggle';
		toggle.innerHTML =
			'<span class="po-checkout-summary-toggle__text">' +
				'<svg class="po-checkout-summary-toggle__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>' +
				'Bestelluebersicht' +
			'</span>' +
			'<span class="po-checkout-summary-toggle__total">' + totalText + '</span>';

		// Insert before summary
		summary.parentNode.insertBefore(toggle, summary);

		// Default: collapsed on mobile
		summary.classList.add('po-checkout-summary--collapsed');

		toggle.addEventListener('click', function () {
			var isCollapsed = summary.classList.contains('po-checkout-summary--collapsed');
			summary.classList.toggle('po-checkout-summary--collapsed');
			toggle.classList.toggle('is-open', isCollapsed);
		});
	}
})();
