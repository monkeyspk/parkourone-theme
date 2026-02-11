(function($) {
	'use strict';

	var sections = document.querySelectorAll('.po-eb');
	if (!sections.length) return;

	sections.forEach(function(section) {
		initEventBooking(section);
	});

	function initEventBooking(section) {
		var grid = section.querySelector('[data-event-grid]');
		var modalsContainer = section.querySelector('[data-event-modals]');
		var skeleton = section.querySelector('.po-eb__skeleton');
		var emptyState = section.querySelector('.po-eb__empty');

		// Block-Attribute aus data-Attributen
		var preFilterAge = section.dataset.filterAge || '';
		var preFilterLocation = section.dataset.filterLocation || '';
		var preFilterOffer = section.dataset.filterOffer || '';
		var preFilterWeekday = section.dataset.filterWeekday || '';
		var buttonText = section.dataset.buttonText || 'Jetzt buchen';

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

		// ========================================
		// API FETCH
		// ========================================

		function buildApiUrl() {
			var params = [];
			// Block pre-filters
			if (preFilterAge) params.push('age=' + encodeURIComponent(preFilterAge));
			if (preFilterLocation) params.push('location=' + encodeURIComponent(preFilterLocation));
			if (preFilterOffer) params.push('offer=' + encodeURIComponent(preFilterOffer));
			if (preFilterWeekday) params.push('weekday=' + encodeURIComponent(preFilterWeekday));

			// Frontend-Filter ueberschreiben die Vorfilter
			if (activeFilters.age) params.push('age=' + encodeURIComponent(activeFilters.age));
			if (activeFilters.location) params.push('location=' + encodeURIComponent(activeFilters.location));
			if (activeFilters.offer) params.push('offer=' + encodeURIComponent(activeFilters.offer));
			if (activeFilters.weekday) params.push('weekday=' + encodeURIComponent(activeFilters.weekday));

			// Deep-Link
			if (deepLinkKlasse) params.push('klasse=' + encodeURIComponent(deepLinkKlasse));

			var url = '/wp-json/events/v1/list';
			if (params.length) url += '?' + params.join('&');
			return url;
		}

		function loadEvents() {
			showSkeleton();

			fetch(buildApiUrl())
				.then(function(res) { return res.json(); })
				.then(function(data) {
					var events = data.events || [];
					var grouped = groupByKlasse(events);
					renderCards(grouped);
					renderModals(grouped);
					hideSkeleton();

					// Deep-Link: automatisch Modal oeffnen
					if (deepLinkKlasse && grouped.length === 1) {
						var modalId = section.id + '-modal-0';
						var modal = document.getElementById(modalId);
						if (modal) {
							setTimeout(function() { openModal(modal); }, 300);
						}
						deepLinkKlasse = ''; // nur einmal
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
		// RENDERING: CARDS
		// ========================================

		function renderCards(klassen) {
			grid.innerHTML = '';

			if (!klassen.length) {
				emptyState.style.display = 'block';
				return;
			}

			emptyState.style.display = 'none';

			klassen.forEach(function(klasse, index) {
				var first = klasse.events[0];
				var allSoldOut = klasse.events.every(function(e) { return parseInt(e.stock) <= 0; });
				var minPrice = Math.min.apply(null, klasse.events.map(function(e) { return parseFloat(e.price) || 0; }));

				var card = document.createElement('article');
				card.className = 'po-eb__card';
				card.setAttribute('data-modal-target', section.id + '-modal-' + index);

				var badgeHtml = '';
				if (allSoldOut) {
					badgeHtml = '<span class="po-eb__card-badge po-eb__card-badge--soldout">Ausgebucht</span>';
				} else if (klasse.is_workshop || (klasse.categories && klasse.categories.indexOf('ferienkurs') !== -1)) {
					badgeHtml = '<span class="po-eb__card-badge po-eb__card-badge--ferienkurs">Ferienkurs</span>';
				}

				var imageHtml = first.image
					? '<img src="' + escHtml(first.image) + '" alt="' + escHtml(klasse.title) + '" class="po-eb__card-image" loading="lazy">'
					: '<div class="po-eb__card-placeholder"></div>';

				var coachHtml = first.headcoach_image
					? '<div class="po-eb__card-coach"><img src="' + escHtml(first.headcoach_image) + '" alt="' + escHtml(first.headcoach) + '" loading="lazy"></div>'
					: '';

				// Naechster Termin
				var nextDate = '';
				var nextTime = '';
				for (var i = 0; i < klasse.events.length; i++) {
					if (parseInt(klasse.events[i].stock) > 0) {
						nextDate = klasse.events[i].date;
						nextTime = klasse.events[i].start_time + (klasse.events[i].end_time ? ' - ' + klasse.events[i].end_time : '');
						break;
					}
				}
				if (!nextDate && klasse.events.length) {
					nextDate = first.date;
					nextTime = first.start_time + (first.end_time ? ' - ' + first.end_time : '');
				}

				var weekday = getWeekdayName(nextDate);

				card.innerHTML =
					'<div class="po-eb__card-visual">' +
						imageHtml +
						'<div class="po-eb__card-gradient"></div>' +
						coachHtml +
					'</div>' +
					'<div class="po-eb__card-body">' +
						badgeHtml +
						'<span class="po-eb__card-eyebrow">' + escHtml(weekday) + '</span>' +
						'<h3 class="po-eb__card-title">' + escHtml(klasse.title) + '</h3>' +
						'<div class="po-eb__card-meta">' +
							(nextTime ? '<span class="po-eb__card-meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>' + escHtml(nextTime) + ' Uhr</span>' : '') +
							(klasse.venue ? '<span class="po-eb__card-meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' + escHtml(klasse.venue) + '</span>' : '') +
						'</div>' +
						(minPrice > 0 ? '<div class="po-eb__card-price">' + formatPrice(minPrice) + '</div>' : '') +
					'</div>' +
					'<button class="po-eb__card-action" aria-label="Mehr erfahren">' +
						'<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="currentColor"/><path d="M12 7v10M7 12h10" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>' +
					'</button>';

				grid.appendChild(card);
			});

			bindCardClicks();
		}

		// ========================================
		// RENDERING: MODALS
		// ========================================

		function renderModals(klassen) {
			modalsContainer.innerHTML = '';

			klassen.forEach(function(klasse, index) {
				var modalId = section.id + '-modal-' + index;
				var first = klasse.events[0];
				var weekday = getWeekdayName(first.date);
				var timeText = first.start_time ? first.start_time + (first.end_time ? ' - ' + first.end_time + ' Uhr' : ' Uhr') : '';

				// Verfuegbare Termine
				var datesHtml = '';
				var hasAvailable = false;
				klasse.events.forEach(function(ev) {
					var stock = parseInt(ev.stock) || 0;
					if (stock > 0) {
						hasAvailable = true;
						var formatted = formatDate(ev.date);
						datesHtml +=
							'<button type="button" class="po-steps__date po-steps__next" data-product-id="' + escHtml(ev.product_id) + '" data-date-text="' + escHtml(formatted) + '">' +
								'<span class="po-steps__date-text">' + escHtml(formatted) + '</span>' +
								'<span class="po-steps__date-stock">' + stock + (stock === 1 ? ' Platz' : ' Pl\u00e4tze') + ' frei</span>' +
							'</button>';
					}
				});

				if (!hasAvailable) {
					datesHtml =
						'<div class="po-steps__empty">' +
							'<p>Aktuell sind keine Termine verf\u00fcgbar.</p>' +
							'<p>Kontaktiere uns f\u00fcr weitere Informationen.</p>' +
						'</div>';
				}

				// Preis
				var minPrice = 0;
				klasse.events.forEach(function(ev) {
					var p = parseFloat(ev.price) || 0;
					if (p > 0 && (minPrice === 0 || p < minPrice)) minPrice = p;
				});

				var priceHtml = minPrice > 0
					? '<div class="po-steps__price"><span class="po-steps__price-label">Preis</span><span class="po-steps__price-value">' + formatPrice(minPrice) + '</span></div>'
					: '';

				var modal = document.createElement('div');
				modal.className = 'po-overlay';
				modal.id = modalId;
				modal.setAttribute('aria-hidden', 'true');
				modal.setAttribute('role', 'dialog');
				modal.setAttribute('aria-modal', 'true');

				modal.innerHTML =
					'<div class="po-overlay__backdrop"></div>' +
					'<div class="po-overlay__panel">' +
						'<button class="po-overlay__close" aria-label="Schlie\u00dfen">' +
							'<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#1d1d1f"/><path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>' +
						'</button>' +
						'<div class="po-steps" data-step="0">' +
							'<div class="po-steps__track">' +

								// Slide 0: Info
								'<div class="po-steps__slide is-active" data-slide="0">' +
									'<header class="po-steps__header">' +
										'<span class="po-steps__eyebrow">' + escHtml(weekday) + '</span>' +
										'<h2 class="po-steps__heading">' + escHtml(klasse.title) + '</h2>' +
									'</header>' +
									'<dl class="po-steps__meta">' +
										(timeText ? '<div class="po-steps__meta-item"><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></dt><dd>' + escHtml(timeText) + '</dd></div>' : '') +
										(klasse.venue ? '<div class="po-steps__meta-item"><dt><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></dt><dd>' + escHtml(klasse.venue) + '</dd></div>' : '') +
									'</dl>' +
									(klasse.description ? '<div class="po-steps__content">' + klasse.description + '</div>' : '') +
									priceHtml +
									(hasAvailable ? '<button type="button" class="po-steps__cta po-steps__next">' + escHtml(buttonText) + '</button>' : '') +
								'</div>' +

								// Slide 1: Terminauswahl
								'<div class="po-steps__slide is-next" data-slide="1">' +
									'<header class="po-steps__header">' +
										'<span class="po-steps__eyebrow">Schritt 1 von 2</span>' +
										'<h2 class="po-steps__heading">Termin w\u00e4hlen</h2>' +
										'<p class="po-steps__subheading">' + escHtml(klasse.title) + '</p>' +
									'</header>' +
									'<div class="po-steps__dates">' + datesHtml + '</div>' +
									'<button type="button" class="po-steps__back-link">\u2190 Zur\u00fcck zur \u00dcbersicht</button>' +
								'</div>' +

								// Slide 2: Teilnehmer-Formular
								'<div class="po-steps__slide is-next" data-slide="2">' +
									'<header class="po-steps__header">' +
										'<span class="po-steps__eyebrow">Schritt 2 von 2</span>' +
										'<h2 class="po-steps__heading">Wer nimmt teil?</h2>' +
										'<p class="po-steps__subheading po-steps__selected-date"></p>' +
									'</header>' +
									'<form class="po-steps__form">' +
										'<input type="hidden" name="product_id" value="">' +
										'<input type="hidden" name="event_id" value="' + escHtml(first.id) + '">' +
										'<div class="po-steps__field"><label>Vorname</label><input type="text" name="vorname" required autocomplete="given-name"></div>' +
										'<div class="po-steps__field"><label>Nachname</label><input type="text" name="name" required autocomplete="family-name"></div>' +
										'<div class="po-steps__field"><label>Geburtsdatum</label><input type="date" name="geburtsdatum" required></div>' +
										'<button type="submit" class="po-steps__cta po-steps__submit">Zum Warenkorb hinzuf\u00fcgen</button>' +
									'</form>' +
									'<button type="button" class="po-steps__back-link">\u2190 Anderer Termin</button>' +
								'</div>' +

								// Slide 3: Erfolg
								'<div class="po-steps__slide is-next" data-slide="3">' +
									'<div class="po-steps__success">' +
										'<div class="po-steps__success-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg></div>' +
										'<h2 class="po-steps__heading">Hinzugef\u00fcgt!</h2>' +
										'<p class="po-steps__subheading">' + escHtml(klasse.title) + '</p>' +
										'<p class="po-steps__selected-date-confirm"></p>' +
									'</div>' +
								'</div>' +

							'</div>' +
						'</div>' +
					'</div>';

				modalsContainer.appendChild(modal);
			});

			bindModalEvents();
		}

		// ========================================
		// MODAL LOGIC
		// ========================================

		function openModal(modal) {
			modal.classList.add('is-active');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('po-no-scroll');
			setTimeout(function() {
				var close = modal.querySelector('.po-overlay__close');
				if (close) close.focus();
			}, 100);
		}

		function closeModal(modal) {
			modal.classList.remove('is-active');
			modal.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('po-no-scroll');

			// Reset steps
			var steps = modal.querySelector('.po-steps');
			if (steps) {
				goToStep(steps, 0);
				var form = steps.querySelector('.po-steps__form');
				if (form) form.reset();
			}
		}

		function goToStep(stepsEl, step) {
			var slides = stepsEl.querySelectorAll('.po-steps__slide');
			slides.forEach(function(slide, i) {
				slide.classList.remove('is-active', 'is-prev', 'is-next');
				if (i < step) {
					slide.classList.add('is-prev');
				} else if (i === step) {
					slide.classList.add('is-active');
				} else {
					slide.classList.add('is-next');
				}
			});
			stepsEl.setAttribute('data-step', step);
			stepsEl.closest('.po-overlay__panel').scrollTop = 0;
		}

		function bindCardClicks() {
			section.querySelectorAll('.po-eb__card').forEach(function(card) {
				card.addEventListener('click', function() {
					var modalId = card.getAttribute('data-modal-target');
					var modal = document.getElementById(modalId);
					if (modal) openModal(modal);
				});
			});
		}

		function bindModalEvents() {
			var modals = modalsContainer.querySelectorAll('.po-overlay');

			modals.forEach(function(modal) {
				// Close button
				var closeBtn = modal.querySelector('.po-overlay__close');
				if (closeBtn) {
					closeBtn.addEventListener('click', function() { closeModal(modal); });
				}

				// Backdrop
				var backdrop = modal.querySelector('.po-overlay__backdrop');
				if (backdrop) {
					backdrop.addEventListener('click', function() { closeModal(modal); });
				}

				// ESC
				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && modal.classList.contains('is-active')) {
						closeModal(modal);
					}
				});

				var stepsEl = modal.querySelector('.po-steps');
				if (!stepsEl) return;

				// Next buttons (nicht die date-buttons und nicht submit)
				modal.querySelectorAll('.po-steps__next:not(.po-steps__date):not(.po-steps__submit)').forEach(function(btn) {
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						var currentStep = parseInt(stepsEl.getAttribute('data-step')) || 0;
						goToStep(stepsEl, currentStep + 1);
					});
				});

				// Date buttons
				modal.querySelectorAll('.po-steps__date').forEach(function(btn) {
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						var productId = btn.dataset.productId;
						var dateText = btn.dataset.dateText;

						stepsEl.querySelector('[name="product_id"]').value = productId;

						var dateEls = stepsEl.querySelectorAll('.po-steps__selected-date');
						dateEls.forEach(function(el) { el.textContent = dateText; });
						var confirmEls = stepsEl.querySelectorAll('.po-steps__selected-date-confirm');
						confirmEls.forEach(function(el) { el.textContent = dateText; });

						var currentStep = parseInt(stepsEl.getAttribute('data-step')) || 0;
						goToStep(stepsEl, currentStep + 1);
					});
				});

				// Back buttons
				modal.querySelectorAll('.po-steps__back-link').forEach(function(btn) {
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						var currentStep = parseInt(stepsEl.getAttribute('data-step')) || 0;
						if (currentStep > 0) goToStep(stepsEl, currentStep - 1);
					});
				});

				// Form submit
				var form = modal.querySelector('.po-steps__form');
				if (form) {
					form.addEventListener('submit', function(e) {
						e.preventDefault();

						var submitBtn = form.querySelector('.po-steps__submit');
						submitBtn.disabled = true;
						submitBtn.textContent = 'Wird hinzugef\u00fcgt...';

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
								goToStep(stepsEl, 3);
								form.reset();

								setTimeout(function() {
									closeModal(modal);
									$(document.body).trigger('wc_fragment_refresh');
									$(document.body).trigger('added_to_cart');
								}, 1500);
							} else {
								alert(response.data && response.data.message ? response.data.message : 'Ein Fehler ist aufgetreten');
							}
							submitBtn.disabled = false;
							submitBtn.textContent = 'Zum Warenkorb hinzuf\u00fcgen';
						}).fail(function() {
							alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
							submitBtn.disabled = false;
							submitBtn.textContent = 'Zum Warenkorb hinzuf\u00fcgen';
						});
					});
				}
			});
		}

		// ========================================
		// FILTER LOGIC
		// ========================================

		var dropdowns = section.querySelectorAll('.po-eb__dropdown');

		dropdowns.forEach(function(dropdown) {
			var trigger = dropdown.querySelector('.po-eb__dropdown-trigger');
			var panel = dropdown.querySelector('.po-eb__dropdown-panel');
			var valueEl = dropdown.querySelector('.po-eb__dropdown-value');
			var options = dropdown.querySelectorAll('.po-eb__dropdown-option');
			var filterType = dropdown.dataset.filterType;

			trigger.addEventListener('click', function(e) {
				e.stopPropagation();
				var isOpen = dropdown.classList.contains('is-open');

				// Close all
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

					// Reload events
					loadEvents();
				});
			});
		});

		// Close on outside click
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
			skeleton.setAttribute('aria-hidden', 'false');
			grid.style.display = 'none';
			emptyState.style.display = 'none';
		}

		function hideSkeleton() {
			skeleton.style.display = 'none';
			skeleton.setAttribute('aria-hidden', 'true');
			grid.style.display = '';
		}

		function escHtml(str) {
			if (!str) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}

		function formatPrice(price) {
			return '\u20ac\u00a0' + parseFloat(price).toFixed(2).replace('.', ',');
		}

		function formatDate(dateStr) {
			if (!dateStr) return '';
			// DD-MM-YYYY -> readable
			var parts = dateStr.split('-');
			if (parts.length !== 3) return dateStr;
			var months = ['Jan.', 'Feb.', 'Mrz.', 'Apr.', 'Mai', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];
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

		// ========================================
		// INIT
		// ========================================
		loadEvents();
	}

})(jQuery);
