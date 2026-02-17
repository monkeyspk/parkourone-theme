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
