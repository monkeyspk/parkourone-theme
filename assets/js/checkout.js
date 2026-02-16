/**
 * ParkourONE Checkout
 * - Move plugin-rendered referrer field into Sonstiges section
 * - Coupon form AJAX handler
 * - Remove coupon handler
 * - Mobile collapsible order summary
 * - Validation error modal
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		initReferrerField();
		initCouponForm();
		initMobileOrderSummary();
		initErrorModal();
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
					// Show notice inside the coupon section
					showCouponNotice(response);
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
					showCouponNotice(response);
					jQuery(document.body).trigger('update_checkout', { update_shipping_method: false });
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});
	}

	/**
	 * Show coupon notice inside the order summary sidebar
	 */
	function showCouponNotice(html) {
		// Remove any existing coupon notice
		jQuery('.po-checkout-summary__notice').remove();

		if (!html) return;

		var notice = jQuery('<div class="po-checkout-summary__notice"></div>').html(html);
		var couponSection = jQuery('.po-checkout-summary__coupon');

		if (couponSection.length) {
			couponSection.prepend(notice);

			// Auto-remove after 6 seconds
			setTimeout(function () {
				notice.fadeOut(300, function () {
					notice.remove();
				});
			}, 6000);
		}
	}

	/**
	 * Validation error modal — intercepts WooCommerce checkout errors
	 * and shows them in a centered modal instead of inline at the top of the form.
	 */
	function initErrorModal() {
		// Create modal shell
		var modal = document.createElement('div');
		modal.className = 'po-error-modal';
		modal.setAttribute('aria-hidden', 'true');
		modal.innerHTML =
			'<div class="po-error-modal__backdrop"></div>' +
			'<div class="po-error-modal__dialog" role="alertdialog" aria-label="Fehler">' +
				'<div class="po-error-modal__header">' +
					'<svg class="po-error-modal__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
						'<circle cx="12" cy="12" r="10"></circle>' +
						'<line x1="12" y1="8" x2="12" y2="12"></line>' +
						'<line x1="12" y1="16" x2="12.01" y2="16"></line>' +
					'</svg>' +
					'<span class="po-error-modal__title">Bitte prüfe deine Eingaben</span>' +
				'</div>' +
				'<div class="po-error-modal__body"></div>' +
				'<button type="button" class="po-error-modal__close">Verstanden</button>' +
			'</div>';
		document.body.appendChild(modal);

		var backdrop = modal.querySelector('.po-error-modal__backdrop');
		var closeBtn = modal.querySelector('.po-error-modal__close');
		var body = modal.querySelector('.po-error-modal__body');

		function closeModal() {
			modal.setAttribute('aria-hidden', 'true');
			document.body.style.overflow = '';
		}

		backdrop.addEventListener('click', closeModal);
		closeBtn.addEventListener('click', closeModal);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
				closeModal();
			}
		});

		// Intercept WooCommerce checkout errors
		jQuery(document.body).on('checkout_error', function () {
			var noticeGroup = document.querySelector('.woocommerce-NoticeGroup-checkout');
			if (!noticeGroup) return;

			var errorList = noticeGroup.querySelector('.woocommerce-error');
			if (!errorList) return;

			// Clone the error list into our modal
			body.innerHTML = '';
			body.appendChild(errorList.cloneNode(true));

			// Hide inline notices
			noticeGroup.style.display = 'none';

			// Show modal
			modal.setAttribute('aria-hidden', 'false');
			document.body.style.overflow = 'hidden';
			closeBtn.focus();
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
