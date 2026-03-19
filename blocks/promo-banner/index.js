(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, Button, TextControl, SelectControl } = wp.components;
	const { createElement: el } = wp.element;

	var schemeColors = {
		dark: { bg: '#1d1d1f', text: '#fff', sub: 'rgba(255,255,255,0.7)', border: 'rgba(255,255,255,0.3)' },
		light: { bg: '#f5f5f7', text: '#1d1d1f', sub: '#6e6e73', border: 'rgba(0,0,0,0.2)' },
		accent: { bg: '#0066cc', text: '#fff', sub: 'rgba(255,255,255,0.8)', border: 'rgba(255,255,255,0.4)' }
	};

	registerBlockType('parkourone/promo-banner', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			var scheme = schemeColors[attributes.colorScheme || 'dark'];
			const blockProps = useBlockProps({ className: 'po-pb-editor' });

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Banner Einstellungen', initialOpen: true }, [
						el(SelectControl, {
							key: 'colorscheme',
							label: 'Farbschema',
							value: attributes.colorScheme || 'dark',
							options: [
								{ label: 'Dunkel', value: 'dark' },
								{ label: 'Hell', value: 'light' },
								{ label: 'Akzent (Blau)', value: 'accent' }
							],
							onChange: function(v) { setAttributes({ colorScheme: v }); }
						}),
						el('div', { key: 'badge', style: { marginBottom: '16px' } },
							el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Badge/Logo (optional)'),
							el(MediaUploadCheck, null,
								el(MediaUpload, {
									onSelect: function(media) { setAttributes({ badgeUrl: media.url }); },
									allowedTypes: ['image'],
									render: function(obj) {
										return el('div', null,
											attributes.badgeUrl
												? el('div', null,
													el('img', { src: attributes.badgeUrl, style: { maxWidth: '100px', marginBottom: '8px' } }),
													el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true, style: { marginRight: '8px' } }, 'Ändern'),
													el(Button, { onClick: function() { setAttributes({ badgeUrl: '' }); }, variant: 'link', isDestructive: true, isSmall: true }, 'Entfernen')
												)
												: el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true }, 'Badge wählen')
										);
									}
								})
							)
						),
						el(TextControl, {
							key: 'btntext',
							label: 'Button Text',
							value: attributes.buttonText,
							onChange: function(v) { setAttributes({ buttonText: v }); }
						}),
						el(TextControl, {
							key: 'btnurl',
							label: 'Button URL',
							value: attributes.buttonUrl,
							onChange: function(v) { setAttributes({ buttonUrl: v }); }
						}),
						el(SelectControl, {
							key: 'btnicon',
							label: 'Button Icon',
							value: attributes.buttonIcon,
							options: [
								{ label: 'Play', value: 'play' },
								{ label: 'Pfeil', value: 'arrow' },
								{ label: 'Keins', value: 'none' }
							],
							onChange: function(v) { setAttributes({ buttonIcon: v }); }
						}),
						el('div', { key: 'image', style: { marginTop: '16px' } },
							el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Bild (rechts)'),
							el(MediaUploadCheck, null,
								el(MediaUpload, {
									onSelect: function(media) { setAttributes({ imageUrl: media.url }); },
									allowedTypes: ['image'],
									render: function(obj) {
										return el('div', null,
											attributes.imageUrl
												? el('div', null,
													el('img', { src: attributes.imageUrl, style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '8px' } }),
													el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true, style: { marginRight: '8px' } }, 'Ändern'),
													el(Button, { onClick: function() { setAttributes({ imageUrl: '' }); }, variant: 'link', isDestructive: true, isSmall: true }, 'Entfernen')
												)
												: el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true }, 'Bild wählen')
										);
									}
								})
							)
						)
					])
				),
				el('div', blockProps,
					el('div', { className: 'po-pb-editor__preview', style: { background: scheme.bg } },
						el('div', { className: 'po-pb-editor__content' },
							attributes.badgeUrl && el('img', { src: attributes.badgeUrl, className: 'po-pb-editor__badge' }),
							el(RichText, {
								tagName: 'h3',
								className: 'po-pb-editor__headline',
								style: { color: scheme.text },
								value: attributes.headline,
								onChange: function(v) { setAttributes({ headline: v }); },
								placeholder: 'Headline...'
							}),
							el(RichText, {
								tagName: 'p',
								className: 'po-pb-editor__subtext',
								style: { color: scheme.sub },
								value: attributes.subtext,
								onChange: function(v) { setAttributes({ subtext: v }); },
								placeholder: 'Subtext...'
							})
						),
						el('div', { className: 'po-pb-editor__right' },
							el('span', { className: 'po-pb-editor__button', style: { color: scheme.text, borderColor: scheme.border } }, attributes.buttonText || 'Button'),
							attributes.imageUrl && el('img', { src: attributes.imageUrl, className: 'po-pb-editor__image' })
						)
					)
				)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
