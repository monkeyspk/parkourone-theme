(function($) {
    'use strict';

    // Single-Booking-Gate: WC-Cookie prüft ob bereits ein Item im Warenkorb liegt.
    // Server-Backstop in parkourone_rest_add_to_cart() (functions.php) blockt zusätzlich.
    function poCartHasItems() {
        return /(?:^|;\s*)woocommerce_items_in_cart=1(?:;|$)/.test(document.cookie);
    }
    var PO_SINGLE_BOOKING_NOTICE = 'Sorry, mehrere Buchungen gleichzeitig sind technisch gerade nicht möglich. Wir arbeiten daran. Bitte schliesse die aktuelle Buchung erst ab und starte dann eine neue für die weitere Person.';

    // Schicker Modal-Dialog statt nativem alert(). Apple-Style: Backdrop mit blur,
    // weiße Card, Brand-Blau CTA. Idempotent, ESC + Backdrop schliessen, Focus auf Button.
    function poShowDialog(message) {
        var existing = document.querySelector('.po-dialog');
        if (existing) { existing.remove(); }

        var dialog = document.createElement('div');
        dialog.className = 'po-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        // z-index: 2147483647 (max int32) — must stack above .po-overlay (999999) and toast (1000000) in components.css
        dialog.style.cssText = 'position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;animation:poDialogFade 180ms ease-out;';

        var backdrop = document.createElement('div');
        backdrop.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);';

        var panel = document.createElement('div');
        panel.style.cssText = 'position:relative;background:#fff;border-radius:18px;padding:1.75rem 1.5rem 1.25rem;max-width:420px;width:calc(100% - 2rem);box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;';

        var msg = document.createElement('p');
        msg.textContent = message;
        msg.style.cssText = 'margin:0 0 1.25rem;color:#1a1a1a;font-size:1rem;line-height:1.5;';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Verstanden';
        btn.style.cssText = 'display:block;width:100%;padding:0.875rem 1.25rem;border:0;border-radius:12px;background:#0066cc;color:#fff;font-size:0.9375rem;font-weight:600;cursor:pointer;-webkit-appearance:none;transition:background 120ms ease;';
        btn.addEventListener('mouseenter', function() { btn.style.background = '#0052a3'; });
        btn.addEventListener('mouseleave', function() { btn.style.background = '#0066cc'; });

        function close() {
            dialog.remove();
            document.removeEventListener('keydown', onKey);

            // Wenn Cart Items hat: Booking-Modal schliessen + Side-Cart öffnen.
            // Sinnvoller Folge-Step für den User — er sieht direkt was schon drin ist.
            if (poCartHasItems()) {
                var $activeOverlay = $('.po-overlay.is-active');
                if ($activeOverlay.length) {
                    $activeOverlay.find('.po-overlay__close').first().trigger('click');
                }
                if (window.poSideCartInstance && typeof window.poSideCartInstance.open === 'function') {
                    setTimeout(function() { window.poSideCartInstance.open(); }, 120);
                }
            }
        }
        function onKey(e) { if (e.key === 'Escape') { close(); } }

        btn.addEventListener('click', close);
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', onKey);

        panel.appendChild(msg);
        panel.appendChild(btn);
        dialog.appendChild(backdrop);
        dialog.appendChild(panel);
        document.body.appendChild(dialog);

        setTimeout(function() { btn.focus(); }, 0);
    }
    window.poShowDialog = poShowDialog;

    function goToStep($steps, step) {
        var $slides = $steps.find('.po-steps__slide');
        
        $slides.each(function(i) {
            var $slide = $(this);
            $slide.removeClass('is-active is-prev is-next');
            if (i < step) {
                $slide.addClass('is-prev');
            } else if (i === step) {
                $slide.addClass('is-active');
            } else {
                $slide.addClass('is-next');
            }
        });
        
        $steps.attr('data-step', step);
        
        $steps.closest('.po-overlay__panel').scrollTop(0);
    }
    
    $(document).on('click', '.po-steps__next', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $this = $(this);
        var $steps = $this.closest('.po-steps');
        var currentStep = parseInt($steps.attr('data-step')) || 0;

        // Single-Booking-Gate: nur am Einstieg (Slide 0 → Slide 1) blocken,
        // damit Step-back-Navigation in laufender Buchung nicht stört.
        if (currentStep === 0 && poCartHasItems()) {
            poShowDialog(PO_SINGLE_BOOKING_NOTICE);
            return;
        }

        if ($this.hasClass('po-steps__date')) {
            var productId = $this.data('product-id');
            var dateText = $this.data('date-text');
            $steps.find('[name="product_id"]').val(productId);
            $steps.find('.po-steps__selected-date').text(dateText);
            $steps.find('.po-steps__selected-date-confirm').text(dateText);
        }
        
        goToStep($steps, currentStep + 1);
    });
    
    $(document).on('click', '.po-steps__back-link', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $steps = $(this).closest('.po-steps');
        var currentStep = parseInt($steps.attr('data-step')) || 0;
        
        if (currentStep > 0) {
            goToStep($steps, currentStep - 1);
        }
    });
    
    $(document).on('submit', '.po-steps__form', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('.po-steps__submit');
        var $modal = $form.closest('.po-overlay');
        var $steps = $form.closest('.po-steps');

        var data = {
            product_id: $form.find('[name="product_id"]').val(),
            event_id: $form.find('[name="event_id"]').val(),
            vorname: $form.find('[name="vorname"]').val(),
            name: $form.find('[name="name"]').val(),
            geburtsdatum: $form.find('[name="geburtsdatum"]').val()
        };

        if ($form.data('eltern-kind')) {
            data.kind_vorname = $form.find('[name="kind_vorname"]').val();
            data.kind_name = $form.find('[name="kind_name"]').val();
            data.kind_geburtsdatum = $form.find('[name="kind_geburtsdatum"]').val();
        }

        $btn.prop('disabled', true).text('Wird hinzugefügt...');

        fetch(poBooking.restUrl || poBooking.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': poBooking.nonce
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success) {
                goToStep($steps, 3);

                $form[0].reset();

                setTimeout(function() {
                    $modal.removeClass('is-active');
                    $modal.attr('aria-hidden', 'true');
                    $('body').removeClass('po-no-scroll');

                    $(document.body).trigger('wc_fragment_refresh');
                    $(document.body).trigger('added_to_cart');

                    setTimeout(function() {
                        goToStep($steps, 0);
                    }, 300);
                }, 1500);
            } else {
                poShowDialog(response.data && response.data.message ? response.data.message : 'Ein Fehler ist aufgetreten');
            }

            $btn.prop('disabled', false).text('Zum Warenkorb hinzufügen');
        })
        .catch(function() {
            poShowDialog('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
            $btn.prop('disabled', false).text('Zum Warenkorb hinzufügen');
        });
    });
    
    $(document).on('click', '.po-overlay__close', function() {
        var $modal = $(this).closest('.po-overlay');
        var $steps = $modal.find('.po-steps');
        
        setTimeout(function() {
            goToStep($steps, 0);
            $steps.find('.po-steps__form')[0]?.reset();
        }, 300);
    });
    
    $(document).on('click', '.po-overlay__backdrop', function() {
        var $modal = $(this).closest('.po-overlay');
        var $steps = $modal.find('.po-steps');
        
        setTimeout(function() {
            goToStep($steps, 0);
            $steps.find('.po-steps__form')[0]?.reset();
        }, 300);
    });
    
})(jQuery);
