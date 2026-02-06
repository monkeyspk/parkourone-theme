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
