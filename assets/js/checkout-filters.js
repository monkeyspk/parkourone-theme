/**
 * ParkourONE Checkout Block Filters
 * Customizes WooCommerce Block Checkout UI
 */
const { registerCheckoutFilters } = window.wc.blocksCheckout;

registerCheckoutFilters('parkourone', {
	placeOrderButtonLabel: () => 'Kostenpflichtig bestellen',
});

/**
 * Filter payment methods — only allow WooPayments (Karte) + PayPal
 *
 * The PayPal Payments plugin registers many sub-gateways (Bancontact, Blik,
 * EPS, iDeal, MyBank, Przelewy24, Trustly, Multibanco, Advanced Card Processing)
 * even when they are not set up. The PHP filter woocommerce_available_payment_gateways
 * does not reliably work with the Block Checkout, so we also filter client-side.
 */
if (window.wc && window.wc.wcBlocksRegistry) {
	const { registerPaymentMethodExtensionCallbacks } = window.wc.wcBlocksRegistry;

	// Block all PayPal APMs and sub-gateways — return false = hide
	registerPaymentMethodExtensionCallbacks('parkourone', {
		'ppcp-credit-card-gateway': () => false,
		'ppcp-bancontact':          () => false,
		'ppcp-blik':                () => false,
		'ppcp-eps':                 () => false,
		'ppcp-ideal':               () => false,
		'ppcp-mybank':              () => false,
		'ppcp-p24':                 () => false,
		'ppcp-trustly':             () => false,
		'ppcp-multibanco':          () => false,
		'ppcp-sepa':                () => false,
		'ppcp-oxxo':                () => false,
	});
}
