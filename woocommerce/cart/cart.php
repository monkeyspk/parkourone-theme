<?php
/**
 * Cart Page - Clean & Modern Design
 *
 * @package ParkourONE
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>

<div class="wc-cart">
	<?php if (WC()->cart->is_empty()) : ?>

		<div class="wc-cart__empty">
			<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
				<circle cx="9" cy="21" r="1"></circle>
				<circle cx="20" cy="21" r="1"></circle>
				<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
			</svg>
			<p>Dein Warenkorb ist leer.</p>
			<a href="<?php echo esc_url(home_url('/angebote/')); ?>" class="wc-cart__btn wc-cart__btn--primary">
				Angebote entdecken
			</a>
		</div>

	<?php else : ?>

		<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

			<!-- Header -->
			<div class="wc-cart__header">
				<h1 class="wc-cart__title">Warenkorb</h1>
				<a href="<?php echo esc_url(home_url('/angebote/')); ?>" class="wc-cart__continue">
					Weiter einkaufen
				</a>
			</div>

			<!-- Table Header -->
			<div class="wc-cart__table-header">
				<span class="wc-cart__col-product">Produkt</span>
				<span class="wc-cart__col-price">Preis</span>
				<span class="wc-cart__col-qty">Anzahl</span>
				<span class="wc-cart__col-total">Gesamt</span>
			</div>

			<!-- Cart Items -->
			<div class="wc-cart__items">
				<?php
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
					$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

					if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
						$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
						$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
						?>

						<div class="wc-cart__item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">

							<!-- Product (Image + Name) -->
							<div class="wc-cart__col-product">
								<div class="wc-cart__item-image">
									<?php
									$thumbnail = $_product->get_image('thumbnail');
									if ($product_permalink) {
										echo '<a href="' . esc_url($product_permalink) . '">' . $thumbnail . '</a>';
									} else {
										echo $thumbnail;
									}
									?>
								</div>
								<div class="wc-cart__item-info">
									<span class="wc-cart__item-name">
										<?php
										if ($product_permalink) {
											echo '<a href="' . esc_url($product_permalink) . '">' . wp_kses_post($product_name) . '</a>';
										} else {
											echo wp_kses_post($product_name);
										}
										?>
									</span>
									<?php echo wc_get_formatted_cart_item_data($cart_item); ?>
								</div>
							</div>

							<!-- Price -->
							<div class="wc-cart__col-price">
								<?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?>
							</div>

							<!-- Quantity -->
							<div class="wc-cart__col-qty">
								<?php
								if ($_product->is_sold_individually()) {
									echo '<span class="wc-cart__qty-single">1</span>';
								} else {
									$max_qty = $_product->get_max_purchase_quantity();
									?>
									<div class="wc-cart__qty-wrapper">
										<button type="button" class="wc-cart__qty-btn wc-cart__qty-minus" aria-label="Minus">−</button>
										<input type="number"
											name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]"
											value="<?php echo esc_attr($cart_item['quantity']); ?>"
											min="0"
											max="<?php echo esc_attr($max_qty > 0 ? $max_qty : ''); ?>"
											class="wc-cart__qty-input"
											aria-label="Anzahl"
										/>
										<button type="button" class="wc-cart__qty-btn wc-cart__qty-plus" aria-label="Plus">+</button>
									</div>
									<?php
								}
								?>
							</div>

							<!-- Total + Remove -->
							<div class="wc-cart__col-total">
								<span class="wc-cart__item-total">
									<?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
								</span>
								<a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>"
								   class="wc-cart__remove"
								   aria-label="<?php esc_attr_e('Entfernen', 'parkourone'); ?>"
								   data-product_id="<?php echo esc_attr($product_id); ?>"
								   data-cart_item_key="<?php echo esc_attr($cart_item_key); ?>">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
									</svg>
								</a>
							</div>

						</div>
						<?php
					}
				}
				?>
			</div>

			<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
			<button type="submit" class="wc-cart__update" name="update_cart" value="1">Aktualisieren</button>

			<!-- Summary -->
			<div class="wc-cart__summary">
				<div class="wc-cart__summary-row">
					<span>Zwischensumme</span>
					<span><?php wc_cart_totals_subtotal_html(); ?></span>
				</div>

				<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
					<div class="wc-cart__summary-row wc-cart__summary-row--coupon">
						<span><?php wc_cart_totals_coupon_label($coupon); ?></span>
						<span><?php wc_cart_totals_coupon_html($coupon); ?></span>
					</div>
				<?php endforeach; ?>

				<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
					<?php wc_cart_totals_shipping_html(); ?>
				<?php endif; ?>

				<?php foreach (WC()->cart->get_fees() as $fee) : ?>
					<div class="wc-cart__summary-row">
						<span><?php echo esc_html($fee->name); ?></span>
						<span><?php wc_cart_totals_fee_html($fee); ?></span>
					</div>
				<?php endforeach; ?>

				<?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
					<div class="wc-cart__summary-row">
						<span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
						<span><?php wc_cart_totals_taxes_total_html(); ?></span>
					</div>
				<?php endif; ?>

				<div class="wc-cart__summary-row wc-cart__summary-row--total">
					<span>Gesamt</span>
					<span><?php wc_cart_totals_order_total_html(); ?></span>
				</div>

				<p class="wc-cart__tax-note">
					<?php echo wp_kses_post(wc_get_price_suffix()); ?>
				</p>

				<?php if (wc_coupons_enabled()) : ?>
					<div class="wc-cart__coupon">
						<input type="text" name="coupon_code" class="wc-cart__coupon-input" placeholder="Gutscheincode" />
						<button type="submit" name="apply_coupon" class="wc-cart__coupon-btn">Einlösen</button>
					</div>
				<?php endif; ?>

				<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="wc-cart__btn wc-cart__btn--checkout">
					Zur Kasse
				</a>
			</div>

		</form>

	<?php endif; ?>
</div>

<?php do_action('woocommerce_after_cart'); ?>
