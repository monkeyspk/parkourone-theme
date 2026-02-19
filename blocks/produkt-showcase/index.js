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

	var EMPTY_PRODUCT = {
		productId: 0,
		headline: '',
		description: '',
		imageUrl: '',
		ctaText: 'In den Warenkorb',
		badgeText: ''
	};

	registerBlockType('parkourone/produkt-showcase', {
		// ── Deprecated: migrate old single-product format ──
		deprecated: [
			{
				attributes: {
					productId:    { type: 'number',  default: 0 },
					headline:     { type: 'string',  default: '' },
					description:  { type: 'string',  default: '' },
					imageUrl:     { type: 'string',  default: '' },
					ctaText:      { type: 'string',  default: 'In den Warenkorb' },
					themeVariant: { type: 'string',  default: 'light' },
					layout:       { type: 'string',  default: 'horizontal' },
					badgeText:    { type: 'string',  default: '' }
				},
				save: function() { return null; },
				migrate: function(oldAttrs) {
					var product = {
						productId:   oldAttrs.productId || 0,
						headline:    oldAttrs.headline || '',
						description: oldAttrs.description || '',
						imageUrl:    oldAttrs.imageUrl || '',
						ctaText:     oldAttrs.ctaText || 'In den Warenkorb',
						badgeText:   oldAttrs.badgeText || ''
					};
					return {
						products: product.productId ? [product] : [],
						themeVariant: oldAttrs.themeVariant || 'light'
					};
				}
			}
		],

		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'po-produkt-showcase-editor' });

			var products = attributes.products || [];

			// Active product tab
			var activeState = useState(0);
			var activeIdx = activeState[0];
			var setActiveIdx = activeState[1];

			// WC product list from REST
			var productListState = useState([]);
			var productList = productListState[0];
			var setProductList = productListState[1];

			var loadingState = useState(true);
			var loading = loadingState[0];
			var setLoading = loadingState[1];

			// Product details cache { [id]: detail }
			var detailsState = useState({});
			var productDetails = detailsState[0];
			var setProductDetails = detailsState[1];

			// Load WC products once
			useEffect(function() {
				wp.apiFetch({ path: '/wp/v2/product?per_page=100&status=publish' })
					.then(function(items) {
						var opts = items.map(function(item) {
							return { label: item.title.rendered + ' (ID: ' + item.id + ')', value: item.id };
						});
						setProductList(opts);
						setLoading(false);
					})
					.catch(function() { setLoading(false); });
			}, []);

			// Load details for each product in array
			useEffect(function() {
				products.forEach(function(p) {
					if (p.productId && !productDetails[p.productId]) {
						wp.apiFetch({ path: '/wp/v2/product/' + p.productId })
							.then(function(detail) {
								setProductDetails(function(prev) {
									var next = {};
									for (var k in prev) next[k] = prev[k];
									next[detail.id] = detail;
									return next;
								});
							});
					}
				});
			}, [JSON.stringify(products.map(function(p) { return p.productId; }))]);

			// Helpers
			function updateProduct(index, key, value) {
				var newProducts = JSON.parse(JSON.stringify(products));
				newProducts[index][key] = value;
				setAttributes({ products: newProducts });
			}

			function addProduct() {
				if (products.length >= 3) return;
				var newProducts = JSON.parse(JSON.stringify(products));
				newProducts.push(JSON.parse(JSON.stringify(EMPTY_PRODUCT)));
				setAttributes({ products: newProducts });
				setActiveIdx(newProducts.length - 1);
			}

			function removeProduct(index) {
				var newProducts = products.filter(function(_, i) { return i !== index; });
				setAttributes({ products: newProducts });
				if (activeIdx >= newProducts.length) {
					setActiveIdx(Math.max(0, newProducts.length - 1));
				}
			}

			// Dropdown options
			var productOptions = [{ label: loading ? 'Produkte werden geladen...' : '— Produkt waehlen —', value: 0 }];
			productOptions = productOptions.concat(productList);

			// Current active product
			var activeProduct = products[activeIdx] || null;
			var activeDetail = activeProduct && activeProduct.productId ? productDetails[activeProduct.productId] : null;

			// Layout info
			var productCount = products.length;
			var layoutLabel = productCount === 0 ? 'Keine Produkte'
				: productCount === 1 ? 'Auto: Horizontal (Bild links)'
				: productCount === 2 ? 'Auto: 2-Spalten Grid'
				: 'Auto: 3-Spalten Grid';

			var isDark = attributes.themeVariant === 'dark';

			// ── Preview helpers ──
			function getPreviewData(p) {
				var detail = p.productId ? productDetails[p.productId] : null;
				return {
					title: p.headline || (detail ? detail.title.rendered : 'Produkt'),
					desc: p.description || (detail ? detail.excerpt.rendered : ''),
					image: p.imageUrl || (detail && detail.featured_media_src_url ? detail.featured_media_src_url : ''),
					price: detail && detail.price_html ? detail.price_html : '',
					cta: p.ctaText || 'In den Warenkorb',
					badge: p.badgeText || '',
					hasProduct: !!p.productId
				};
			}

			// ── Card preview element ──
			function renderCardPreview(p, index) {
				var d = getPreviewData(p);
				return el('div', {
					key: 'card-' + index,
					style: {
						background: isDark ? '#2d2d2f' : '#ffffff',
						borderRadius: '20px',
						overflow: 'hidden',
						display: 'flex',
						flexDirection: 'column',
						boxShadow: '0 4px 24px rgba(0,0,0,0.08)',
						border: index === activeIdx ? '2px solid #0066cc' : '2px solid transparent',
						cursor: 'pointer',
						transition: 'border-color 0.2s ease'
					},
					onClick: function() { setActiveIdx(index); }
				}, [
					// Badge
					d.badge && el('div', {
						key: 'badge',
						style: {
							position: 'absolute', top: '12px', right: '12px', zIndex: 1,
							background: isDark ? '#fff' : '#1d1d1f', color: isDark ? '#1d1d1f' : '#fff',
							fontSize: '11px', fontWeight: '600', padding: '3px 10px', borderRadius: '980px',
							textTransform: 'uppercase', letterSpacing: '0.02em'
						}
					}, d.badge),
					// Image
					d.image ? el('img', {
						key: 'img',
						src: d.image,
						style: { width: '100%', height: '140px', objectFit: 'cover', display: 'block' }
					}) : el('div', {
						key: 'img-placeholder',
						style: { width: '100%', height: '80px', background: isDark ? '#3d3d3f' : '#f0f0f0' }
					}),
					// Content
					el('div', { key: 'content', style: { padding: '16px', display: 'flex', flexDirection: 'column', flex: 1 } }, [
						el('h3', {
							key: 'title',
							style: { fontSize: '16px', fontWeight: '700', margin: '0 0 6px', color: isDark ? '#fff' : '#1d1d1f', letterSpacing: '-0.02em' }
						}, d.title),
						d.price ? el('div', {
							key: 'price',
							style: { fontSize: '22px', fontWeight: '700', marginBottom: '12px', color: isDark ? '#fff' : '#1d1d1f' },
							dangerouslySetInnerHTML: { __html: d.price }
						}) : el('div', {
							key: 'price-ph',
							style: { fontSize: '22px', fontWeight: '700', marginBottom: '12px', color: isDark ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.2)' }
						}, '--,-- \u20ac'),
						el('div', {
							key: 'cta',
							style: {
								marginTop: 'auto', background: isDark ? '#fff' : '#1d1d1f', color: isDark ? '#1d1d1f' : '#fff',
								padding: '10px 16px', borderRadius: '980px', textAlign: 'center', fontSize: '13px', fontWeight: '600'
							}
						}, d.cta)
					])
				]);
			}

			// ── Horizontal (single) preview element ──
			function renderHorizontalPreview(p) {
				var d = getPreviewData(p);
				return el('div', {
					key: 'horizontal',
					style: {
						display: 'grid', gridTemplateColumns: d.image ? '1fr 1fr' : '1fr',
						gap: '32px', alignItems: 'center',
						background: isDark ? '#1d1d1f' : '#f5f5f7', borderRadius: '16px', padding: '40px 32px'
					}
				}, [
					d.image && el('div', { key: 'media', style: { position: 'relative' } }, [
						d.badge && el('div', {
							key: 'badge',
							style: {
								position: 'absolute', top: '12px', right: '12px', zIndex: 1,
								background: isDark ? '#fff' : '#1d1d1f', color: isDark ? '#1d1d1f' : '#fff',
								fontSize: '12px', fontWeight: '600', padding: '4px 12px', borderRadius: '980px',
								textTransform: 'uppercase', letterSpacing: '0.02em'
							}
						}, d.badge),
						el('img', {
							key: 'img', src: d.image,
							style: { width: '100%', borderRadius: '16px', display: 'block', boxShadow: '0 4px 24px rgba(0,0,0,0.1)' }
						})
					]),
					el('div', { key: 'content' }, [
						!d.hasProduct && el('div', {
							key: 'warn',
							style: {
								background: 'rgba(214,54,56,0.15)', border: '1px solid rgba(214,54,56,0.3)',
								borderRadius: '8px', padding: '10px 16px', marginBottom: '16px', fontSize: '13px',
								color: isDark ? '#ff8a8a' : '#d63638'
							}
						}, 'Kein Produkt gewaehlt.'),
						el('h2', {
							key: 'title',
							style: { fontSize: '28px', fontWeight: '700', margin: '0 0 12px', color: isDark ? '#fff' : '#1d1d1f', letterSpacing: '-0.02em' }
						}, d.title),
						d.desc ? el('div', {
							key: 'desc',
							style: { fontSize: '14px', color: isDark ? 'rgba(255,255,255,0.55)' : '#6e6e73', marginBottom: '16px', lineHeight: '1.6' },
							dangerouslySetInnerHTML: { __html: d.desc }
						}) : null,
						d.price ? el('div', {
							key: 'price',
							style: { fontSize: '32px', fontWeight: '700', marginBottom: '20px', color: isDark ? '#fff' : '#1d1d1f' },
							dangerouslySetInnerHTML: { __html: d.price }
						}) : el('div', {
							key: 'price-ph',
							style: { fontSize: '32px', fontWeight: '700', marginBottom: '20px', color: isDark ? 'rgba(255,255,255,0.3)' : 'rgba(0,0,0,0.2)' }
						}, '--,-- \u20ac'),
						el('div', {
							key: 'cta',
							style: {
								background: isDark ? '#fff' : '#1d1d1f', color: isDark ? '#1d1d1f' : '#fff',
								padding: '14px 36px', borderRadius: '12px', display: 'inline-block', fontWeight: '600', fontSize: '15px'
							}
						}, d.cta)
					])
				]);
			}

			return el('div', null, [
				// ── Inspector Controls ──
				el(InspectorControls, { key: 'controls' }, [

					// Panel 1: Produkte
					el(PanelBody, { key: 'products-panel', title: 'Produkte (' + productCount + '/3)', initialOpen: true }, [
						// Tab bar
						el('div', { key: 'tabs', style: { display: 'flex', gap: '8px', marginBottom: '16px', flexWrap: 'wrap' } },
							products.map(function(p, i) {
								return el(Button, {
									key: 'tab-' + i,
									variant: i === activeIdx ? 'primary' : 'secondary',
									onClick: function() { setActiveIdx(i); },
									style: { minWidth: '40px' }
								}, String(i + 1));
							}).concat([
								products.length < 3 && el(Button, {
									key: 'add',
									variant: 'secondary',
									onClick: addProduct,
									style: { minWidth: '40px' }
								}, '+')
							])
						),

						// Empty state
						products.length === 0 && el('div', {
							key: 'empty',
							style: { padding: '16px', textAlign: 'center', color: '#6e6e73', fontSize: '13px' }
						}, 'Klicke "+" um ein Produkt hinzuzufuegen.'),

						// Active product controls
						activeProduct && el('div', { key: 'active-controls' }, [
							el(SelectControl, {
								key: 'product-select',
								label: 'Produkt',
								help: activeProduct.productId ? 'Produkt-ID: ' + activeProduct.productId : 'Waehle ein WooCommerce-Produkt.',
								value: activeProduct.productId || 0,
								options: productOptions,
								onChange: function(v) { updateProduct(activeIdx, 'productId', parseInt(v, 10)); }
							}),
							!activeProduct.productId && el('div', {
								key: 'warn',
								style: { background: '#fcf0f1', border: '1px solid #d63638', borderRadius: '4px', padding: '8px 12px', fontSize: '12px', color: '#d63638', marginBottom: '12px' }
							}, 'Ohne Produkt funktioniert der Warenkorb-Button nicht.'),
							products.length > 1 && el(Button, {
								key: 'remove',
								variant: 'link',
								isDestructive: true,
								onClick: function() { removeProduct(activeIdx); },
								style: { marginTop: '8px' }
							}, 'Produkt entfernen')
						])
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
						el('div', {
							key: 'layout-info',
							style: { padding: '8px 0', fontSize: '13px', color: '#6e6e73' }
						}, 'Layout: ' + layoutLabel)
					]),

					// Panel 3: Inhalte ueberschreiben (for active product)
					activeProduct && el(PanelBody, { key: 'content', title: 'Inhalte ueberschreiben (Produkt ' + (activeIdx + 1) + ')', initialOpen: false }, [
						el(TextControl, {
							key: 'headline',
							label: 'Headline (leer = Produkttitel)',
							value: activeProduct.headline || '',
							onChange: function(v) { updateProduct(activeIdx, 'headline', v); }
						}),
						el(TextareaControl, {
							key: 'description',
							label: 'Beschreibung (leer = Produktbeschreibung)',
							value: activeProduct.description || '',
							onChange: function(v) { updateProduct(activeIdx, 'description', v); }
						}),
						el(TextControl, {
							key: 'cta',
							label: 'Button Text',
							value: activeProduct.ctaText || '',
							onChange: function(v) { updateProduct(activeIdx, 'ctaText', v); }
						}),
						el(TextControl, {
							key: 'badge',
							label: 'Badge Text (z.B. "Beliebt")',
							help: 'Optional. Wird als Badge auf dem Bild angezeigt.',
							value: activeProduct.badgeText || '',
							onChange: function(v) { updateProduct(activeIdx, 'badgeText', v); }
						}),
						el('div', { key: 'image-upload', style: { marginBottom: '16px' } }, [
							el('label', { key: 'label', style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Bild (leer = Produktbild)'),
							el(MediaUpload, {
								key: 'upload',
								onSelect: function(media) { updateProduct(activeIdx, 'imageUrl', media.url); },
								allowedTypes: ['image'],
								render: function(obj) {
									return el('div', null, [
										activeProduct.imageUrl && el('img', {
											key: 'preview',
											src: activeProduct.imageUrl,
											style: { maxWidth: '200px', borderRadius: '8px', marginBottom: '8px', display: 'block' }
										}),
										el(Button, {
											key: 'btn',
											variant: 'secondary',
											onClick: obj.open
										}, activeProduct.imageUrl ? 'Bild wechseln' : 'Bild ueberschreiben'),
										activeProduct.imageUrl && el(Button, {
											key: 'remove',
											variant: 'link',
											isDestructive: true,
											onClick: function() { updateProduct(activeIdx, 'imageUrl', ''); },
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
					// Empty state
					products.length === 0 && el('div', {
						key: 'empty',
						style: {
							background: isDark ? '#1d1d1f' : '#f5f5f7', color: isDark ? 'rgba(255,255,255,0.5)' : '#6e6e73',
							borderRadius: '16px', padding: '60px 32px', textAlign: 'center', fontSize: '15px'
						}
					}, 'Klicke "+" in den Block-Einstellungen um Produkte hinzuzufuegen.'),

					// Single product: horizontal
					products.length === 1 && renderHorizontalPreview(products[0]),

					// Multi product: grid
					products.length > 1 && el('div', {
						key: 'grid',
						style: {
							display: 'grid',
							gridTemplateColumns: 'repeat(' + products.length + ', 1fr)',
							gap: '20px'
						}
					}, products.map(function(p, i) { return renderCardPreview(p, i); }))
				])
			]);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
