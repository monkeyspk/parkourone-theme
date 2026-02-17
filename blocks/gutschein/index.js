(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var Button = wp.components.Button;
	var el = wp.element.createElement;

	registerBlockType('parkourone/gutschein', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'po-gutschein-editor' });

			function updateInspiration(index, key, value) {
				var updated = JSON.parse(JSON.stringify(attributes.inspirations));
				updated[index][key] = value;
				setAttributes({ inspirations: updated });
			}

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'general', title: 'Allgemein', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Headline',
							value: attributes.headline || '',
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextareaControl, {
							key: 'subtext',
							label: 'Subtext',
							value: attributes.subtext || '',
							onChange: function(v) { setAttributes({ subtext: v }); }
						}),
						el(TextControl, {
							key: 'cta',
							label: 'CTA Text',
							value: attributes.ctaText || '',
							onChange: function(v) { setAttributes({ ctaText: v }); }
						}),
						el('div', { key: 'image-upload', style: { marginBottom: '16px' } }, [
							el('label', { key: 'label', style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Gutschein-Bild'),
							el(MediaUpload, {
								key: 'upload',
								onSelect: function(media) { setAttributes({ image: media.url }); },
								allowedTypes: ['image'],
								render: function(obj) {
									return el('div', null, [
										attributes.image && el('img', {
											key: 'preview',
											src: attributes.image,
											style: { maxWidth: '200px', borderRadius: '8px', marginBottom: '8px', display: 'block' }
										}),
										el(Button, {
											key: 'btn',
											variant: 'secondary',
											onClick: obj.open
										}, attributes.image ? 'Bild wechseln' : 'Bild hochladen'),
										attributes.image && el(Button, {
											key: 'remove',
											variant: 'link',
											isDestructive: true,
											onClick: function() { setAttributes({ image: '' }); },
											style: { marginLeft: '8px' }
										}, 'Entfernen')
									]);
								}
							})
						])
					]),
					el(PanelBody, { key: 'inspirations', title: 'Inspirations-Karten', initialOpen: false },
						attributes.inspirations.map(function(item, i) {
							return el('div', {
								key: i,
								style: { marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #e0e0e0' }
							}, [
								el(TextControl, {
									key: 'title-' + i,
									label: 'Titel ' + (i + 1),
									value: item.title || '',
									onChange: function(v) { updateInspiration(i, 'title', v); }
								}),
								el(TextareaControl, {
									key: 'desc-' + i,
									label: 'Beschreibung',
									value: item.description || '',
									onChange: function(v) { updateInspiration(i, 'description', v); }
								})
							]);
						})
					)
				]),

				el('div', blockProps, [
					el('div', {
						key: 'preview',
						style: {
							background: '#1d1d1f',
							color: '#fff',
							borderRadius: '16px',
							padding: '40px 32px',
							textAlign: 'center'
						}
					}, [
						el('h2', {
							key: 'headline',
							style: { fontSize: '28px', fontWeight: '600', margin: '0 0 8px', color: '#fff' }
						}, attributes.headline || 'Gutschein'),
						el('p', {
							key: 'subtext',
							style: { fontSize: '14px', color: 'rgba(255,255,255,0.6)', margin: '0 0 24px' }
						}, attributes.subtext),
						el('div', { key: 'cards', style: { display: 'flex', gap: '12px', justifyContent: 'center', flexWrap: 'wrap' } },
							attributes.inspirations.map(function(item, i) {
								return el('div', {
									key: i,
									style: {
										background: 'rgba(255,255,255,0.06)',
										borderRadius: '12px',
										padding: '20px 16px',
										flex: '1 1 140px',
										maxWidth: '200px'
									}
								}, [
									el('strong', { key: 't', style: { display: 'block', fontSize: '14px', marginBottom: '4px' } }, item.title),
									el('span', { key: 'd', style: { fontSize: '11px', color: 'rgba(255,255,255,0.5)' } },
										(item.description || '').substring(0, 60) + '...'
									)
								]);
							})
						),
						attributes.image && el('img', {
							key: 'img',
							src: attributes.image,
							style: { maxWidth: '180px', margin: '24px auto 0', display: 'block', borderRadius: '12px' }
						}),
						el('div', {
							key: 'amounts',
							style: { display: 'flex', gap: '8px', justifyContent: 'center', margin: '24px 0 16px' }
						}, [
							el('span', { key: '1', style: { background: 'rgba(255,255,255,0.1)', padding: '8px 16px', borderRadius: '20px', fontSize: '13px' } }, '25 \u20ac'),
							el('span', { key: '2', style: { background: '#fff', color: '#1d1d1f', padding: '8px 16px', borderRadius: '20px', fontSize: '13px', fontWeight: '600' } }, '50 \u20ac'),
							el('span', { key: '3', style: { background: 'rgba(255,255,255,0.1)', padding: '8px 16px', borderRadius: '20px', fontSize: '13px' } }, '100 \u20ac')
						]),
						el('div', {
							key: 'cta',
							style: {
								background: '#fff',
								color: '#1d1d1f',
								padding: '12px 32px',
								borderRadius: '10px',
								display: 'inline-block',
								fontWeight: '600',
								fontSize: '15px',
								marginTop: '8px'
							}
						}, attributes.ctaText || 'In den Warenkorb')
					])
				])
			]);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
