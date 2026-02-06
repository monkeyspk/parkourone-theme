/**
 * Modern Cart Block - Editor Script
 */
(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, TextControl } = wp.components;
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType('parkourone/modern-cart', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-modern-cart-editor'
			});

			return el('div', {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Einstellungen', 'parkourone'), initialOpen: true },
						el(ToggleControl, {
							label: __('Gutscheinfeld anzeigen', 'parkourone'),
							checked: attributes.showCoupon,
							onChange: function(value) {
								setAttributes({ showCoupon: value });
							}
						}),
						el(ToggleControl, {
							label: __('Produktbilder anzeigen', 'parkourone'),
							checked: attributes.showThumbnails,
							onChange: function(value) {
								setAttributes({ showThumbnails: value });
							}
						}),
						el(TextControl, {
							label: __('Checkout-Button Text', 'parkourone'),
							value: attributes.checkoutButtonText,
							onChange: function(value) {
								setAttributes({ checkoutButtonText: value });
							}
						}),
						el(TextControl, {
							label: __('Leerer Warenkorb Text', 'parkourone'),
							value: attributes.emptyCartText,
							onChange: function(value) {
								setAttributes({ emptyCartText: value });
							}
						}),
						el(TextControl, {
							label: __('Weiter einkaufen URL', 'parkourone'),
							value: attributes.continueShoppingUrl,
							onChange: function(value) {
								setAttributes({ continueShoppingUrl: value });
							}
						})
					)
				),
				el('div', blockProps,
					el('div', { className: 'po-modern-cart-editor__preview' },
						el('div', { className: 'po-modern-cart-editor__icon' },
							el('svg', { width: 48, height: 48, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 1.5 },
								el('circle', { cx: 9, cy: 21, r: 1 }),
								el('circle', { cx: 20, cy: 21, r: 1 }),
								el('path', { d: 'm1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6' })
							)
						),
						el('h3', { className: 'po-modern-cart-editor__title' }, 'Modern Warenkorb'),
						el('p', { className: 'po-modern-cart-editor__desc' }, 'Zeigt den Warenkorb mit modernem Design an.')
					)
				)
			);
		}
	});
})(window.wp);
