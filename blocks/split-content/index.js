(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, ToggleControl, TextControl, Button, ColorPalette } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/split-content', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-split' + (attributes.imageRight ? ' po-split--image-right' : ''),
				style: { backgroundColor: attributes.backgroundColor }
			});

			const colors = [
				{ name: 'Weiß', color: '#ffffff' },
				{ name: 'Hellgrau', color: '#f5f5f7' },
				{ name: 'Schwarz', color: '#000000' }
			];

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Layout' }, [
						el(ToggleControl, {
							key: 'imgpos',
							label: 'Bild rechts',
							checked: attributes.imageRight,
							onChange: function(val) { setAttributes({ imageRight: val }); }
						}),
						el('p', { key: 'colorlabel', style: { marginBottom: '8px' } }, 'Hintergrundfarbe'),
						el(ColorPalette, {
							key: 'bgcolor',
							colors: colors,
							value: attributes.backgroundColor,
							onChange: function(val) { setAttributes({ backgroundColor: val || '#ffffff' }); }
						})
					]),
					el(PanelBody, { title: 'Button', initialOpen: false }, [
						el(ToggleControl, {
							key: 'showcta',
							label: 'Button anzeigen',
							checked: attributes.showCta,
							onChange: function(val) { setAttributes({ showCta: val }); }
						}),
						attributes.showCta && el(TextControl, {
							key: 'ctatext',
							label: 'Button Text',
							value: attributes.ctaText,
							onChange: function(val) { setAttributes({ ctaText: val }); }
						}),
						attributes.showCta && el(TextControl, {
							key: 'ctaurl',
							label: 'Button URL',
							value: attributes.ctaUrl,
							onChange: function(val) { setAttributes({ ctaUrl: val }); }
						})
					])
				),
				el('div', blockProps, [
					el('div', { key: 'text', className: 'po-split__text' }, [
						el(RichText, {
							key: 'headline',
							tagName: 'h2',
							className: 'po-split__headline',
							value: attributes.headline,
							onChange: function(val) { setAttributes({ headline: val }); },
							placeholder: 'Überschrift...'
						}),
						el(RichText, {
							key: 'content',
							tagName: 'div',
							className: 'po-split__content',
							value: attributes.text,
							onChange: function(val) { setAttributes({ text: val }); },
							placeholder: 'Text eingeben...',
							multiline: 'p'
						}),
						attributes.showCta && el('span', {
							key: 'cta',
							className: 'po-split__cta'
						}, attributes.ctaText)
					]),
					el('div', { key: 'media', className: 'po-split__media' }, [
						el(MediaUploadCheck, { key: 'upload' },
							el(MediaUpload, {
								onSelect: function(media) { setAttributes({ imageUrl: media.url }); },
								allowedTypes: ['image'],
								render: function(obj) {
									return attributes.imageUrl
										? el('div', { style: { position: 'relative' } }, [
											el('img', {
												key: 'img',
												src: attributes.imageUrl,
												style: { width: '100%', height: 'auto' }
											}),
											el(Button, {
												key: 'change',
												onClick: obj.open,
												variant: 'secondary',
												style: { position: 'absolute', top: '10px', right: '10px' }
											}, 'Ändern')
										])
										: el(Button, {
											onClick: obj.open,
											variant: 'secondary',
											style: { padding: '3rem', border: '2px dashed #ccc', width: '100%' }
										}, 'Bild auswählen');
								}
							})
						)
					])
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
