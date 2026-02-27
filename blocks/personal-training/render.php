<?php
$headline        = $attributes['headline'] ?? 'Personal Training';
$subtext         = $attributes['subtext'] ?? '';
$packages        = $attributes['packages'] ?? [];
$work_on_options = $attributes['workOnOptions'] ?? [];
$work_on_label   = $attributes['workOnLabel'] ?? 'Woran moechtest du arbeiten?';
$cta_text        = $attributes['ctaText'] ?? 'Jetzt buchen';

// Produkt-ID: primaer aus Block-Attribut
$product_id = !empty($attributes['productId']) ? intval($attributes['productId']) : 0;

$nonce = wp_create_nonce('po_pt_nonce');

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'po-pt']);
?>

<section <?php echo $wrapper_attributes; ?>>

    <?php if (empty($product_id) && current_user_can('manage_options')): ?>
        <div class="po-pt__notice">
            Kein WooCommerce-Produkt konfiguriert. Bitte im Block-Editor unter den Block-Einstellungen ein Produkt auswaehlen.
        </div>
    <?php endif; ?>

    <div class="po-pt__header">
        <h2 class="po-pt__headline"><?php echo esc_html($headline); ?></h2>
        <?php if ($subtext): ?>
            <p class="po-pt__subtext"><?php echo esc_html($subtext); ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($packages)): ?>
        <div class="po-pt__packages">
            <?php foreach ($packages as $index => $pkg): ?>
                <button type="button"
                        class="po-pt__card<?php echo !empty($pkg['highlighted']) ? ' po-pt__card--highlighted' : ''; ?>"
                        data-package-index="<?php echo esc_attr($index); ?>"
                        data-package-title="<?php echo esc_attr($pkg['title'] ?? ''); ?>"
                        data-package-price="<?php echo esc_attr($pkg['price'] ?? '0'); ?>">
                    <?php if (!empty($pkg['highlighted'])): ?>
                        <span class="po-pt__card-badge">Beliebt</span>
                    <?php endif; ?>
                    <span class="po-pt__card-title"><?php echo esc_html($pkg['title'] ?? ''); ?></span>
                    <span class="po-pt__card-price"><?php echo esc_html(number_format((float)($pkg['price'] ?? 0), 0, ',', '.')); ?>&nbsp;&euro;</span>
                    <span class="po-pt__card-hours"><?php echo esc_html($pkg['hours'] ?? '1'); ?>&nbsp;<?php echo ((int)($pkg['hours'] ?? 1)) === 1 ? 'Stunde' : 'Stunden'; ?></span>
                    <span class="po-pt__card-desc"><?php echo esc_html($pkg['description'] ?? ''); ?></span>
                    <span class="po-pt__card-radio" aria-hidden="true"></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($work_on_options)): ?>
        <div class="po-pt__work-on">
            <h3 class="po-pt__work-on-label"><?php echo esc_html($work_on_label); ?></h3>
            <div class="po-pt__work-on-options">
                <?php foreach ($work_on_options as $i => $option): ?>
                    <label class="po-pt__work-on-option">
                        <input type="checkbox"
                               name="po_pt_work_on[]"
                               value="<?php echo esc_attr($option); ?>"
                               class="po-pt__work-on-checkbox">
                        <span class="po-pt__work-on-check" aria-hidden="true"></span>
                        <span class="po-pt__work-on-text"><?php echo esc_html($option); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="po-pt__work-on-other" hidden>
                <label for="po-pt-other-text" class="po-pt__work-on-other-label">Was genau?</label>
                <textarea id="po-pt-other-text"
                          class="po-pt__textarea"
                          rows="3"
                          maxlength="500"
                          placeholder="Beschreibe kurz, woran du arbeiten moechtest..."></textarea>
            </div>
        </div>
    <?php endif; ?>

    <div class="po-pt__actions">
        <button type="button" class="po-pt__cta" disabled>
            <?php echo esc_html($cta_text); ?>
        </button>
        <div class="po-pt__feedback" role="status" aria-live="polite"></div>
    </div>

    <input type="hidden" class="po-pt__product-id" value="<?php echo esc_attr($product_id); ?>">
    <input type="hidden" class="po-pt__nonce" value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" class="po-pt__ajax-url" value="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
</section>
