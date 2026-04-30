(function() {
    var roots = document.querySelectorAll('.po-produkt-showcase');
    if (!roots.length) return;

    // Roger-Notice — wörtlich identisch zu custom-events-plugin commit ced37d5.
    var PO_SINGLE_BOOKING_MSG = 'Sorry, mehrere Buchungen gleichzeitig sind technisch gerade nicht möglich. Wir arbeiten daran. Bitte schliesse die aktuelle Buchung erst ab und starte dann eine neue für die weitere Person.';

    // Cookie-Pre-Check: WooCommerce setzt diesen Cookie bei nicht-leerem Cart.
    function poCartHasItems() {
        return /(?:^|;\s*)woocommerce_items_in_cart=1(?:;|$)/.test(document.cookie);
    }

    // Render Inline-Alert nahe dem geklickten Item (kein Modal in diesem Block).
    function poShowSingleBookingAlert(item) {
        // Existierenden Alert in diesem Item entfernen, falls schon einer da ist.
        var existing = item.querySelector('.po-produkt-showcase-alert');
        if (existing) existing.remove();

        var alert = document.createElement('div');
        alert.className = 'po-produkt-showcase-alert';
        alert.setAttribute('role', 'alert');
        alert.style.cssText = 'border:1px solid #d63638;background:#fff5f5;border-radius:12px;padding:1.25rem;margin:1rem 0;';
        alert.innerHTML =
            '<h3 style="margin:0 0 0.5rem 0;font-size:1.125rem;font-weight:600;">Nur eine Buchung gleichzeitig</h3>' +
            '<p style="margin:0 0 0.75rem 0;">' + PO_SINGLE_BOOKING_MSG + '</p>' +
            '<button type="button" class="po-produkt-showcase-alert__close" data-po-single-booking-ack>Verstanden</button>';

        item.appendChild(alert);

        var ackBtn = alert.querySelector('[data-po-single-booking-ack]');
        if (ackBtn) {
            ackBtn.addEventListener('click', function() { alert.remove(); });
        }
    }

    roots.forEach(function(root) {
        var nonce   = root.dataset.nonce;
        var ajaxUrl = root.dataset.ajaxUrl;

        // Find all product containers (cards in grid, or single __inner)
        var items = root.querySelectorAll('.po-produkt-showcase__card, .po-produkt-showcase__inner');

        items.forEach(function(item) {
            var cta       = item.querySelector('.po-produkt-showcase__cta');
            var feedback  = item.querySelector('.po-produkt-showcase__feedback');
            var priceEl   = item.querySelector('.po-produkt-showcase__price');
            var isVariable = cta && cta.dataset.isVariable === '1';

            if (!cta) return;

            // ── Variation handling ──
            var variationsData = [];
            var selectedVariationId = 0;

            if (isVariable && item.dataset.variations) {
                try {
                    variationsData = JSON.parse(item.dataset.variations);
                } catch(e) { /* ignore */ }

                var selects = item.querySelectorAll('.po-produkt-showcase__variation-select');

                selects.forEach(function(sel) {
                    sel.addEventListener('change', function() {
                        // Gather all selected attributes
                        var selected = {};
                        var allSelected = true;
                        selects.forEach(function(s) {
                            var attrName = s.dataset.attributeName;
                            var val = s.value;
                            if (!val) allSelected = false;
                            selected[attrName] = val;
                        });

                        if (!allSelected) {
                            cta.disabled = true;
                            selectedVariationId = 0;
                            return;
                        }

                        // Find matching variation
                        var match = variationsData.find(function(v) {
                            return Object.keys(v.attributes).every(function(key) {
                                // Empty string in WC means "any value matches"
                                return v.attributes[key] === '' || v.attributes[key] === selected[key];
                            });
                        });

                        if (match && match.is_in_stock) {
                            selectedVariationId = match.variation_id;
                            cta.disabled = false;
                            // Update price display
                            if (priceEl && match.price_html) {
                                priceEl.innerHTML = match.price_html;
                            }
                        } else {
                            selectedVariationId = 0;
                            cta.disabled = true;
                            if (priceEl) {
                                priceEl.innerHTML = '<span style="opacity:0.4">Nicht verfuegbar</span>';
                            }
                        }
                    });
                });
            }

            // ── Add-to-cart click ──
            cta.addEventListener('click', function() {
                // Pre-Check: bei nicht-leerem Cart sofort Roger-Notice statt AJAX.
                if (poCartHasItems()) {
                    poShowSingleBookingAlert(item);
                    return;
                }

                if (cta.disabled) return;

                cta.disabled = true;
                cta.classList.add('is-loading');
                feedback.textContent = '';
                feedback.className = 'po-produkt-showcase__feedback';

                var data = new FormData();
                data.append('action', 'po_produkt_showcase_add_to_cart');
                data.append('nonce', nonce);
                data.append('product_id', cta.dataset.productId);

                if (isVariable && selectedVariationId) {
                    data.append('variation_id', selectedVariationId);
                    // Send selected attribute values
                    var selects = item.querySelectorAll('.po-produkt-showcase__variation-select');
                    selects.forEach(function(s) {
                        data.append(s.dataset.attributeName, s.value);
                    });
                }

                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(function(res) { return res.json(); })
                .then(function(res) {
                    if (res.success) {
                        feedback.textContent = res.data.message || 'Zum Warenkorb hinzugefuegt!';
                        feedback.classList.add('is-success');

                        // WooCommerce Fragments aktualisieren + Side-Cart oeffnen
                        if (typeof jQuery !== 'undefined') {
                            jQuery(document.body).trigger('wc_fragment_refresh');
                            jQuery(document.body).trigger('added_to_cart');
                        }

                        // Button nach Erfolg wieder aktivieren
                        setTimeout(function() {
                            cta.disabled = false;
                        }, 2000);
                    } else {
                        feedback.textContent = res.data.message || 'Ein Fehler ist aufgetreten.';
                        feedback.classList.add('is-error');
                        cta.disabled = false;
                    }
                })
                .catch(function() {
                    feedback.textContent = 'Verbindungsfehler. Bitte versuche es erneut.';
                    feedback.classList.add('is-error');
                    cta.disabled = false;
                })
                .finally(function() {
                    cta.classList.remove('is-loading');
                });
            });
        });
    });
})();
