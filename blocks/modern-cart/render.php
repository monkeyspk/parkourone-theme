<?php
/**
 * Modern Cart Block - Render
 *
 * @package ParkourONE
 */

if (!defined('ABSPATH')) {
	exit;
}

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
	return;
}

$show_coupon = $attributes['showCoupon'] ?? true;
$show_thumbnails = $attributes['showThumbnails'] ?? true;
$checkout_button_text = $attributes['checkoutButtonText'] ?? 'Zur Kasse';
$empty_cart_text = $attributes['emptyCartText'] ?? 'Dein Warenkorb ist leer';
$continue_shopping_url = $attributes['continueShoppingUrl'] ?? '/angebote/';

$cart = WC()->cart;
$cart_items = $cart->get_cart();
$cart_count = $cart->get_cart_contents_count();
$cart_subtotal = $cart->get_cart_subtotal();
$cart_total = $cart->get_cart_total();

// Wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes([
	'class' => 'po-modern-cart',
	'data-ajax-url' => admin_url('admin-ajax.php'),
	'data-nonce' => wp_create_nonce('po_modern_cart_nonce'),
]);
?>

<div <?php echo $wrapper_attributes; ?>>
	<?php if (empty($cart_items)) : ?>
		<!-- Empty Cart State -->
		<div class="po-modern-cart__empty">
			<div class="po-modern-cart__empty-icon">
				<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<circle cx="9" cy="21" r="1"></circle>
					<circle cx="20" cy="21" r="1"></circle>
					<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
				</svg>
			</div>
			<h2 class="po-modern-cart__empty-title"><?php echo esc_html($empty_cart_text); ?></h2>
			<p class="po-modern-cart__empty-text">Entdecke unsere Angebote und finde das passende Training.</p>
			<a href="<?php echo esc_url(home_url($continue_shopping_url)); ?>" class="po-modern-cart__btn po-modern-cart__btn--primary">
				Angebote entdecken
			</a>
		</div>
	<?php else : ?>
		<!-- Cart Header -->
		<div class="po-modern-cart__header">
			<h1 class="po-modern-cart__title">Warenkorb</h1>
			<span class="po-modern-cart__count"><?php echo esc_html($cart_count); ?> <?php echo $cart_count === 1 ? 'Artikel' : 'Artikel'; ?></span>
		</div>

		<div class="po-modern-cart__layout">
			<!-- Cart Items -->
			<div class="po-modern-cart__items" data-cart-items>
				<?php foreach ($cart_items as $cart_item_key => $cart_item) :
					$product = $cart_item['data'];
					$product_id = $cart_item['product_id'];
					$quantity = $cart_item['quantity'];
					$product_name = $product->get_name();
					$product_price = WC()->cart->get_product_price($product);
					$product_subtotal = WC()->cart->get_product_subtotal($product, $quantity);
					$product_permalink = $product->get_permalink();
					$max_qty = $product->get_max_purchase_quantity();
					$min_qty = $product->get_min_purchase_quantity();

					// Get participant data if available (for Angebote)
					$participants = isset($cart_item['angebot_teilnehmer']) ? $cart_item['angebot_teilnehmer'] : [];
					$event_date = isset($cart_item['angebot_termin']) ? $cart_item['angebot_termin'] : '';
				?>
					<div class="po-modern-cart__item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>">
						<?php if ($show_thumbnails) : ?>
							<div class="po-modern-cart__item-image">
								<?php echo $product->get_image('thumbnail'); ?>
							</div>
						<?php endif; ?>

						<div class="po-modern-cart__item-details">
							<a href="<?php echo esc_url($product_permalink); ?>" class="po-modern-cart__item-name">
								<?php echo esc_html($product_name); ?>
							</a>

							<?php if ($event_date) : ?>
								<div class="po-modern-cart__item-meta">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
										<line x1="16" y1="2" x2="16" y2="6"></line>
										<line x1="8" y1="2" x2="8" y2="6"></line>
										<line x1="3" y1="10" x2="21" y2="10"></line>
									</svg>
									<?php echo esc_html($event_date); ?>
								</div>
							<?php endif; ?>

							<?php if (!empty($participants)) : ?>
								<div class="po-modern-cart__item-participants">
									<?php foreach ($participants as $participant) : ?>
										<span class="po-modern-cart__participant">
											<?php echo esc_html($participant['vorname'] . ' ' . $participant['name']); ?>
										</span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<div class="po-modern-cart__item-price-single">
								<?php echo $product_price; ?>
							</div>
						</div>

						<div class="po-modern-cart__item-actions">
							<div class="po-modern-cart__quantity">
								<button type="button" class="po-modern-cart__qty-btn" data-action="decrease" aria-label="Menge verringern" <?php echo $quantity <= $min_qty ? 'disabled' : ''; ?>>
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<line x1="5" y1="12" x2="19" y2="12"></line>
									</svg>
								</button>
								<span class="po-modern-cart__qty-value" data-quantity><?php echo esc_html($quantity); ?></span>
								<button type="button" class="po-modern-cart__qty-btn" data-action="increase" aria-label="Menge erhöhen" <?php echo ($max_qty > 0 && $quantity >= $max_qty) ? 'disabled' : ''; ?>>
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<line x1="12" y1="5" x2="12" y2="19"></line>
										<line x1="5" y1="12" x2="19" y2="12"></line>
									</svg>
								</button>
							</div>

							<div class="po-modern-cart__item-subtotal" data-subtotal>
								<?php echo $product_subtotal; ?>
							</div>

							<button type="button" class="po-modern-cart__remove" data-action="remove" aria-label="Artikel entfernen">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M3 6h18"></path>
									<path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
									<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
									<line x1="10" y1="11" x2="10" y2="17"></line>
									<line x1="14" y1="11" x2="14" y2="17"></line>
								</svg>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Cart Summary -->
			<div class="po-modern-cart__summary">
				<div class="po-modern-cart__summary-card">
					<h3 class="po-modern-cart__summary-title">Zusammenfassung</h3>

					<?php if ($show_coupon) : ?>
						<div class="po-modern-cart__coupon">
							<div class="po-modern-cart__coupon-toggle" data-coupon-toggle>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"></path>
									<path d="M12 2v10"></path>
									<path d="m4.93 10.93 1.41 1.41"></path>
									<path d="m17.66 10.93-1.41 1.41"></path>
								</svg>
								Gutscheincode hinzufügen
							</div>
							<div class="po-modern-cart__coupon-form" data-coupon-form style="display: none;">
								<input type="text" class="po-modern-cart__coupon-input" placeholder="Gutscheincode" data-coupon-input>
								<button type="button" class="po-modern-cart__coupon-btn" data-coupon-apply>Einlösen</button>
							</div>
						</div>
					<?php endif; ?>

					<div class="po-modern-cart__totals">
						<div class="po-modern-cart__totals-row">
							<span>Zwischensumme</span>
							<span data-cart-subtotal><?php echo $cart_subtotal; ?></span>
						</div>

						<?php foreach ($cart->get_coupons() as $coupon_code => $coupon) : ?>
							<div class="po-modern-cart__totals-row po-modern-cart__totals-row--coupon">
								<span>Gutschein: <?php echo esc_html($coupon_code); ?></span>
								<span>-<?php echo wc_price($coupon->get_discount()); ?></span>
							</div>
						<?php endforeach; ?>

						<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
							<div class="po-modern-cart__totals-row">
								<span>Versand</span>
								<span>Wird an der Kasse berechnet</span>
							</div>
						<?php endif; ?>

						<div class="po-modern-cart__totals-row po-modern-cart__totals-row--total">
							<span>Gesamt</span>
							<span data-cart-total><?php echo $cart_total; ?></span>
						</div>
					</div>

					<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="po-modern-cart__btn po-modern-cart__btn--primary po-modern-cart__btn--checkout">
						<?php echo esc_html($checkout_button_text); ?>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="5" y1="12" x2="19" y2="12"></line>
							<polyline points="12 5 19 12 12 19"></polyline>
						</svg>
					</a>

					<a href="<?php echo esc_url(home_url($continue_shopping_url)); ?>" class="po-modern-cart__continue">
						Weiter einkaufen
					</a>

					<div class="po-modern-cart__trust">
						<div class="po-modern-cart__trust-item">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
								<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
							</svg>
							Sichere Bezahlung
						</div>
						<div class="po-modern-cart__trust-item">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
							</svg>
							SSL-verschlüsselt
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Loading Overlay -->
	<div class="po-modern-cart__loading" data-loading style="display: none;">
		<div class="po-modern-cart__spinner"></div>
	</div>
</div>
