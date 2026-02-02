(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck, RichText } = wp.blockEditor;
	const { PanelBody, ToggleControl, TextControl, Button, ColorPalette, SelectControl } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/about-section', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-about' + (attributes.mediaRight ? ' po-about--media-right' : ''),
				style: { backgroundColor: attributes.backgroundColor }
			});

			const colors = [
				{ name: 'Weiß', color: '#ffffff' },
				{ name: 'Hellgrau', color: '#f5f5f7' },
				{ name: 'Schwarz', color: '#000000' }
			];

			const renderMedia = function() {
				if (attributes.mediaType === 'video') {
					if (attributes.videoUrl) {
						return el('div', { className: 'po-about__video-placeholder', style: { position: 'relative' } }, [
							el('svg', {
								key: 'icon',
								xmlns: 'http://www.w3.org/2000/svg',
								width: '48',
								height: '48',
								viewBox: '0 0 24 24',
								fill: 'none',
								stroke: 'currentColor',
								strokeWidth: '1.5'
							}, [
								el('circle', { key: 'c', cx: '12', cy: '12', r: '10' }),
								el('polygon', { key: 'p', points: '10 8 16 12 10 16 10 8', fill: 'currentColor' })
							]),
							el('span', { key: 'text', style: { display: 'block', marginTop: '0.5rem', fontSize: '0.75rem' } }, 'Video: ' + attributes.videoUrl.substring(0, 30) + '...')
						]);
					}
					return el('div', { className: 'po-about__video-placeholder' },
						el('svg', {
							xmlns: 'http://www.w3.org/2000/svg',
							width: '64',
							height: '64',
							viewBox: '0 0 24 24',
							fill: 'none',
							stroke: 'currentColor',
							strokeWidth: '1.5'
						}, [
							el('circle', { key: 'c', cx: '12', cy: '12', r: '10' }),
							el('polygon', { key: 'p', points: '10 8 16 12 10 16 10 8', fill: 'currentColor' })
						])
					);
				} else {
					// Image
					return el(MediaUploadCheck, null,
						el(MediaUpload, {
							onSelect: function(media) { setAttributes({ imageUrl: media.url }); },
							allowedTypes: ['image'],
							render: function(obj) {
								return attributes.imageUrl
									? el('div', { style: { position: 'relative' } }, [
										el('img', {
											key: 'img',
											src: attributes.imageUrl,
											className: 'po-about__image'
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
										style: { padding: '3rem', border: '2px dashed #ccc', width: '100%', aspectRatio: '16/9', display: 'flex', alignItems: 'center', justifyContent: 'center' }
									}, 'Bild auswählen');
							}
						})
					);
				}
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Medien' }, [
						el(SelectControl, {
							key: 'mediatype',
							label: 'Medientyp',
							value: attributes.mediaType,
							options: [
								{ label: 'Video', value: 'video' },
								{ label: 'Bild', value: 'image' }
							],
							onChange: function(val) { setAttributes({ mediaType: val }); }
						}),
						attributes.mediaType === 'video' && el(SelectControl, {
							key: 'videotype',
							label: 'Video-Typ',
							value: attributes.videoType,
							options: [
								{ label: 'YouTube / Vimeo (Embed)', value: 'embed' },
								{ label: 'Selbst gehostet', value: 'self' }
							],
							onChange: function(val) { setAttributes({ videoType: val }); }
						}),
						attributes.mediaType === 'video' && el(TextControl, {
							key: 'videourl',
							label: 'Video URL',
							value: attributes.videoUrl,
							onChange: function(val) { setAttributes({ videoUrl: val }); },
							placeholder: attributes.videoType === 'embed' ? 'https://youtube.com/watch?v=...' : 'https://example.com/video.mp4'
						})
					]),
					el(PanelBody, { title: 'Layout', initialOpen: false }, [
						el(ToggleControl, {
							key: 'mediapos',
							label: 'Media rechts',
							checked: attributes.mediaRight,
							onChange: function(val) { setAttributes({ mediaRight: val }); }
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
						el(TextControl, {
							key: 'ctatext',
							label: 'Button Text',
							value: attributes.ctaText,
							onChange: function(val) { setAttributes({ ctaText: val }); }
						}),
						el(TextControl, {
							key: 'ctaurl',
							label: 'Button URL',
							value: attributes.ctaUrl,
							onChange: function(val) { setAttributes({ ctaUrl: val }); }
						})
					])
				),
				el('div', blockProps, [
					el('div', { key: 'text', className: 'po-about__text' }, [
						el(RichText, {
							key: 'subheadline',
							tagName: 'span',
							className: 'po-about__subheadline',
							value: attributes.subheadline,
							onChange: function(val) { setAttributes({ subheadline: val }); },
							placeholder: 'ABOUT'
						}),
						el(RichText, {
							key: 'headline',
							tagName: 'h2',
							className: 'po-about__headline',
							value: attributes.headline,
							onChange: function(val) { setAttributes({ headline: val }); },
							placeholder: 'parkourONE'
						}),
						el(RichText, {
							key: 'content',
							tagName: 'div',
							className: 'po-about__content',
							value: attributes.text,
							onChange: function(val) { setAttributes({ text: val }); },
							placeholder: 'Text eingeben...',
							multiline: 'p'
						}),
						attributes.ctaText && el('span', {
							key: 'cta',
							className: 'po-about__cta'
						}, attributes.ctaText)
					]),
					el('div', { key: 'media', className: 'po-about__media' },
						renderMedia()
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
