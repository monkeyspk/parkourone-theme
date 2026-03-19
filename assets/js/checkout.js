/**
 * ParkourONE Checkout
 * - Hide plugin referral field
 * - Move plugin-rendered referrer field into Sonstiges section
 * - Coupon form AJAX handler
 * - Mobile collapsible order summary
 * - Inline field errors + fixed error bar
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		hidePluginReferralField();
		initReferrerField();
		initCouponForm();
		initMobileOrderSummary();
		initInlineErrors();
	});

	// Re-init mobile summary after WooCommerce updates checkout fragments
	jQuery(document.body).on('updated_checkout', function () {
		initMobileOrderSummary();
	});

	/**
	 * Hide plugin "Wie haben Sie von uns erfahren" field and remove required
	 */
	function hidePluginReferralField() {
		var allLabels = document.querySelectorAll('label, legend, .form-row label');
		allLabels.forEach(function(label) {
			var text = label.textContent || '';
			if (text.indexOf('Wie haben Sie') !== -1 || text.indexOf('How did you') !== -1) {
				var wrapper = label.closest('p, div.form-row, .form-row');
				if (wrapper) {
					wrapper.style.display = 'none';
					var inputs = wrapper.querySelectorAll('input, select, textarea');
					inputs.forEach(function(input) {
						input.removeAttribute('required');
						input.removeAttribute('aria-required');
						input.classList.remove('validate-required');
					});
					wrapper.classList.remove('validate-required');
				}
			}
		});

		var selects = document.querySelectorAll('select');
		selects.forEach(function(sel) {
			var options = sel.querySelectorAll('option');
			var isReferral = false;
			options.forEach(function(opt) {
				if (opt.textContent.indexOf('Andere') !== -1 || opt.textContent.indexOf('Google') !== -1) {
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
					showCouponNotice(response);
					jQuery(document.body).trigger('update_checkout', { update_shipping_method: false });
					input.val('');
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});

		jQuery(document).on('keypress', '.po-checkout-summary__coupon-input', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				jQuery(this).siblings('.po-checkout-summary__coupon-btn').trigger('click');
			}
		});

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

	function showCouponNotice(html) {
		jQuery('.po-checkout-summary__notice').remove();

		if (!html) return;

		var notice = jQuery('<div class="po-checkout-summary__notice"></div>').html(html);
		var couponSection = jQuery('.po-checkout-summary__coupon');

		if (couponSection.length) {
			couponSection.prepend(notice);

			setTimeout(function () {
				notice.fadeOut(300, function () {
					notice.remove();
				});
			}, 6000);
		}
	}

	/**
	 * Inline field errors + fixed error bar at top.
	 * Parses WooCommerce AJAX error response directly and maps errors to fields.
	 */
	function initInlineErrors() {
		// Create fixed error bar (hidden by default)
		var errorBar = document.createElement('div');
		errorBar.className = 'po-error-bar';
		errorBar.innerHTML =
			'<div class="po-error-bar__inner">' +
				'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
					'<circle cx="12" cy="12" r="10"></circle>' +
					'<line x1="12" y1="8" x2="12" y2="12"></line>' +
					'<line x1="12" y1="16" x2="12.01" y2="16"></line>' +
				'</svg>' +
				'<span class="po-error-bar__text"></span>' +
				'<button type="button" class="po-error-bar__close" aria-label="Schließen">' +
					'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
						'<line x1="18" y1="6" x2="6" y2="18"></line>' +
						'<line x1="6" y1="6" x2="18" y2="18"></line>' +
					'</svg>' +
				'</button>' +
			'</div>';
		document.body.appendChild(errorBar);

		errorBar.querySelector('.po-error-bar__close').addEventListener('click', function() {
			errorBar.classList.remove('is-visible');
		});

		// Field name mapping: WooCommerce error text → field ID
		var fieldMap = {
			'vorname': 'billing_first_name',
			'first_name': 'billing_first_name',
			'nachname': 'billing_last_name',
			'last_name': 'billing_last_name',
			'e-mail': 'billing_email',
			'email': 'billing_email',
			'telefon': 'billing_phone',
			'phone': 'billing_phone',
			'straße': 'billing_address_1',
			'hausnummer': 'billing_address_1',
			'address': 'billing_address_1',
			'postleitzahl': 'billing_postcode',
			'plz': 'billing_postcode',
			'postcode': 'billing_postcode',
			'ort': 'billing_city',
			'stadt': 'billing_city',
			'city': 'billing_city',
			'geschäftsbedingungen': 'terms',
			'agb': 'terms'
		};

		function clearInlineErrors() {
			document.querySelectorAll('.po-field-error').forEach(function(el) { el.remove(); });
			document.querySelectorAll('.po-field--has-error').forEach(function(el) {
				el.classList.remove('po-field--has-error');
			});
		}

		function showInlineError(fieldId, message) {
			var field = document.getElementById(fieldId + '_field') || document.getElementById(fieldId);
			if (!field) return;

			field.classList.add('po-field--has-error');

			// Fehlertext unter dem Feld anzeigen
			var existing = field.querySelector('.po-field-error');
			if (existing) existing.remove();

			var errorEl = document.createElement('span');
			errorEl.className = 'po-field-error';
			errorEl.textContent = message;
			field.appendChild(errorEl);
		}

		function matchFieldFromError(errorText) {
			var lower = errorText.toLowerCase();
			for (var keyword in fieldMap) {
				if (lower.indexOf(keyword) !== -1) {
					return fieldMap[keyword];
				}
			}
			return null;
		}

		function handleErrors(errorMessages) {
			clearInlineErrors();

			if (!errorMessages || errorMessages.length === 0) return;

			var unmatchedErrors = [];
			var matchedCount = 0;

			errorMessages.forEach(function(msg) {
				var fieldId = matchFieldFromError(msg);
				if (fieldId) {
					showInlineError(fieldId, msg);
					matchedCount++;
				} else {
					unmatchedErrors.push(msg);
				}
			});

			// Error bar oben anzeigen
			var totalErrors = errorMessages.length;
			var barText = errorBar.querySelector('.po-error-bar__text');

			if (unmatchedErrors.length > 0) {
				barText.innerHTML = unmatchedErrors.join(' &bull; ');
			} else {
				barText.textContent = totalErrors === 1
					? 'Bitte prüfe das markierte Feld'
					: 'Bitte prüfe die ' + totalErrors + ' markierten Felder';
			}

			errorBar.classList.add('is-visible');

			// Zum ersten Fehler-Feld scrollen
			var firstError = document.querySelector('.po-field--has-error');
			if (firstError) {
				firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}

		// AJAX-Response direkt abfangen — zuverlässigste Methode
		jQuery(document).ajaxComplete(function(event, xhr, settings) {
			if (!settings || !settings.url) return;
			if (settings.url.indexOf('wc-ajax=checkout') === -1) return;

			var response;
			try {
				response = JSON.parse(xhr.responseText);
			} catch(e) {
				return;
			}

			if (response.result !== 'failure' || !response.messages) return;

			// WooCommerce liefert HTML — Fehler-Texte extrahieren
			var tmp = document.createElement('div');
			tmp.innerHTML = response.messages;
			var errorItems = tmp.querySelectorAll('.woocommerce-error li, .woocommerce-error');
			var messages = [];

			errorItems.forEach(function(li) {
				var text = li.textContent.trim();
				if (text && messages.indexOf(text) === -1) {
					messages.push(text);
				}
			});

			// Falls keine <li> gefunden, ganzen Text nehmen
			if (messages.length === 0) {
				var fullText = tmp.textContent.trim();
				if (fullText) messages.push(fullText);
			}

			if (messages.length > 0) {
				handleErrors(messages);
			}

			// Inline-Notices aus dem DOM entfernen (werden nicht gebraucht)
			setTimeout(function() {
				document.querySelectorAll('.woocommerce-NoticeGroup-checkout').forEach(function(el) { el.remove(); });
			}, 10);
		});

		// Bei neuem Checkout-Versuch alte Fehler löschen
		jQuery('form.checkout').on('checkout_place_order', function() {
			clearInlineErrors();
			errorBar.classList.remove('is-visible');
		});
	}

	/**
	 * Mobile collapsible order summary
	 */
	function initMobileOrderSummary() {
		if (window.innerWidth > 900) {
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

		if (review.querySelector('.po-checkout-summary-toggle')) return;

		var totalEl = review.querySelector('.order-total td');
		var totalText = totalEl ? totalEl.textContent.trim() : '';

		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'po-checkout-summary-toggle';
		toggle.innerHTML =
			'<span class="po-checkout-summary-toggle__text">' +
				'<svg class="po-checkout-summary-toggle__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>' +
				'Bestellübersicht' +
			'</span>' +
			'<span class="po-checkout-summary-toggle__total">' + totalText + '</span>';

		summary.parentNode.insertBefore(toggle, summary);

		summary.classList.add('po-checkout-summary--collapsed');

		toggle.addEventListener('click', function () {
			var isCollapsed = summary.classList.contains('po-checkout-summary--collapsed');
			summary.classList.toggle('po-checkout-summary--collapsed');
			toggle.classList.toggle('is-open', isCollapsed);
		});
	}
})();
