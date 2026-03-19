(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText, AlignmentToolbar, BlockControls } = wp.blockEditor;
	const { PanelBody, SelectControl, ColorPalette } = wp.components;
	const { createElement: el } = wp.element;

	const fontSizes = [
		{ label: 'Klein', value: 'small' },
		{ label: 'Normal', value: 'medium' },
		{ label: 'Gross', value: 'large' },
		{ label: 'Extra Gross', value: 'xlarge' }
	];

	const colors = [
		{ name: 'Transparent', color: '' },
		{ name: 'Weiß', color: '#ffffff' },
		{ name: 'Hellgrau', color: '#f5f5f7' },
		{ name: 'Dunkelgrau', color: '#1d1d1f' },
		{ name: 'Schwarz', color: '#000000' },
		{ name: 'Blau', color: '#0066cc' },
		{ name: 'Blau hell', color: '#e8f4fd' },
		{ name: 'Rot', color: '#ff3b30' },
		{ name: 'Grün', color: '#34c759' },
		{ name: 'Lila', color: '#667eea' },
		{ name: 'Lila hell', color: 'rgba(102, 126, 234, 0.1)' }
	];

	registerBlockType('parkourone/po-text', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-text po-text--' + attributes.fontSize,
				style: {
					backgroundColor: attributes.backgroundColor || undefined,
					color: attributes.textColor || undefined,
					textAlign: attributes.textAlign
				}
			});

			return el('div', null, [
				el(BlockControls, { key: 'toolbar' },
					el(AlignmentToolbar, {
						value: attributes.textAlign,
						onChange: function(v) { setAttributes({ textAlign: v }); }
					})
				),
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'typography', title: 'Typografie', initialOpen: true }, [
						el(SelectControl, {
							key: 'fontSize',
							label: 'Schriftgrösse',
							value: attributes.fontSize,
							options: fontSizes,
							onChange: function(v) { setAttributes({ fontSize: v }); }
						})
					]),
					el(PanelBody, { key: 'colors', title: 'Farben', initialOpen: false }, [
						el('p', { key: 'bgLabel', style: { marginBottom: '8px', fontWeight: 500 } }, 'Hintergrundfarbe'),
						el(ColorPalette, {
							key: 'bgColor',
							colors: colors,
							value: attributes.backgroundColor,
							onChange: function(v) { setAttributes({ backgroundColor: v }); }
						}),
						el('p', { key: 'textLabel', style: { marginBottom: '8px', marginTop: '16px', fontWeight: 500 } }, 'Textfarbe'),
						el(ColorPalette, {
							key: 'textColor',
							colors: colors,
							value: attributes.textColor,
							onChange: function(v) { setAttributes({ textColor: v }); }
						})
					])
				]),
				el(RichText, Object.assign({}, blockProps, {
					key: 'content',
					tagName: 'p',
					value: attributes.content,
					onChange: function(v) { setAttributes({ content: v }); },
					placeholder: 'Text hier eingeben...',
					allowedFormats: ['core/bold', 'core/italic', 'core/link']
				}))
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
