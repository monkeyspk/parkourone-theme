<?php
/**
 * ParkourONE WooCommerce Customizations
 *
 * Minimal setup for WooCommerce native blocks
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
 * Disable XOO Side Cart Plugin (if still installed)
 */
function parkourone_disable_xoo_side_cart() {
	if (class_exists('Xoo_Wsc')) {
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
 * Enqueue WooCommerce custom styles
 */
function parkourone_wc_enqueue_assets() {
	if (!class_exists('WooCommerce')) {
		return;
	}

	wp_enqueue_style(
		'parkourone-woocommerce',
		get_template_directory_uri() . '/assets/css/woocommerce.css',
		[],
		filemtime(get_template_directory() . '/assets/css/woocommerce.css')
	);
}
add_action('wp_enqueue_scripts', 'parkourone_wc_enqueue_assets');

/**
 * Add WooCommerce cart fragments for header cart count
 */
function parkourone_wc_cart_fragments($fragments) {
	$cart_count = WC()->cart->get_cart_contents_count();

	// Header cart count badge
	$fragments['.po-header__cart-count'] = '<span class="po-header__cart-count" data-cart-count="' . esc_attr($cart_count) . '">' . esc_html($cart_count) . '</span>';

	return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'parkourone_wc_cart_fragments');

/**
 * Modern Cart Block - AJAX: Update quantity
 */
function parkourone_modern_cart_update() {
	check_ajax_referer('po_modern_cart_nonce', 'nonce');

	$cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
	$quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Invalid cart item']);
	}

	// Update quantity
	WC()->cart->set_quantity($cart_item_key, $quantity, true);

	// Get updated item data
	$cart_item = WC()->cart->get_cart_item($cart_item_key);
	$item_subtotal = '';
	$max_qty = 0;

	if ($cart_item) {
		$product = $cart_item['data'];
		$item_subtotal = WC()->cart->get_product_subtotal($product, $quantity);
		$max_qty = $product->get_max_purchase_quantity();
	}

	wp_send_json_success([
		'item_subtotal' => $item_subtotal,
		'cart_subtotal' => WC()->cart->get_cart_subtotal(),
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_count' => WC()->cart->get_cart_contents_count(),
		'max_qty' => $max_qty,
	]);
}
add_action('wp_ajax_po_modern_cart_update', 'parkourone_modern_cart_update');
add_action('wp_ajax_nopriv_po_modern_cart_update', 'parkourone_modern_cart_update');

/**
 * Modern Cart Block - AJAX: Remove item
 */
function parkourone_modern_cart_remove() {
	check_ajax_referer('po_modern_cart_nonce', 'nonce');

	$cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Invalid cart item']);
	}

	WC()->cart->remove_cart_item($cart_item_key);

	wp_send_json_success([
		'cart_subtotal' => WC()->cart->get_cart_subtotal(),
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_count' => WC()->cart->get_cart_contents_count(),
	]);
}
add_action('wp_ajax_po_modern_cart_remove', 'parkourone_modern_cart_remove');
add_action('wp_ajax_nopriv_po_modern_cart_remove', 'parkourone_modern_cart_remove');

/**
 * Modern Cart Block - AJAX: Apply coupon
 */
function parkourone_modern_cart_coupon() {
	check_ajax_referer('po_modern_cart_nonce', 'nonce');

	$coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';

	if (empty($coupon_code)) {
		wp_send_json_error(['message' => 'Bitte gib einen Gutscheincode ein.']);
	}

	$result = WC()->cart->apply_coupon($coupon_code);

	if ($result) {
		wp_send_json_success([
			'message' => 'Gutschein wurde angewendet.',
			'cart_subtotal' => WC()->cart->get_cart_subtotal(),
			'cart_total' => WC()->cart->get_cart_total(),
		]);
	} else {
		wp_send_json_error(['message' => 'Gutscheincode ungültig oder abgelaufen.']);
	}
}
add_action('wp_ajax_po_modern_cart_coupon', 'parkourone_modern_cart_coupon');
add_action('wp_ajax_nopriv_po_modern_cart_coupon', 'parkourone_modern_cart_coupon');

/**
 * Mini Cart Block - AJAX: Remove item
 */
function parkourone_mini_cart_remove() {
	check_ajax_referer('po_mini_cart_nonce', 'nonce');

	$cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Invalid cart item']);
	}

	WC()->cart->remove_cart_item($cart_item_key);

	wp_send_json_success([
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_count' => WC()->cart->get_cart_contents_count(),
	]);
}
add_action('wp_ajax_po_mini_cart_remove', 'parkourone_mini_cart_remove');
add_action('wp_ajax_nopriv_po_mini_cart_remove', 'parkourone_mini_cart_remove');

/**
 * Mini Cart Block - AJAX: Refresh content
 */
function parkourone_mini_cart_refresh() {
	check_ajax_referer('po_mini_cart_nonce', 'nonce');

	$cart_items = WC()->cart->get_cart();

	ob_start();

	if (empty($cart_items)) {
		?>
		<div class="po-mini-cart__empty">
			<div class="po-mini-cart__empty-icon">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<circle cx="9" cy="21" r="1"></circle>
					<circle cx="20" cy="21" r="1"></circle>
					<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
				</svg>
			</div>
			<p>Dein Warenkorb ist leer</p>
			<a href="<?php echo esc_url(home_url('/angebote/')); ?>" class="po-mini-cart__btn po-mini-cart__btn--secondary">
				Angebote entdecken
			</a>
		</div>
		<?php
	} else {
		?>
		<div class="po-mini-cart__items" data-mini-cart-items>
			<?php foreach ($cart_items as $cart_item_key => $cart_item) :
				$product = $cart_item['data'];
				$quantity = $cart_item['quantity'];
				$product_name = $product->get_name();
				$product_price = WC()->cart->get_product_subtotal($product, $quantity);
				$product_permalink = $product->get_permalink();
				$thumbnail = $product->get_image('thumbnail');
				$participants = isset($cart_item['angebot_teilnehmer']) ? $cart_item['angebot_teilnehmer'] : [];
			?>
				<div class="po-mini-cart__item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>">
					<div class="po-mini-cart__item-image">
						<?php echo $thumbnail; ?>
					</div>
					<div class="po-mini-cart__item-details">
						<a href="<?php echo esc_url($product_permalink); ?>" class="po-mini-cart__item-name">
							<?php echo esc_html($product_name); ?>
						</a>
						<?php if (!empty($participants)) : ?>
							<div class="po-mini-cart__item-meta">
								<?php echo count($participants); ?> Teilnehmer
							</div>
						<?php endif; ?>
						<div class="po-mini-cart__item-qty">Anzahl: <?php echo esc_html($quantity); ?></div>
						<div class="po-mini-cart__item-price"><?php echo $product_price; ?></div>
					</div>
					<button type="button" class="po-mini-cart__item-remove" data-remove-item aria-label="Entfernen">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	$content_html = ob_get_clean();

	wp_send_json_success([
		'content_html' => $content_html,
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_count' => WC()->cart->get_cart_contents_count(),
	]);
}
add_action('wp_ajax_po_mini_cart_refresh', 'parkourone_mini_cart_refresh');
add_action('wp_ajax_nopriv_po_mini_cart_refresh', 'parkourone_mini_cart_refresh');
