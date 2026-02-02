document.addEventListener('DOMContentLoaded', function() {
	const karussell = document.querySelector('.po-angebote-karussell');
	if (!karussell) return;

	// Native Drag-Scroll für Track
	const track = karussell.querySelector('.po-angebote-karussell__track');
	if (track) {
		let isDown = false;
		let startX;
		let scrollLeft;

		track.addEventListener('mousedown', function(e) {
			isDown = true;
			track.classList.add('is-grabbing');
			startX = e.pageX - track.offsetLeft;
			scrollLeft = track.scrollLeft;
		});

		track.addEventListener('mouseleave', function() {
			isDown = false;
			track.classList.remove('is-grabbing');
		});

		track.addEventListener('mouseup', function() {
			isDown = false;
			track.classList.remove('is-grabbing');
		});

		track.addEventListener('mousemove', function(e) {
			if (!isDown) return;
			e.preventDefault();
			const x = e.pageX - track.offsetLeft;
			const walk = (x - startX) * 1.5;
			track.scrollLeft = scrollLeft - walk;
		});
	}

	// AJAX Config
	var ajaxConfig = window.poAngebotBooking || {};

	// Modal Funktionalität
	const cards = karussell.querySelectorAll('.po-angebote-karussell__card');
	const modal = document.getElementById('po-angebote-karussell-modal');
	const modalContent = modal?.querySelector('.po-angebote-modal__content');
	const modalClose = modal?.querySelector('.po-angebote-modal__close');
	const modalBackdrop = modal?.querySelector('.po-angebote-modal__backdrop');

	// Aktuelles Angebot für Buchungsformular
	var currentAngebot = null;

	cards.forEach(function(card) {
		card.addEventListener('click', function() {
			const data = JSON.parse(this.dataset.modal);
			openModal(data);
		});
	});

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

		if (data.bild) {
			html += '<img src="' + data.bild + '" alt="' + escapeHtml(data.titel) + '" class="po-angebote-modal__image">';
		}

		html += '<div class="po-angebote-modal__body">';

		if (data.kategorie) {
			html += '<span class="po-angebote-modal__badge">' + escapeHtml(data.kategorie) + '</span>';
		}

		html += '<h2 class="po-angebote-modal__title">' + escapeHtml(data.titel) + '</h2>';

		if (data.beschreibung) {
			html += '<div class="po-angebote-modal__description">' + data.beschreibung + '</div>';
		}

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
		if (data.preis) details.push({ label: 'Preis', value: data.preis });
		if (data.ansprechperson) details.push({ label: 'Coach', value: data.ansprechperson });

		if (details.length > 0) {
			html += '<div class="po-angebote-modal__details">';
			details.forEach(function(d) {
				html += '<div class="po-angebote-modal__detail">';
				html += '<span class="po-angebote-modal__detail-label">' + d.label + '</span>';
				html += '<span class="po-angebote-modal__detail-value">' + (d.raw ? d.value : escapeHtml(d.value)) + '</span>';
				html += '</div>';
			});
			html += '</div>';
		}

		// Termine für buchbare Workshops
		if (data.termine && data.termine.length > 0 && data.buchungsart === 'woocommerce') {
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
				html += '</div>';
				if (termin.produkt_id) {
					html += '<button class="po-angebote-modal__termin-btn" data-product-id="' + termin.produkt_id + '" data-termin-index="' + index + '">Buchen</button>';
				}
				html += '</div>';
			});
			html += '</div>';
		}

		// CTA je nach Buchungsart
		if (data.buchungsart === 'kontakt') {
			html += renderKontaktForm(data);
		} else if (data.buchungsart === 'extern' && data.cta_url) {
			html += '<a href="' + data.cta_url + '" target="_blank" rel="noopener" class="po-angebote-modal__cta">Zur Anmeldung</a>';
		}

		html += '</div>';

		modalContent.innerHTML = html;
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';

		// Kontaktformular Handler
		const kontaktForm = document.getElementById('angebot-kontakt-form-karussell');
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
	}

	function renderKontaktForm(data) {
		let html = '<div class="po-angebote-modal__form">';
		html += '<h3 class="po-angebote-modal__form-title">Anfrage senden</h3>';
		html += '<form id="angebot-kontakt-form-karussell" data-angebot-id="' + data.id + '">';

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
		const termin = data.termine[terminIndex];
		const isPaerchen = data.teilnehmer_typ === 'paerchen';
		const anzahlTeilnehmer = isPaerchen ? 2 : 1;

		let html = '<div class="po-angebote-modal__booking">';
		html += '<button class="po-angebote-modal__booking-back" type="button">&larr; Zurück</button>';
		html += '<h3 class="po-angebote-modal__booking-title">Buchung: ' + escapeHtml(data.titel) + '</h3>';

		if (termin) {
			html += '<div class="po-angebote-modal__booking-info">';
			if (termin.datum) html += '<div><strong>Datum:</strong> ' + formatDate(termin.datum) + '</div>';
			if (termin.uhrzeit) html += '<div><strong>Uhrzeit:</strong> ' + escapeHtml(termin.uhrzeit) + '</div>';
			if (termin.ort) html += '<div><strong>Ort:</strong> ' + escapeHtml(termin.ort) + '</div>';
			if (termin.preis) html += '<div><strong>Preis:</strong> ' + escapeHtml(termin.preis) + '</div>';
			html += '</div>';
		}

		html += '<form id="angebot-booking-form-karussell" data-product-id="' + productId + '" data-angebot-id="' + data.id + '">';

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
		const bookingForm = document.getElementById('angebot-booking-form-karussell');
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
		return date.toLocaleDateString('de-DE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
	}
});
