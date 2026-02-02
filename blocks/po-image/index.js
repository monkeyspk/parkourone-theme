(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, RangeControl, Button } = wp.components;
	const { createElement: el } = wp.element;

	const imageSizes = [
		{ label: 'Thumbnail', value: 'thumbnail' },
		{ label: 'Medium', value: 'medium' },
		{ label: 'Gross', value: 'large' },
		{ label: 'Volle Grösse', value: 'full' }
	];

	registerBlockType('parkourone/po-image', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-image-editor' });

			var onSelectMedia = function(media) {
				var url = media.sizes && media.sizes[attributes.sizeSlug]
					? media.sizes[attributes.sizeSlug].url
					: media.url;
				setAttributes({
					mediaId: media.id,
					mediaUrl: url,
					alt: media.alt || ''
				});
			};

			var onRemoveMedia = function() {
				setAttributes({ mediaId: 0, mediaUrl: '', alt: '' });
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'image', title: 'Bild-Einstellungen', initialOpen: true }, [
						el(SelectControl, {
							key: 'size',
							label: 'Bildgrösse',
							value: attributes.sizeSlug,
							options: imageSizes,
							onChange: function(v) { setAttributes({ sizeSlug: v }); }
						}),
						el(RangeControl, {
							key: 'radius',
							label: 'Eckenradius',
							value: attributes.borderRadius,
							onChange: function(v) { setAttributes({ borderRadius: v }); },
							min: 0,
							max: 50,
							step: 2
						}),
						el(TextControl, {
							key: 'alt',
							label: 'Alt-Text',
							value: attributes.alt,
							onChange: function(v) { setAttributes({ alt: v }); },
							help: 'Beschreibung für Screenreader und SEO'
						})
					]),
					el(PanelBody, { key: 'link', title: 'Verlinkung', initialOpen: false }, [
						el(TextControl, {
							key: 'linkUrl',
							label: 'Link URL',
							value: attributes.linkUrl,
							onChange: function(v) { setAttributes({ linkUrl: v }); },
							placeholder: 'https://...'
						}),
						el(SelectControl, {
							key: 'linkTarget',
							label: 'Link öffnen in',
							value: attributes.linkTarget,
							options: [
								{ label: 'Gleiches Fenster', value: '_self' },
								{ label: 'Neues Fenster', value: '_blank' }
							],
							onChange: function(v) { setAttributes({ linkTarget: v }); }
						})
					])
				]),
				el('figure', blockProps, [
					el(MediaUploadCheck, { key: 'upload-check' },
						el(MediaUpload, {
							key: 'upload',
							onSelect: onSelectMedia,
							allowedTypes: ['image'],
							value: attributes.mediaId,
							render: function(obj) {
								if (attributes.mediaUrl) {
									return el('div', { key: 'img-container', className: 'po-image-editor__container' }, [
										el('img', {
											key: 'img',
											src: attributes.mediaUrl,
											alt: attributes.alt,
											className: 'po-image-editor__img',
											style: { borderRadius: attributes.borderRadius + 'px' }
										}),
										el('div', { key: 'buttons', className: 'po-image-editor__buttons' }, [
											el(Button, {
												key: 'replace',
												onClick: obj.open,
												variant: 'secondary',
												size: 'small'
											}, 'Ersetzen'),
											el(Button, {
												key: 'remove',
												onClick: onRemoveMedia,
												variant: 'secondary',
												isDestructive: true,
												size: 'small'
											}, 'Entfernen')
										])
									]);
								}
								return el(Button, {
									key: 'placeholder',
									onClick: obj.open,
									className: 'po-image-editor__placeholder'
								}, [
									el('span', { key: 'icon', className: 'dashicons dashicons-format-image' }),
									el('span', { key: 'text' }, 'Bild auswählen')
								]);
							}
						})
					),
					attributes.mediaUrl && el(RichText, {
						key: 'caption',
						tagName: 'figcaption',
						className: 'po-image-editor__caption',
						value: attributes.caption,
						onChange: function(v) { setAttributes({ caption: v }); },
						placeholder: 'Bildunterschrift hinzufügen...'
					})
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
