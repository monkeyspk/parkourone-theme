<?php
/**
 * Cart Page
 * ParkourONE Custom Apple-Style Design
 *
 * This template overrides the default WooCommerce cart template.
 *
 * @package ParkourONE
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>

<div class="po-cart">
	<form class="po-cart__form woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
		<?php do_action('woocommerce_before_cart_table'); ?>

		<div class="po-cart__layout">
			<!-- Cart Items Column -->
			<div class="po-cart__items-column">
				<div class="po-cart__header">
					<h1 class="po-cart__title">Warenkorb</h1>
					<span class="po-cart__count"><?php echo WC()->cart->get_cart_contents_count(); ?> <?php echo WC()->cart->get_cart_contents_count() === 1 ? 'Artikel' : 'Artikel'; ?></span>
				</div>

				<?php do_action('woocommerce_before_cart_contents'); ?>

				<div class="po-cart__items">
					<?php
					foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
						$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
						$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
						$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

						if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
							$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);

							// Get participant data
							$participants = isset($cart_item['angebot_teilnehmer']) ? $cart_item['angebot_teilnehmer'] : [];

							// Get event details
							$event_location = get_post_meta($product_id, '_angebot_termin_ort', true);
							$event_date = get_post_meta($product_id, '_angebot_termin_datum', true);
							$event_time = get_post_meta($product_id, '_angebot_termin_zeit', true);
							?>
							<div class="po-cart__item woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-cart-item="<?php echo esc_attr($cart_item_key); ?>">

								<!-- Product Image -->
								<div class="po-cart__item-image">
									<?php
									$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('medium'), $cart_item, $cart_item_key);
									if (!$product_permalink) {
										echo $thumbnail;
									} else {
										printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
									}
									?>
								</div>

								<!-- Product Details -->
								<div class="po-cart__item-details">
									<div class="po-cart__item-header">
										<h3 class="po-cart__item-name">
											<?php
											if (!$product_permalink) {
												echo wp_kses_post($product_name);
											} else {
												echo wp_kses_post(sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $product_name));
											}
											?>
										</h3>

										<!-- Remove Button -->
										<button type="button" class="po-cart__remove" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>" aria-label="<?php esc_attr_e('Produkt entfernen', 'parkourone'); ?>">
											<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
												<path d="M3 6h18"></path>
												<path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
												<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
											</svg>
										</button>
									</div>

									<!-- Event Info -->
									<?php if ($event_date || $event_location) : ?>
										<div class="po-cart__item-meta">
											<?php if ($event_date) : ?>
												<div class="po-cart__item-date">
													<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
														<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
														<line x1="16" y1="2" x2="16" y2="6"></line>
														<line x1="8" y1="2" x2="8" y2="6"></line>
														<line x1="3" y1="10" x2="21" y2="10"></line>
													</svg>
													<?php echo esc_html($event_date); ?>
													<?php if ($event_time) : ?>
														<span class="po-cart__item-time"><?php echo esc_html($event_time); ?></span>
													<?php endif; ?>
												</div>
											<?php endif; ?>

											<?php if ($event_location) : ?>
												<div class="po-cart__item-location">
													<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
														<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
														<circle cx="12" cy="10" r="3"></circle>
													</svg>
													<?php echo esc_html($event_location); ?>
												</div>
											<?php endif; ?>
										</div>
									<?php endif; ?>

									<!-- Participants -->
									<?php if (!empty($participants)) : ?>
										<div class="po-cart__item-participants">
											<span class="po-cart__participants-label">Teilnehmer:</span>
											<?php foreach ($participants as $participant) : ?>
												<div class="po-cart__participant">
													<span class="po-cart__participant-name">
														<?php echo esc_html($participant['vorname'] . ' ' . $participant['name']); ?>
													</span>
													<?php if (!empty($participant['geburtsdatum'])) : ?>
														<span class="po-cart__participant-dob">(<?php echo esc_html($participant['geburtsdatum']); ?>)</span>
													<?php endif; ?>
												</div>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>

									<!-- Product Meta Data -->
									<?php echo wc_get_formatted_cart_item_data($cart_item); ?>

									<!-- Price and Quantity Row -->
									<div class="po-cart__item-footer">
										<!-- Quantity -->
										<div class="po-cart__quantity">
											<?php
											if ($_product->is_sold_individually()) {
												$min_quantity = 1;
												$max_quantity = 1;
											} else {
												$min_quantity = 0;
												$max_quantity = $_product->get_max_purchase_quantity();
											}

											$product_quantity = woocommerce_quantity_input(
												array(
													'input_name'   => "cart[{$cart_item_key}][qty]",
													'input_value'  => $cart_item['quantity'],
													'max_value'    => $max_quantity,
													'min_value'    => $min_quantity,
													'product_name' => $product_name,
													'classes'      => array('po-cart__qty-input'),
												),
												$_product,
												false
											);

											echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
											?>
										</div>

										<!-- Price -->
										<div class="po-cart__item-price">
											<?php
											echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
											?>
										</div>
									</div>
								</div>
							</div>
							<?php
						}
					}
					?>
				</div>

				<?php do_action('woocommerce_cart_contents'); ?>

				<!-- Update Cart Button (hidden, triggered by JS) -->
				<div class="po-cart__update">
					<button type="submit" class="po-cart__update-btn" name="update_cart" value="<?php esc_attr_e('Warenkorb aktualisieren', 'parkourone'); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 2v6h-6"></path>
							<path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
							<path d="M3 22v-6h6"></path>
							<path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
						</svg>
						<?php esc_html_e('Warenkorb aktualisieren', 'parkourone'); ?>
					</button>
				</div>

				<?php do_action('woocommerce_after_cart_contents'); ?>

				<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
			</div>

			<!-- Cart Totals Column -->
			<div class="po-cart__totals-column">
				<div class="po-cart__totals-panel">
					<?php do_action('woocommerce_before_cart_collaterals'); ?>

					<div class="cart-collaterals">
						<div class="po-cart__totals cart_totals <?php echo (WC()->customer->has_calculated_shipping()) ? 'calculated_shipping' : ''; ?>">

							<?php do_action('woocommerce_before_cart_totals'); ?>

							<h2 class="po-cart__totals-title"><?php esc_html_e('Zusammenfassung', 'parkourone'); ?></h2>

							<div class="po-cart__totals-rows">
								<!-- Subtotal -->
								<div class="po-cart__totals-row po-cart__totals-row--subtotal">
									<span class="po-cart__totals-label"><?php esc_html_e('Zwischensumme', 'parkourone'); ?></span>
									<span class="po-cart__totals-value" data-title="<?php esc_attr_e('Zwischensumme', 'parkourone'); ?>"><?php wc_cart_totals_subtotal_html(); ?></span>
								</div>

								<!-- Coupons -->
								<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
									<div class="po-cart__totals-row po-cart__totals-row--coupon cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
										<span class="po-cart__totals-label"><?php wc_cart_totals_coupon_label($coupon); ?></span>
										<span class="po-cart__totals-value" data-title="<?php echo esc_attr(wc_cart_totals_coupon_label($coupon, false)); ?>"><?php wc_cart_totals_coupon_html($coupon); ?></span>
									</div>
								<?php endforeach; ?>

								<!-- Shipping -->
								<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
									<?php do_action('woocommerce_cart_totals_before_shipping'); ?>
									<?php wc_cart_totals_shipping_html(); ?>
									<?php do_action('woocommerce_cart_totals_after_shipping'); ?>
								<?php elseif (WC()->cart->needs_shipping() && 'yes' === get_option('woocommerce_enable_shipping_calc')) : ?>
									<div class="po-cart__totals-row po-cart__totals-row--shipping shipping">
										<span class="po-cart__totals-label"><?php esc_html_e('Versand', 'parkourone'); ?></span>
										<span class="po-cart__totals-value" data-title="<?php esc_attr_e('Versand', 'parkourone'); ?>"><?php woocommerce_shipping_calculator(); ?></span>
									</div>
								<?php endif; ?>

								<!-- Fees -->
								<?php foreach (WC()->cart->get_fees() as $fee) : ?>
									<div class="po-cart__totals-row po-cart__totals-row--fee fee">
										<span class="po-cart__totals-label"><?php echo esc_html($fee->name); ?></span>
										<span class="po-cart__totals-value" data-title="<?php echo esc_attr($fee->name); ?>"><?php wc_cart_totals_fee_html($fee); ?></span>
									</div>
								<?php endforeach; ?>

								<!-- Tax -->
								<?php
								if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) {
									$taxable_address = WC()->customer->get_taxable_address();
									$estimated_text  = '';

									if (WC()->customer->is_customer_outside_base() && !WC()->customer->has_calculated_shipping()) {
										$estimated_text = sprintf(' <small>' . esc_html__('(estimated for %s)', 'woocommerce') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]);
									}

									if ('itemized' === get_option('woocommerce_tax_total_display')) {
										foreach (WC()->cart->get_tax_totals() as $code => $tax) { ?>
											<div class="po-cart__totals-row po-cart__totals-row--tax tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
												<span class="po-cart__totals-label"><?php echo esc_html($tax->label) . $estimated_text; ?></span>
												<span class="po-cart__totals-value" data-title="<?php echo esc_attr($tax->label); ?>"><?php echo wp_kses_post($tax->formatted_amount); ?></span>
											</div>
											<?php
										}
									} else { ?>
										<div class="po-cart__totals-row po-cart__totals-row--tax tax-total">
											<span class="po-cart__totals-label"><?php echo esc_html(WC()->countries->tax_or_vat()) . $estimated_text; ?></span>
											<span class="po-cart__totals-value" data-title="<?php echo esc_attr(WC()->countries->tax_or_vat()); ?>"><?php wc_cart_totals_taxes_total_html(); ?></span>
										</div>
										<?php
									}
								}
								?>

								<?php do_action('woocommerce_cart_totals_before_order_total'); ?>

								<!-- Total -->
								<div class="po-cart__totals-row po-cart__totals-row--total order-total">
									<span class="po-cart__totals-label"><?php esc_html_e('Gesamt', 'parkourone'); ?></span>
									<span class="po-cart__totals-value" data-title="<?php esc_attr_e('Gesamt', 'parkourone'); ?>"><?php wc_cart_totals_order_total_html(); ?></span>
								</div>

								<?php do_action('woocommerce_cart_totals_after_order_total'); ?>
							</div>

							<!-- Coupon Form -->
							<?php if (wc_coupons_enabled()) : ?>
								<div class="po-cart__coupon">
									<div class="po-cart__coupon-form">
										<label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Gutscheincode', 'parkourone'); ?></label>
										<input type="text" name="coupon_code" class="po-cart__coupon-input" id="coupon_code" value="" placeholder="<?php esc_attr_e('Gutscheincode eingeben', 'parkourone'); ?>" />
										<button type="submit" class="po-cart__coupon-btn" name="apply_coupon" value="<?php esc_attr_e('Einlösen', 'parkourone'); ?>">
											<?php esc_html_e('Einlösen', 'parkourone'); ?>
										</button>
									</div>
								</div>
							<?php endif; ?>

							<!-- Checkout Button -->
							<div class="po-cart__checkout wc-proceed-to-checkout">
								<?php do_action('woocommerce_proceed_to_checkout'); ?>
							</div>

							<?php do_action('woocommerce_after_cart_totals'); ?>
						</div>
					</div>
				</div>

				<!-- Continue Shopping -->
				<a href="<?php echo esc_url(home_url('/angebote/')); ?>" class="po-cart__continue">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 12H5"></path>
						<path d="M12 19l-7-7 7-7"></path>
					</svg>
					<?php esc_html_e('Weiter stöbern', 'parkourone'); ?>
				</a>
			</div>
		</div>

		<?php do_action('woocommerce_after_cart_table'); ?>
	</form>
</div>

<?php do_action('woocommerce_after_cart'); ?>
