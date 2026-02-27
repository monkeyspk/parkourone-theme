(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;

	registerBlockType('parkourone/personal-training', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'po-pt-editor' });

			var productState = useState([]);
			var products = productState[0];
			var setProducts = productState[1];

			var loadingState = useState(true);
			var loading = loadingState[0];
			var setLoading = loadingState[1];

			// WooCommerce-Produkte laden
			useEffect(function() {
				wp.apiFetch({ path: '/wp/v2/product?per_page=100&status=publish' })
					.then(function(items) {
						var opts = items.map(function(item) {
							return { label: item.title.rendered + ' (ID: ' + item.id + ')', value: item.id };
						});
						setProducts(opts);
						setLoading(false);
					})
					.catch(function() {
						setLoading(false);
					});
			}, []);

			// Paket-Helfer
			function updatePackage(index, key, value) {
				var updated = JSON.parse(JSON.stringify(attributes.packages));
				updated[index][key] = value;
				setAttributes({ packages: updated });
			}

			function addPackage() {
				var updated = JSON.parse(JSON.stringify(attributes.packages));
				updated.push({ title: 'Neues Paket', hours: '1', price: '0', description: '', highlighted: false });
				setAttributes({ packages: updated });
			}

			function removePackage(index) {
				var updated = JSON.parse(JSON.stringify(attributes.packages));
				updated.splice(index, 1);
				setAttributes({ packages: updated });
			}

			// Work-On Optionen Helfer
			function updateWorkOnOption(index, value) {
				var updated = attributes.workOnOptions.slice();
				updated[index] = value;
				setAttributes({ workOnOptions: updated });
			}

			function addWorkOnOption() {
				var updated = attributes.workOnOptions.slice();
				updated.push('Neue Option');
				setAttributes({ workOnOptions: updated });
			}

			function removeWorkOnOption(index) {
				var updated = attributes.workOnOptions.slice();
				updated.splice(index, 1);
				setAttributes({ workOnOptions: updated });
			}

			// Produkt-Dropdown Optionen
			var productOptions = [{ label: loading ? 'Produkte werden geladen...' : '— Produkt waehlen —', value: 0 }];
			productOptions = productOptions.concat(products);

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [

					// Panel: WooCommerce Produkt
					el(PanelBody, { key: 'product', title: 'WooCommerce Produkt', initialOpen: true }, [
						el(SelectControl, {
							key: 'product-select',
							label: 'Training-Produkt',
							help: attributes.productId ? 'Produkt-ID: ' + attributes.productId : 'Waehle das WooCommerce-Produkt fuer das Personal Training.',
							value: attributes.productId || 0,
							options: productOptions,
							onChange: function(v) { setAttributes({ productId: parseInt(v, 10) }); }
						}),
						!attributes.productId && el('div', {
							key: 'product-warning',
							style: {
								background: '#fcf0f1',
								border: '1px solid #d63638',
								borderRadius: '4px',
								padding: '8px 12px',
								fontSize: '12px',
								color: '#d63638'
							}
						}, 'Ohne Produkt funktioniert der Warenkorb-Button nicht.')
					]),

					// Panel: Allgemein
					el(PanelBody, { key: 'general', title: 'Allgemein', initialOpen: false }, [
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
						})
					]),

					// Panel: Pakete
					el(PanelBody, { key: 'packages', title: 'Pakete', initialOpen: false }, [
						attributes.packages.map(function(pkg, i) {
							return el('div', {
								key: 'pkg-' + i,
								style: { marginBottom: '20px', paddingBottom: '16px', borderBottom: '1px solid #e0e0e0' }
							}, [
								el('div', {
									key: 'pkg-header-' + i,
									style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }
								}, [
									el('strong', { key: 'label' }, 'Paket ' + (i + 1)),
									attributes.packages.length > 1 && el(Button, {
										key: 'remove',
										variant: 'link',
										isDestructive: true,
										onClick: function() { removePackage(i); },
										style: { fontSize: '12px' }
									}, 'Entfernen')
								]),
								el(TextControl, {
									key: 'title-' + i,
									label: 'Titel',
									value: pkg.title || '',
									onChange: function(v) { updatePackage(i, 'title', v); }
								}),
								el(TextControl, {
									key: 'hours-' + i,
									label: 'Stunden',
									type: 'number',
									value: pkg.hours || '1',
									onChange: function(v) { updatePackage(i, 'hours', v); }
								}),
								el(TextControl, {
									key: 'price-' + i,
									label: 'Preis (EUR)',
									type: 'number',
									value: pkg.price || '0',
									onChange: function(v) { updatePackage(i, 'price', v); }
								}),
								el(TextareaControl, {
									key: 'desc-' + i,
									label: 'Beschreibung',
									value: pkg.description || '',
									onChange: function(v) { updatePackage(i, 'description', v); }
								}),
								el(ToggleControl, {
									key: 'highlight-' + i,
									label: 'Hervorgehoben',
									checked: !!pkg.highlighted,
									onChange: function(v) { updatePackage(i, 'highlighted', v); }
								})
							]);
						}),
						el(Button, {
							key: 'add-package',
							variant: 'secondary',
							onClick: addPackage,
							style: { marginTop: '8px' }
						}, '+ Paket hinzufuegen')
					]),

					// Panel: Optionen
					el(PanelBody, { key: 'options', title: 'Optionen (Woran arbeiten)', initialOpen: false }, [
						el(TextControl, {
							key: 'work-on-label',
							label: 'Ueberschrift',
							value: attributes.workOnLabel || '',
							onChange: function(v) { setAttributes({ workOnLabel: v }); }
						}),
						el('div', { key: 'options-list', style: { marginTop: '12px' } },
							attributes.workOnOptions.map(function(opt, i) {
								return el('div', {
									key: 'opt-' + i,
									style: { display: 'flex', gap: '6px', alignItems: 'center', marginBottom: '8px' }
								}, [
									el(TextControl, {
										key: 'input-' + i,
										value: opt,
										onChange: function(v) { updateWorkOnOption(i, v); },
										__nextHasNoMarginBottom: true,
										style: { flex: '1', marginBottom: '0' }
									}),
									el(Button, {
										key: 'remove-' + i,
										variant: 'link',
										isDestructive: true,
										onClick: function() { removeWorkOnOption(i); },
										style: { flexShrink: '0' }
									}, '\u00d7')
								]);
							})
						),
						el(Button, {
							key: 'add-option',
							variant: 'secondary',
							onClick: addWorkOnOption,
							style: { marginTop: '4px' }
						}, '+ Option hinzufuegen')
					])
				]),

				// Editor Preview
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
						!attributes.productId && el('div', {
							key: 'no-product',
							style: {
								background: 'rgba(214, 54, 56, 0.15)',
								border: '1px solid rgba(214, 54, 56, 0.3)',
								borderRadius: '8px',
								padding: '10px 16px',
								marginBottom: '20px',
								fontSize: '13px',
								color: '#ff8a8a'
							}
						}, 'Kein Produkt gewaehlt — bitte in den Block-Einstellungen ein WooCommerce-Produkt auswaehlen.'),

						el('h2', {
							key: 'headline',
							style: { fontSize: '28px', fontWeight: '600', margin: '0 0 8px', color: '#fff' }
						}, attributes.headline || 'Personal Training'),

						el('p', {
							key: 'subtext',
							style: { fontSize: '14px', color: 'rgba(255,255,255,0.6)', margin: '0 0 32px' }
						}, attributes.subtext),

						// Package cards preview
						el('div', {
							key: 'cards',
							style: { display: 'flex', gap: '14px', justifyContent: 'center', flexWrap: 'wrap', marginBottom: '32px' }
						},
							attributes.packages.map(function(pkg, i) {
								var isHighlighted = !!pkg.highlighted;
								return el('div', {
									key: 'card-' + i,
									style: {
										background: isHighlighted ? 'rgba(255,255,255,0.1)' : 'rgba(255,255,255,0.06)',
										border: isHighlighted ? '1.5px solid rgba(255,255,255,0.25)' : '1px solid rgba(255,255,255,0.1)',
										borderRadius: '14px',
										padding: '24px 20px',
										flex: '1 1 160px',
										maxWidth: '220px',
										position: 'relative',
										textAlign: 'center'
									}
								}, [
									isHighlighted && el('span', {
										key: 'badge',
										style: {
											position: 'absolute',
											top: '-10px',
											left: '50%',
											transform: 'translateX(-50%)',
											background: '#fff',
											color: '#1d1d1f',
											fontSize: '11px',
											fontWeight: '600',
											padding: '3px 12px',
											borderRadius: '20px',
											textTransform: 'uppercase',
											letterSpacing: '0.03em'
										}
									}, 'Beliebt'),
									el('strong', {
										key: 'title',
										style: { display: 'block', fontSize: '15px', marginBottom: '8px' }
									}, pkg.title || ''),
									el('span', {
										key: 'price',
										style: { display: 'block', fontSize: '28px', fontWeight: '700', marginBottom: '4px' }
									}, (pkg.price || '0') + ' \u20ac'),
									el('span', {
										key: 'hours',
										style: { display: 'block', fontSize: '13px', color: 'rgba(255,255,255,0.5)', marginBottom: '10px' }
									}, (pkg.hours || '1') + ' ' + (parseInt(pkg.hours || '1', 10) === 1 ? 'Stunde' : 'Stunden')),
									el('span', {
										key: 'desc',
										style: { display: 'block', fontSize: '12px', color: 'rgba(255,255,255,0.45)', lineHeight: '1.4' }
									}, pkg.description || '')
								]);
							})
						),

						// Work-on preview
						el('div', {
							key: 'work-on',
							style: {
								maxWidth: '520px',
								margin: '0 auto 24px',
								textAlign: 'left'
							}
						}, [
							el('h3', {
								key: 'label',
								style: { fontSize: '16px', fontWeight: '600', marginBottom: '12px', textAlign: 'center' }
							}, attributes.workOnLabel || ''),
							el('div', {
								key: 'opts',
								style: { display: 'flex', flexWrap: 'wrap', gap: '8px', justifyContent: 'center' }
							},
								attributes.workOnOptions.map(function(opt, i) {
									return el('span', {
										key: 'opt-' + i,
										style: {
											background: 'rgba(255,255,255,0.06)',
											border: '1px solid rgba(255,255,255,0.1)',
											padding: '8px 14px',
											borderRadius: '8px',
											fontSize: '13px',
											color: 'rgba(255,255,255,0.7)'
										}
									}, opt);
								})
							)
						]),

						// CTA preview
						el('div', {
							key: 'cta',
							style: {
								background: '#fff',
								color: '#1d1d1f',
								padding: '14px 40px',
								borderRadius: '10px',
								display: 'inline-block',
								fontWeight: '600',
								fontSize: '15px',
								marginTop: '8px',
								opacity: '0.35'
							}
						}, attributes.ctaText || 'Jetzt buchen')
					])
				])
			]);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
