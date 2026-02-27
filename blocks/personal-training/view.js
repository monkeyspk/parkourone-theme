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

    var selectedPackage = null;
    var ctaLabel = cta.textContent.trim();

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
        cta.textContent = ctaLabel + ' \u2013 ' + parseFloat(selectedPackage.price).toLocaleString('de-DE') + ' \u20ac';
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
