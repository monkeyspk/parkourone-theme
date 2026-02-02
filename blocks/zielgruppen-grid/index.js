(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, Button, TextControl, CardBody, Card } = wp.components;
	const { createElement: el, useState } = wp.element;

	registerBlockType('parkourone/zielgruppen-grid', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-cg-editor' });

			function updateCategory(index, key, value) {
				const newCategories = [...attributes.categories];
				newCategories[index] = { ...newCategories[index], [key]: value };
				setAttributes({ categories: newCategories });
			}

			function addCategory() {
				if (attributes.categories.length < 4) {
					setAttributes({
						categories: [...attributes.categories, { label: 'Neue Zielgruppe', imageUrl: '', linkUrl: '#' }]
					});
				}
			}

			function removeCategory(index) {
				if (attributes.categories.length > 3) {
					const newCategories = attributes.categories.filter((_, i) => i !== index);
					setAttributes({ categories: newCategories });
				}
			}

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Zielgruppen', initialOpen: true },
						attributes.categories.map(function(cat, index) {
							return el(Card, { key: index, style: { marginBottom: '16px' } },
								el(CardBody, null,
									el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' } },
										el('strong', null, 'Zielgruppe ' + (index + 1)),
										attributes.categories.length > 3 && el(Button, {
											isDestructive: true,
											isSmall: true,
											onClick: function() { removeCategory(index); }
										}, 'Entfernen')
									),
									el(TextControl, {
										label: 'Label',
										value: cat.label,
										onChange: function(v) { updateCategory(index, 'label', v); }
									}),
									el(TextControl, {
										label: 'Link URL',
										value: cat.linkUrl,
										onChange: function(v) { updateCategory(index, 'linkUrl', v); }
									}),
									el(MediaUploadCheck, null,
										el(MediaUpload, {
											onSelect: function(media) { updateCategory(index, 'imageUrl', media.url); },
											allowedTypes: ['image'],
											render: function(obj) {
												return el('div', null,
													cat.imageUrl
														? el('div', null,
															el('img', { src: cat.imageUrl, style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '8px' } }),
															el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true }, 'Ändern')
														)
														: el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true }, 'Bild wählen')
												);
											}
										})
									)
								)
							);
						}),
						attributes.categories.length < 4 && el(Button, {
							variant: 'secondary',
							onClick: addCategory,
							style: { width: '100%' }
						}, '+ Zielgruppe hinzufügen (max. 4)')
					)
				),
				el('div', blockProps,
					el(RichText, {
						tagName: 'h2',
						className: 'po-cg-editor__headline',
						value: attributes.headline,
						onChange: function(v) { setAttributes({ headline: v }); },
						placeholder: 'Headline...'
					}),
					el(RichText, {
						tagName: 'p',
						className: 'po-cg-editor__subtext',
						value: attributes.subtext,
						onChange: function(v) { setAttributes({ subtext: v }); },
						placeholder: 'Subtext...'
					}),
					el('div', { className: 'po-cg-editor__grid po-cg-editor__grid--' + attributes.categories.length },
						attributes.categories.map(function(cat, index) {
							return el('div', { 
								key: index, 
								className: 'po-cg-editor__card',
								style: { backgroundImage: cat.imageUrl ? 'url(' + cat.imageUrl + ')' : 'none' }
							},
								el('span', { className: 'po-cg-editor__label' }, '→ ' + cat.label)
							);
						})
					)
				)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
