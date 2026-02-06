/**
 * ParkourONE Checkout Block Filters
 * Customizes WooCommerce Block Checkout UI
 */
const { registerCheckoutFilters } = window.wc.blocksCheckout;

registerCheckoutFilters('parkourone', {
	placeOrderButtonLabel: () => 'Kostenpflichtig bestellen',
});
