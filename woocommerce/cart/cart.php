<?php
/**
 * Cart Page
 * @package ParkourONE
 */
defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>

<div class="wc-cart">
<?php if (WC()->cart->is_empty()) : ?>

	<div class="wc-cart__empty">
		<p>Dein Warenkorb ist leer.</p>
		<a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="wc-cart__btn">
			Weiter einkaufen
		</a>
	</div>

<?php else : ?>

	<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
		<?php do_action('woocommerce_before_cart_table'); ?>

		<div class="wc-cart__header">
			<h1>Warenkorb</h1>
			<a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Weiter einkaufen</a>
		</div>

		<table class="wc-cart__table">
			<thead>
				<tr>
					<th class="product-thumbnail">&nbsp;</th>
					<th class="product-name">Produkt</th>
					<th class="product-price">Preis</th>
					<th class="product-quantity">Anzahl</th>
					<th class="product-subtotal">Gesamt</th>
					<th class="product-remove">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
				$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

				if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
					$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
					?>
					<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">

						<td class="product-thumbnail">
							<?php
							$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
							if (!$product_permalink) {
								echo $thumbnail;
							} else {
								printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
							}
							?>
						</td>

						<td class="product-name" data-title="<?php esc_attr_e('Produkt', 'woocommerce'); ?>">
							<?php
							if (!$product_permalink) {
								echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
							} else {
								echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
							}
							do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
							echo wc_get_formatted_cart_item_data($cart_item);
							?>
						</td>

						<td class="product-price" data-title="<?php esc_attr_e('Preis', 'woocommerce'); ?>">
							<?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?>
						</td>

						<td class="product-quantity" data-title="<?php esc_attr_e('Anzahl', 'woocommerce'); ?>">
							<?php
							if ($_product->is_sold_individually()) {
								$product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
							} else {
								$product_quantity = woocommerce_quantity_input(
									array(
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $_product->get_max_purchase_quantity(),
										'min_value'    => '0',
										'product_name' => $_product->get_name(),
									),
									$_product,
									false
								);
							}
							echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
							?>
						</td>

						<td class="product-subtotal" data-title="<?php esc_attr_e('Gesamt', 'woocommerce'); ?>">
							<?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
						</td>

						<td class="product-remove">
							<?php
							echo apply_filters(
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
									esc_url(wc_get_cart_remove_url($cart_item_key)),
									esc_html__('Entfernen', 'woocommerce'),
									esc_attr($product_id),
									esc_attr($_product->get_sku())
								),
								$cart_item_key
							);
							?>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>

		<div class="wc-cart__actions">
			<?php if (wc_coupons_enabled()) : ?>
				<div class="coupon">
					<input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e('Gutscheincode', 'woocommerce'); ?>" />
					<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e('Einlösen', 'woocommerce'); ?>"><?php esc_html_e('Einlösen', 'woocommerce'); ?></button>
				</div>
			<?php endif; ?>
			<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e('Aktualisieren', 'woocommerce'); ?>"><?php esc_html_e('Warenkorb aktualisieren', 'woocommerce'); ?></button>
			<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
		</div>

		<?php do_action('woocommerce_after_cart_table'); ?>
	</form>

	<div class="wc-cart__totals">
		<?php do_action('woocommerce_before_cart_collaterals'); ?>

		<div class="cart_totals">
			<h2><?php esc_html_e('Zusammenfassung', 'woocommerce'); ?></h2>

			<table>
				<tr class="cart-subtotal">
					<th><?php esc_html_e('Zwischensumme', 'woocommerce'); ?></th>
					<td><?php wc_cart_totals_subtotal_html(); ?></td>
				</tr>

				<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
					<tr class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
						<th><?php wc_cart_totals_coupon_label($coupon); ?></th>
						<td><?php wc_cart_totals_coupon_html($coupon); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php foreach (WC()->cart->get_fees() as $fee) : ?>
					<tr class="fee">
						<th><?php echo esc_html($fee->name); ?></th>
						<td><?php wc_cart_totals_fee_html($fee); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
					<?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
						<?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
							<tr class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
								<th><?php echo esc_html($tax->label); ?></th>
								<td><?php echo wp_kses_post($tax->formatted_amount); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr class="tax-total">
							<th><?php echo esc_html(WC()->countries->tax_or_vat()); ?></th>
							<td><?php wc_cart_totals_taxes_total_html(); ?></td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>

				<tr class="order-total">
					<th><?php esc_html_e('Gesamt', 'woocommerce'); ?></th>
					<td><?php wc_cart_totals_order_total_html(); ?></td>
				</tr>
			</table>

			<div class="wc-proceed-to-checkout">
				<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button button">
					<?php esc_html_e('Zur Kasse', 'woocommerce'); ?>
				</a>
			</div>
		</div>
	</div>

<?php endif; ?>
</div>

<?php do_action('woocommerce_after_cart'); ?>
