(function() {
    var root = document.querySelector('.po-gutschein');
    if (!root) return;

    var amounts      = root.querySelectorAll('.po-gutschein__amount');
    var customInput  = root.querySelector('.po-gutschein__custom-input');
    var cta          = root.querySelector('.po-gutschein__cta');
    var feedback     = root.querySelector('.po-gutschein__feedback');
    var productId    = root.querySelector('.po-gutschein__product-id');
    var nonce        = root.querySelector('.po-gutschein__nonce');
    var ajaxUrl      = root.querySelector('.po-gutschein__ajax-url');
    var toggle       = root.querySelector('.po-gutschein__recipient-toggle');
    var form         = root.querySelector('.po-gutschein__recipient-form');
    var emailInput   = root.querySelector('#po-gutschein-email');
    var messageInput = root.querySelector('#po-gutschein-message');
    var charCurrent  = root.querySelector('.po-gutschein__char-current');
    var chevron      = root.querySelector('.po-gutschein__recipient-chevron');

    var selectedAmount = 0;
    var ctaLabel = cta.textContent.trim();

    function setAmount(val) {
        selectedAmount = parseFloat(val) || 0;
        cta.disabled = selectedAmount <= 0;
        if (selectedAmount > 0) {
            cta.textContent = ctaLabel + ' \u2013 ' + selectedAmount.toLocaleString('de-DE') + ' \u20ac';
        } else {
            cta.textContent = ctaLabel;
        }
    }

    // Preset-Buttons
    amounts.forEach(function(btn) {
        btn.addEventListener('click', function() {
            amounts.forEach(function(b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            customInput.value = '';
            setAmount(btn.dataset.amount);
        });
    });

    // Custom-Input
    customInput.addEventListener('input', function() {
        amounts.forEach(function(b) { b.classList.remove('is-active'); });
        setAmount(customInput.value);
    });

    // Empfaenger-Toggle
    toggle.addEventListener('click', function() {
        var isHidden = form.hidden;
        form.hidden = !isHidden;
        chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
        toggle.classList.toggle('is-open', isHidden);
    });

    // Zeichenzaehler
    if (messageInput && charCurrent) {
        messageInput.addEventListener('input', function() {
            charCurrent.textContent = messageInput.value.length;
        });
    }

    // CTA Click
    cta.addEventListener('click', function() {
        if (cta.disabled || selectedAmount <= 0) return;

        cta.disabled = true;
        cta.classList.add('is-loading');
        feedback.textContent = '';
        feedback.className = 'po-gutschein__feedback';

        var data = new FormData();
        data.append('action', 'ab_gutschein_add_to_cart');
        data.append('nonce', nonce.value);
        data.append('product_id', productId.value);
        data.append('amount', selectedAmount);
        if (emailInput && emailInput.value) {
            data.append('recipient_email', emailInput.value);
        }
        if (messageInput && messageInput.value) {
            data.append('message', messageInput.value);
        }

        fetch(ajaxUrl.value, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.success) {
                feedback.textContent = res.data.message || 'Gutschein hinzugefuegt!';
                feedback.classList.add('is-success');

                // WooCommerce Fragments aktualisieren + Side-Cart oeffnen
                // Der Side-Cart hoert auf 'added_to_cart' und oeffnet sich automatisch
                if (typeof jQuery !== 'undefined') {
                    jQuery(document.body).trigger('wc_fragment_refresh');
                    jQuery(document.body).trigger('added_to_cart');
                }

                // Formular zuruecksetzen
                amounts.forEach(function(b) { b.classList.remove('is-active'); });
                customInput.value = '';
                if (emailInput) emailInput.value = '';
                if (messageInput) {
                    messageInput.value = '';
                    if (charCurrent) charCurrent.textContent = '0';
                }
                setAmount(0);
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
})();
