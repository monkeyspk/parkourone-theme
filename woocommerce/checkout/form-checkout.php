<?php
/**
 * Checkout Form
 * ParkourONE Custom Apple-Style Design
 *
 * This template overrides the default WooCommerce checkout form.
 *
 * @package ParkourONE
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
	echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('Du musst angemeldet sein, um zur Kasse zu gehen.', 'parkourone')));
	return;
}
?>

<div class="po-checkout">
	<form name="checkout" method="post" class="po-checkout__form checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

		<?php if ($checkout->get_checkout_fields()) : ?>

			<div class="po-checkout__layout">
				<!-- Left Column: Customer Details -->
				<div class="po-checkout__details-column">
					<div class="po-checkout__header">
						<h1 class="po-checkout__title">Kasse</h1>
						<p class="po-checkout__subtitle">Bitte fülle deine Daten aus, um die Buchung abzuschließen.</p>
					</div>

					<?php do_action('woocommerce_checkout_before_customer_details'); ?>

					<div class="po-checkout__customer-details" id="customer_details">
						<!-- Billing Fields -->
						<div class="po-checkout__section po-checkout__section--billing">
							<h2 class="po-checkout__section-title">
								<span class="po-checkout__section-number">1</span>
								Rechnungsdetails
							</h2>

							<div class="po-checkout__fields woocommerce-billing-fields">
								<?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

								<div class="po-checkout__fields-grid woocommerce-billing-fields__field-wrapper">
									<?php
									$fields = $checkout->get_checkout_fields('billing');

									foreach ($fields as $key => $field) {
										woocommerce_form_field($key, $field, $checkout->get_value($key));
									}
									?>
								</div>

								<?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
							</div>
						</div>

						<!-- Shipping Fields (if needed) -->
						<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
							<div class="po-checkout__section po-checkout__section--shipping">
								<h2 class="po-checkout__section-title">
									<span class="po-checkout__section-number">2</span>
									Lieferadresse
								</h2>

								<div class="po-checkout__fields woocommerce-shipping-fields">
									<?php if (apply_filters('woocommerce_cart_needs_shipping_address', true)) : ?>

										<div class="po-checkout__ship-to-different">
											<label class="po-checkout__checkbox-label woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
												<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?> type="checkbox" name="ship_to_different_address" value="1" />
												<span class="po-checkout__checkbox-text"><?php esc_html_e('An eine andere Adresse liefern?', 'parkourone'); ?></span>
											</label>
										</div>

										<div class="shipping_address">
											<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

											<div class="po-checkout__fields-grid woocommerce-shipping-fields__field-wrapper">
												<?php
												$fields = $checkout->get_checkout_fields('shipping');

												foreach ($fields as $key => $field) {
													woocommerce_form_field($key, $field, $checkout->get_value($key));
												}
												?>
											</div>

											<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
										</div>

									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<!-- Additional Fields (Order notes, etc.) -->
						<?php if ($checkout->get_checkout_fields('order')) : ?>
							<div class="po-checkout__section po-checkout__section--additional">
								<h2 class="po-checkout__section-title">
									<span class="po-checkout__section-number"><?php echo WC()->cart->needs_shipping() ? '3' : '2'; ?></span>
									Zusätzliche Informationen
								</h2>

								<div class="po-checkout__fields woocommerce-additional-fields">
									<?php do_action('woocommerce_before_order_notes', $checkout); ?>

									<div class="po-checkout__fields-grid woocommerce-additional-fields__field-wrapper">
										<?php
										foreach ($checkout->get_checkout_fields('order') as $key => $field) {
											woocommerce_form_field($key, $field, $checkout->get_value($key));
										}
										?>
									</div>

									<?php do_action('woocommerce_after_order_notes', $checkout); ?>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<?php do_action('woocommerce_checkout_after_customer_details'); ?>
				</div>

				<!-- Right Column: Order Summary -->
				<div class="po-checkout__order-column">
					<div class="po-checkout__order-panel">
						<h2 class="po-checkout__order-title">Deine Bestellung</h2>

						<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

						<div class="po-checkout__order-review" id="order_review" class="woocommerce-checkout-review-order">
							<?php do_action('woocommerce_checkout_before_order_review'); ?>

							<!-- Order Items -->
							<div class="po-checkout__order-items">
								<?php
								foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
									$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
									$product_id = $cart_item['product_id'];

									if ($_product && $_product->exists() && $cart_item['quantity'] > 0) {
										$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
										$thumbnail = $_product->get_image('thumbnail');
										$product_subtotal = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);

										// Get participant data
										$participants = isset($cart_item['angebot_teilnehmer']) ? $cart_item['angebot_teilnehmer'] : [];

										// Get event details
										$event_location = get_post_meta($product_id, '_angebot_termin_ort', true);
										$event_date = get_post_meta($product_id, '_angebot_termin_datum', true);
										?>
										<div class="po-checkout__order-item">
											<div class="po-checkout__order-item-image">
												<?php echo $thumbnail; ?>
												<span class="po-checkout__order-item-qty"><?php echo $cart_item['quantity']; ?></span>
											</div>
											<div class="po-checkout__order-item-details">
												<span class="po-checkout__order-item-name"><?php echo wp_kses_post($product_name); ?></span>

												<?php if ($event_date || $event_location) : ?>
													<div class="po-checkout__order-item-meta">
														<?php if ($event_date) : ?>
															<span class="po-checkout__order-item-date"><?php echo esc_html($event_date); ?></span>
														<?php endif; ?>
														<?php if ($event_location) : ?>
															<span class="po-checkout__order-item-location"><?php echo esc_html($event_location); ?></span>
														<?php endif; ?>
													</div>
												<?php endif; ?>

												<?php if (!empty($participants)) : ?>
													<div class="po-checkout__order-item-participants">
														<?php foreach ($participants as $participant) : ?>
															<span class="po-checkout__order-participant">
																<?php echo esc_html($participant['vorname'] . ' ' . $participant['name']); ?>
															</span>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>

												<?php echo wc_get_formatted_cart_item_data($cart_item); ?>
											</div>
											<div class="po-checkout__order-item-price">
												<?php echo $product_subtotal; ?>
											</div>
										</div>
										<?php
									}
								}
								?>
							</div>

							<!-- Order Totals -->
							<div class="po-checkout__order-totals">
								<!-- Subtotal -->
								<div class="po-checkout__order-row">
									<span class="po-checkout__order-label"><?php esc_html_e('Zwischensumme', 'parkourone'); ?></span>
									<span class="po-checkout__order-value"><?php wc_cart_totals_subtotal_html(); ?></span>
								</div>

								<!-- Coupons -->
								<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
									<div class="po-checkout__order-row po-checkout__order-row--coupon">
										<span class="po-checkout__order-label"><?php wc_cart_totals_coupon_label($coupon); ?></span>
										<span class="po-checkout__order-value"><?php wc_cart_totals_coupon_html($coupon); ?></span>
									</div>
								<?php endforeach; ?>

								<!-- Shipping -->
								<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
									<?php do_action('woocommerce_review_order_before_shipping'); ?>
									<?php wc_cart_totals_shipping_html(); ?>
									<?php do_action('woocommerce_review_order_after_shipping'); ?>
								<?php endif; ?>

								<!-- Fees -->
								<?php foreach (WC()->cart->get_fees() as $fee) : ?>
									<div class="po-checkout__order-row">
										<span class="po-checkout__order-label"><?php echo esc_html($fee->name); ?></span>
										<span class="po-checkout__order-value"><?php wc_cart_totals_fee_html($fee); ?></span>
									</div>
								<?php endforeach; ?>

								<!-- Tax -->
								<?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
									<?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
										<?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
											<div class="po-checkout__order-row">
												<span class="po-checkout__order-label"><?php echo esc_html($tax->label); ?></span>
												<span class="po-checkout__order-value"><?php echo wp_kses_post($tax->formatted_amount); ?></span>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="po-checkout__order-row">
											<span class="po-checkout__order-label"><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
											<span class="po-checkout__order-value"><?php wc_cart_totals_taxes_total_html(); ?></span>
										</div>
									<?php endif; ?>
								<?php endif; ?>

								<?php do_action('woocommerce_review_order_before_order_total'); ?>

								<!-- Total -->
								<div class="po-checkout__order-row po-checkout__order-row--total">
									<span class="po-checkout__order-label"><?php esc_html_e('Gesamt', 'parkourone'); ?></span>
									<span class="po-checkout__order-value"><?php wc_cart_totals_order_total_html(); ?></span>
								</div>

								<?php do_action('woocommerce_review_order_after_order_total'); ?>
							</div>

							<!-- Coupon Form -->
							<?php if (wc_coupons_enabled()) : ?>
								<div class="po-checkout__coupon">
									<div class="po-checkout__coupon-toggle">
										<span class="po-checkout__coupon-toggle-text">Hast du einen Gutschein?</span>
									</div>
									<div class="po-checkout__coupon-form" style="display: none;">
										<input type="text" name="coupon_code" class="po-checkout__coupon-input" id="checkout_coupon_code" placeholder="<?php esc_attr_e('Gutscheincode eingeben', 'parkourone'); ?>" />
										<button type="button" class="po-checkout__coupon-btn" id="apply_coupon_btn">
											<?php esc_html_e('Einlösen', 'parkourone'); ?>
										</button>
									</div>
								</div>
							<?php endif; ?>

							<!-- Payment Methods -->
							<div class="po-checkout__payment">
								<h3 class="po-checkout__payment-title"><?php esc_html_e('Zahlungsart', 'parkourone'); ?></h3>

								<?php if (WC()->cart->needs_payment()) : ?>
									<ul class="po-checkout__payment-methods wc_payment_methods payment_methods methods">
										<?php
										if (!empty($available_gateways = WC()->payment_gateways->get_available_payment_gateways())) {
											foreach ($available_gateways as $gateway) {
												wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
											}
										} else {
											echo '<li>' . esc_html(apply_filters('woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__('Leider akzeptieren wir momentan keine Zahlungen. Bitte kontaktiere uns.', 'parkourone') : esc_html__('Bitte fülle deine Rechnungsdaten aus, um die verfügbaren Zahlungsmethoden zu sehen.', 'parkourone'))) . '</li>';
										}
										?>
									</ul>
								<?php endif; ?>

								<div class="form-row place-order">
									<noscript>
										<?php esc_html_e('Da JavaScript in deinem Browser deaktiviert ist, kann die Bestellung nicht verarbeitet werden. Bitte aktiviere JavaScript.', 'parkourone'); ?>
									</noscript>

									<?php wc_get_template('checkout/terms.php'); ?>

									<?php do_action('woocommerce_review_order_before_submit'); ?>

									<?php echo apply_filters('woocommerce_order_button_html', '<button type="submit" class="po-checkout__submit-btn button alt' . esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '') . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">' . esc_html($order_button_text) . '</button>'); ?>

									<?php do_action('woocommerce_review_order_after_submit'); ?>

									<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
								</div>
							</div>

							<?php do_action('woocommerce_checkout_after_order_review'); ?>
						</div>

						<!-- Secure Payment Notice -->
						<div class="po-checkout__secure">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
								<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
							</svg>
							<span><?php esc_html_e('Sichere Zahlung mit SSL-Verschlüsselung', 'parkourone'); ?></span>
						</div>
					</div>

					<!-- Back to Cart Link -->
					<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="po-checkout__back">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M19 12H5"></path>
							<path d="M12 19l-7-7 7-7"></path>
						</svg>
						<?php esc_html_e('Zurück zum Warenkorb', 'parkourone'); ?>
					</a>
				</div>
			</div>

		<?php endif; ?>

	</form>
</div>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
