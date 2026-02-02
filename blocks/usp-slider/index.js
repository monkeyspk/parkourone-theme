(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, Button, TextControl, TextareaControl } = wp.components;
	const { createElement: el, useState } = wp.element;

	registerBlockType('parkourone/usp-slider', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-usp-editor' });
			const [activeSlide, setActiveSlide] = useState(0);

			function updateSlide(index, key, value) {
				const newSlides = [...attributes.slides];
				newSlides[index] = { ...newSlides[index], [key]: value };
				setAttributes({ slides: newSlides });
			}

			const slide = attributes.slides[activeSlide];

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'main', title: 'Einstellungen', initialOpen: true },
						el(TextControl, {
							label: 'Ueberschrift',
							value: attributes.headline || '',
							onChange: function(v) { setAttributes({ headline: v }); },
							help: 'z.B. "Warum ParkourONE?" oder "Warum Parkour in Berlin?"'
						})
					),
					el(PanelBody, { key: 'slides', title: 'Slides bearbeiten', initialOpen: true }, [
						el('div', { key: 'tabs', style: { display: 'flex', gap: '4px', marginBottom: '16px', flexWrap: 'wrap' } },
							attributes.slides.map(function(s, i) {
								return el(Button, {
									key: i,
									variant: i === activeSlide ? 'primary' : 'secondary',
									isSmall: true,
									onClick: function() { setActiveSlide(i); }
								}, (i + 1));
							})
						),
						slide && el('div', { key: 'form' }, [
							el(TextControl, {
								key: 'eyebrow',
								label: 'Eyebrow',
								value: slide.eyebrow,
								onChange: function(v) { updateSlide(activeSlide, 'eyebrow', v); }
							}),
							el(TextControl, {
								key: 'headline',
								label: 'Headline',
								value: slide.headline,
								onChange: function(v) { updateSlide(activeSlide, 'headline', v); }
							}),
							el(TextareaControl, {
								key: 'text',
								label: 'Kurztext (Card)',
								value: slide.text,
								onChange: function(v) { updateSlide(activeSlide, 'text', v); },
								help: 'Wird nicht mehr auf der Card angezeigt, nur im Modal'
							}),
							el(TextareaControl, {
								key: 'modal',
								label: 'Modal Text (erweitert)',
								value: slide.modalText,
								onChange: function(v) { updateSlide(activeSlide, 'modalText', v); }
							}),
							el(TextControl, {
								key: 'btntext',
								label: 'Button Text',
								value: slide.buttonText,
								onChange: function(v) { updateSlide(activeSlide, 'buttonText', v); }
							}),
							el(TextControl, {
								key: 'btnurl',
								label: 'Button URL',
								value: slide.buttonUrl,
								onChange: function(v) { updateSlide(activeSlide, 'buttonUrl', v); }
							}),
							el('div', { key: 'image', style: { marginTop: '16px' } }, [
								el('label', { key: 'label', style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Bild'),
								el(MediaUploadCheck, { key: 'check' },
									el(MediaUpload, {
										onSelect: function(media) { updateSlide(activeSlide, 'imageUrl', media.url); },
										allowedTypes: ['image'],
										render: function(obj) {
											return el('div', null,
												slide.imageUrl
													? el('div', null, [
														el('img', { key: 'img', src: slide.imageUrl, style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '8px' } }),
														el(Button, { key: 'change', onClick: obj.open, variant: 'secondary', isSmall: true, style: { marginRight: '8px' } }, 'Aendern'),
														el(Button, { key: 'remove', onClick: function() { updateSlide(activeSlide, 'imageUrl', ''); }, variant: 'link', isDestructive: true, isSmall: true }, 'Entfernen')
													])
													: el(Button, { onClick: obj.open, variant: 'secondary' }, 'Bild waehlen')
											);
										}
									})
								)
							])
						])
					])
				]),
				el('div', blockProps, [
					attributes.headline
						? el('h2', { key: 'headline', style: { fontSize: '24px', fontWeight: '600', marginBottom: '16px' } }, attributes.headline)
						: null,
					el('p', { key: 'desc', style: { color: '#86868b', marginBottom: '16px' } }, 'USP Slider - Apple Style Cards'),
					el('div', { key: 'preview', className: 'po-usp-editor__preview' },
						attributes.slides.map(function(s, i) {
							return el('div', { key: i, className: 'po-usp-editor__card' }, [
								s.imageUrl
									? el('div', { key: 'img', className: 'po-usp-editor__card-image' },
										el('img', { src: s.imageUrl })
									)
									: null,
								el('div', { key: 'gradient', className: 'po-usp-editor__card-gradient' }),
								el('div', { key: 'content', className: 'po-usp-editor__card-content' }, [
									el('span', { key: 'eyebrow', className: 'po-usp-editor__eyebrow' }, s.eyebrow),
									el('h4', { key: 'title', className: 'po-usp-editor__title' }, s.headline)
								])
							]);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
