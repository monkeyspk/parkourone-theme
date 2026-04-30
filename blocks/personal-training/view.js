(function() {
    var root = document.querySelector('.po-pt');
    if (!root) return;

    var cards        = root.querySelectorAll('.po-pt__card');
    var checkboxes   = root.querySelectorAll('.po-pt__work-on-checkbox');
    var otherWrap    = root.querySelector('.po-pt__work-on-other');
    var otherText    = root.querySelector('#po-pt-other-text');
    var cta          = root.querySelector('.po-pt__cta');
    var feedback     = root.querySelector('.po-pt__feedback');
    var productId    = root.querySelector('.po-pt__product-id');
    var nonce        = root.querySelector('.po-pt__nonce');
    var ajaxUrl      = root.querySelector('.po-pt__ajax-url');

    // Roger-Notice — wörtlich identisch zu custom-events-plugin commit ced37d5.
    var PO_SINGLE_BOOKING_MSG = 'Sorry, mehrere Buchungen gleichzeitig sind technisch gerade nicht möglich. Wir arbeiten daran. Bitte schliesse die aktuelle Buchung erst ab und starte dann eine neue für die weitere Person.';

    // Cookie-Pre-Check: WooCommerce setzt diesen Cookie bei nicht-leerem Cart.
    function poCartHasItems() {
        return /(?:^|;\s*)woocommerce_items_in_cart=1(?:;|$)/.test(document.cookie);
    }

    // Render Inline-Alert (kein Modal vorhanden in diesem Block).
    function poShowSingleBookingAlert() {
        // Existierenden Alert entfernen, falls schon einer da ist (Idempotency).
        var existing = root.querySelector('.po-pt-alert');
        if (existing) existing.remove();

        var alert = document.createElement('div');
        alert.className = 'po-pt-alert';
        alert.setAttribute('role', 'alert');
        alert.style.cssText = 'border:1px solid #d63638;background:#fff5f5;border-radius:12px;padding:1.25rem;margin:1rem 0;';
        alert.innerHTML =
            '<h3 style="margin:0 0 0.5rem 0;font-size:1.125rem;font-weight:600;">Nur eine Buchung gleichzeitig</h3>' +
            '<p style="margin:0 0 0.75rem 0;">' + PO_SINGLE_BOOKING_MSG + '</p>' +
            '<button type="button" class="po-pt-alert__close" data-po-single-booking-ack>Verstanden</button>';

        // Vor das CTA einfügen, damit es im Sichtfeld erscheint.
        if (cta && cta.parentNode) {
            cta.parentNode.insertBefore(alert, cta);
        } else {
            root.appendChild(alert);
        }

        var ackBtn = alert.querySelector('[data-po-single-booking-ack]');
        if (ackBtn) {
            ackBtn.addEventListener('click', function() { alert.remove(); });
        }
    }

    var selectedPackage = null;
    var ctaLabel = cta.textContent.trim();
    var currency = root.dataset.currency || '€';

    // ── Package Card Selection ──

    function selectCard(card) {
        cards.forEach(function(c) { c.classList.remove('is-selected'); });
        card.classList.add('is-selected');
        selectedPackage = {
            index: card.dataset.packageIndex,
            title: card.dataset.packageTitle,
            price: card.dataset.packagePrice
        };
        cta.disabled = false;
        cta.textContent = ctaLabel + ' \u2013 ' + parseFloat(selectedPackage.price).toLocaleString('de-DE') + ' ' + currency;
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            selectCard(card);
        });
    });

    // ── Checkbox Interaction ──

    function updateCheckboxState(checkbox) {
        var label = checkbox.closest('.po-pt__work-on-option');
        if (!label) return;
        if (checkbox.checked) {
            label.classList.add('is-checked');
        } else {
            label.classList.remove('is-checked');
        }
    }

    function toggleOtherField() {
        if (!otherWrap) return;
        var sonstigesChecked = false;
        checkboxes.forEach(function(cb) {
            if (cb.value === 'Sonstiges' && cb.checked) {
                sonstigesChecked = true;
            }
        });
        otherWrap.hidden = !sonstigesChecked;
        if (!sonstigesChecked && otherText) {
            otherText.value = '';
        }
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateCheckboxState(cb);
            toggleOtherField();
        });
    });

    // ── Collect selected work-on options ──

    function getSelectedWorkOn() {
        var selected = [];
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                selected.push(cb.value);
            }
        });
        return selected;
    }

    // ── CTA Click ──

    cta.addEventListener('click', function() {
        // Pre-Check: bei nicht-leerem Cart sofort Roger-Notice statt AJAX.
        if (poCartHasItems()) {
            poShowSingleBookingAlert();
            return;
        }

        if (cta.disabled || !selectedPackage) return;

        // Validate
        if (!productId || !productId.value || productId.value === '0') {
            showFeedback('Produkt nicht konfiguriert. Bitte kontaktiere den Administrator.', 'error');
            return;
        }

        cta.disabled = true;
        cta.classList.add('is-loading');
        clearFeedback();

        var workOn = getSelectedWorkOn();
        var otherValue = (otherText && !otherWrap.hidden) ? otherText.value.trim() : '';

        var data = new FormData();
        data.append('action', 'po_pt_add_to_cart');
        data.append('nonce', nonce.value);
        data.append('product_id', productId.value);
        data.append('package_index', selectedPackage.index);
        data.append('package_title', selectedPackage.title);
        data.append('package_price', selectedPackage.price);

        workOn.forEach(function(opt) {
            data.append('work_on[]', opt);
        });

        if (otherValue) {
            data.append('work_on_other', otherValue);
        }

        fetch(ajaxUrl.value, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.success) {
                showFeedback(res.data.message || 'Paket hinzugefuegt!', 'success');

                // WooCommerce Fragments aktualisieren + Side-Cart oeffnen
                if (typeof jQuery !== 'undefined') {
                    jQuery(document.body).trigger('wc_fragment_refresh');
                    jQuery(document.body).trigger('added_to_cart');
                }

                // Formular zuruecksetzen
                resetForm();
            } else {
                showFeedback(res.data.message || 'Ein Fehler ist aufgetreten.', 'error');
                cta.disabled = false;
            }
        })
        .catch(function() {
            showFeedback('Verbindungsfehler. Bitte versuche es erneut.', 'error');
            cta.disabled = false;
        })
        .finally(function() {
            cta.classList.remove('is-loading');
        });
    });

    // ── Helpers ──

    function showFeedback(message, type) {
        feedback.textContent = message;
        feedback.className = 'po-pt__feedback';
        if (type === 'success') {
            feedback.classList.add('is-success');
        } else if (type === 'error') {
            feedback.classList.add('is-error');
        }
    }

    function clearFeedback() {
        feedback.textContent = '';
        feedback.className = 'po-pt__feedback';
    }

    function resetForm() {
        selectedPackage = null;
        cards.forEach(function(c) { c.classList.remove('is-selected'); });
        checkboxes.forEach(function(cb) {
            cb.checked = false;
            updateCheckboxState(cb);
        });
        if (otherText) otherText.value = '';
        if (otherWrap) otherWrap.hidden = true;
        cta.textContent = ctaLabel;
        cta.disabled = true;
    }
})();
