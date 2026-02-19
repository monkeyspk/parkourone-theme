<?php
$products       = $attributes['products'] ?? [];
$theme_variant  = $attributes['themeVariant'] ?? 'light';

// Backward compat: alte Blöcke die noch nicht im Editor re-saved wurden
if (empty($products) && !empty($attributes['productId'])) {
    $products = [[
        'productId'   => intval($attributes['productId']),
        'headline'    => $attributes['headline'] ?? '',
        'description' => $attributes['description'] ?? '',
        'imageUrl'    => $attributes['imageUrl'] ?? '',
        'ctaText'     => $attributes['ctaText'] ?? 'In den Warenkorb',
        'badgeText'   => $attributes['badgeText'] ?? '',
    ]];
}

$product_count = count($products);

if ($product_count === 0) {
    if (current_user_can('manage_options')): ?>
        <div class="po-produkt-showcase__notice">Keine Produkte konfiguriert.</div>
    <?php endif;
    return;
}

// Auto-Layout
$auto_layout = $product_count === 1 ? 'horizontal' : 'grid-' . min($product_count, 3);

$nonce = wp_create_nonce('po_produkt_showcase_nonce');
$uid   = 'po-ps-' . uniqid();

$block_class = 'po-produkt-showcase'
    . ' po-produkt-showcase--' . esc_attr($theme_variant)
    . ' po-produkt-showcase--' . esc_attr($auto_layout);

$wrapper_attributes = get_block_wrapper_attributes(['class' => $block_class]);
?>

<section <?php echo $wrapper_attributes; ?>
    id="<?php echo esc_attr($uid); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
>

<?php if ($product_count > 1): ?>
<div class="po-produkt-showcase__grid">
<?php endif; ?>

<?php foreach ($products as $item):
    $product_id = !empty($item['productId']) ? intval($item['productId']) : 0;
    $product    = $product_id && function_exists('wc_get_product') ? wc_get_product($product_id) : null;

    if (!$product) continue;

    $headline    = !empty($item['headline'])    ? $item['headline']    : $product->get_name();
    $description = !empty($item['description']) ? $item['description'] : $product->get_short_description();
    $image_url   = !empty($item['imageUrl'])    ? $item['imageUrl']    : wp_get_attachment_url($product->get_image_id());
    $cta_text    = !empty($item['ctaText'])      ? $item['ctaText']    : 'In den Warenkorb';
    $badge_text  = $item['badgeText'] ?? '';
    $price_html  = $product->get_price_html();
    $is_purchasable = $product->is_purchasable() && $product->is_in_stock();

    // Variable product detection
    $is_variable = $product->is_type('variable');
    $variations_json = '';
    $variation_attributes = [];

    if ($is_variable) {
        $variations = $product->get_available_variations();
        $variation_data = array_map(function($v) {
            return [
                'variation_id'  => $v['variation_id'],
                'attributes'    => $v['attributes'],
                'price_html'    => $v['price_html'],
                'is_in_stock'   => $v['is_in_stock'],
                'display_price' => $v['display_price'],
            ];
        }, $variations);
        $variations_json = wp_json_encode($variation_data);

        $attrs = $product->get_variation_attributes();
        foreach ($attrs as $attr_name => $options) {
            $label = wc_attribute_label($attr_name, $product);
            $variation_attributes[] = [
                'name'    => 'attribute_' . sanitize_title($attr_name),
                'label'   => $label,
                'options' => array_values($options),
            ];
        }
    }

    $card_class = $product_count > 1 ? 'po-produkt-showcase__card' : 'po-produkt-showcase__inner';
?>

    <div class="<?php echo esc_attr($card_class); ?>"
        <?php if ($is_variable && $variations_json): ?>
        data-variations="<?php echo esc_attr($variations_json); ?>"
        <?php endif; ?>
    >
        <?php if ($image_url): ?>
        <div class="po-produkt-showcase__media">
            <?php if ($badge_text): ?>
            <span class="po-produkt-showcase__badge"><?php echo esc_html($badge_text); ?></span>
            <?php endif; ?>
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

            <?php if ($is_variable && !empty($variation_attributes)): ?>
            <div class="po-produkt-showcase__variations">
                <?php foreach ($variation_attributes as $attr): ?>
                <div class="po-produkt-showcase__variation-group">
                    <label class="po-produkt-showcase__variation-label">
                        <?php echo esc_html($attr['label']); ?>
                    </label>
                    <select class="po-produkt-showcase__variation-select"
                            data-attribute-name="<?php echo esc_attr($attr['name']); ?>">
                        <option value="">— <?php echo esc_html($attr['label']); ?> waehlen —</option>
                        <?php foreach ($attr['options'] as $option): ?>
                        <option value="<?php echo esc_attr($option); ?>">
                            <?php echo esc_html($option); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
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
                    <?php echo ($is_purchasable && !$is_variable) ? '' : 'disabled'; ?>
                    data-product-id="<?php echo esc_attr($product_id); ?>"
                    <?php if ($is_variable): ?>data-is-variable="1"<?php endif; ?>
                >
                    <?php echo esc_html($cta_text); ?>
                </button>
                <div class="po-produkt-showcase__feedback" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>

<?php endforeach; ?>

<?php if ($product_count > 1): ?>
</div>
<?php endif; ?>

</section>
