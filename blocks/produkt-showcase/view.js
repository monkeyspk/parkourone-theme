(function() {
    var roots = document.querySelectorAll('.po-produkt-showcase');
    if (!roots.length) return;

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
