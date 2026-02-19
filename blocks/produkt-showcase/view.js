(function() {
    var roots = document.querySelectorAll('.po-produkt-showcase');
    if (!roots.length) return;

    roots.forEach(function(root) {
        var cta      = root.querySelector('.po-produkt-showcase__cta');
        var feedback = root.querySelector('.po-produkt-showcase__feedback');
        var nonce    = root.dataset.nonce;
        var ajaxUrl  = root.dataset.ajaxUrl;

        if (!cta || cta.disabled) return;

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
})();
