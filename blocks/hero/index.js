(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, Button, TextControl } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/hero', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-hero-editor' });

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Hero Einstellungen', initialOpen: true },
						el(MediaUploadCheck, null,
							el(MediaUpload, {
								onSelect: function(media) {
									setAttributes({ imageUrl: media.url, imageId: media.id });
								},
								allowedTypes: ['image'],
								value: attributes.imageId,
								render: function(obj) {
									return el('div', { style: { marginBottom: '16px' } },
										attributes.imageUrl
											? el('div', null,
												el('img', { src: attributes.imageUrl, style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '8px' } }),
												el(Button, { onClick: obj.open, variant: 'secondary', style: { marginRight: '8px' } }, 'Bild ändern'),
												el(Button, { onClick: function() { setAttributes({ imageUrl: '', imageId: 0 }); }, variant: 'link', isDestructive: true }, 'Entfernen')
											)
											: el(Button, { onClick: obj.open, variant: 'primary' }, 'Hintergrundbild wählen')
									);
								}
							})
						),
						el(TextControl, {
							label: 'Button Text',
							value: attributes.buttonText,
							onChange: function(v) { setAttributes({ buttonText: v }); }
						}),
						el(TextControl, {
							label: 'Button Link',
							value: attributes.buttonUrl,
							onChange: function(v) { setAttributes({ buttonUrl: v }); }
						})
					)
				),
				el('div', blockProps,
					el('div', { 
						className: 'po-hero-editor__preview',
						style: { backgroundImage: attributes.imageUrl ? 'url(' + attributes.imageUrl + ')' : 'none' }
					},
						el('div', { className: 'po-hero-editor__content' },
							el(RichText, {
								tagName: 'h1',
								className: 'po-hero-editor__headline',
								value: attributes.headline,
								onChange: function(v) { setAttributes({ headline: v }); },
								placeholder: 'Headline...'
							}),
							el(RichText, {
								tagName: 'p',
								className: 'po-hero-editor__subtext',
								value: attributes.subtext,
								onChange: function(v) { setAttributes({ subtext: v }); },
								placeholder: 'Subtext...'
							}),
							el('span', { className: 'po-hero-editor__button' }, attributes.buttonText || 'Button')
						)
					)
				)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
