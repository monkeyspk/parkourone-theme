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
		hidePluginReferralField();
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
	 * Hide plugin "Wie haben Sie von uns erfahren" field and remove required
	 */
	function hidePluginReferralField() {
		// Find all fields that contain "Wie haben Sie" or similar plugin fields
		var allLabels = document.querySelectorAll('label, legend, .form-row label');
		allLabels.forEach(function(label) {
			var text = label.textContent || '';
			if (text.indexOf('Wie haben Sie') !== -1 || text.indexOf('How did you') !== -1) {
				// Find the parent wrapper (p.form-row or div)
				var wrapper = label.closest('p, div.form-row, .form-row');
				if (wrapper) {
					wrapper.style.display = 'none';
					// Remove required from all inputs/selects inside
					var inputs = wrapper.querySelectorAll('input, select, textarea');
					inputs.forEach(function(input) {
						input.removeAttribute('required');
						input.removeAttribute('aria-required');
						input.classList.remove('validate-required');
					});
					// Also remove validate-required from wrapper
					wrapper.classList.remove('validate-required');
				}
			}
		});

		// Also check for select2/chosen containers (tag-style fields like "Andere")
		var selects = document.querySelectorAll('select');
		selects.forEach(function(sel) {
			var options = sel.querySelectorAll('option');
			var isReferral = false;
			options.forEach(function(opt) {
				if (opt.textContent.indexOf('Andere') !== -1 || opt.textContent.indexOf('Google') !== -1) {
					// Check if this is the plugin field (not our po_referral_source)
					if (sel.name && sel.name !== 'po_referral_source' && sel.name.indexOf('referr') !== -1) {
						isReferral = true;
					}
				}
			});
			if (isReferral) {
				var wrapper = sel.closest('p, div.form-row, .form-row');
				if (wrapper) {
					wrapper.style.display = 'none';
					sel.removeAttribute('required');
					sel.removeAttribute('aria-required');
				}
			}
		});
	}

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
	 * Error Toast — slides in from right with close button.
	 * Replaces the old modal approach.
	 */
	function initErrorModal() {
		// Create toast container (fixed, top-right)
		var toast = document.createElement('div');
		toast.className = 'po-error-toast';
		toast.innerHTML =
			'<div class="po-error-toast__inner">' +
				'<div class="po-error-toast__header">' +
					'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
						'<circle cx="12" cy="12" r="10"></circle>' +
						'<line x1="12" y1="8" x2="12" y2="12"></line>' +
						'<line x1="12" y1="16" x2="12.01" y2="16"></line>' +
					'</svg>' +
					'<span class="po-error-toast__title">Bitte prüfe deine Eingaben</span>' +
					'<button type="button" class="po-error-toast__close" aria-label="Schließen">' +
						'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
							'<line x1="18" y1="6" x2="6" y2="18"></line>' +
							'<line x1="6" y1="6" x2="18" y2="18"></line>' +
						'</svg>' +
					'</button>' +
				'</div>' +
				'<div class="po-error-toast__body"></div>' +
			'</div>';
		document.body.appendChild(toast);

		var closeBtn = toast.querySelector('.po-error-toast__close');
		var body = toast.querySelector('.po-error-toast__body');
		var autoCloseTimer = null;

		function showToast() {
			toast.classList.add('is-visible');
			// Auto-close nach 15 Sekunden
			clearTimeout(autoCloseTimer);
			autoCloseTimer = setTimeout(closeToast, 15000);
		}

		function closeToast() {
			toast.classList.remove('is-visible');
			clearTimeout(autoCloseTimer);
		}

		closeBtn.addEventListener('click', closeToast);

		// Capture scroll position before WooCommerce hijacks it
		var scrollPosBeforeError = 0;
		jQuery(document).ajaxComplete(function (event, xhr, settings) {
			if (settings && settings.url && settings.url.indexOf('wc-ajax=checkout') !== -1) {
				scrollPosBeforeError = window.scrollY || window.pageYOffset;
			}
		});

		jQuery(document.body).on('checkout_error', function () {
			var noticeGroup = document.querySelector('.woocommerce-NoticeGroup-checkout');
			var errorList = noticeGroup ? noticeGroup.querySelector('.woocommerce-error') : null;

			if (!errorList) {
				errorList = document.querySelector('.woocommerce-checkout .woocommerce-error');
			}

			if (!errorList) return;

			// Fehler in den Toast klonen
			body.innerHTML = '';
			body.appendChild(errorList.cloneNode(true));

			// Inline-Notices entfernen
			if (noticeGroup) noticeGroup.remove();
			document.querySelectorAll('.woocommerce-checkout > .woocommerce-error').forEach(function(el) { el.remove(); });

			// Scroll-Position wiederherstellen
			window.scrollTo(0, scrollPosBeforeError);

			// Toast anzeigen
			showToast();
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
				'Bestellübersicht' +
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
