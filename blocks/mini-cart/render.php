<?php
/**
 * Mini Cart Block - Render
 *
 * @package ParkourONE
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WooCommerce')) {
	return;
}

$position = $attributes['position'] ?? 'right';
$show_on_add = $attributes['showOnAdd'] ?? true;
$trigger_style = $attributes['triggerStyle'] ?? 'icon';

$cart = WC()->cart;
$cart_items = $cart->get_cart();
$cart_count = $cart->get_cart_contents_count();
$cart_total = $cart->get_cart_total();

$wrapper_attributes = get_block_wrapper_attributes([
	'class' => 'po-mini-cart',
	'data-position' => $position,
	'data-show-on-add' => $show_on_add ? 'true' : 'false',
	'data-ajax-url' => admin_url('admin-ajax.php'),
	'data-nonce' => wp_create_nonce('po_mini_cart_nonce'),
]);
?>

<div <?php echo $wrapper_attributes; ?>>
	<!-- Trigger Button -->
	<button type="button" class="po-mini-cart__trigger po-mini-cart__trigger--<?php echo esc_attr($trigger_style); ?>" data-mini-cart-trigger aria-label="Warenkorb öffnen">
		<?php if ($trigger_style === 'icon' || $trigger_style === 'button') : ?>
			<svg class="po-mini-cart__trigger-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<circle cx="9" cy="21" r="1"></circle>
				<circle cx="20" cy="21" r="1"></circle>
				<path d="m1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6"></path>
			</svg>
		<?php endif; ?>
		<?php if ($trigger_style === 'button' || $trigger_style === 'text') : ?>
			<span class="po-mini-cart__trigger-text">Warenkorb</span>
		<?php endif; ?>
		<span class="po-mini-cart__trigger-count" data-cart-count="<?php echo esc_attr($cart_count); ?>"><?php echo esc_html($cart_count); ?></span>
	</button>

	<!-- Overlay -->
	<div class="po-mini-cart__overlay" data-mini-cart-overlay></div>

	<!-- Drawer -->
	<div class="po-mini-cart__drawer" data-mini-cart-drawer aria-hidden="true">
		<div class="po-mini-cart__drawer-inner">
			<!-- Header -->
			<div class="po-mini-cart__header">
				<h2 class="po-mini-cart__title">
					Warenkorb
					<span class="po-mini-cart__count" data-drawer-count><?php echo esc_html($cart_count); ?></span>
				</h2>
				<button type="button" class="po-mini-cart__close" data-mini-cart-close aria-label="Warenkorb schliessen">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<!-- Content -->
			<div class="po-mini-cart__content" data-mini-cart-content>
				<?php if (empty($cart_items)) : ?>
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
				<?php else : ?>
					<div class="po-mini-cart__items" data-mini-cart-items>
						<?php foreach ($cart_items as $cart_item_key => $cart_item) :
							$product = $cart_item['data'];
							$product_id = $cart_item['product_id'];
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
				<?php endif; ?>
			</div>

			<!-- Footer -->
			<?php if (!empty($cart_items)) : ?>
				<div class="po-mini-cart__footer">
					<div class="po-mini-cart__total">
						<span>Gesamt</span>
						<span data-mini-cart-total><?php echo $cart_total; ?></span>
					</div>
					<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="po-mini-cart__btn po-mini-cart__btn--primary">
						Zur Kasse
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="5" y1="12" x2="19" y2="12"></line>
							<polyline points="12 5 19 12 12 19"></polyline>
						</svg>
					</a>
					<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="po-mini-cart__link">
						Warenkorb anzeigen
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
