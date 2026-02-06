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

// =====================================================
// Custom Checkout Fields (WC 8.9+ Additional Fields API)
// =====================================================

add_action('woocommerce_init', function() {
	if (!function_exists('woocommerce_register_additional_checkout_field')) {
		return;
	}

	woocommerce_register_additional_checkout_field([
		'id'       => 'parkourone/referral-source',
		'label'    => 'Wie hast du von uns erfahren?',
		'location' => 'order',
		'type'     => 'select',
		'required' => false,
		'options'  => [
			['value' => '',          'label' => 'Bitte wählen...'],
			['value' => 'instagram', 'label' => 'Instagram'],
			['value' => 'google',    'label' => 'Google'],
			['value' => 'freunde',   'label' => 'Freunde / Bekannte'],
			['value' => 'facebook',  'label' => 'Facebook'],
			['value' => 'tiktok',    'label' => 'TikTok'],
			['value' => 'flyer',     'label' => 'Flyer / Plakat'],
			['value' => 'sonstiges', 'label' => 'Sonstiges'],
		],
	]);
});

// =====================================================
// Payment Gateway Filter — only allow enabled methods
// =====================================================
// PayPal Payments registers many sub-gateways (Bancontact, Blik, EPS, etc.)
// even when they are not set up. Whitelist only the methods we actually use.

add_filter('woocommerce_available_payment_gateways', function($gateways) {
	$allowed = [
		'woocommerce_payments', // WooPayments (Karte)
		'ppcp-gateway',         // PayPal
	];

	foreach ($gateways as $id => $gateway) {
		if (!in_array($id, $allowed, true)) {
			unset($gateways[$id]);
		}
	}

	return $gateways;
});

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
 * Enqueue WooCommerce custom styles and scripts
 */
function parkourone_wc_enqueue_assets() {
	if (!class_exists('WooCommerce')) {
		return;
	}

	// WooCommerce styles
	wp_enqueue_style(
		'parkourone-woocommerce',
		get_template_directory_uri() . '/assets/css/woocommerce.css',
		[],
		filemtime(get_template_directory() . '/assets/css/woocommerce.css')
	);

	// Side Cart styles
	wp_enqueue_style(
		'parkourone-side-cart',
		get_template_directory_uri() . '/assets/css/side-cart.css',
		[],
		filemtime(get_template_directory() . '/assets/css/side-cart.css')
	);

	// Side Cart JavaScript
	wp_enqueue_script(
		'parkourone-side-cart',
		get_template_directory_uri() . '/assets/js/side-cart.js',
		['jquery'],
		filemtime(get_template_directory() . '/assets/js/side-cart.js'),
		true
	);

	wp_localize_script('parkourone-side-cart', 'poSideCart', [
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('po_side_cart_nonce'),
		'cartUrl' => wc_get_cart_url(),
		'checkoutUrl' => wc_get_checkout_url(),
	]);

	// Checkout Block filters (button text, payment method filtering)
	if (is_checkout()) {
		wp_enqueue_script(
			'parkourone-checkout-filters',
			get_template_directory_uri() . '/assets/js/checkout-filters.js',
			['wc-blocks-checkout', 'wc-blocks-registry'],
			filemtime(get_template_directory() . '/assets/js/checkout-filters.js'),
			true
		);
	}
}
add_action('wp_enqueue_scripts', 'parkourone_wc_enqueue_assets');

/**
 * Output Side Cart in footer
 */
function parkourone_render_side_cart() {
	if (!class_exists('WooCommerce')) {
		return;
	}

	$cart = WC()->cart;
	$cart_items = $cart->get_cart();
	$cart_count = $cart->get_cart_contents_count();
	$cart_total = $cart->get_cart_total();
	?>
	<!-- Side Cart Overlay -->
	<div class="po-side-cart-overlay" data-side-cart-overlay></div>

	<!-- Side Cart Drawer -->
	<div class="po-side-cart" data-side-cart aria-hidden="true">
		<div class="po-side-cart__inner">
			<!-- Header -->
			<div class="po-side-cart__header">
				<h2 class="po-side-cart__title">
					Warenkorb
					<span class="po-side-cart__badge" data-side-cart-count><?php echo esc_html($cart_count); ?></span>
				</h2>
				<button type="button" class="po-side-cart__close" data-side-cart-close aria-label="Schliessen">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<!-- Content -->
			<div class="po-side-cart__content" data-side-cart-content>
				<?php echo parkourone_get_side_cart_items_html(); ?>
			</div>

			<!-- Footer -->
			<div class="po-side-cart__footer" data-side-cart-footer style="<?php echo empty($cart_items) ? 'display:none;' : ''; ?>">
				<div class="po-side-cart__total">
					<span>Gesamt</span>
					<span data-side-cart-total><?php echo $cart_total; ?></span>
				</div>
				<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="po-side-cart__checkout">
					Zur Kasse
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="5" y1="12" x2="19" y2="12"></line>
						<polyline points="12 5 19 12 12 19"></polyline>
					</svg>
				</a>
				<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="po-side-cart__view-cart">
					Warenkorb anzeigen
				</a>
			</div>
		</div>
	</div>
	<?php
}
add_action('wp_footer', 'parkourone_render_side_cart', 10);

/**
 * Get Side Cart items HTML
 */
function parkourone_get_side_cart_items_html() {
	$cart_items = WC()->cart->get_cart();

	if (empty($cart_items)) {
		ob_start();
		?>
		<div class="po-side-cart__empty">
			<div class="po-side-cart__empty-icon">
				<svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<circle cx="9" cy="21" r="1"></circle>
					<circle cx="20" cy="21" r="1"></circle>
					<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
				</svg>
			</div>
			<p class="po-side-cart__empty-text">Dein Warenkorb ist leer</p>
			<a href="<?php echo esc_url(home_url('/angebote/')); ?>" class="po-side-cart__empty-btn">
				Angebote entdecken
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	ob_start();
	?>
	<div class="po-side-cart__items">
		<?php foreach ($cart_items as $cart_item_key => $cart_item) :
			$product = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$quantity = $cart_item['quantity'];
			$product_price = WC()->cart->get_product_subtotal($product, $quantity);
			$product_permalink = $product->get_permalink();

			// Quell-IDs ermitteln
			$angebot_id = isset($cart_item['angebot_id']) ? $cart_item['angebot_id'] : get_post_meta($product_id, '_angebot_id', true);
			$event_id = isset($cart_item['event_id']) ? $cart_item['event_id'] : get_post_meta($product_id, '_event_id', true);

			// Bild: Event-Bild → Angebots-Bild → WC-Produkt-Bild
			$thumbnail = '';
			if ($event_id && function_exists('parkourone_get_event_image')) {
				$image_url = parkourone_get_event_image($event_id);
				if ($image_url) {
					$thumbnail = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product->get_name()) . '">';
				}
			}
			if (!$thumbnail && $angebot_id && function_exists('parkourone_get_angebot_image')) {
				$image_url = parkourone_get_angebot_image($angebot_id, 'thumbnail');
				if ($image_url) {
					$thumbnail = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product->get_name()) . '">';
				}
			}
			if (!$thumbnail) {
				$thumbnail = $product->get_image('thumbnail');
			}

			// Teilnehmer-Daten (Events vs. Angebote)
			$participants = [];
			if (!empty($cart_item['event_participant_data'])) {
				foreach ($cart_item['event_participant_data'] as $p) {
					$participants[] = ['vorname' => $p['vorname'] ?? '', 'name' => $p['name'] ?? ''];
				}
			} elseif (!empty($cart_item['angebot_teilnehmer'])) {
				$participants = $cart_item['angebot_teilnehmer'];
			}

			// Anzeigename und Details
			if ($event_id) {
				$display_name = get_the_title($event_id);
				$details = isset($cart_item['minimal_event_details']) ? $cart_item['minimal_event_details'] : [];
				$termin_datum = !empty($details['date']) ? $details['date'] : get_post_meta($product_id, '_event_date', true);
				$termin_ort = !empty($details['venue']) ? $details['venue'] : '';
			} else {
				$angebot_title = $angebot_id ? get_the_title($angebot_id) : '';
				$display_name = $angebot_title ?: $product->get_name();
				$termin_ort = get_post_meta($product_id, '_angebot_termin_ort', true);
				$termin_datum = get_post_meta($product_id, '_angebot_termin_datum', true);
			}
		?>
			<div class="po-side-cart__item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>">
				<div class="po-side-cart__item-image">
					<?php echo $thumbnail; ?>
				</div>
				<div class="po-side-cart__item-details">
					<a href="<?php echo esc_url($product_permalink); ?>" class="po-side-cart__item-name">
						<?php echo esc_html($display_name); ?>
					</a>
					<?php if ($termin_datum || $termin_ort) : ?>
						<div class="po-side-cart__item-event">
							<?php if ($termin_datum) : ?>
								<span class="po-side-cart__item-date"><?php echo esc_html(date_i18n('d. M Y', strtotime($termin_datum))); ?></span>
							<?php endif; ?>
							<?php if ($termin_ort) : ?>
								<span class="po-side-cart__item-location"><?php echo esc_html($termin_ort); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if (!empty($participants)) : ?>
						<div class="po-side-cart__item-participants">
							<?php foreach ($participants as $p) : ?>
								<span><?php echo esc_html($p['vorname'] . ' ' . $p['name']); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="po-side-cart__item-price"><?php echo $product_price; ?></div>
				</div>
				<button type="button" class="po-side-cart__item-remove" data-remove-item="<?php echo esc_attr($cart_item_key); ?>" aria-label="Entfernen">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Add WooCommerce cart fragments for header cart count
 */
function parkourone_wc_cart_fragments($fragments) {
	$cart_count = WC()->cart->get_cart_contents_count();
	$cart_total = WC()->cart->get_cart_total();

	// Header cart count badge
	$fragments['.po-header__cart-count'] = '<span class="po-header__cart-count" data-cart-count="' . esc_attr($cart_count) . '">' . esc_html($cart_count) . '</span>';

	// Side cart count badge
	$fragments['[data-side-cart-count]'] = '<span class="po-side-cart__badge" data-side-cart-count>' . esc_html($cart_count) . '</span>';

	// Side cart content
	$fragments['[data-side-cart-content]'] = '<div class="po-side-cart__content" data-side-cart-content>' . parkourone_get_side_cart_items_html() . '</div>';

	// Side cart total
	$fragments['[data-side-cart-total]'] = '<span data-side-cart-total>' . $cart_total . '</span>';

	return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'parkourone_wc_cart_fragments');

/**
 * Side Cart - AJAX: Remove item
 */
function parkourone_side_cart_remove() {
	check_ajax_referer('po_side_cart_nonce', 'nonce');

	$cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

	if (empty($cart_item_key)) {
		wp_send_json_error(['message' => 'Invalid cart item']);
	}

	WC()->cart->remove_cart_item($cart_item_key);

	wp_send_json_success([
		'content_html' => parkourone_get_side_cart_items_html(),
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_count' => WC()->cart->get_cart_contents_count(),
	]);
}
add_action('wp_ajax_po_side_cart_remove', 'parkourone_side_cart_remove');
add_action('wp_ajax_nopriv_po_side_cart_remove', 'parkourone_side_cart_remove');

/**
 * Side Cart - AJAX: Refresh content
 */
function parkourone_side_cart_refresh() {
	check_ajax_referer('po_side_cart_nonce', 'nonce');

	wp_send_json_success([
		'content_html' => parkourone_get_side_cart_items_html(),
		'cart_total' => WC()->cart->get_cart_total(),
		'cart_count' => WC()->cart->get_cart_contents_count(),
	]);
}
add_action('wp_ajax_po_side_cart_refresh', 'parkourone_side_cart_refresh');
add_action('wp_ajax_nopriv_po_side_cart_refresh', 'parkourone_side_cart_refresh');

