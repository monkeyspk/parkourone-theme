(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, Button } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/footer', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const standorte = attributes.standorte || [];
			const blockProps = useBlockProps({ className: 'po-footer' });

			const updateStandort = function(i, key, val) {
				const arr = [...standorte];
				arr[i] = { ...arr[i], [key]: val };
				setAttributes({ standorte: arr });
			};
			
			const addStandort = function() {
				setAttributes({ standorte: [...standorte, {name:'Neuer Standort', url:'#'}] });
			};
			
			const removeStandort = function(i) {
				setAttributes({ standorte: standorte.filter(function(_, idx) { return idx !== i; }) });
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Firma' }, [
						el(TextControl, {
							key: 'company',
							label: 'Firmenname',
							value: attributes.companyName,
							onChange: function(v) { setAttributes({ companyName: v }); }
						}),
						el(TextareaControl, {
							key: 'address',
							label: 'Adresse',
							value: attributes.companyAddress,
							onChange: function(v) { setAttributes({ companyAddress: v }); }
						})
					]),
					el(PanelBody, { title: 'Social Media', initialOpen: false }, [
						el(TextControl, {
							key: 'ig',
							label: 'Instagram URL',
							value: attributes.socialInstagram,
							onChange: function(v) { setAttributes({ socialInstagram: v }); }
						}),
						el(TextControl, {
							key: 'yt',
							label: 'YouTube URL',
							value: attributes.socialYoutube,
							onChange: function(v) { setAttributes({ socialYoutube: v }); }
						}),
						el(TextControl, {
							key: 'pod',
							label: 'Podcast URL',
							value: attributes.socialPodcast,
							onChange: function(v) { setAttributes({ socialPodcast: v }); }
						})
					]),
					el(PanelBody, { title: 'Kontakt', initialOpen: false }, [
						el(TextControl, {
							key: 'phone',
							label: 'Telefon',
							value: attributes.phone,
							onChange: function(v) { setAttributes({ phone: v }); }
						}),
						el(TextControl, {
							key: 'email',
							label: 'E-Mail',
							value: attributes.email,
							onChange: function(v) { setAttributes({ email: v }); }
						}),
						el(TextControl, {
							key: 'formurl',
							label: 'Kontaktformular URL',
							value: attributes.contactFormUrl,
							onChange: function(v) { setAttributes({ contactFormUrl: v }); }
						}),
						el(TextareaControl, {
							key: 'hours',
							label: 'Telefonzeiten',
							value: attributes.phoneHours,
							onChange: function(v) { setAttributes({ phoneHours: v }); }
						})
					]),
					el(PanelBody, { title: 'Standorte', initialOpen: false }, [
						standorte.map(function(s, i) {
							return el('div', { key: i, style: { marginBottom: '1rem', display: 'flex', gap: '8px', alignItems: 'flex-end' } }, [
								el(TextControl, {
									key: 'name' + i,
									label: i === 0 ? 'Name' : '',
									value: s.name,
									onChange: function(v) { updateStandort(i, 'name', v); },
									style: { flex: 1 }
								}),
								el(TextControl, {
									key: 'url' + i,
									label: i === 0 ? 'URL' : '',
									value: s.url,
									onChange: function(v) { updateStandort(i, 'url', v); },
									style: { flex: 1 }
								}),
								el(Button, {
									key: 'remove' + i,
									isDestructive: true,
									isSmall: true,
									onClick: function() { removeStandort(i); }
								}, '×')
							]);
						}),
						el(Button, {
							key: 'add',
							variant: 'secondary',
							onClick: addStandort
						}, '+ Standort')
					]),
					el(PanelBody, { title: 'Newsletter', initialOpen: false }, [
						el(TextControl, {
							key: 'nlhead',
							label: 'Überschrift',
							value: attributes.newsletterHeadline,
							onChange: function(v) { setAttributes({ newsletterHeadline: v }); }
						}),
						el(TextControl, {
							key: 'nltext',
							label: 'Text',
							value: attributes.newsletterText,
							onChange: function(v) { setAttributes({ newsletterText: v }); }
						})
					]),
					el(PanelBody, { title: 'Links & Copyright', initialOpen: false }, [
						el(TextControl, {
							key: 'impr',
							label: 'Impressum URL',
							value: attributes.impressumUrl,
							onChange: function(v) { setAttributes({ impressumUrl: v }); }
						}),
						el(TextControl, {
							key: 'daten',
							label: 'Datenschutz URL',
							value: attributes.datenschutzUrl,
							onChange: function(v) { setAttributes({ datenschutzUrl: v }); }
						}),
						el(TextControl, {
							key: 'cook',
							label: 'Cookies URL',
							value: attributes.cookiesUrl,
							onChange: function(v) { setAttributes({ cookiesUrl: v }); }
						}),
						el(TextControl, {
							key: 'year',
							label: 'Copyright Jahr',
							value: attributes.copyrightYear,
							onChange: function(v) { setAttributes({ copyrightYear: v }); }
						})
					])
				),
				el('footer', blockProps, [
					el('div', { key: 'main', className: 'po-footer__main' }, [
						el('div', { key: 'col1', className: 'po-footer__col' }, [
							el('strong', { key: 'name' }, attributes.companyName),
							el('p', { key: 'addr', style: { whiteSpace: 'pre-line' } }, attributes.companyAddress),
							el('div', { key: 'social' }, 'Follow Us: IG YT Podcast')
						]),
						el('div', { key: 'col2', className: 'po-footer__col' }, [
							el('strong', { key: 'title' }, 'Kontaktiere Uns'),
							el('p', { key: 'phone' }, attributes.phone),
							el('p', { key: 'email' }, attributes.email)
						]),
						el('div', { key: 'col3', className: 'po-footer__col' }, [
							el('strong', { key: 'title' }, 'Standorte'),
							standorte.map(function(s, i) {
								return el('span', { key: i, style: { display: 'block' } }, s.name);
							})
						]),
						el('div', { key: 'col4', className: 'po-footer__col' }, [
							el('strong', { key: 'title' }, attributes.newsletterHeadline),
							el('p', { key: 'text' }, attributes.newsletterText)
						])
					]),
					el('div', { key: 'bottom', className: 'po-footer__bottom' }, [
						el('span', { key: 'logo' }, 'ParkourONE'),
						el('span', { key: 'links' }, 'Impressum | Datenschutz | Cookies'),
						el('span', { key: 'copy' }, '© ' + attributes.copyrightYear + ' ParkourONE')
					])
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
