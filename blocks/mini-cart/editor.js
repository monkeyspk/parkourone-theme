/**
 * Mini Cart Block - Editor Script
 */
(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, SelectControl } = wp.components;
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType('parkourone/mini-cart', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-mini-cart-editor'
			});

			return el('div', {},
				el(InspectorControls, {},
					el(PanelBody, { title: __('Einstellungen', 'parkourone'), initialOpen: true },
						el(SelectControl, {
							label: __('Position', 'parkourone'),
							value: attributes.position,
							options: [
								{ label: __('Rechts', 'parkourone'), value: 'right' },
								{ label: __('Links', 'parkourone'), value: 'left' }
							],
							onChange: function(value) {
								setAttributes({ position: value });
							}
						}),
						el(ToggleControl, {
							label: __('Automatisch öffnen beim Hinzufügen', 'parkourone'),
							help: __('Öffnet den Mini-Cart automatisch wenn ein Produkt hinzugefügt wird.', 'parkourone'),
							checked: attributes.showOnAdd,
							onChange: function(value) {
								setAttributes({ showOnAdd: value });
							}
						}),
						el(SelectControl, {
							label: __('Trigger-Stil', 'parkourone'),
							value: attributes.triggerStyle,
							options: [
								{ label: __('Nur Icon', 'parkourone'), value: 'icon' },
								{ label: __('Button mit Text', 'parkourone'), value: 'button' },
								{ label: __('Nur Text', 'parkourone'), value: 'text' }
							],
							onChange: function(value) {
								setAttributes({ triggerStyle: value });
							}
						})
					)
				),
				el('div', blockProps,
					el('div', { className: 'po-mini-cart-editor__preview' },
						el('button', { className: 'po-mini-cart-editor__trigger' },
							el('svg', { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2 },
								el('circle', { cx: 9, cy: 21, r: 1 }),
								el('circle', { cx: 20, cy: 21, r: 1 }),
								el('path', { d: 'm1 1 4 4 2.68 11.69a2 2 0 0 0 2 1.31h9.72a2 2 0 0 0 2-1.31L23 6H6' })
							),
							attributes.triggerStyle !== 'icon' && el('span', {}, 'Warenkorb'),
							el('span', { className: 'po-mini-cart-editor__count' }, '0')
						),
						el('p', { className: 'po-mini-cart-editor__hint' },
							'Mini Warenkorb • Position: ' + (attributes.position === 'right' ? 'Rechts' : 'Links')
						)
					)
				)
			);
		}
	});
})(window.wp);
