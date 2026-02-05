(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, Button, TextControl, SelectControl, RangeControl, BaseControl } = wp.components;
	const { createElement: el, useState } = wp.element;

	registerBlockType('parkourone/hero', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-hero-editor' });

			// Stats Management
			const stats = attributes.stats || [];
			const addStat = function() {
				setAttributes({ stats: [...stats, { number: '', suffix: '', label: '' }] });
			};
			const updateStat = function(index, key, value) {
				const newStats = [...stats];
				newStats[index] = { ...newStats[index], [key]: value };
				setAttributes({ stats: newStats });
			};
			const removeStat = function(index) {
				setAttributes({ stats: stats.filter(function(_, i) { return i !== index; }) });
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					// Layout Panel
					el(PanelBody, { title: 'Layout', initialOpen: true },
						el(SelectControl, {
							label: 'Ausrichtung',
							value: attributes.layout || 'centered',
							options: [
								{ label: 'Zentriert', value: 'centered' },
								{ label: 'Links', value: 'left' }
							],
							onChange: function(v) { setAttributes({ layout: v }); }
						}),
						el(RangeControl, {
							label: 'Overlay Deckkraft',
							value: attributes.overlayOpacity || 50,
							onChange: function(v) { setAttributes({ overlayOpacity: v }); },
							min: 0,
							max: 90,
							step: 5
						})
					),

					// Media Panel
					el(PanelBody, { title: 'Hintergrundbild', initialOpen: false },
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
												el(Button, { onClick: obj.open, variant: 'secondary', style: { marginRight: '8px' } }, 'Ändern'),
												el(Button, { onClick: function() { setAttributes({ imageUrl: '', imageId: 0 }); }, variant: 'link', isDestructive: true }, 'Entfernen')
											)
											: el(Button, { onClick: obj.open, variant: 'primary' }, 'Bild wählen')
									);
								}
							})
						)
					),

					// Buttons Panel
					el(PanelBody, { title: 'Buttons', initialOpen: false },
						el('p', { style: { fontWeight: '600', marginBottom: '8px' } }, 'Primärer Button'),
						el(TextControl, {
							label: 'Text',
							value: attributes.buttonText,
							onChange: function(v) { setAttributes({ buttonText: v }); }
						}),
						el(TextControl, {
							label: 'Link',
							value: attributes.buttonUrl,
							onChange: function(v) { setAttributes({ buttonUrl: v }); },
							help: 'z.B. #stundenplan für Scroll oder /kontakt/ für Seite'
						}),
						el('hr', { style: { margin: '20px 0' } }),
						el('p', { style: { fontWeight: '600', marginBottom: '8px' } }, 'Video Button (oder zweiter Link)'),
						el(TextControl, {
							label: 'YouTube URL',
							value: attributes.videoUrl || '',
							onChange: function(v) { setAttributes({ videoUrl: v }); },
							help: 'YouTube Link für Modal-Video'
						}),
						attributes.videoUrl
							? el(TextControl, {
								label: 'Video Button Text',
								value: attributes.videoButtonText || 'Film ansehen',
								onChange: function(v) { setAttributes({ videoButtonText: v }); }
							})
							: el('div', null,
								el(TextControl, {
									label: 'Zweiter Button Text',
									value: attributes.secondButtonText || '',
									onChange: function(v) { setAttributes({ secondButtonText: v }); }
								}),
								el(TextControl, {
									label: 'Zweiter Button Link',
									value: attributes.secondButtonUrl || '',
									onChange: function(v) { setAttributes({ secondButtonUrl: v }); }
								})
							)
					),

					// Stats Panel
					el(PanelBody, { title: 'Statistiken', initialOpen: false },
						el('p', { style: { color: '#757575', fontSize: '12px', marginBottom: '12px' } },
							'Zahlen werden unten im Hero angezeigt'
						),
						stats.map(function(stat, index) {
							return el('div', {
								key: index,
								style: {
									background: '#f0f0f0',
									padding: '12px',
									borderRadius: '8px',
									marginBottom: '12px'
								}
							},
								el('div', { style: { display: 'flex', gap: '8px', marginBottom: '8px' } },
									el(TextControl, {
										label: 'Zahl',
										value: stat.number || '',
										onChange: function(v) { updateStat(index, 'number', v); },
										style: { flex: 2 }
									}),
									el(TextControl, {
										label: 'Suffix',
										value: stat.suffix || '',
										onChange: function(v) { updateStat(index, 'suffix', v); },
										style: { flex: 1 },
										placeholder: '+, %, etc.'
									})
								),
								el(TextControl, {
									label: 'Label',
									value: stat.label || '',
									onChange: function(v) { updateStat(index, 'label', v); }
								}),
								el(Button, {
									variant: 'link',
									isDestructive: true,
									onClick: function() { removeStat(index); },
									style: { marginTop: '4px' }
								}, 'Entfernen')
							);
						}),
						el(Button, {
							variant: 'secondary',
							onClick: addStat,
							style: { width: '100%' }
						}, '+ Statistik hinzufügen')
					)
				),

				// Preview
				el('div', blockProps,
					el('div', {
						className: 'po-hero-editor__preview',
						style: { backgroundImage: attributes.imageUrl ? 'url(' + attributes.imageUrl + ')' : 'none' }
					},
						el('div', { className: 'po-hero-editor__content' },
							el(TextControl, {
								className: 'po-hero-editor__eyebrow',
								value: attributes.eyebrow || '',
								onChange: function(v) { setAttributes({ eyebrow: v }); },
								placeholder: 'EYEBROW TEXT'
							}),
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
							el('div', { className: 'po-hero-editor__buttons' },
								el('span', { className: 'po-hero-editor__button po-hero-editor__button--primary' },
									attributes.buttonText || 'Jetzt starten'
								),
								(attributes.videoUrl || attributes.secondButtonText) &&
									el('span', { className: 'po-hero-editor__button po-hero-editor__button--video' },
										attributes.videoUrl
											? el('span', null, '▶ ', attributes.videoButtonText || 'Film ansehen')
											: attributes.secondButtonText
									)
							)
						)
					)
				)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
