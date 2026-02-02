(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, TextControl, Button, ColorPalette } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/schulen-grid', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const schulen = attributes.schulen || [];
			const blockProps = useBlockProps({ 
				className: 'po-schulen',
				style: { backgroundColor: attributes.backgroundColor }
			});

			const colors = [
				{ name: 'Weiß', color: '#ffffff' },
				{ name: 'Hellgrau', color: '#f5f5f7' }
			];

			const updateSchule = function(i, key, val) {
				const arr = [...schulen];
				arr[i] = { ...arr[i], [key]: val };
				setAttributes({ schulen: arr });
			};
			
			const addSchule = function() {
				setAttributes({ schulen: [...schulen, {name:'Neue Stadt', imageUrl:'', url:'#'}] });
			};
			
			const removeSchule = function(i) {
				setAttributes({ schulen: schulen.filter(function(_, idx) { return idx !== i; }) });
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Einstellungen' }, [
						el('p', { key: 'colorlabel', style: { marginBottom: '8px' } }, 'Hintergrundfarbe'),
						el(ColorPalette, {
							key: 'bgcolor',
							colors: colors,
							value: attributes.backgroundColor,
							onChange: function(val) { setAttributes({ backgroundColor: val || '#ffffff' }); }
						})
					]),
					el(PanelBody, { title: 'Standorte', initialOpen: true }, [
						schulen.map(function(s, i) {
							return el('div', { key: i, style: { marginBottom: '1.5rem', paddingBottom: '1.5rem', borderBottom: '1px solid #ddd' } }, [
								el('strong', { key: 'label' }, s.name || 'Standort ' + (i + 1)),
								el(TextControl, {
									key: 'name' + i,
									label: 'Name',
									value: s.name,
									onChange: function(v) { updateSchule(i, 'name', v); }
								}),
								el(TextControl, {
									key: 'url' + i,
									label: 'URL',
									value: s.url,
									onChange: function(v) { updateSchule(i, 'url', v); }
								}),
								el(MediaUploadCheck, { key: 'imgupload' + i },
									el(MediaUpload, {
										onSelect: function(media) { updateSchule(i, 'imageUrl', media.url); },
										allowedTypes: ['image'],
										render: function(obj) {
											return el('div', { style: { marginTop: '8px' } }, [
												s.imageUrl && el('img', {
													key: 'preview',
													src: s.imageUrl,
													style: { width: '100%', height: '80px', objectFit: 'cover', marginBottom: '8px', borderRadius: '4px' }
												}),
												el(Button, {
													key: 'btn',
													onClick: obj.open,
													variant: 'secondary',
													isSmall: true
												}, s.imageUrl ? 'Bild ändern' : 'Bild wählen')
											]);
										}
									})
								),
								el(Button, {
									key: 'remove' + i,
									isDestructive: true,
									variant: 'link',
									onClick: function() { removeSchule(i); },
									style: { marginTop: '8px' }
								}, 'Entfernen')
							]);
						}),
						el(Button, {
							key: 'add',
							variant: 'secondary',
							onClick: addSchule
						}, '+ Standort hinzufügen')
					])
				),
				el('div', blockProps, [
					el(RichText, {
						key: 'headline',
						tagName: 'h2',
						className: 'po-schulen__headline',
						value: attributes.headline,
						onChange: function(v) { setAttributes({ headline: v }); },
						placeholder: 'Überschrift...'
					}),
					el(RichText, {
						key: 'intro',
						tagName: 'p',
						className: 'po-schulen__intro',
						value: attributes.intro,
						onChange: function(v) { setAttributes({ intro: v }); },
						placeholder: 'Einleitung...'
					}),
					el('div', { key: 'grid', className: 'po-schulen__grid' },
						schulen.map(function(s, i) {
							return el('div', { key: i, className: 'po-schule-card' }, [
								el('div', { 
									key: 'img',
									className: 'po-schule-card__image',
									style: s.imageUrl ? { backgroundImage: 'url(' + s.imageUrl + ')' } : {}
								}),
								el('h3', { key: 'name', className: 'po-schule-card__name' }, s.name)
							]);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
