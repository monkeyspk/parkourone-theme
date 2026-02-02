document.addEventListener('DOMContentLoaded', function() {
	var selektor = document.querySelector('.po-klassen-selektor');
	if (!selektor) return;

	var groups = selektor.querySelectorAll('.po-klassen-group');
	var items = selektor.querySelectorAll('.po-klassen-item');
	var bookingPanel = selektor.querySelector('.po-booking-panel');
	var showBooking = selektor.dataset.showBooking === 'true';

	// Panel elements
	var placeholder = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__placeholder') : null;
	var form = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__form') : null;
	var panelTitle = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__title') : null;
	var dateSelect = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__date-select') : null;
	var detailsStep = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__step--details') : null;
	var submitBtn = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__submit') : null;
	var backBtn = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__back') : null;
	var loadingEl = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__loading') : null;
	var successEl = bookingPanel ? bookingPanel.querySelector('.po-booking-panel__success') : null;

	var selectedEvent = null;
	var selectedProductId = null;

	// Toggle accordion groups
	groups.forEach(function(group) {
		var header = group.querySelector('.po-klassen-group__header');
		var content = group.querySelector('.po-klassen-group__content');

		header.addEventListener('click', function() {
			var isExpanded = header.getAttribute('aria-expanded') === 'true';
			header.setAttribute('aria-expanded', !isExpanded);
		});
	});

	// Auto-expand first group
	var firstHeader = groups[0] ? groups[0].querySelector('.po-klassen-group__header') : null;
	if (firstHeader) {
		firstHeader.setAttribute('aria-expanded', 'true');
	}

	// Select item
	items.forEach(function(item) {
		item.addEventListener('click', function() {
			selectItem(item);
		});

		var selectBtn = item.querySelector('.po-klassen-item__select');
		if (selectBtn) {
			selectBtn.addEventListener('click', function(e) {
				e.stopPropagation();
				selectItem(item);
			});
		}
	});

	function selectItem(item) {
		// Clear previous selection
		items.forEach(function(i) { i.classList.remove('is-selected'); });
		item.classList.add('is-selected');

		if (!showBooking || !bookingPanel) return;

		selectedEvent = {
			id: item.dataset.eventId,
			title: item.dataset.eventTitle,
			dates: JSON.parse(item.dataset.eventDates || '[]')
		};

		showBookingForm();
	}

	function showBookingForm() {
		if (!form || !placeholder) return;

		placeholder.style.display = 'none';
		form.style.display = 'block';
		panelTitle.textContent = selectedEvent.title;

		// Populate dates
		dateSelect.innerHTML = '<option value="">Datum auswählen...</option>';
		selectedEvent.dates.forEach(function(date) {
			var option = document.createElement('option');
			option.value = date.product_id;
			option.textContent = date.date_formatted + ' (' + date.stock + ' Plätze frei)';
			dateSelect.appendChild(option);
		});

		// Reset form state
		detailsStep.style.display = 'none';
		submitBtn.style.display = 'none';
		loadingEl.style.display = 'none';
		successEl.style.display = 'none';
		dateSelect.value = '';

		// Show panel on mobile
		bookingPanel.classList.add('is-visible');
		bookingPanel.setAttribute('aria-hidden', 'false');
	}

	// Date selection
	if (dateSelect) {
		dateSelect.addEventListener('change', function() {
			selectedProductId = this.value;
			if (selectedProductId) {
				detailsStep.style.display = 'flex';
				submitBtn.style.display = 'block';
			} else {
				detailsStep.style.display = 'none';
				submitBtn.style.display = 'none';
			}
		});
	}

	// Back button
	if (backBtn) {
		backBtn.addEventListener('click', function() {
			resetBookingPanel();
		});
	}

	function resetBookingPanel() {
		if (!form || !placeholder) return;

		form.style.display = 'none';
		placeholder.style.display = 'block';
		bookingPanel.classList.remove('is-visible');
		bookingPanel.setAttribute('aria-hidden', 'true');

		items.forEach(function(i) { i.classList.remove('is-selected'); });
		selectedEvent = null;
		selectedProductId = null;

		// Reset form fields
		var inputs = form.querySelectorAll('input');
		inputs.forEach(function(input) { input.value = ''; });
	}

	// Submit booking
	if (submitBtn) {
		submitBtn.addEventListener('click', function() {
			if (!selectedProductId || !selectedEvent) return;

			var vorname = form.querySelector('input[name="vorname"]').value.trim();
			var name = form.querySelector('input[name="name"]').value.trim();
			var geburtsdatum = form.querySelector('input[name="geburtsdatum"]').value;

			if (!vorname || !name || !geburtsdatum) {
				alert('Bitte alle Felder ausfüllen.');
				return;
			}

			// Show loading
			detailsStep.style.display = 'none';
			submitBtn.style.display = 'none';
			loadingEl.style.display = 'block';

			// AJAX request
			var formData = new FormData();
			formData.append('action', 'po_add_to_cart');
			formData.append('nonce', window.poKlassenBooking.nonce);
			formData.append('product_id', selectedProductId);
			formData.append('event_id', selectedEvent.id);
			formData.append('vorname', vorname);
			formData.append('name', name);
			formData.append('geburtsdatum', geburtsdatum);

			fetch(window.poKlassenBooking.ajaxUrl, {
				method: 'POST',
				body: formData
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				loadingEl.style.display = 'none';

				if (data.success) {
					successEl.style.display = 'block';

					// Trigger side cart
					setTimeout(function() {
						if (typeof jQuery !== 'undefined') {
							jQuery(document.body).trigger('wc_fragment_refresh');
							jQuery(document.body).trigger('added_to_cart');
						}

						// XOO Side Cart
						if (typeof xoo_wsc_params !== 'undefined') {
							jQuery(document.body).trigger('xoo_wsc_cart_updated');
						}

						// Woodmart
						if (typeof woodmart_settings !== 'undefined' && woodmart_settings.cart_widget) {
							jQuery(document.body).trigger('woodmart-cart-fragments-loaded');
						}

						// Reset after delay
						setTimeout(function() {
							resetBookingPanel();
						}, 2000);
					}, 800);
				} else {
					alert(data.data.message || 'Ein Fehler ist aufgetreten.');
					detailsStep.style.display = 'flex';
					submitBtn.style.display = 'block';
				}
			})
			.catch(function(error) {
				console.error('Booking error:', error);
				loadingEl.style.display = 'none';
				detailsStep.style.display = 'flex';
				submitBtn.style.display = 'block';
				alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
			});
		});
	}

	// Mobile: Close panel on overlay click
	if (window.innerWidth <= 1024 && bookingPanel) {
		document.addEventListener('click', function(e) {
			if (bookingPanel.classList.contains('is-visible') &&
				!bookingPanel.contains(e.target) &&
				!e.target.closest('.po-klassen-item')) {
				resetBookingPanel();
			}
		});
	}
});
