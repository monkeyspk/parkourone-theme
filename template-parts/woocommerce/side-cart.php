<?php
/**
 * ParkourONE Side Cart
 * Apple-style slide-out cart panel
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

$cart = WC()->cart;
$cart_count = $cart ? $cart->get_cart_contents_count() : 0;
$cart_total = $cart ? $cart->get_cart_subtotal() : '';
$cart_items = $cart ? $cart->get_cart() : [];
?>

<!-- Side Cart Panel -->
<div id="po-side-cart" class="po-side-cart" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Warenkorb">
	<div class="po-side-cart__overlay"></div>

	<div class="po-side-cart__panel">
		<!-- Header -->
		<div class="po-side-cart__header">
			<h2 class="po-side-cart__title">
				Warenkorb
				<span class="po-side-cart__count" data-cart-count><?php echo esc_html($cart_count); ?></span>
			</h2>
			<button type="button" class="po-side-cart__close" aria-label="Warenkorb schließen" data-close-cart>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="18" y1="6" x2="6" y2="18"></line>
					<line x1="6" y1="6" x2="18" y2="18"></line>
				</svg>
			</button>
		</div>

		<!-- Cart Content -->
		<div class="po-side-cart__content" data-cart-content>
			<?php if (empty($cart_items)) : ?>
				<div class="po-side-cart__empty">
					<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<circle cx="9" cy="21" r="1"></circle>
						<circle cx="20" cy="21" r="1"></circle>
						<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
					</svg>
					<p>Dein Warenkorb ist leer</p>
					<a href="<?php echo esc_url(home_url('/angebote/')); ?>" class="po-side-cart__btn po-side-cart__btn--primary">
						Angebote entdecken
					</a>
				</div>
			<?php else : ?>
				<div class="po-side-cart__items">
					<?php foreach ($cart_items as $cart_item_key => $cart_item) :
						$product = $cart_item['data'];
						$product_id = $cart_item['product_id'];
						$quantity = $cart_item['quantity'];
						$product_name = $product->get_name();
						$product_price = WC()->cart->get_product_price($product);
						$thumbnail = $product->get_image('thumbnail');

						// Get participant data if available
						$participants = isset($cart_item['angebot_teilnehmer']) ? $cart_item['angebot_teilnehmer'] : [];

						// Get event details from product meta
						$event_name = get_post_meta($product_id, '_angebot_event_name', true);
						$event_date = get_post_meta($product_id, '_angebot_termin_datum', true);
						$event_location = get_post_meta($product_id, '_angebot_termin_ort', true);
					?>
						<div class="po-side-cart__item" data-cart-item="<?php echo esc_attr($cart_item_key); ?>">
							<div class="po-side-cart__item-image">
								<?php echo $thumbnail; ?>
							</div>

							<div class="po-side-cart__item-details">
								<h3 class="po-side-cart__item-name"><?php echo esc_html($product_name); ?></h3>

								<?php if (!empty($participants)) : ?>
									<div class="po-side-cart__item-participants">
										<?php foreach ($participants as $i => $participant) : ?>
											<span class="po-side-cart__participant">
												<?php echo esc_html($participant['vorname'] . ' ' . $participant['name']); ?>
												<?php if (!empty($participant['geburtsdatum'])) : ?>
													<small>(<?php echo esc_html($participant['geburtsdatum']); ?>)</small>
												<?php endif; ?>
											</span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ($event_location) : ?>
									<div class="po-side-cart__item-location">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
											<circle cx="12" cy="10" r="3"></circle>
										</svg>
										<?php echo esc_html($event_location); ?>
									</div>
								<?php endif; ?>

								<div class="po-side-cart__item-price"><?php echo $product_price; ?></div>
							</div>

							<div class="po-side-cart__item-actions">
								<div class="po-side-cart__quantity">
									<button type="button" class="po-side-cart__qty-btn" data-qty-minus aria-label="Menge verringern">-</button>
									<span class="po-side-cart__qty-value"><?php echo esc_html($quantity); ?></span>
									<button type="button" class="po-side-cart__qty-btn" data-qty-plus aria-label="Menge erhöhen">+</button>
								</div>

								<button type="button" class="po-side-cart__remove" data-remove-item aria-label="Produkt entfernen">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M3 6h18"></path>
										<path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
										<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
									</svg>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Footer -->
		<?php if (!empty($cart_items)) : ?>
			<div class="po-side-cart__footer">
				<div class="po-side-cart__subtotal">
					<span>Zwischensumme</span>
					<span class="po-side-cart__total" data-cart-total><?php echo $cart_total; ?></span>
				</div>

				<div class="po-side-cart__actions">
					<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="po-side-cart__btn po-side-cart__btn--secondary">
						Warenkorb ansehen
					</a>
					<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="po-side-cart__btn po-side-cart__btn--primary">
						Zur Kasse
					</a>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
