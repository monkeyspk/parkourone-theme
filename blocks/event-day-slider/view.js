(function($) {
	'use strict';

	var sections = document.querySelectorAll('.po-eds');
	if (!sections.length) return;

	sections.forEach(function(section) {
		initEventDaySlider(section);
	});

	function initEventDaySlider(section) {
		// DOM Refs
		var dateTrack   = section.querySelector('[data-date-track]');
		var sliderTrack = section.querySelector('[data-slider-track]');
		var skeleton    = section.querySelector('[data-skeleton]');
		var emptyState  = section.querySelector('[data-empty]');

		// Config aus data-Attributen
		var buttonText  = section.dataset.buttonText || 'Jetzt buchen';
		var initialDays = parseInt(section.dataset.initialDays) || 8;
		var ageColors   = {};
		try { ageColors = JSON.parse(section.dataset.ageColors || '{}'); } catch(e) {}

		// State
		var allEvents      = [];
		var filteredEvents = [];
		var eventsByDate   = {};
		var loadedDayCount = initialDays;
		var selectedDateKey = null;
		var activeBookingEl = null;

		// Filter-State
		var activeFilters = { age: '', location: '' };

		// ========================================
		// API FETCH
		// ========================================

		function loadEvents() {
			showSkeleton();

			fetch('/wp-json/events/v1/list?per_page=-1')
				.then(function(res) { return res.json(); })
				.then(function(data) {
					allEvents = data.events || [];
					applyFiltersAndRender();
					hideSkeleton();
				})
				.catch(function(err) {
					console.error('Event-Day-Slider: Fehler beim Laden', err);
					hideSkeleton();
					emptyState.style.display = 'block';
				});
		}

		// ========================================
		// FILTERING (client-side)
		// ========================================

		function applyFiltersAndRender() {
			filteredEvents = allEvents.filter(function(ev) {
				var matchAge = !activeFilters.age ||
					(ev.categories && ev.categories.indexOf(activeFilters.age) !== -1);
				var matchLocation = !activeFilters.location ||
					(ev.categories && ev.categories.indexOf(activeFilters.location) !== -1);
				return matchAge && matchLocation;
			});

			groupByDate();
			renderDateTabs();
			renderDayCards();
		}

		// ========================================
		// GRUPPIERUNG NACH DATUM
		// ========================================

		function groupByDate() {
			eventsByDate = {};
			filteredEvents.forEach(function(ev) {
				if (!ev.date) return;
				if (!eventsByDate[ev.date]) {
					eventsByDate[ev.date] = [];
				}
				eventsByDate[ev.date].push(ev);
			});

			// Events innerhalb jedes Tages nach start_time sortieren
			Object.keys(eventsByDate).forEach(function(dateKey) {
				eventsByDate[dateKey].sort(function(a, b) {
					return (a.start_time || '').localeCompare(b.start_time || '');
				});
			});
		}

		// ========================================
		// DATUMS-GENERIERUNG
		// ========================================

		function generateDateKeys(count) {
			var keys = [];
			var today = new Date();
			today.setHours(0, 0, 0, 0);

			for (var i = 0; i < count; i++) {
				var d = new Date(today);
				d.setDate(today.getDate() + i);
				var dd = String(d.getDate()).padStart(2, '0');
				var mm = String(d.getMonth() + 1).padStart(2, '0');
				var yyyy = d.getFullYear();
				keys.push(dd + '-' + mm + '-' + yyyy);
			}
			return keys;
		}

		function formatDateLabel(dateKey, index) {
			if (index === 0) return 'Heute';
			if (index === 1) return 'Morgen';
			if (index === 2) return '\u00dcbermorgen';

			var parts = dateKey.split('-');
			var d = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
			var dayNames = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
			var monthNames = ['Jan.', 'Feb.', 'M\u00e4rz', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];
			return dayNames[d.getDay()] + ', ' + d.getDate() + '. ' + monthNames[d.getMonth()];
		}

		function formatDateForBooking(dateStr) {
			if (!dateStr) return '';
			var parts = dateStr.split('-');
			if (parts.length !== 3) return dateStr;
			var d = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
			var dayNames = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
			var monthNames = ['Jan.', 'Feb.', 'M\u00e4rz', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];
			return dayNames[d.getDay()] + ', ' + d.getDate() + '. ' + monthNames[d.getMonth()] + ' ' + parts[2];
		}

		// ========================================
		// RENDER: DATE TABS
		// ========================================

		function renderDateTabs() {
			dateTrack.innerHTML = '';
			var dateKeys = generateDateKeys(loadedDayCount);

			// Auto-Select: erster Tag mit Events
			if (!selectedDateKey || !dateKeys.includes(selectedDateKey)) {
				selectedDateKey = null;
				for (var i = 0; i < dateKeys.length; i++) {
					if (eventsByDate[dateKeys[i]] && eventsByDate[dateKeys[i]].length > 0) {
						selectedDateKey = dateKeys[i];
						break;
					}
				}
				if (!selectedDateKey) selectedDateKey = dateKeys[0];
			}

			dateKeys.forEach(function(key, index) {
				var eventCount = (eventsByDate[key] || []).length;
				var isSelected = key === selectedDateKey;
				var hasEvents  = eventCount > 0;

				var tab = document.createElement('button');
				tab.type = 'button';
				tab.className = 'po-eds__date-tab' +
					(isSelected ? ' is-selected' : '') +
					(!hasEvents ? ' is-empty' : '');
				tab.setAttribute('data-date', key);

				tab.innerHTML =
					'<span class="po-eds__date-label">' + escHtml(formatDateLabel(key, index)) + '</span>' +
					(hasEvents
						? '<span class="po-eds__date-count">' + eventCount + '</span>'
						: '<span class="po-eds__date-none">\u2013\u2013</span>');

				tab.addEventListener('click', function() {
					selectedDateKey = key;
					// Update active tab visually
					dateTrack.querySelectorAll('.po-eds__date-tab').forEach(function(t) {
						t.classList.remove('is-selected');
					});
					tab.classList.add('is-selected');
					renderDayCards();
					scrollTabIntoView(tab);
				});

				dateTrack.appendChild(tab);
			});

			// "Mehr" Button
			var moreTab = document.createElement('button');
			moreTab.type = 'button';
			moreTab.className = 'po-eds__date-tab po-eds__date-more';
			moreTab.innerHTML = '<span class="po-eds__date-label">Mehr \u2192</span>';
			moreTab.addEventListener('click', function() {
				loadMoreDays();
			});
			dateTrack.appendChild(moreTab);
		}

		function loadMoreDays() {
			loadedDayCount += 7;
			renderDateTabs();
		}

		function scrollTabIntoView(tab) {
			tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
		}

		// ========================================
		// RENDER: EVENT CARDS
		// ========================================

		function renderDayCards() {
			sliderTrack.innerHTML = '';
			closeBookingForm();

			var events = eventsByDate[selectedDateKey] || [];

			if (events.length === 0) {
				sliderTrack.innerHTML =
					'<div class="po-eds__no-events">' +
						'<p>Keine Trainings an diesem Tag.</p>' +
					'</div>';
				emptyState.style.display = 'none';
				return;
			}

			emptyState.style.display = 'none';

			events.forEach(function(ev) {
				// Farbe bestimmen
				var dotColor = '#0066cc';
				if (ev.categories && ev.categories.length) {
					for (var c = 0; c < ev.categories.length; c++) {
						if (ageColors[ev.categories[c]]) {
							dotColor = ageColors[ev.categories[c]];
							break;
						}
					}
				}

				var stock = parseInt(ev.stock) || 0;
				var isSoldOut = stock <= 0;
				var timeText = ev.start_time || '';
				if (ev.end_time) timeText += ' \u2013 ' + ev.end_time;

				// Stock-Anzeige
				var stockHtml = '';
				if (isSoldOut) {
					stockHtml = '<span class="po-eds__card-stock po-eds__card-stock--none">Ausgebucht</span>';
				} else if (stock <= 3) {
					stockHtml = '<span class="po-eds__card-stock po-eds__card-stock--low">' +
						stock + (stock === 1 ? ' Platz' : ' Pl\u00e4tze') + '</span>';
				} else {
					stockHtml = '<span class="po-eds__card-stock">' +
						stock + ' Pl\u00e4tze</span>';
				}

				// Coach-Bild (falls vorhanden)
				var imgHtml = '';
				if (ev.headcoach_image) {
					imgHtml = '<img src="' + escHtml(ev.headcoach_image) + '" alt="' +
						escHtml(ev.headcoach || '') + '" class="po-eds__card-img">';
				}

				// Action: Button oder Ausgebucht
				var actionHtml = isSoldOut
					? '<span class="po-eds__card-soldout">Ausgebucht</span>'
					: '<button type="button" class="po-eds__card-btn" ' +
					  'data-product-id="' + escHtml(ev.product_id) + '" ' +
					  'data-event-id="' + escHtml(ev.id) + '" ' +
					  'data-date-text="' + escHtml(formatDateForBooking(ev.date)) + '">' +
					  escHtml(buttonText) + '</button>';

				var card = document.createElement('div');
				card.className = 'po-eds__card' + (isSoldOut ? ' is-soldout' : '');

				card.innerHTML =
					'<div class="po-eds__card-header">' +
						imgHtml +
						'<div class="po-eds__card-content">' +
							'<span class="po-eds__card-time">' + escHtml(timeText) + '</span>' +
							'<span class="po-eds__card-title" style="color:' + escHtml(dotColor) + '">' +
								escHtml(ev.title) + '</span>' +
							(ev.headcoach
								? '<span class="po-eds__card-coach">' + escHtml(ev.headcoach) + '</span>'
								: '') +
							(ev.venue
								? '<span class="po-eds__card-venue">' + escHtml(ev.venue) + '</span>'
								: '') +
						'</div>' +
					'</div>' +
					'<div class="po-eds__card-footer">' +
						stockHtml +
						actionHtml +
					'</div>';

				// Booking Button Event
				var btn = card.querySelector('.po-eds__card-btn');
				if (btn) {
					btn.addEventListener('click', function() {
						openBookingForm(this, card);
					});
				}

				sliderTrack.appendChild(card);
			});
		}

		// ========================================
		// INLINE BOOKING FORM
		// ========================================

		function openBookingForm(btn, card) {
			var productId = btn.dataset.productId;
			var eventId   = btn.dataset.eventId;
			var dateText  = btn.dataset.dateText;

			closeBookingForm();

			var formEl = document.createElement('div');
			formEl.className = 'po-eds__booking';
			formEl.innerHTML =
				'<div class="po-eds__booking-header">' +
					'<span class="po-eds__booking-title">Teilnehmer f\u00fcr ' + escHtml(dateText) + '</span>' +
					'<button type="button" class="po-eds__booking-close" aria-label="Schlie\u00dfen">' +
						'<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
					'</button>' +
				'</div>' +
				'<form class="po-eds__booking-form">' +
					'<input type="hidden" name="product_id" value="' + escHtml(productId) + '">' +
					'<input type="hidden" name="event_id" value="' + escHtml(eventId) + '">' +
					'<div class="po-eds__booking-fields">' +
						'<div class="po-eds__booking-field">' +
							'<label>Vorname</label>' +
							'<input type="text" name="vorname" required autocomplete="given-name">' +
						'</div>' +
						'<div class="po-eds__booking-field">' +
							'<label>Nachname</label>' +
							'<input type="text" name="name" required autocomplete="family-name">' +
						'</div>' +
						'<div class="po-eds__booking-field">' +
							'<label>Geburtsdatum</label>' +
							'<input type="date" name="geburtsdatum" required>' +
						'</div>' +
					'</div>' +
					'<button type="submit" class="po-eds__booking-submit">Zum Warenkorb hinzuf\u00fcgen</button>' +
				'</form>';

			card.after(formEl);
			activeBookingEl = formEl;

			formEl.querySelector('.po-eds__booking-close').addEventListener('click', function() {
				closeBookingForm();
			});

			var form = formEl.querySelector('.po-eds__booking-form');
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				submitBooking(form, formEl, dateText);
			});

			var firstInput = formEl.querySelector('input[name="vorname"]');
			if (firstInput) {
				setTimeout(function() { firstInput.focus(); }, 100);
			}

			setTimeout(function() {
				formEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}, 50);
		}

		function closeBookingForm() {
			if (activeBookingEl) {
				activeBookingEl.remove();
				activeBookingEl = null;
			}
		}

		function submitBooking(form, formEl, dateText) {
			var submitBtn = form.querySelector('.po-eds__booking-submit');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Wird hinzugef\u00fcgt\u2026';

			var data = {
				action: 'po_add_to_cart',
				nonce: poBooking.nonce,
				product_id: form.querySelector('[name="product_id"]').value,
				event_id: form.querySelector('[name="event_id"]').value,
				vorname: form.querySelector('[name="vorname"]').value,
				name: form.querySelector('[name="name"]').value,
				geburtsdatum: form.querySelector('[name="geburtsdatum"]').value
			};

			$.post(poBooking.ajaxUrl, data, function(response) {
				if (response.success) {
					showBookingSuccess(formEl, dateText);

					setTimeout(function() {
						closeBookingForm();
						$(document.body).trigger('wc_fragment_refresh');
						$(document.body).trigger('added_to_cart');
					}, 2000);
				} else {
					alert(response.data && response.data.message ? response.data.message : 'Ein Fehler ist aufgetreten');
					submitBtn.disabled = false;
					submitBtn.textContent = 'Zum Warenkorb hinzuf\u00fcgen';
				}
			}).fail(function() {
				alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
				submitBtn.disabled = false;
				submitBtn.textContent = 'Zum Warenkorb hinzuf\u00fcgen';
			});
		}

		function showBookingSuccess(formEl, dateText) {
			formEl.innerHTML =
				'<div class="po-eds__booking-success">' +
					'<div class="po-eds__booking-success-icon">' +
						'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>' +
					'</div>' +
					'<span class="po-eds__booking-success-text">Hinzugef\u00fcgt! \u2013 ' + escHtml(dateText) + '</span>' +
				'</div>';
		}

		// ========================================
		// FILTER LOGIC
		// ========================================

		var dropdowns = section.querySelectorAll('.po-eds__dropdown');

		dropdowns.forEach(function(dropdown) {
			var trigger    = dropdown.querySelector('.po-eds__dropdown-trigger');
			var valueEl    = dropdown.querySelector('.po-eds__dropdown-value');
			var options    = dropdown.querySelectorAll('.po-eds__dropdown-option');
			var filterType = dropdown.dataset.filterType;

			trigger.addEventListener('click', function(e) {
				e.stopPropagation();
				var isOpen = dropdown.classList.contains('is-open');

				dropdowns.forEach(function(d) {
					d.classList.remove('is-open');
					d.querySelector('.po-eds__dropdown-trigger').setAttribute('aria-expanded', 'false');
				});

				if (!isOpen) {
					dropdown.classList.add('is-open');
					trigger.setAttribute('aria-expanded', 'true');
				}
			});

			options.forEach(function(option) {
				option.addEventListener('click', function(e) {
					e.stopPropagation();
					var value = option.dataset.value;

					options.forEach(function(o) { o.classList.remove('is-selected'); });
					option.classList.add('is-selected');
					valueEl.textContent = option.textContent.trim();

					activeFilters[filterType] = value;

					dropdown.classList.remove('is-open');
					trigger.setAttribute('aria-expanded', 'false');

					// Reset selectedDateKey bei Filteraenderung
					selectedDateKey = null;
					applyFiltersAndRender();
				});
			});
		});

		document.addEventListener('click', function() {
			dropdowns.forEach(function(d) {
				d.classList.remove('is-open');
				d.querySelector('.po-eds__dropdown-trigger').setAttribute('aria-expanded', 'false');
			});
		});

		// ========================================
		// HELPERS
		// ========================================

		function showSkeleton() {
			skeleton.style.display = '';
			section.querySelector('.po-eds__date-nav').style.display = 'none';
			section.querySelector('.po-eds__slider').style.display = 'none';
			emptyState.style.display = 'none';
		}

		function hideSkeleton() {
			skeleton.style.display = 'none';
			section.querySelector('.po-eds__date-nav').style.display = '';
			section.querySelector('.po-eds__slider').style.display = '';
		}

		function escHtml(str) {
			if (!str) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(String(str)));
			return div.innerHTML;
		}

		// ========================================
		// INIT
		// ========================================
		loadEvents();
	}

})(jQuery);
