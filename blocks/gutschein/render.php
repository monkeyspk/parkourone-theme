<?php
$headline     = $attributes['headline'] ?? 'Parkour verschenken';
$subtext      = $attributes['subtext'] ?? '';
$image        = $attributes['image'] ?? '';
$inspirations = $attributes['inspirations'] ?? [];
$cta_text     = $attributes['ctaText'] ?? 'In den Warenkorb';

// Plugin-Daten laden
$product_id     = '';
$preset_amounts = [25, 50, 100];
$min_amount     = 10;
$max_amount     = 500;

if (class_exists('AB_Gutschein_Settings')) {
    $product_id     = AB_Gutschein_Settings::get_setting('product_id', '');
    $preset_amounts = AB_Gutschein_Settings::get_preset_amounts();
    $min_amount     = floatval(AB_Gutschein_Settings::get_setting('min_amount', 10));
    $max_amount     = floatval(AB_Gutschein_Settings::get_setting('max_amount', 500));
}

$nonce = wp_create_nonce('ab_gutschein_nonce');

// Icons fuer Inspirations-Karten
$icons = [
    'ticket'   => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>',
    'user'     => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
];

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'po-gutschein']);
?>

<section <?php echo $wrapper_attributes; ?>>

    <?php if (empty($product_id) && current_user_can('manage_options')): ?>
        <div class="po-gutschein__notice">
            Gutschein-Produkt nicht konfiguriert. Bitte unter
            <a href="<?php echo esc_url(admin_url('admin.php?page=ab-gutschein-settings')); ?>">WooCommerce &rarr; AB Gutscheine</a>
            einrichten.
        </div>
    <?php endif; ?>

    <div class="po-gutschein__header">
        <h2 class="po-gutschein__headline"><?php echo esc_html($headline); ?></h2>
        <?php if ($subtext): ?>
            <p class="po-gutschein__subtext"><?php echo esc_html($subtext); ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($inspirations)): ?>
        <div class="po-gutschein__inspirations">
            <?php foreach ($inspirations as $item): ?>
                <div class="po-gutschein__card">
                    <?php
                    $icon_key = $item['icon'] ?? 'ticket';
                    if (isset($icons[$icon_key])):
                    ?>
                        <div class="po-gutschein__card-icon"><?php echo $icons[$icon_key]; ?></div>
                    <?php endif; ?>
                    <h3 class="po-gutschein__card-title"><?php echo esc_html($item['title'] ?? ''); ?></h3>
                    <p class="po-gutschein__card-desc"><?php echo esc_html($item['description'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($image): ?>
        <div class="po-gutschein__visual">
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($headline); ?>" loading="lazy">
        </div>
    <?php endif; ?>

    <div class="po-gutschein__selector">
        <h3 class="po-gutschein__selector-title">Betrag waehlen</h3>
        <div class="po-gutschein__amounts">
            <?php foreach ($preset_amounts as $amount): ?>
                <button type="button" class="po-gutschein__amount" data-amount="<?php echo esc_attr($amount); ?>">
                    <?php echo number_format($amount, 0, ',', '.'); ?>&nbsp;&euro;
                </button>
            <?php endforeach; ?>
        </div>
        <div class="po-gutschein__custom">
            <label class="po-gutschein__custom-label" for="po-gutschein-custom">Eigener Betrag</label>
            <div class="po-gutschein__custom-wrap">
                <input type="number"
                       id="po-gutschein-custom"
                       class="po-gutschein__custom-input"
                       min="<?php echo esc_attr($min_amount); ?>"
                       max="<?php echo esc_attr($max_amount); ?>"
                       step="1"
                       placeholder="<?php echo esc_attr($min_amount . ' – ' . $max_amount); ?>">
                <span class="po-gutschein__custom-currency">&euro;</span>
            </div>
        </div>
    </div>

    <div class="po-gutschein__recipient">
        <button type="button" class="po-gutschein__recipient-toggle">
            <span>Direkt an jemanden verschicken</span>
            <span class="po-gutschein__recipient-badge">Optional</span>
            <svg class="po-gutschein__recipient-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="po-gutschein__recipient-form" hidden>
            <div class="po-gutschein__field">
                <label for="po-gutschein-email">E-Mail des Empfaengers</label>
                <input type="email" id="po-gutschein-email" class="po-gutschein__input" placeholder="empfaenger@email.de">
            </div>
            <div class="po-gutschein__field">
                <label for="po-gutschein-message">Persoenliche Nachricht</label>
                <textarea id="po-gutschein-message" class="po-gutschein__textarea" rows="3" maxlength="500" placeholder="Alles Gute zum Geburtstag! Viel Spass beim Parkour..."></textarea>
                <span class="po-gutschein__char-count"><span class="po-gutschein__char-current">0</span>/500</span>
            </div>
        </div>
    </div>

    <div class="po-gutschein__actions">
        <button type="button" class="po-gutschein__cta" disabled>
            <?php echo esc_html($cta_text); ?>
        </button>
        <div class="po-gutschein__feedback" role="status" aria-live="polite"></div>
    </div>

    <input type="hidden" class="po-gutschein__product-id" value="<?php echo esc_attr($product_id); ?>">
    <input type="hidden" class="po-gutschein__nonce" value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" class="po-gutschein__ajax-url" value="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
</section>

<script>
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

                // WooCommerce Fragments aktualisieren
                if (typeof jQuery !== 'undefined') {
                    jQuery(document.body).trigger('wc_fragment_refresh');
                }

                // Side-Cart oeffnen (falls vorhanden)
                setTimeout(function() {
                    var cartToggle = document.querySelector('.po-side-cart-toggle, .cart-toggle, [data-cart-toggle]');
                    if (cartToggle) cartToggle.click();
                }, 400);

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
</script>
