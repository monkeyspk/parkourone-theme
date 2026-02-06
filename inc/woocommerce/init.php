<?php
/**
 * ParkourONE WooCommerce Customizations
 *
 * Custom Side Cart, Cart Page, and Checkout
 *
 * @package ParkourONE
 */

if (!defined('ABSPATH')) {
	exit;
}

// Only load if WooCommerce is active
if (!class_exists('WooCommerce')) {
	return;
}

/**
 * Disable XOO Side Cart Plugin
 */
function parkourone_disable_xoo_side_cart() {
	// Remove XOO Side Cart hooks if plugin is active
	if (class_exists('Xoo_Wsc')) {
		// Try to get instance and remove hooks
		if (method_exists('Xoo_Wsc', 'get_instance')) {
			$xoo_instance = Xoo_Wsc::get_instance();
			if ($xoo_instance) {
				remove_action('wp_footer', [$xoo_instance, 'render_side_cart']);
			}
		}
	}

	// Dequeue XOO assets
	add_action('wp_enqueue_scripts', function() {
		wp_dequeue_style('xoo-wsc-style');
		wp_dequeue_style('xoo-wsc-fonts');
		wp_dequeue_script('xoo-wsc-js');
		wp_dequeue_script('xoo-wsc-main-js');
	}, 100);
}
add_action('plugins_loaded', 'parkourone_disable_xoo_side_cart', 20);

/**
 * Enqueue WooCommerce custom assets
 */
function parkourone_wc_enqueue_assets() {
	if (!class_exists('WooCommerce')) {
		return;
	}

	// CSS
	wp_enqueue_style(
		'parkourone-woocommerce',
		get_template_directory_uri() . '/assets/css/woocommerce.css',
		[],
		filemtime(get_template_directory() . '/assets/css/woocommerce.css')
	);

	// JavaScript
	wp_enqueue_script(
		'parkourone-side-cart',
		get_template_directory_uri() . '/assets/js/side-cart.js',
		['jquery'],
		filemtime(get_template_directory() . '/assets/js/side-cart.js'),
		true
	);

	// Pass data to JavaScript
	wp_localize_script('parkourone-side-cart', 'poSideCart', [
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('po_side_cart_nonce'),
		'shopUrl' => home_url('/angebote/'),
		'cartUrl' => wc_get_cart_url(),
		'checkoutUrl' => wc_get_checkout_url(),
	]);
}
add_action('wp_enqueue_scripts', 'parkourone_wc_enqueue_assets');

/**
 * Output Side Cart in footer
 */
function parkourone_output_side_cart() {
	if (!class_exists('WooCommerce')) {
		return;
	}

	get_template_part('template-parts/woocommerce/side-cart');
}
add_action('wp_footer', 'parkourone_output_side_cart', 10);

/**
 * Add cart trigger to header
 * Replaces the default cart link
 */
function parkourone_cart_trigger() {
	if (!class_exists('WooCommerce')) {
		return '';
	}

	$cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

	ob_start();
	?>
	<button type="button" class="po-cart-trigger" data-open-cart aria-label="Warenkorb öffnen">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<circle cx="9" cy="21" r="1"></circle>
			<circle cx="20" cy="21" r="1"></circle>
			<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
		</svg>
		<span class="po-cart-trigger__count" data-cart-count="<?php echo esc_attr($cart_count); ?>"><?php echo esc_html($cart_count); ?></span>
	</button>
	<?php
	return ob_get_clean();
}

/**
 * AJAX: Update cart item quantity
 */
function parkourone_ajax_update_cart_item() {
	check_ajax_referer('po_side_cart_nonce', 'nonce');

	$cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
	$quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Invalid cart item']);
	}

	// Update quantity
	WC()->cart->set_quantity($cart_item_key, $quantity, true);

	// Get updated cart data
	$cart_html = parkourone_get_side_cart_items_html();
	$cart_total = WC()->cart->get_cart_subtotal();
	$cart_count = WC()->cart->get_cart_contents_count();

	wp_send_json_success([
		'cart_html' => $cart_html,
		'cart_total' => $cart_total,
		'cart_count' => $cart_count,
	]);
}
add_action('wp_ajax_po_update_cart_item', 'parkourone_ajax_update_cart_item');
add_action('wp_ajax_nopriv_po_update_cart_item', 'parkourone_ajax_update_cart_item');

/**
 * AJAX: Remove cart item
 */
function parkourone_ajax_remove_cart_item() {
	check_ajax_referer('po_side_cart_nonce', 'nonce');

	$cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Invalid cart item']);
	}

	// Remove item
	WC()->cart->remove_cart_item($cart_item_key);

	// Get updated cart data
	$cart_html = parkourone_get_side_cart_items_html();
	$cart_total = WC()->cart->get_cart_subtotal();
	$cart_count = WC()->cart->get_cart_contents_count();

	wp_send_json_success([
		'cart_html' => $cart_html,
		'cart_total' => $cart_total,
		'cart_count' => $cart_count,
	]);
}
add_action('wp_ajax_po_remove_cart_item', 'parkourone_ajax_remove_cart_item');
add_action('wp_ajax_nopriv_po_remove_cart_item', 'parkourone_ajax_remove_cart_item');

/**
 * AJAX: Get cart content HTML
 */
function parkourone_ajax_get_cart_content() {
	check_ajax_referer('po_side_cart_nonce', 'nonce');

	$cart_html = parkourone_get_side_cart_items_html();
	$cart_total = WC()->cart->get_cart_subtotal();
	$cart_count = WC()->cart->get_cart_contents_count();

	wp_send_json_success([
		'cart_html' => $cart_html,
		'cart_total' => $cart_total,
		'cart_count' => $cart_count,
	]);
}
add_action('wp_ajax_po_get_cart_content', 'parkourone_ajax_get_cart_content');
add_action('wp_ajax_nopriv_po_get_cart_content', 'parkourone_ajax_get_cart_content');

/**
 * Get side cart items HTML
 */
function parkourone_get_side_cart_items_html() {
	$cart_items = WC()->cart->get_cart();

	if (empty($cart_items)) {
		return '';
	}

	ob_start();
	?>
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
	<?php
	return ob_get_clean();
}

/**
 * Add WooCommerce cart fragments for AJAX updates
 */
function parkourone_wc_cart_fragments($fragments) {
	// Cart count badge
	$cart_count = WC()->cart->get_cart_contents_count();
	$fragments['.po-cart-trigger__count'] = '<span class="po-cart-trigger__count" data-cart-count="' . esc_attr($cart_count) . '">' . esc_html($cart_count) . '</span>';

	// Side cart content
	$fragments['.po-side-cart__content'] = '<div class="po-side-cart__content" data-cart-content>' . parkourone_get_side_cart_items_html() . '</div>';

	// Cart total
	$fragments['.po-side-cart__total'] = '<span class="po-side-cart__total" data-cart-total>' . WC()->cart->get_cart_subtotal() . '</span>';

	// Cart count in header
	$fragments['.po-side-cart__count'] = '<span class="po-side-cart__count" data-cart-count>' . $cart_count . '</span>';

	return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'parkourone_wc_cart_fragments');

/**
 * Enqueue Cart Page JavaScript
 */
function parkourone_wc_cart_checkout_scripts() {
	if (!class_exists('WooCommerce')) {
		return;
	}

	// Only load on cart and checkout pages
	if (!is_cart() && !is_checkout()) {
		return;
	}

	// Inline JavaScript for cart and checkout enhancements
	$inline_js = "
	(function() {
		'use strict';

		// Coupon toggle on checkout
		const couponToggle = document.querySelector('.po-checkout__coupon-toggle');
		const couponForm = document.querySelector('.po-checkout__coupon-form');

		if (couponToggle && couponForm) {
			couponToggle.addEventListener('click', function() {
				couponForm.style.display = couponForm.style.display === 'none' ? 'flex' : 'none';
			});
		}

		// Apply coupon via AJAX on checkout
		const applyCouponBtn = document.getElementById('apply_coupon_btn');
		const couponInput = document.getElementById('checkout_coupon_code');

		if (applyCouponBtn && couponInput) {
			applyCouponBtn.addEventListener('click', function() {
				const couponCode = couponInput.value.trim();
				if (!couponCode) return;

				applyCouponBtn.disabled = true;
				applyCouponBtn.textContent = '...';

				// Use WooCommerce's built-in coupon application
				const formData = new FormData();
				formData.append('coupon_code', couponCode);
				formData.append('security', wc_checkout_params?.apply_coupon_nonce || '');

				fetch(wc_checkout_params?.ajax_url || '/wp-admin/admin-ajax.php', {
					method: 'POST',
					body: new URLSearchParams({
						action: 'woocommerce_apply_coupon',
						coupon_code: couponCode,
						security: wc_checkout_params?.apply_coupon_nonce || ''
					})
				})
				.then(response => response.text())
				.then(() => {
					// Refresh the checkout
					if (typeof jQuery !== 'undefined') {
						jQuery(document.body).trigger('update_checkout');
					}
					couponInput.value = '';
				})
				.finally(() => {
					applyCouponBtn.disabled = false;
					applyCouponBtn.textContent = 'Einlösen';
				});
			});
		}

		// Cart page: AJAX remove item
		document.querySelectorAll('.po-cart__remove').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				const cartItemKey = this.dataset.cartItemKey;
				const item = this.closest('.po-cart__item');

				if (!cartItemKey || !item) return;

				item.style.opacity = '0.5';
				item.style.pointerEvents = 'none';

				fetch(poSideCart?.ajaxUrl || '/wp-admin/admin-ajax.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'po_remove_cart_item',
						nonce: poSideCart?.nonce || '',
						cart_item_key: cartItemKey
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Animate removal
						item.style.transform = 'translateX(-20px)';
						item.style.transition = 'all 0.3s ease';

						setTimeout(() => {
							item.remove();

							// Update totals
							if (data.data.cart_count === 0) {
								location.reload();
							} else {
								// Trigger WooCommerce update
								if (typeof jQuery !== 'undefined') {
									jQuery('[name=\"update_cart\"]').prop('disabled', false).trigger('click');
								}
							}
						}, 300);
					}
				})
				.catch(() => {
					item.style.opacity = '1';
					item.style.pointerEvents = 'auto';
				});
			});
		});

		// Auto-submit cart on quantity change
		const qtyInputs = document.querySelectorAll('.po-cart__quantity .qty');
		qtyInputs.forEach(function(input) {
			let timeout;
			input.addEventListener('change', function() {
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					const updateBtn = document.querySelector('[name=\"update_cart\"]');
					if (updateBtn) {
						updateBtn.disabled = false;
						updateBtn.click();
					}
				}, 500);
			});
		});
	})();
	";

	wp_add_inline_script('parkourone-side-cart', $inline_js);
}
add_action('wp_enqueue_scripts', 'parkourone_wc_cart_checkout_scripts', 20);

/**
 * Custom proceed to checkout button
 */
function parkourone_wc_proceed_to_checkout_button() {
	?>
	<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button button alt wc-forward">
		<?php esc_html_e('Zur Kasse', 'parkourone'); ?>
	</a>
	<?php
}
// Remove default button and add custom one
remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
add_action('woocommerce_proceed_to_checkout', 'parkourone_wc_proceed_to_checkout_button', 20);
