(function($) {
    'use strict';
    
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
            action: 'po_add_to_cart',
            nonce: poBooking.nonce,
            product_id: $form.find('[name="product_id"]').val(),
            event_id: $form.find('[name="event_id"]').val(),
            vorname: $form.find('[name="vorname"]').val(),
            name: $form.find('[name="name"]').val(),
            geburtsdatum: $form.find('[name="geburtsdatum"]').val()
        };
        
        $btn.prop('disabled', true).text('Wird hinzugefügt...');
        
        $.post(poBooking.ajaxUrl, data, function(response) {
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
                alert(response.data.message || 'Ein Fehler ist aufgetreten');
            }
            
            $btn.prop('disabled', false).text('Zum Warenkorb hinzufügen');
        }).fail(function() {
            alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
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
