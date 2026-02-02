(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, ToggleControl, Button, ColorPalette } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/feature-cards', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const cards = attributes.cards || [];
			const blockProps = useBlockProps({ 
				className: 'po-feature-cards',
				style: { backgroundColor: attributes.backgroundColor }
			});

			const colors = [
				{ name: 'Weiß', color: '#ffffff' },
				{ name: 'Hellgrau', color: '#f5f5f7' },
				{ name: 'Schwarz', color: '#000000' }
			];

			const updateCard = function(i, key, val) {
				const newCards = [...cards];
				newCards[i] = { ...newCards[i], [key]: val };
				setAttributes({ cards: newCards });
			};
			const addCard = function() { 
				setAttributes({ cards: [...cards, {iconUrl:'', title:'Neu', desc:'Beschreibung'}] }); 
			};
			const removeCard = function(i) { 
				setAttributes({ cards: cards.filter(function(_, idx) { return idx !== i; }) }); 
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Einstellungen' }, [
						el(ToggleControl, {
							key: 'showheadline',
							label: 'Überschrift anzeigen',
							checked: attributes.showHeadline,
							onChange: function(val) { setAttributes({ showHeadline: val }); }
						}),
						el('p', { key: 'colorlabel', style: { marginBottom: '8px' } }, 'Hintergrundfarbe'),
						el(ColorPalette, {
							key: 'bgcolor',
							colors: colors,
							value: attributes.backgroundColor,
							onChange: function(val) { setAttributes({ backgroundColor: val || '#ffffff' }); }
						})
					]),
					el(PanelBody, { title: 'Karten', initialOpen: true }, [
						cards.map(function(c, i) {
							return el('div', { key: i, style: { marginBottom: '1.5rem', paddingBottom: '1.5rem', borderBottom: '1px solid #ddd' } }, [
								el('strong', { key: 'label' + i }, 'Karte ' + (i + 1)),
								el(MediaUploadCheck, { key: 'iconupload' + i },
									el(MediaUpload, {
										onSelect: function(media) { updateCard(i, 'iconUrl', media.url); },
										allowedTypes: ['image'],
										render: function(obj) {
											return el('div', { style: { marginTop: '8px' } }, [
												c.iconUrl && el('img', { 
													key: 'preview',
													src: c.iconUrl, 
													style: { width: '50px', height: '50px', objectFit: 'contain', marginBottom: '8px', display: 'block' } 
												}),
												el(Button, {
													key: 'btn',
													onClick: obj.open,
													variant: 'secondary',
													isSmall: true
												}, c.iconUrl ? 'Icon ändern' : 'Icon wählen')
											]);
										}
									})
								),
								el(TextControl, { 
									key: 'title' + i,
									label: 'Titel', 
									value: c.title, 
									onChange: function(v) { updateCard(i, 'title', v); } 
								}),
								el(TextareaControl, { 
									key: 'desc' + i,
									label: 'Beschreibung', 
									value: c.desc, 
									onChange: function(v) { updateCard(i, 'desc', v); } 
								}),
								el(Button, { 
									key: 'remove' + i,
									isDestructive: true, 
									variant: 'link', 
									onClick: function() { removeCard(i); } 
								}, 'Entfernen')
							]);
						}),
						el(Button, { 
							key: 'add',
							variant: 'secondary', 
							onClick: addCard 
						}, '+ Karte hinzufügen')
					])
				),
				el('div', blockProps, [
					attributes.showHeadline && el(RichText, {
						key: 'headline',
						tagName: 'h2',
						className: 'po-feature-cards__headline',
						value: attributes.headline,
						onChange: function(val) { setAttributes({ headline: val }); },
						placeholder: 'Überschrift...'
					}),
					el('div', { key: 'grid', className: 'po-feature-cards__grid' },
						cards.map(function(c, i) {
							return el('div', { key: i, className: 'po-feature-card' }, [
								c.iconUrl 
									? el('img', { key: 'icon', className: 'po-feature-card__icon', src: c.iconUrl, alt: '' })
									: el('div', { key: 'placeholder', className: 'po-feature-card__icon-placeholder' }, 'Icon'),
								el('h3', { key: 'title', className: 'po-feature-card__title' }, c.title),
								el('p', { key: 'desc', className: 'po-feature-card__desc' }, c.desc)
							]);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
