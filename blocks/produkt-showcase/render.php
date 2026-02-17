<?php
$product_id    = !empty($attributes['productId']) ? intval($attributes['productId']) : 0;
$cta_text      = $attributes['ctaText'] ?? 'In den Warenkorb';
$theme_variant = $attributes['themeVariant'] ?? 'light';
$layout        = $attributes['layout'] ?? 'horizontal';

// WooCommerce-Produkt laden
$product = $product_id && function_exists('wc_get_product') ? wc_get_product($product_id) : null;

// Attribute-Override > Produktdaten > Fallback
$headline    = !empty($attributes['headline'])
    ? $attributes['headline']
    : ($product ? $product->get_name() : 'Produkt');

$description = !empty($attributes['description'])
    ? $attributes['description']
    : ($product ? $product->get_short_description() : '');

$image_url = !empty($attributes['imageUrl'])
    ? $attributes['imageUrl']
    : ($product ? wp_get_attachment_url($product->get_image_id()) : '');

$price_html     = $product ? $product->get_price_html() : '';
$is_purchasable = $product && $product->is_purchasable() && $product->is_in_stock();

$nonce = wp_create_nonce('po_produkt_showcase_nonce');

// Unique ID fuer mehrere Instanzen auf einer Seite
$uid = 'po-ps-' . uniqid();

$block_class = 'po-produkt-showcase'
    . ' po-produkt-showcase--' . esc_attr($theme_variant)
    . ' po-produkt-showcase--' . esc_attr($layout);

$wrapper_attributes = get_block_wrapper_attributes(['class' => $block_class]);
?>

<section <?php echo $wrapper_attributes; ?> id="<?php echo esc_attr($uid); ?>">

    <?php if (!$product && current_user_can('manage_options')): ?>
        <div class="po-produkt-showcase__notice">
            Kein Produkt ausgewaehlt. Bitte in den Block-Einstellungen ein WooCommerce-Produkt waehlen.
        </div>
    <?php endif; ?>

    <?php if ($product): ?>
    <div class="po-produkt-showcase__inner">

        <?php if ($image_url): ?>
        <div class="po-produkt-showcase__media">
            <img
                src="<?php echo esc_url($image_url); ?>"
                alt="<?php echo esc_attr($headline); ?>"
                loading="lazy"
                class="po-produkt-showcase__image"
            >
        </div>
        <?php endif; ?>

        <div class="po-produkt-showcase__content">
            <h2 class="po-produkt-showcase__headline"><?php echo esc_html($headline); ?></h2>

            <?php if ($description): ?>
            <div class="po-produkt-showcase__description">
                <?php echo wp_kses_post($description); ?>
            </div>
            <?php endif; ?>

            <?php if ($price_html): ?>
            <div class="po-produkt-showcase__price">
                <?php echo $price_html; ?>
            </div>
            <?php endif; ?>

            <div class="po-produkt-showcase__actions">
                <button
                    type="button"
                    class="po-produkt-showcase__cta"
                    <?php echo $is_purchasable ? '' : 'disabled'; ?>
                    data-product-id="<?php echo esc_attr($product_id); ?>"
                >
                    <?php echo esc_html($cta_text); ?>
                </button>
                <div class="po-produkt-showcase__feedback" role="status" aria-live="polite"></div>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <input type="hidden" class="po-produkt-showcase__nonce" value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" class="po-produkt-showcase__ajax-url" value="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
</section>

<script>
(function() {
    var root = document.getElementById('<?php echo esc_js($uid); ?>');
    if (!root) return;

    var cta      = root.querySelector('.po-produkt-showcase__cta');
    var feedback = root.querySelector('.po-produkt-showcase__feedback');
    var nonce    = root.querySelector('.po-produkt-showcase__nonce');
    var ajaxUrl  = root.querySelector('.po-produkt-showcase__ajax-url');

    if (!cta || cta.disabled) return;

    cta.addEventListener('click', function() {
        if (cta.disabled) return;

        cta.disabled = true;
        cta.classList.add('is-loading');
        feedback.textContent = '';
        feedback.className = 'po-produkt-showcase__feedback';

        var data = new FormData();
        data.append('action', 'po_produkt_showcase_add_to_cart');
        data.append('nonce', nonce.value);
        data.append('product_id', cta.dataset.productId);

        fetch(ajaxUrl.value, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.success) {
                feedback.textContent = res.data.message || 'Zum Warenkorb hinzugefuegt!';
                feedback.classList.add('is-success');

                // WooCommerce Fragments aktualisieren
                if (typeof jQuery !== 'undefined') {
                    jQuery(document.body).trigger('wc_fragment_refresh');
                    jQuery(document.body).trigger('added_to_cart');
                }

                // Side-Cart oeffnen
                setTimeout(function() {
                    var cartToggle = document.querySelector('.po-side-cart-toggle, .cart-toggle, [data-cart-toggle]');
                    if (cartToggle) cartToggle.click();
                }, 400);

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
})();
</script>
