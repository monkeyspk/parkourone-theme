(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, TextControl, RangeControl, Button, SelectControl, ColorPicker } = wp.components;
	const { createElement: el, Fragment } = wp.element;

	registerBlockType('parkourone/page-header', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-ph po-ph--' + (attributes.variant || 'centered')
			});

			// Varianten-Labels
			const variantOptions = [
				{ label: 'Centered - Grosser Text zentriert', value: 'centered' },
				{ label: 'Split - Text links, Bild rechts', value: 'split' },
				{ label: 'Fullscreen - Vollbild mit Overlay', value: 'fullscreen' },
				{ label: 'Banner - Kompakter Bild-Banner', value: 'banner' }
			];

			// Inspector Controls
			var inspectorPanels = [
				// Varianten-Auswahl Panel
				el(PanelBody, { title: 'Layout-Variante', initialOpen: true, key: 'variant-panel' },
					el(SelectControl, {
						label: 'Header Stil',
						value: attributes.variant || 'centered',
						options: variantOptions,
						onChange: function(val) { setAttributes({ variant: val }); }
					}),
					el('p', { style: { fontSize: '12px', color: '#757575', marginTop: '8px' } },
						attributes.variant === 'centered' ? 'Grosser zentrierter Text mit optionalem Stats-Counter. Ideal f\u00fcr Hauptseiten.' :
						attributes.variant === 'split' ? 'Text links, grosses Bild rechts. Ideal f\u00fcr Unterseiten.' :
						attributes.variant === 'banner' ? 'Kompakter Querformat-Banner mit Bild und kleinem Titel. Ideal wenn weniger Platz gew\u00fcnscht.' :
						'Vollbild-Hintergrundbild mit Text-Overlay. Ideal f\u00fcr visuelle Impact.'
					)
				)
			];

			// Bild Panel (f\u00fcr Split, Fullscreen und Banner)
			if (attributes.variant === 'split' || attributes.variant === 'fullscreen' || attributes.variant === 'banner') {
				inspectorPanels.push(
					el(PanelBody, { title: 'Bild', initialOpen: true, key: 'image-panel' }, [
						el(MediaUploadCheck, { key: 'imgupload' },
							el(MediaUpload, {
								onSelect: function(media) {
									setAttributes({
										image: media.url,
										imageAlt: media.alt || ''
									});
								},
								allowedTypes: ['image'],
								render: function(obj) {
									return el(Button, {
										onClick: obj.open,
										variant: 'secondary'
									}, attributes.image ? 'Bild \u00e4ndern' : 'Bild w\u00e4hlen');
								}
							})
						),
						attributes.image && el(Button, {
							key: 'removeimg',
							onClick: function() { setAttributes({ image: '', imageAlt: '' }); },
							variant: 'link',
							isDestructive: true,
							style: { marginTop: '8px', display: 'block' }
						}, 'Bild entfernen'),
						attributes.variant === 'split' && el(RangeControl, {
							key: 'rotation',
							label: 'Bild-Rotation',
							value: attributes.imageRotation || 3,
							onChange: function(val) { setAttributes({ imageRotation: val }); },
							min: -10,
							max: 10,
							step: 1
						}),
						(attributes.variant === 'fullscreen' || attributes.variant === 'banner') && el(RangeControl, {
							key: 'overlay',
							label: 'Overlay St\u00e4rke (%)',
							value: attributes.overlayOpacity || 50,
							onChange: function(val) { setAttributes({ overlayOpacity: val }); },
							min: 0,
							max: 100,
							step: 5
						})
					])
				);
			}

			// Akzentfarbe Panel
			inspectorPanels.push(
				el(PanelBody, { title: 'Akzentfarbe', initialOpen: false, key: 'color-panel' },
					el('div', { style: { marginBottom: '8px' } },
						el('label', { style: { display: 'block', marginBottom: '4px', fontWeight: 500 } }, 'Farbe f\u00fcr Akzent-Text'),
						el(ColorPicker, {
							color: attributes.accentColor || '#0066cc',
							onChangeComplete: function(color) { setAttributes({ accentColor: color.hex }); },
							disableAlpha: true
						})
					)
				)
			);

			// Prim\u00e4rer Button Panel
			inspectorPanels.push(
				el(PanelBody, { title: 'Prim\u00e4rer Button', initialOpen: false, key: 'cta-panel' }, [
					el(TextControl, {
						key: 'ctatext',
						label: 'Button Text',
						value: attributes.ctaText || '',
						onChange: function(val) { setAttributes({ ctaText: val }); }
					}),
					el(TextControl, {
						key: 'ctaurl',
						label: 'Button URL',
						value: attributes.ctaUrl || '',
						onChange: function(val) { setAttributes({ ctaUrl: val }); }
					})
				])
			);

			// Sekund\u00e4rer Link Panel
			inspectorPanels.push(
				el(PanelBody, { title: 'Sekund\u00e4rer Link', initialOpen: false, key: 'cta2-panel' }, [
					el(TextControl, {
						key: 'cta2text',
						label: 'Link Text',
						value: attributes.ctaSecondaryText || '',
						onChange: function(val) { setAttributes({ ctaSecondaryText: val }); }
					}),
					el(TextControl, {
						key: 'cta2url',
						label: 'Link URL',
						value: attributes.ctaSecondaryUrl || '',
						onChange: function(val) { setAttributes({ ctaSecondaryUrl: val }); }
					})
				])
			);

			// Preview erstellen basierend auf Variante
			var previewContent;

			if (attributes.variant === 'centered') {
				// CENTERED Preview
				previewContent = el('div', {
					className: 'po-ph__container po-ph__container--centered',
					style: {
						background: 'linear-gradient(180deg, #f5f5f7 0%, #fff 50%)',
						padding: '4rem 2rem',
						textAlign: 'center',
						borderRadius: '12px'
					}
				}, [
					el('div', { className: 'po-ph__content po-ph__content--centered', key: 'content', style: { maxWidth: '800px', margin: '0 auto' } }, [
						el(RichText, {
							key: 'title',
							tagName: 'h1',
							className: 'po-ph__title po-ph__title--centered',
							value: attributes.title || '',
							onChange: function(val) { setAttributes({ title: val }); },
							placeholder: 'Titel eingeben...',
							style: { fontSize: 'clamp(2rem, 5vw, 3.5rem)', fontWeight: 700, lineHeight: 1, margin: '0 0 0.5rem', letterSpacing: '-0.03em' }
						}),
						el(RichText, {
							key: 'accent',
							tagName: 'span',
							className: 'po-ph__title-accent',
							value: attributes.titleAccent || '',
							onChange: function(val) { setAttributes({ titleAccent: val }); },
							placeholder: 'Akzent-Text (farbig)...',
							style: {
								fontSize: 'clamp(2rem, 5vw, 3.5rem)',
								fontWeight: 700,
								lineHeight: 1,
								display: 'block',
								background: 'linear-gradient(135deg, ' + (attributes.accentColor || '#0066cc') + ' 0%, #0099ff 100%)',
								WebkitBackgroundClip: 'text',
								WebkitTextFillColor: 'transparent',
								marginBottom: '1.5rem'
							}
						}),
						el(RichText, {
							key: 'desc',
							tagName: 'p',
							className: 'po-ph__description po-ph__description--centered',
							value: attributes.description || '',
							onChange: function(val) { setAttributes({ description: val }); },
							placeholder: 'Beschreibung eingeben...',
							style: { fontSize: '1.1rem', color: '#6e6e73', lineHeight: 1.6, maxWidth: '600px', margin: '0 auto 1.5rem' }
						}),
						(attributes.ctaText || attributes.ctaSecondaryText) && el('div', {
							key: 'actions',
							style: { display: 'flex', gap: '1rem', justifyContent: 'center', flexWrap: 'wrap' }
						}, [
							attributes.ctaText && el('span', {
								key: 'cta1',
								style: { padding: '0.75rem 1.5rem', background: '#1d1d1f', color: '#fff', borderRadius: '100px', fontSize: '0.9rem', fontWeight: 600 }
							}, attributes.ctaText),
							attributes.ctaSecondaryText && el('span', {
								key: 'cta2',
								style: { padding: '0.75rem 0.5rem', color: '#1d1d1f', fontSize: '0.9rem', fontWeight: 600 }
							}, attributes.ctaSecondaryText + ' \u2192')
						])
					])
				]);
			} else if (attributes.variant === 'split') {
				// SPLIT Preview
				previewContent = el('div', {
					className: 'po-ph__container po-ph__container--split',
					style: { display: 'grid', gridTemplateColumns: '1fr 1.2fr', gap: '2rem', alignItems: 'center', padding: '2rem' }
				}, [
					el('div', { className: 'po-ph__content po-ph__content--split', key: 'content' }, [
						el(RichText, {
							key: 'title',
							tagName: 'h1',
							className: 'po-ph__title po-ph__title--split',
							value: attributes.title || '',
							onChange: function(val) { setAttributes({ title: val }); },
							placeholder: 'Titel eingeben...',
							style: { fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', fontWeight: 700, lineHeight: 1.1, margin: '0 0 0.5rem' }
						}),
						attributes.titleAccent && el('span', {
							key: 'accent',
							style: { display: 'block', fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', fontWeight: 700, color: attributes.accentColor || '#0066cc', marginBottom: '1rem' }
						}, attributes.titleAccent),
						el(RichText, {
							key: 'desc',
							tagName: 'p',
							value: attributes.description || '',
							onChange: function(val) { setAttributes({ description: val }); },
							placeholder: 'Beschreibung eingeben...',
							style: { fontSize: '1rem', color: '#6e6e73', lineHeight: 1.6, marginBottom: '1.5rem' }
						}),
						(attributes.ctaText || attributes.ctaSecondaryText) && el('div', {
							key: 'actions',
							style: { display: 'flex', gap: '1rem', flexWrap: 'wrap' }
						}, [
							attributes.ctaText && el('span', {
								key: 'cta1',
								style: { padding: '0.75rem 1.5rem', background: '#1d1d1f', color: '#fff', borderRadius: '100px', fontSize: '0.9rem', fontWeight: 600 }
							}, attributes.ctaText),
							attributes.ctaSecondaryText && el('span', {
								key: 'cta2',
								style: { padding: '0.75rem 0.5rem', color: '#1d1d1f', fontSize: '0.9rem', fontWeight: 600 }
							}, attributes.ctaSecondaryText + ' \u2192')
						])
					]),
					el('div', { className: 'po-ph__visual po-ph__visual--split', key: 'visual' },
						el('div', {
							className: 'po-ph__image-wrapper po-ph__image-wrapper--split',
							style: { transform: 'rotate(' + (attributes.imageRotation || 3) + 'deg)', maxWidth: '100%' }
						},
							attributes.image
								? el('img', {
									src: attributes.image,
									alt: attributes.imageAlt || '',
									style: { width: '100%', borderRadius: '16px', boxShadow: '0 20px 60px rgba(0,0,0,0.15)' }
								})
								: el('div', {
									style: { background: '#f5f5f7', borderRadius: '16px', padding: '4rem 2rem', textAlign: 'center', color: '#86868b' }
								}, 'Bild ausw\u00e4hlen \u2192')
						)
					)
				]);
			} else if (attributes.variant === 'banner') {
				// BANNER Preview
				previewContent = el('div', {
					style: {
						position: 'relative',
						minHeight: '200px',
						borderRadius: '12px',
						overflow: 'hidden',
						display: 'flex',
						alignItems: 'flex-end'
					}
				}, [
					el('div', {
						key: 'bg',
						style: {
							position: 'absolute',
							inset: 0,
							background: attributes.image ? 'url(' + attributes.image + ') center/cover' : '#2d2d2f'
						}
					}),
					el('div', {
						key: 'overlay',
						style: {
							position: 'absolute',
							inset: 0,
							background: 'linear-gradient(0deg, rgba(0,0,0,' + ((attributes.overlayOpacity || 50) / 100 * 1.4) + ') 0%, rgba(0,0,0,' + ((attributes.overlayOpacity || 50) / 100 * 0.2) + ') 100%)'
						}
					}),
					el('div', {
						key: 'content',
						style: {
							position: 'relative',
							zIndex: 1,
							padding: '2rem',
							width: '100%',
							maxWidth: '600px'
						}
					}, [
						el(RichText, {
							key: 'title',
							tagName: 'h1',
							value: attributes.title || '',
							onChange: function(val) { setAttributes({ title: val }); },
							placeholder: 'Titel eingeben...',
							style: { fontSize: 'clamp(1.5rem, 3vw, 2.25rem)', fontWeight: 700, lineHeight: 1.1, margin: 0, color: '#fff', textShadow: '0 2px 10px rgba(0,0,0,0.3)' }
						}),
						attributes.titleAccent && el('span', {
							key: 'accent',
							style: { display: 'block', fontSize: 'clamp(1.5rem, 3vw, 2.25rem)', fontWeight: 700, color: attributes.accentColor || '#0066cc', textShadow: '0 0 20px rgba(0,102,204,0.3)' }
						}, attributes.titleAccent),
						el(RichText, {
							key: 'desc',
							tagName: 'p',
							value: attributes.description || '',
							onChange: function(val) { setAttributes({ description: val }); },
							placeholder: 'Kurze Beschreibung...',
							style: { fontSize: '0.9rem', color: 'rgba(255,255,255,0.85)', lineHeight: 1.5, margin: '0.5rem 0 0', maxWidth: '450px' }
						}),
						attributes.ctaText && el('span', {
							key: 'cta1',
							style: { display: 'inline-block', marginTop: '0.75rem', padding: '0.5rem 1rem', background: '#fff', color: '#1d1d1f', borderRadius: '100px', fontSize: '0.8rem', fontWeight: 600 }
						}, attributes.ctaText)
					])
				]);
			} else {
				// FULLSCREEN Preview
				previewContent = el('div', {
					style: {
						position: 'relative',
						minHeight: '400px',
						borderRadius: '12px',
						overflow: 'hidden'
					}
				}, [
					el('div', {
						key: 'bg',
						style: {
							position: 'absolute',
							inset: 0,
							background: attributes.image ? 'url(' + attributes.image + ') center/cover' : '#1d1d1f'
						}
					}),
					el('div', {
						key: 'overlay',
						style: {
							position: 'absolute',
							inset: 0,
							background: 'rgba(0,0,0,' + ((attributes.overlayOpacity || 50) / 100) + ')'
						}
					}),
					el('div', {
						key: 'content',
						style: {
							position: 'relative',
							zIndex: 1,
							padding: '4rem 2rem',
							textAlign: 'center',
							display: 'flex',
							flexDirection: 'column',
							alignItems: 'center',
							justifyContent: 'center',
							minHeight: '400px'
						}
					}, [
						el(RichText, {
							key: 'title',
							tagName: 'h1',
							value: attributes.title || '',
							onChange: function(val) { setAttributes({ title: val }); },
							placeholder: 'Titel eingeben...',
							style: { fontSize: 'clamp(2rem, 5vw, 3.5rem)', fontWeight: 700, lineHeight: 1, margin: '0 0 0.5rem', color: '#fff', textShadow: '0 4px 20px rgba(0,0,0,0.3)' }
						}),
						el(RichText, {
							key: 'accent',
							tagName: 'span',
							value: attributes.titleAccent || '',
							onChange: function(val) { setAttributes({ titleAccent: val }); },
							placeholder: 'Akzent-Text...',
							style: {
								fontSize: 'clamp(2rem, 5vw, 3.5rem)',
								fontWeight: 700,
								display: 'block',
								color: attributes.accentColor || '#0066cc',
								textShadow: '0 0 40px ' + (attributes.accentColor || '#0066cc'),
								marginBottom: '1.5rem'
							}
						}),
						el(RichText, {
							key: 'desc',
							tagName: 'p',
							value: attributes.description || '',
							onChange: function(val) { setAttributes({ description: val }); },
							placeholder: 'Beschreibung eingeben...',
							style: { fontSize: '1.1rem', color: 'rgba(255,255,255,0.9)', lineHeight: 1.6, maxWidth: '600px', margin: '0 auto 1.5rem' }
						}),
						(attributes.ctaText || attributes.ctaSecondaryText) && el('div', {
							key: 'actions',
							style: { display: 'flex', gap: '1rem', justifyContent: 'center', flexWrap: 'wrap' }
						}, [
							attributes.ctaText && el('span', {
								key: 'cta1',
								style: { padding: '0.75rem 1.5rem', background: '#fff', color: '#1d1d1f', borderRadius: '100px', fontSize: '0.9rem', fontWeight: 600 }
							}, attributes.ctaText),
							attributes.ctaSecondaryText && el('span', {
								key: 'cta2',
								style: { padding: '0.75rem 0.5rem', color: '#fff', fontSize: '0.9rem', fontWeight: 600 }
							}, attributes.ctaSecondaryText + ' \u2192')
						])
					])
				]);
			}

			return el(Fragment, null, [
				el(InspectorControls, { key: 'controls' }, inspectorPanels),
				el('div', blockProps, previewContent)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
