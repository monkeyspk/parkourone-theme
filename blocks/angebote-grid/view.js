document.addEventListener('DOMContentLoaded', function() {
	const grid = document.querySelector('.po-angebote-grid');
	if (!grid) return;

	const filterBtns = grid.querySelectorAll('.po-angebote-grid__filter-btn');
	const cards = grid.querySelectorAll('.po-angebote-grid__card');
	const modal = document.getElementById('po-angebote-modal');
	const modalContent = modal?.querySelector('.po-angebote-modal__content');
	const modalClose = modal?.querySelector('.po-angebote-modal__close');
	const modalBackdrop = modal?.querySelector('.po-angebote-modal__backdrop');

	// AJAX Config
	var ajaxConfig = window.poAngebotBooking || {};

	// Aktuelles Angebot für Buchungsformular
	var currentAngebot = null;

	// Filter
	filterBtns.forEach(function(btn) {
		btn.addEventListener('click', function() {
			const filter = this.dataset.filter;

			filterBtns.forEach(function(b) { b.classList.remove('active'); });
			this.classList.add('active');

			cards.forEach(function(card) {
				if (filter === 'alle' || card.dataset.kategorie === filter) {
					card.classList.remove('hidden');
				} else {
					card.classList.add('hidden');
				}
			});
		});
	});

	// Modal öffnen
	cards.forEach(function(card) {
		card.addEventListener('click', function() {
			const data = JSON.parse(this.dataset.modal);
			openModal(data);
		});
	});

	// Modal schliessen
	if (modalClose) {
		modalClose.addEventListener('click', closeModal);
	}
	if (modalBackdrop) {
		modalBackdrop.addEventListener('click', closeModal);
	}
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal?.getAttribute('aria-hidden') === 'false') {
			closeModal();
		}
	});

	function openModal(data) {
		if (!modal || !modalContent) return;

		currentAngebot = data;
		let html = '';

		// Bild
		if (data.bild) {
			html += '<img src="' + data.bild + '" alt="' + escapeHtml(data.titel) + '" class="po-angebote-modal__image">';
		}

		html += '<div class="po-angebote-modal__body">';

		// Badge
		if (data.kategorie) {
			html += '<span class="po-angebote-modal__badge">' + escapeHtml(data.kategorie) + '</span>';
		}

		// Titel
		html += '<h2 class="po-angebote-modal__title">' + escapeHtml(data.titel) + '</h2>';

		// Share Button
		if (typeof poShare !== 'undefined') {
			html += poShare.buttonHtml(
				window.location.origin + window.location.pathname + '?angebot=' + data.id,
				data.titel + ' \u2013 ParkourONE',
				'',
				true
			);
		}

		// Beschreibung
		if (data.beschreibung) {
			html += '<div class="po-angebote-modal__description">' + data.beschreibung + '</div>';
		}

		// Details
		const details = [];
		if (data.wann) details.push({ label: 'Wann', value: data.wann });
		if (data.saison) {
			const saisonLabels = { winter: 'Nur Winter', sommer: 'Nur Sommer', einmalig: 'Einmaliges Event' };
			details.push({ label: 'Saison', value: saisonLabels[data.saison] || data.saison });
		}
		if (data.wo) {
			let woValue = escapeHtml(data.wo);
			if (data.maps_link) {
				woValue += ' <a href="' + data.maps_link + '" target="_blank" rel="noopener">(Karte)</a>';
			}
			details.push({ label: 'Wo', value: woValue, raw: true });
		}
		if (data.voraussetzungen) details.push({ label: 'Voraussetzungen', value: data.voraussetzungen });
		if (data.was_mitbringen) details.push({ label: 'Was mitbringen', value: data.was_mitbringen });
		if (data.ansprechperson) {
			var coachValue = data.ansprechperson_image
				? '<img src="' + data.ansprechperson_image + '" alt="" class="po-angebote-modal__coach-img"> ' + escapeHtml(data.ansprechperson)
				: escapeHtml(data.ansprechperson);
			details.push({ label: 'Coach', value: coachValue, raw: true });
		}

		if (details.length > 0) {
			html += '<div class="po-angebote-modal__details">';
			details.forEach(function(d) {
				var detailClass = 'po-angebote-modal__detail';
				html += '<div class="' + detailClass + '">';
				html += '<span class="po-angebote-modal__detail-label">' + d.label + '</span>';
				html += '<span class="po-angebote-modal__detail-value">' + (d.raw ? d.value : escapeHtml(d.value)) + '</span>';
				html += '</div>';
			});
			html += '</div>';
		}

		// Single-Product-Modus: Kurs/Workshop/Ferienkurs mit EINEM WC-Produkt
		// über alle Termine. Zeigt Tagesliste + einen Gesamt-Buchungsbutton.
		var isSingleProduct = !!(data.ferienkurs_produkt_id && data.ferienkurs_produkt_id > 0);
		if (isSingleProduct) {
			// Datum-Range
			if (data.datum_range) {
				html += '<div class="po-angebote-modal__date-range" style="display:flex;align-items:center;gap:1rem;background:#fff3e6;border:1px solid #ff6b00;border-radius:12px;padding:1rem 1.5rem;margin-bottom:1.5rem;">';
				html += '<div class="po-angebote-modal__date-range-label" style="font-weight:600;font-size:0.875rem;color:#ff6b00;text-transform:uppercase;letter-spacing:0.02em;">Zeitraum</div>';
				html += '<div class="po-angebote-modal__date-range-value" style="font-size:1.125rem;font-weight:600;">' + escapeHtml(data.datum_range) + '</div>';
				html += '</div>';
			}

			// Tages-Liste
			if (data.termine && data.termine.length > 0) {
				html += '<div class="po-angebote-modal__days-list" style="margin-bottom:1.5rem;">';
				html += '<h3 style="font-size:1rem;font-weight:600;margin:0 0 0.75rem 0;">Kurstage</h3>';
				data.termine.forEach(function(termin) {
					var datumFormatiert = termin.datum ? formatDate(termin.datum) : '';
					html += '<div class="po-angebote-modal__days-list-item" style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1rem;background:#f5f5f7;border-radius:8px;margin-bottom:0.375rem;font-size:0.875rem;">';
					html += '<div style="font-weight:600;">' + datumFormatiert + '</div>';
					var details = '';
					if (termin.uhrzeit) details += termin.uhrzeit;
					if (termin.ort) details += ' · ' + escapeHtml(termin.ort);
					if (details) {
						html += '<div style="color:#666;">' + details + '</div>';
					}
					html += '</div>';
				});
				html += '</div>';
			}

			// Gesamtpreis
			if (data.preis) {
				html += '<div class="po-angebote-modal__ferienkurs-preis" style="display:flex;align-items:center;gap:1rem;background:#f5f5f7;border-radius:12px;padding:1rem 1.5rem;margin-bottom:1.5rem;">';
				html += '<div style="font-weight:600;font-size:0.875rem;color:#666;">Gesamtpreis</div>';
				html += '<div style="font-size:1.5rem;font-weight:700;color:#0066cc;">' + escapeHtml(data.preis) + '</div>';
				html += '</div>';
			}

			// Verfügbarkeit
			if (typeof data.ferienkurs_verfuegbar !== 'undefined' && data.ferienkurs_verfuegbar !== null) {
				var spotColor = data.ferienkurs_verfuegbar > 3 ? '#00a32a' : (data.ferienkurs_verfuegbar > 0 ? '#dba617' : '#d63638');
				var spotText = data.ferienkurs_verfuegbar > 0 ? data.ferienkurs_verfuegbar + ' Plätze frei' : 'Ausgebucht';
				html += '<div style="font-size:0.875rem;color:' + spotColor + ';font-weight:600;margin-bottom:1rem;">' + spotText + '</div>';
			}

			// Ein Buchungsbutton
			if (data.ferienkurs_produkt_id && data.buchungsart === 'woocommerce') {
				if (typeof data.ferienkurs_verfuegbar !== 'undefined' && data.ferienkurs_verfuegbar !== null && data.ferienkurs_verfuegbar <= 0) {
					html += '<button class="po-angebote-modal__cta" disabled style="opacity:0.5;cursor:not-allowed;">Ausgebucht</button>';
				} else {
					html += '<button class="po-angebote-modal__cta po-angebote-modal__ferienkurs-book" data-product-id="' + data.ferienkurs_produkt_id + '">Ferienkurs buchen</button>';
				}
			}
		}
		// Normale Termine (für buchbare Workshops)
		else if (data.termine && data.termine.length > 0 && data.buchungsart === 'woocommerce') {
			html += '<div class="po-angebote-modal__termine">';
			html += '<h3 class="po-angebote-modal__termine-title">Verfügbare Termine</h3>';
			data.termine.forEach(function(termin, index) {
				const datumFormatiert = termin.datum ? formatDate(termin.datum) : '';
				html += '<div class="po-angebote-modal__termin" data-termin-index="' + index + '">';
				html += '<div class="po-angebote-modal__termin-info">';
				html += '<div class="po-angebote-modal__termin-date">' + datumFormatiert + '</div>';
				html += '<div class="po-angebote-modal__termin-details">';
				if (termin.uhrzeit) html += termin.uhrzeit;
				if (termin.ort) html += ' | ' + escapeHtml(termin.ort);
				if (termin.preis) html += ' | ' + escapeHtml(termin.preis);
				html += '</div>';
				if (typeof termin.verfuegbar !== 'undefined') {
					var spotColor = termin.verfuegbar > 3 ? '#00a32a' : (termin.verfuegbar > 0 ? '#dba617' : '#d63638');
					var spotText = termin.verfuegbar > 0 ? termin.verfuegbar + ' Plätze frei' : 'Ausgebucht';
					html += '<div class="po-angebote-modal__termin-spots" style="font-size:12px;color:' + spotColor + ';font-weight:600;">' + spotText + '</div>';
				}
				html += '</div>';
				if (termin.produkt_id) {
					if (typeof termin.verfuegbar !== 'undefined' && termin.verfuegbar <= 0) {
						html += '<button class="po-angebote-modal__termin-btn" disabled style="opacity:0.5;cursor:not-allowed;">Ausgebucht</button>';
					} else {
						html += '<button class="po-angebote-modal__termin-btn" data-product-id="' + termin.produkt_id + '" data-termin-index="' + index + '">Buchen</button>';
					}
				}
				html += '</div>';
			});
			html += '</div>';
		}

		// CTA je nach Buchungsart — auch für Ferienkurse mit Kontakt/externem Link
		if (data.buchungsart === 'kontakt') {
			html += renderKontaktForm(data);
		} else if (data.buchungsart === 'extern' && data.cta_url) {
			html += '<a href="' + data.cta_url + '" target="_blank" rel="noopener" class="po-angebote-modal__cta">Zur Anmeldung</a>';
		}

		html += '</div>'; // .po-angebote-modal__body

		modalContent.innerHTML = html;
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';

		// Kontaktformular Handler
		const kontaktForm = document.getElementById('angebot-kontakt-form');
		if (kontaktForm) {
			kontaktForm.addEventListener('submit', handleContactForm);
		}

		// Buchung Handler - öffnet Buchungsformular
		const bookBtns = modal.querySelectorAll('.po-angebote-modal__termin-btn');
		bookBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				const productId = this.dataset.productId;
				const terminIndex = this.dataset.terminIndex;
				showBookingForm(data, terminIndex, productId);
			});
		});

		// Ferienkurs Buchung Handler
		const ferienkursBtn = modal.querySelector('.po-angebote-modal__ferienkurs-book');
		if (ferienkursBtn) {
			ferienkursBtn.addEventListener('click', function() {
				showBookingForm(data, null, this.dataset.productId);
			});
		}
	}

	function renderKontaktForm(data) {
		let html = '<div class="po-angebote-modal__form">';
		html += '<h3 class="po-angebote-modal__form-title">Anfrage senden</h3>';
		html += '<form id="angebot-kontakt-form" data-angebot-id="' + data.id + '">';

		html += '<div class="po-angebote-modal__form-row">';
		html += '<div class="po-angebote-modal__form-group">';
		html += '<label class="po-angebote-modal__form-label">Name *</label>';
		html += '<input type="text" name="name" class="po-angebote-modal__form-input" required>';
		html += '</div>';
		html += '<div class="po-angebote-modal__form-group">';
		html += '<label class="po-angebote-modal__form-label">E-Mail *</label>';
		html += '<input type="email" name="email" class="po-angebote-modal__form-input" required>';
		html += '</div>';
		html += '</div>';

		html += '<div class="po-angebote-modal__form-row">';
		html += '<div class="po-angebote-modal__form-group">';
		html += '<label class="po-angebote-modal__form-label">Telefon</label>';
		html += '<input type="tel" name="telefon" class="po-angebote-modal__form-input">';
		html += '</div>';
		html += '<div class="po-angebote-modal__form-group">';
		html += '<label class="po-angebote-modal__form-label">Anzahl Teilnehmende</label>';
		html += '<input type="number" name="anzahl" class="po-angebote-modal__form-input" min="1" value="1">';
		html += '</div>';
		html += '</div>';

		html += '<div class="po-angebote-modal__form-group">';
		html += '<label class="po-angebote-modal__form-label">Nachricht</label>';
		html += '<textarea name="nachricht" class="po-angebote-modal__form-textarea" rows="4" placeholder="Ihre Nachricht..."></textarea>';
		html += '</div>';

		html += '<div class="po-angebote-modal__form-group po-angebote-modal__form-checkbox">';
		html += '<label>';
		html += '<input type="checkbox" name="agb" value="1" required>';
		html += ' Ich akzeptiere die <a href="/agb" target="_blank">AGB</a> und <a href="/datenschutz" target="_blank">Datenschutzbestimmungen</a> *';
		html += '</label>';
		html += '</div>';

		html += '<div class="po-angebote-modal__form-message" style="display:none;"></div>';
		html += '<button type="submit" class="po-angebote-modal__form-submit">Anfrage senden</button>';
		html += '</form>';
		html += '</div>';
		return html;
	}

	function showBookingForm(data, terminIndex, productId) {
		const termin = terminIndex !== null && data.termine ? data.termine[terminIndex] : null;
		const isPaerchen = data.teilnehmer_typ === 'paerchen';
		const anzahlTeilnehmer = isPaerchen ? 2 : 1;

		let html = '<div class="po-angebote-modal__booking">';
		html += '<button class="po-angebote-modal__booking-back" type="button">&larr; Zurück</button>';
		html += '<h3 class="po-angebote-modal__booking-title">Buchung: ' + escapeHtml(data.titel) + '</h3>';

		if (data.ferienkurs_produkt_id && data.ferienkurs_produkt_id > 0) {
			html += '<div class="po-angebote-modal__booking-info">';
			if (data.datum_range) html += '<div><strong>Zeitraum:</strong> ' + escapeHtml(data.datum_range) + '</div>';
			if (data.preis) html += '<div><strong>Gesamtpreis:</strong> ' + escapeHtml(data.preis) + '</div>';
			html += '</div>';
		} else if (termin) {
			html += '<div class="po-angebote-modal__booking-info">';
			if (termin.datum) html += '<div><strong>Datum:</strong> ' + formatDate(termin.datum) + '</div>';
			if (termin.uhrzeit) html += '<div><strong>Uhrzeit:</strong> ' + escapeHtml(termin.uhrzeit) + '</div>';
			if (termin.ort) html += '<div><strong>Ort:</strong> ' + escapeHtml(termin.ort) + '</div>';
			if (termin.preis) html += '<div><strong>Preis:</strong> ' + escapeHtml(termin.preis) + '</div>';
			html += '</div>';
		}

		html += '<form id="angebot-booking-form" data-product-id="' + productId + '" data-angebot-id="' + data.id + '">';

		for (var i = 1; i <= anzahlTeilnehmer; i++) {
			var label = isPaerchen ? 'Teilnehmer ' + i + (i === 1 ? ' (Erwachsener)' : ' (Kind/Jugendlicher)') : 'Teilnehmerdaten';
			html += '<div class="po-angebote-modal__booking-participant">';
			html += '<h4>' + label + '</h4>';

			html += '<div class="po-angebote-modal__form-row">';
			html += '<div class="po-angebote-modal__form-group">';
			html += '<label class="po-angebote-modal__form-label">Vorname *</label>';
			html += '<input type="text" name="vorname_' + i + '" class="po-angebote-modal__form-input" required>';
			html += '</div>';
			html += '<div class="po-angebote-modal__form-group">';
			html += '<label class="po-angebote-modal__form-label">Nachname *</label>';
			html += '<input type="text" name="name_' + i + '" class="po-angebote-modal__form-input" required>';
			html += '</div>';
			html += '</div>';

			html += '<div class="po-angebote-modal__form-group">';
			html += '<label class="po-angebote-modal__form-label">Geburtsdatum *</label>';
			html += '<input type="date" name="geburtsdatum_' + i + '" class="po-angebote-modal__form-input" required>';
			html += '</div>';
			html += '</div>';
		}

		html += '<div class="po-angebote-modal__form-message" style="display:none;"></div>';
		html += '<button type="submit" class="po-angebote-modal__form-submit">Jetzt buchen</button>';
		html += '</form>';
		html += '</div>';

		// Modal-Content ersetzen
		modalContent.innerHTML = html;

		// Back Button Handler
		const backBtn = modalContent.querySelector('.po-angebote-modal__booking-back');
		if (backBtn) {
			backBtn.addEventListener('click', function() {
				openModal(data);
			});
		}

		// Booking Form Handler
		const bookingForm = document.getElementById('angebot-booking-form');
		if (bookingForm) {
			bookingForm.addEventListener('submit', handleBookingForm);
		}
	}

	function closeModal() {
		if (!modal) return;
		modal.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
		currentAngebot = null;
	}

	function handleContactForm(e) {
		e.preventDefault();
		const form = e.target;
		const btn = form.querySelector('.po-angebote-modal__form-submit');
		const msgEl = form.querySelector('.po-angebote-modal__form-message');
		const originalText = btn.textContent;

		btn.textContent = 'Wird gesendet...';
		btn.disabled = true;
		msgEl.style.display = 'none';

		const formData = new FormData(form);
		formData.append('action', 'po_angebot_kontakt');
		formData.append('angebot_id', form.dataset.angebotId);

		fetch(ajaxConfig.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) { return response.json(); })
		.then(function(result) {
			if (result.success) {
				msgEl.textContent = result.data.message;
				msgEl.className = 'po-angebote-modal__form-message po-angebote-modal__form-message--success';
				msgEl.style.display = 'block';
				btn.textContent = 'Gesendet!';
				form.reset();
				setTimeout(function() {
					closeModal();
				}, 2500);
			} else {
				msgEl.textContent = result.data.message || 'Es ist ein Fehler aufgetreten.';
				msgEl.className = 'po-angebote-modal__form-message po-angebote-modal__form-message--error';
				msgEl.style.display = 'block';
				btn.textContent = originalText;
				btn.disabled = false;
			}
		})
		.catch(function(error) {
			msgEl.textContent = 'Verbindungsfehler. Bitte versuche es erneut.';
			msgEl.className = 'po-angebote-modal__form-message po-angebote-modal__form-message--error';
			msgEl.style.display = 'block';
			btn.textContent = originalText;
			btn.disabled = false;
		});
	}

	function handleBookingForm(e) {
		e.preventDefault();
		const form = e.target;
		const btn = form.querySelector('.po-angebote-modal__form-submit');
		const msgEl = form.querySelector('.po-angebote-modal__form-message');
		const originalText = btn.textContent;

		btn.textContent = 'Wird gebucht...';
		btn.disabled = true;
		msgEl.style.display = 'none';

		const formData = new FormData(form);
		formData.append('action', 'po_angebot_add_to_cart');
		formData.append('nonce', ajaxConfig.nonce);
		formData.append('product_id', form.dataset.productId);
		formData.append('angebot_id', form.dataset.angebotId);

		fetch(ajaxConfig.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) { return response.json(); })
		.then(function(result) {
			if (result.success) {
				msgEl.textContent = result.data.message;
				msgEl.className = 'po-angebote-modal__form-message po-angebote-modal__form-message--success';
				msgEl.style.display = 'block';
				btn.textContent = 'Erfolgreich!';

				// Modal schliessen und Side Cart öffnen (kein Redirect)
				setTimeout(function() {
					closeModal();
					// Mini-Cart / Side Cart triggern (WooCommerce Standard)
					if (typeof jQuery !== 'undefined') {
						jQuery(document.body).trigger('wc_fragment_refresh');
						jQuery(document.body).trigger('added_to_cart');
					}
					// Falls Side Cart Plugin vorhanden (z.B. XOO WooCommerce)
					if (typeof xoo_wsc_params !== 'undefined' && typeof xoo_wsc !== 'undefined') {
						xoo_wsc.show();
					}
					// Falls Woodmart Theme Side Cart
					if (typeof woodmart_open_side_cart === 'function') {
						woodmart_open_side_cart();
					}
				}, 800);
			} else {
				msgEl.textContent = result.data.message || 'Buchung fehlgeschlagen.';
				msgEl.className = 'po-angebote-modal__form-message po-angebote-modal__form-message--error';
				msgEl.style.display = 'block';
				btn.textContent = originalText;
				btn.disabled = false;
			}
		})
		.catch(function(error) {
			msgEl.textContent = 'Verbindungsfehler. Bitte versuche es erneut.';
			msgEl.className = 'po-angebote-modal__form-message po-angebote-modal__form-message--error';
			msgEl.style.display = 'block';
			btn.textContent = originalText;
			btn.disabled = false;
		});
	}

	function escapeHtml(str) {
		if (!str) return '';
		const div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function formatDate(dateStr) {
		if (!dateStr) return '';
		const parts = dateStr.split('-');
		if (parts.length !== 3) return dateStr;
		const date = new Date(parts[0], parts[1] - 1, parts[2]);
		const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
		return date.toLocaleDateString('de-DE', options);
	}
});
