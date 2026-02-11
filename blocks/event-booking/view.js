(function($) {
	'use strict';

	var sections = document.querySelectorAll('.po-eb');
	if (!sections.length) return;

	sections.forEach(function(section) {
		initEventBooking(section);
	});

	function initEventBooking(section) {
		var listContainer = section.querySelector('[data-event-list]');
		var skeleton = section.querySelector('.po-eb__skeleton');
		var emptyState = section.querySelector('.po-eb__empty');

		// Block-Attribute aus data-Attributen
		var preFilterAge = section.dataset.filterAge || '';
		var preFilterLocation = section.dataset.filterLocation || '';
		var preFilterOffer = section.dataset.filterOffer || '';
		var preFilterWeekday = section.dataset.filterWeekday || '';
		var buttonText = section.dataset.buttonText || 'Jetzt buchen';
		var ageColors = {};
		try { ageColors = JSON.parse(section.dataset.ageColors || '{}'); } catch(e) {}

		// Aktive Frontend-Filter
		var activeFilters = {
			offer: '',
			age: '',
			location: '',
			weekday: ''
		};

		// Deep-Link Support
		var urlParams = new URLSearchParams(window.location.search);
		var deepLinkKlasse = urlParams.get('klasse') || '';

		// Aktive Buchungsform tracken
		var activeBookingEl = null;

		// ========================================
		// API FETCH
		// ========================================

		function buildApiUrl() {
			var params = ['per_page=-1'];
			if (preFilterAge) params.push('age=' + encodeURIComponent(preFilterAge));
			if (preFilterLocation) params.push('location=' + encodeURIComponent(preFilterLocation));
			if (preFilterOffer) params.push('offer=' + encodeURIComponent(preFilterOffer));
			if (preFilterWeekday) params.push('weekday=' + encodeURIComponent(preFilterWeekday));

			if (activeFilters.age) params.push('age=' + encodeURIComponent(activeFilters.age));
			if (activeFilters.location) params.push('location=' + encodeURIComponent(activeFilters.location));
			if (activeFilters.offer) params.push('offer=' + encodeURIComponent(activeFilters.offer));
			if (activeFilters.weekday) params.push('weekday=' + encodeURIComponent(activeFilters.weekday));

			if (deepLinkKlasse) params.push('klasse=' + encodeURIComponent(deepLinkKlasse));

			var url = '/wp-json/events/v1/list?' + params.join('&');
			return url;
		}

		function loadEvents() {
			showSkeleton();

			fetch(buildApiUrl())
				.then(function(res) { return res.json(); })
				.then(function(data) {
					var events = data.events || [];
					var grouped = groupByKlasse(events);
					renderList(grouped);
					hideSkeleton();

					// Deep-Link: Klasse aufklappen und hinscrolled
					if (deepLinkKlasse && grouped.length >= 1) {
						var firstKlasse = listContainer.querySelector('.po-eb__klasse');
						if (firstKlasse) {
							firstKlasse.classList.add('is-open');
							setTimeout(function() {
								firstKlasse.scrollIntoView({ behavior: 'smooth', block: 'start' });
							}, 100);
						}
						deepLinkKlasse = '';
					}
				})
				.catch(function(err) {
					console.error('Event-Booking: Fehler beim Laden', err);
					hideSkeleton();
					emptyState.style.display = 'block';
				});
		}

		// ========================================
		// GRUPPIERUNG
		// ========================================

		function groupByKlasse(events) {
			var map = {};
			events.forEach(function(ev) {
				var key = ev.permalink || ev.title;
				if (!map[key]) {
					map[key] = {
						permalink: ev.permalink,
						title: ev.title,
						headcoach: ev.headcoach || '',
						description: ev.description || '',
						venue: ev.venue || '',
						categories: ev.categories || [],
						is_workshop: ev.is_workshop || false,
						events: []
					};
				}
				map[key].events.push(ev);
			});

			return Object.values(map).sort(function(a, b) {
				return a.title.localeCompare(b.title);
			});
		}

		// ========================================
		// RENDERING: GRUPPIERTE LISTE
		// ========================================

		function renderList(klassen) {
			listContainer.innerHTML = '';
			activeBookingEl = null;

			if (!klassen.length) {
				emptyState.style.display = 'block';
				return;
			}

			emptyState.style.display = 'none';

			klassen.forEach(function(klasse, index) {
				var allSoldOut = klasse.events.every(function(e) { return parseInt(e.stock) <= 0; });
				var availableCount = klasse.events.filter(function(e) { return parseInt(e.stock) > 0; }).length;
				var totalCount = klasse.events.length;

				// Altersgruppen-Farbe bestimmen
				var dotColor = '#0066cc';
				if (klasse.categories && klasse.categories.length) {
					for (var c = 0; c < klasse.categories.length; c++) {
						if (ageColors[klasse.categories[c]]) {
							dotColor = ageColors[klasse.categories[c]];
							break;
						}
					}
				}

				// Badge
				var badgeHtml = '';
				if (allSoldOut) {
					badgeHtml = '<span class="po-eb__klasse-badge po-eb__klasse-badge--soldout">Ausgebucht</span>';
				} else if (klasse.is_workshop || (klasse.categories && klasse.categories.indexOf('ferienkurs') !== -1)) {
					badgeHtml = '<span class="po-eb__klasse-badge po-eb__klasse-badge--ferienkurs">Ferienkurs</span>';
				}

				// Subtitle: Coach + Venue
				var subtitleParts = [];
				if (klasse.headcoach) subtitleParts.push('Coach ' + klasse.headcoach);
				if (klasse.venue) subtitleParts.push(klasse.venue);
				var subtitleHtml = subtitleParts.length ? '<div class="po-eb__klasse-subtitle">' + escHtml(subtitleParts.join(' \u00b7 ')) + '</div>' : '';

				// Termin-Zeilen (erste 10 sichtbar, Rest hinter "Mehr anzeigen")
				var initialVisible = 10;
				var maxTermine = klasse.events.length;
				var termineHtml = '';
				for (var t = 0; t < maxTermine; t++) {
					var ev = klasse.events[t];
					var stock = parseInt(ev.stock) || 0;
					var isSoldOut = stock <= 0;
					var dateFormatted = formatDate(ev.date);
					var weekday = getWeekdayShort(ev.date);
					var timeText = ev.start_time ? ev.start_time + (ev.end_time ? ' \u2013 ' + ev.end_time : '') : '';

					var stockClass = '';
					var stockText = '';
					if (isSoldOut) {
						stockClass = 'po-eb__termin-stock--none';
						stockText = 'Ausgebucht';
					} else if (stock <= 3) {
						stockClass = 'po-eb__termin-stock--low';
						stockText = stock + (stock === 1 ? ' Platz' : ' Pl\u00e4tze');
					} else {
						stockText = stock + ' Pl\u00e4tze';
					}

					var actionHtml = isSoldOut
						? '<span class="po-eb__termin-soldout">Ausgebucht</span>'
						: '<button type="button" class="po-eb__termin-btn" data-product-id="' + escHtml(ev.product_id) + '" data-event-id="' + escHtml(ev.id) + '" data-date-text="' + escHtml(weekday + ', ' + dateFormatted) + '">' + escHtml(buttonText) + '</button>';

					var hiddenClass = t >= initialVisible ? ' po-eb__termin--hidden' : '';

					termineHtml +=
						'<div class="po-eb__termin' + (isSoldOut ? ' is-soldout' : '') + hiddenClass + '">' +
							'<span class="po-eb__termin-date">' + escHtml(weekday + ', ' + dateFormatted) + '</span>' +
							'<span class="po-eb__termin-time">' + escHtml(timeText) + '</span>' +
							'<span class="po-eb__termin-venue">' + escHtml(ev.venue || klasse.venue || '') + '</span>' +
							'<span class="po-eb__termin-stock ' + stockClass + '">' + escHtml(stockText) + '</span>' +
							actionHtml +
						'</div>';
				}

				// "Mehr anzeigen" Button wenn mehr als 10 Termine
				var moreCount = maxTermine - initialVisible;
				if (moreCount > 0) {
					termineHtml += '<button type="button" class="po-eb__termine-more">Weitere ' + moreCount + ' Termine anzeigen</button>';
				}

				// Klasse zusammenbauen
				var klasseEl = document.createElement('div');
				klasseEl.className = 'po-eb__klasse' + (index === 0 ? ' is-open' : '');
				klasseEl.setAttribute('data-permalink', klasse.permalink || '');

				klasseEl.innerHTML =
					'<div class="po-eb__klasse-header" role="button" tabindex="0">' +
						'<span class="po-eb__klasse-dot" style="background: ' + dotColor + '"></span>' +
						'<div class="po-eb__klasse-info">' +
							'<h3 class="po-eb__klasse-title">' + escHtml(klasse.title) + '</h3>' +
							subtitleHtml +
						'</div>' +
						badgeHtml +
						'<span class="po-eb__klasse-count">' + totalCount + (totalCount === 1 ? ' Termin' : ' Termine') + '</span>' +
						'<svg class="po-eb__klasse-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
					'</div>' +
					'<div class="po-eb__klasse-body">' +
						'<div class="po-eb__termine">' + termineHtml + '</div>' +
					'</div>';

				listContainer.appendChild(klasseEl);
			});

			bindListEvents();
		}

		// ========================================
		// LIST EVENTS
		// ========================================

		function bindListEvents() {
			// Accordion headers
			listContainer.querySelectorAll('.po-eb__klasse-header').forEach(function(header) {
				header.addEventListener('click', function() {
					var klasse = header.closest('.po-eb__klasse');
					klasse.classList.toggle('is-open');
				});
				header.addEventListener('keydown', function(e) {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						var klasse = header.closest('.po-eb__klasse');
						klasse.classList.toggle('is-open');
					}
				});
			});

			// Buchen-Buttons
			listContainer.querySelectorAll('.po-eb__termin-btn').forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.stopPropagation();
					openBookingForm(btn);
				});
			});

			// "Mehr anzeigen" Buttons
			listContainer.querySelectorAll('.po-eb__termine-more').forEach(function(moreBtn) {
				moreBtn.addEventListener('click', function(e) {
					e.stopPropagation();
					var termine = moreBtn.closest('.po-eb__termine');
					termine.querySelectorAll('.po-eb__termin--hidden').forEach(function(row) {
						row.classList.remove('po-eb__termin--hidden');
					});
					moreBtn.remove();
				});
			});
		}

		// ========================================
		// INLINE BOOKING FORM
		// ========================================

		function openBookingForm(btn) {
			var termin = btn.closest('.po-eb__termin');
			var klasse = btn.closest('.po-eb__klasse');
			var productId = btn.dataset.productId;
			var eventId = btn.dataset.eventId;
			var dateText = btn.dataset.dateText;

			// Schliesse vorherige Form
			closeBookingForm();

			// Form-Element erstellen
			var formEl = document.createElement('div');
			formEl.className = 'po-eb__booking';
			formEl.innerHTML =
				'<div class="po-eb__booking-header">' +
					'<span class="po-eb__booking-title">Teilnehmer f\u00fcr ' + escHtml(dateText) + '</span>' +
					'<button type="button" class="po-eb__booking-close" aria-label="Schlie\u00dfen">' +
						'<svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
					'</button>' +
				'</div>' +
				'<form class="po-eb__booking-form">' +
					'<input type="hidden" name="product_id" value="' + escHtml(productId) + '">' +
					'<input type="hidden" name="event_id" value="' + escHtml(eventId) + '">' +
					'<div class="po-eb__booking-fields">' +
						'<div class="po-eb__booking-field">' +
							'<label>Vorname</label>' +
							'<input type="text" name="vorname" required autocomplete="given-name">' +
						'</div>' +
						'<div class="po-eb__booking-field">' +
							'<label>Nachname</label>' +
							'<input type="text" name="name" required autocomplete="family-name">' +
						'</div>' +
						'<div class="po-eb__booking-field">' +
							'<label>Geburtsdatum</label>' +
							'<input type="date" name="geburtsdatum" required>' +
						'</div>' +
					'</div>' +
					'<button type="submit" class="po-eb__booking-submit">Zum Warenkorb hinzuf\u00fcgen</button>' +
				'</form>';

			// Nach dem Termin einfuegen
			termin.after(formEl);
			activeBookingEl = formEl;

			// Close-Button
			formEl.querySelector('.po-eb__booking-close').addEventListener('click', function() {
				closeBookingForm();
			});

			// Form submit
			var form = formEl.querySelector('.po-eb__booking-form');
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				submitBooking(form, formEl, dateText);
			});

			// Focus auf erstes Feld
			var firstInput = formEl.querySelector('input[name="vorname"]');
			if (firstInput) {
				setTimeout(function() { firstInput.focus(); }, 100);
			}

			// Smooth scroll zum Formular
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
			var submitBtn = form.querySelector('.po-eb__booking-submit');
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
				'<div class="po-eb__booking-success">' +
					'<div class="po-eb__booking-success-icon">' +
						'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>' +
					'</div>' +
					'<span class="po-eb__booking-success-text">Hinzugef\u00fcgt! \u2013 ' + escHtml(dateText) + '</span>' +
				'</div>';
		}

		// ========================================
		// FILTER LOGIC
		// ========================================

		var dropdowns = section.querySelectorAll('.po-eb__dropdown');

		dropdowns.forEach(function(dropdown) {
			var trigger = dropdown.querySelector('.po-eb__dropdown-trigger');
			var valueEl = dropdown.querySelector('.po-eb__dropdown-value');
			var options = dropdown.querySelectorAll('.po-eb__dropdown-option');
			var filterType = dropdown.dataset.filterType;

			trigger.addEventListener('click', function(e) {
				e.stopPropagation();
				var isOpen = dropdown.classList.contains('is-open');

				dropdowns.forEach(function(d) {
					d.classList.remove('is-open');
					d.querySelector('.po-eb__dropdown-trigger').setAttribute('aria-expanded', 'false');
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

					loadEvents();
				});
			});
		});

		document.addEventListener('click', function() {
			dropdowns.forEach(function(d) {
				d.classList.remove('is-open');
				d.querySelector('.po-eb__dropdown-trigger').setAttribute('aria-expanded', 'false');
			});
		});

		// ========================================
		// HELPERS
		// ========================================

		function showSkeleton() {
			skeleton.style.display = '';
			listContainer.style.display = 'none';
			emptyState.style.display = 'none';
		}

		function hideSkeleton() {
			skeleton.style.display = 'none';
			listContainer.style.display = '';
		}

		function escHtml(str) {
			if (!str) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(String(str)));
			return div.innerHTML;
		}

		function formatPrice(price) {
			return '\u20ac\u00a0' + parseFloat(price).toFixed(2).replace('.', ',');
		}

		function formatDate(dateStr) {
			if (!dateStr) return '';
			var parts = dateStr.split('-');
			if (parts.length !== 3) return dateStr;
			var months = ['Jan.', 'Feb.', 'M\u00e4rz', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];
			var day = parseInt(parts[0]);
			var month = parseInt(parts[1]) - 1;
			var year = parts[2];
			return day + '. ' + (months[month] || parts[1]) + ' ' + year;
		}

		function getWeekdayName(dateStr) {
			if (!dateStr) return '';
			var parts = dateStr.split('-');
			if (parts.length !== 3) return '';
			var d = new Date(parts[2], parseInt(parts[1]) - 1, parseInt(parts[0]));
			var days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
			return days[d.getDay()] || '';
		}

		function getWeekdayShort(dateStr) {
			if (!dateStr) return '';
			var parts = dateStr.split('-');
			if (parts.length !== 3) return '';
			var d = new Date(parts[2], parseInt(parts[1]) - 1, parseInt(parts[0]));
			var days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
			return days[d.getDay()] || '';
		}

		// ========================================
		// INIT
		// ========================================
		loadEvents();
	}

})(jQuery);
