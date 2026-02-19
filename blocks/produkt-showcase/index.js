(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var Button = wp.components.Button;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;

	registerBlockType('parkourone/produkt-showcase', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'po-produkt-showcase-editor' });

			// Produkt-Liste aus REST API
			var productState = useState([]);
			var products = productState[0];
			var setProducts = productState[1];

			var loadingState = useState(true);
			var loading = loadingState[0];
			var setLoading = loadingState[1];

			// Produkt-Details fuer Preview
			var detailState = useState(null);
			var productDetail = detailState[0];
			var setProductDetail = detailState[1];

			// Produkte laden
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

			// Produkt-Details laden wenn productId sich aendert
			useEffect(function() {
				if (!attributes.productId) {
					setProductDetail(null);
					return;
				}
				wp.apiFetch({ path: '/wp/v2/product/' + attributes.productId })
					.then(function(p) {
						setProductDetail(p);
					})
					.catch(function() {
						setProductDetail(null);
					});
			}, [attributes.productId]);

			// Dropdown-Optionen
			var productOptions = [{ label: loading ? 'Produkte werden geladen...' : '— Produkt waehlen —', value: 0 }];
			productOptions = productOptions.concat(products);

			// Preview-Werte: Attribut-Override > Produktdaten > Fallback
			var previewTitle = attributes.headline
				|| (productDetail ? productDetail.title.rendered : 'Produkt Showcase');
			var previewDesc = attributes.description
				|| (productDetail ? productDetail.excerpt.rendered : '');
			var previewImage = attributes.imageUrl
				|| (productDetail && productDetail.featured_media_src_url ? productDetail.featured_media_src_url : '');
			var previewPrice = productDetail && productDetail.price_html ? productDetail.price_html : '';

			var isDark = attributes.themeVariant === 'dark';
			var isHorizontal = attributes.layout === 'horizontal';
			var isCard = attributes.layout === 'card';

			return el('div', null, [
				// ── Inspector Controls ──
				el(InspectorControls, { key: 'controls' }, [

					// Panel 1: Produkt-Auswahl
					el(PanelBody, { key: 'product', title: 'WooCommerce Produkt', initialOpen: true }, [
						el(SelectControl, {
							key: 'product-select',
							label: 'Produkt',
							help: attributes.productId ? 'Produkt-ID: ' + attributes.productId : 'Waehle das WooCommerce-Produkt.',
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

					// Panel 2: Darstellung
					el(PanelBody, { key: 'display', title: 'Darstellung', initialOpen: false }, [
						el(SelectControl, {
							key: 'theme',
							label: 'Theme',
							value: attributes.themeVariant,
							options: [
								{ label: 'Light', value: 'light' },
								{ label: 'Dark', value: 'dark' }
							],
							onChange: function(v) { setAttributes({ themeVariant: v }); }
						}),
						el(SelectControl, {
							key: 'layout',
							label: 'Layout',
							value: attributes.layout,
							options: [
								{ label: 'Horizontal (Bild links)', value: 'horizontal' },
								{ label: 'Zentriert (gestapelt)', value: 'centered' },
								{ label: 'Card (fuer Grids)', value: 'card' }
							],
							onChange: function(v) { setAttributes({ layout: v }); }
						})
					]),

					// Panel 3: Inhalte ueberschreiben
					el(PanelBody, { key: 'content', title: 'Inhalte ueberschreiben', initialOpen: false }, [
						el(TextControl, {
							key: 'headline',
							label: 'Headline (leer = Produkttitel)',
							value: attributes.headline || '',
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextareaControl, {
							key: 'description',
							label: 'Beschreibung (leer = Produktbeschreibung)',
							value: attributes.description || '',
							onChange: function(v) { setAttributes({ description: v }); }
						}),
						el(TextControl, {
							key: 'cta',
							label: 'Button Text',
							value: attributes.ctaText || '',
							onChange: function(v) { setAttributes({ ctaText: v }); }
						}),
						el(TextControl, {
							key: 'badge',
							label: 'Badge Text (z.B. "Beliebt")',
							help: 'Optional. Wird als Badge auf dem Bild angezeigt.',
							value: attributes.badgeText || '',
							onChange: function(v) { setAttributes({ badgeText: v }); }
						}),
						el('div', { key: 'image-upload', style: { marginBottom: '16px' } }, [
							el('label', { key: 'label', style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Bild (leer = Produktbild)'),
							el(MediaUpload, {
								key: 'upload',
								onSelect: function(media) { setAttributes({ imageUrl: media.url }); },
								allowedTypes: ['image'],
								render: function(obj) {
									return el('div', null, [
										attributes.imageUrl && el('img', {
											key: 'preview',
											src: attributes.imageUrl,
											style: { maxWidth: '200px', borderRadius: '8px', marginBottom: '8px', display: 'block' }
										}),
										el(Button, {
											key: 'btn',
											variant: 'secondary',
											onClick: obj.open
										}, attributes.imageUrl ? 'Bild wechseln' : 'Bild ueberschreiben'),
										attributes.imageUrl && el(Button, {
											key: 'remove',
											variant: 'link',
											isDestructive: true,
											onClick: function() { setAttributes({ imageUrl: '' }); },
											style: { marginLeft: '8px' }
										}, 'Entfernen')
									]);
								}
							})
						])
					])
				]),

				// ── Editor Preview ──
				el('div', blockProps, [
					el('div', {
						key: 'preview',
						style: {
							background: isCard ? (isDark ? '#2d2d2f' : '#ffffff') : (isDark ? '#1d1d1f' : '#f5f5f7'),
							color: isDark ? '#fff' : '#1d1d1f',
							borderRadius: isCard ? '20px' : '16px',
							padding: isCard ? '0' : '40px 32px',
							display: isCard ? 'flex' : (isHorizontal ? 'grid' : 'block'),
							flexDirection: isCard ? 'column' : undefined,
							gridTemplateColumns: isHorizontal && !isCard ? '1fr 1fr' : undefined,
							gap: isCard ? '0' : '32px',
							alignItems: isHorizontal && !isCard ? 'center' : undefined,
							textAlign: !isHorizontal && !isCard ? 'center' : 'left',
							boxShadow: isCard ? '0 4px 24px rgba(0,0,0,0.08)' : undefined,
							overflow: isCard ? 'hidden' : undefined
						}
					}, [
						// Badge
						isCard && attributes.badgeText && el('div', {
							key: 'badge',
							style: {
								position: 'absolute',
								top: '12px',
								right: '12px',
								background: isDark ? '#fff' : '#1d1d1f',
								color: isDark ? '#1d1d1f' : '#fff',
								fontSize: '12px',
								fontWeight: '600',
								padding: '4px 12px',
								borderRadius: '980px',
								zIndex: 1,
								letterSpacing: '0.02em',
								textTransform: 'uppercase'
							}
						}, attributes.badgeText),

						// Warnung: kein Produkt
						!attributes.productId && el('div', {
							key: 'no-product',
							style: {
								gridColumn: '1 / -1',
								background: 'rgba(214, 54, 56, 0.15)',
								border: '1px solid rgba(214, 54, 56, 0.3)',
								borderRadius: '8px',
								padding: '10px 16px',
								margin: isCard ? '16px' : '0 0 20px',
								fontSize: '13px',
								color: isDark ? '#ff8a8a' : '#d63638'
							}
						}, 'Kein Produkt gewaehlt — bitte in den Block-Einstellungen ein WooCommerce-Produkt auswaehlen.'),

						// Bild
						previewImage ? el('div', { key: 'media', style: { position: 'relative' } }, [
							el('img', {
								key: 'img',
								src: previewImage,
								style: {
									width: '100%',
									maxWidth: !isHorizontal && !isCard ? '300px' : '100%',
									maxHeight: isCard ? '180px' : undefined,
									objectFit: isCard ? 'cover' : undefined,
									margin: !isHorizontal && !isCard ? '0 auto 24px' : undefined,
									borderRadius: isCard ? '0' : '16px',
									display: 'block',
									boxShadow: isCard ? 'none' : '0 4px 24px rgba(0,0,0,0.1)'
								}
							})
						]) : null,

						// Inhalt
						el('div', { key: 'content', style: isCard ? { padding: '24px', display: 'flex', flexDirection: 'column', flex: '1' } : undefined }, [
							el('h2', {
								key: 'headline',
								style: {
									fontSize: isCard ? '20px' : '28px',
									fontWeight: '700',
									margin: '0 0 ' + (isCard ? '8px' : '12px'),
									color: isDark ? '#fff' : '#1d1d1f',
									letterSpacing: '-0.02em'
								}
							}, previewTitle),
							previewDesc ? el('div', {
								key: 'desc',
								style: { fontSize: isCard ? '13px' : '14px', color: isDark ? 'rgba(255,255,255,0.55)' : '#6e6e73', marginBottom: isCard ? '12px' : '16px', lineHeight: '1.6' },
								dangerouslySetInnerHTML: { __html: previewDesc }
							}) : null,
							previewPrice ? el('div', {
								key: 'price',
								style: { fontSize: isCard ? '26px' : '32px', fontWeight: '700', marginBottom: isCard ? '16px' : '20px', color: isDark ? '#fff' : '#1d1d1f' },
								dangerouslySetInnerHTML: { __html: previewPrice }
							}) : el('div', {
								key: 'price-placeholder',
								style: { fontSize: isCard ? '26px' : '32px', fontWeight: '700', marginBottom: isCard ? '16px' : '20px', color: isDark ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.2)' }
							}, '--,-- \u20ac'),
							el('div', {
								key: 'cta',
								style: {
									background: isDark ? '#fff' : '#1d1d1f',
									color: isDark ? '#1d1d1f' : '#fff',
									padding: isCard ? '12px 24px' : '14px 36px',
									borderRadius: isCard ? '980px' : '12px',
									display: isCard ? 'block' : 'inline-block',
									fontWeight: '600',
									fontSize: isCard ? '14px' : '15px',
									textAlign: 'center',
									marginTop: isCard ? 'auto' : undefined
								}
							}, attributes.ctaText || 'In den Warenkorb')
						])
					])
				])
			]);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
