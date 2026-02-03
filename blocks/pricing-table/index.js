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

	registerBlockType('parkourone/pricing-table', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'po-pricing-editor' });

			var _useState = useState(0);
			var activeCategory = _useState[0];
			var setActiveCategory = _useState[1];

			var _useState2 = useState(0);
			var activeClass = _useState2[0];
			var setActiveClass = _useState2[1];

			function updateCategory(index, key, value) {
				var newCategories = JSON.parse(JSON.stringify(attributes.categories));
				newCategories[index][key] = value;
				setAttributes({ categories: newCategories });
			}

			function updateClass(catIndex, classIndex, key, value) {
				var newCategories = JSON.parse(JSON.stringify(attributes.categories));
				newCategories[catIndex].classes[classIndex][key] = value;
				setAttributes({ categories: newCategories });
			}

			function addCategory() {
				var newCategories = JSON.parse(JSON.stringify(attributes.categories));
				newCategories.push({
					name: 'Neue Kategorie',
					fromPrice: '0',
					classes: [{ name: 'Klasse', price: '0', details: '' }],
					features: [],
					ctaText: 'Probetraining',
					ctaUrl: '/probetraining/'
				});
				setAttributes({ categories: newCategories });
				setActiveCategory(newCategories.length - 1);
			}

			function removeCategory(index) {
				if (attributes.categories.length <= 1) return;
				var newCategories = attributes.categories.filter(function(_, i) { return i !== index; });
				setAttributes({ categories: newCategories });
				if (activeCategory >= newCategories.length) {
					setActiveCategory(newCategories.length - 1);
				}
			}

			function addClass(catIndex) {
				var newCategories = JSON.parse(JSON.stringify(attributes.categories));
				newCategories[catIndex].classes.push({ name: 'Neue Klasse', price: '0', details: '' });
				setAttributes({ categories: newCategories });
				setActiveClass(newCategories[catIndex].classes.length - 1);
			}

			function removeClass(catIndex, classIndex) {
				if (attributes.categories[catIndex].classes.length <= 1) return;
				var newCategories = JSON.parse(JSON.stringify(attributes.categories));
				newCategories[catIndex].classes = newCategories[catIndex].classes.filter(function(_, i) { return i !== classIndex; });
				setAttributes({ categories: newCategories });
				if (activeClass >= newCategories[catIndex].classes.length) {
					setActiveClass(newCategories[catIndex].classes.length - 1);
				}
			}

			function updateFeatures(catIndex, value) {
				var newCategories = JSON.parse(JSON.stringify(attributes.categories));
				newCategories[catIndex].features = value.split('\n').filter(function(f) { return f.trim(); });
				setAttributes({ categories: newCategories });
			}

			var currentCat = attributes.categories[activeCategory];
			var currentClass = currentCat && currentCat.classes[activeClass];

			return el('div', null, [
				// Inspector Controls
				el(InspectorControls, { key: 'controls' }, [
					// General Settings
					el(PanelBody, { key: 'general', title: 'Allgemein', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Headline',
							value: attributes.headline || '',
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextControl, {
							key: 'subtext',
							label: 'Subtext',
							value: attributes.subtext || '',
							onChange: function(v) { setAttributes({ subtext: v }); }
						}),
						el(SelectControl, {
							key: 'style',
							label: 'Style',
							value: attributes.style,
							options: [
								{ label: 'Light', value: 'light' },
								{ label: 'Dark', value: 'dark' }
							],
							onChange: function(v) { setAttributes({ style: v }); }
						}),
						el(TextControl, {
							key: 'currency',
							label: 'Währung',
							value: attributes.currency || '€',
							onChange: function(v) { setAttributes({ currency: v }); }
						}),
						el(TextControl, {
							key: 'footnote',
							label: 'Fußnote',
							value: attributes.footnote || '',
							onChange: function(v) { setAttributes({ footnote: v }); }
						})
					]),

					// Categories
					el(PanelBody, { key: 'categories', title: 'Kategorien', initialOpen: true }, [
						el('div', {
							key: 'cat-tabs',
							style: { display: 'flex', gap: '4px', marginBottom: '16px', flexWrap: 'wrap' }
						},
							attributes.categories.map(function(cat, i) {
								return el(Button, {
									key: i,
									variant: i === activeCategory ? 'primary' : 'secondary',
									isSmall: true,
									onClick: function() { setActiveCategory(i); setActiveClass(0); }
								}, i + 1);
							}).concat([
								el(Button, {
									key: 'add',
									variant: 'secondary',
									isSmall: true,
									onClick: addCategory
								}, '+')
							])
						),
						currentCat && el('div', { key: 'cat-form' }, [
							el(TextControl, {
								key: 'cat-name',
								label: 'Kategorie Name',
								value: currentCat.name || '',
								onChange: function(v) { updateCategory(activeCategory, 'name', v); }
							}),
							el(TextControl, {
								key: 'cat-price',
								label: 'Ab-Preis',
								value: currentCat.fromPrice || '',
								onChange: function(v) { updateCategory(activeCategory, 'fromPrice', v); }
							}),
							el(ToggleControl, {
								key: 'cat-highlight',
								label: 'Hervorgehoben',
								checked: currentCat.highlighted || false,
								onChange: function(v) { updateCategory(activeCategory, 'highlighted', v); }
							}),
							el(TextControl, {
								key: 'cat-cta-text',
								label: 'CTA Text',
								value: currentCat.ctaText || '',
								onChange: function(v) { updateCategory(activeCategory, 'ctaText', v); }
							}),
							el(TextControl, {
								key: 'cat-cta-url',
								label: 'CTA URL',
								value: currentCat.ctaUrl || '',
								onChange: function(v) { updateCategory(activeCategory, 'ctaUrl', v); }
							}),
							el(TextareaControl, {
								key: 'cat-features',
								label: 'Features (eine pro Zeile)',
								value: (currentCat.features || []).join('\n'),
								onChange: function(v) { updateFeatures(activeCategory, v); }
							}),
							attributes.categories.length > 1 && el(Button, {
								key: 'remove-cat',
								variant: 'link',
								isDestructive: true,
								onClick: function() { removeCategory(activeCategory); }
							}, 'Kategorie löschen')
						])
					]),

					// Classes within category
					currentCat && el(PanelBody, { key: 'classes', title: 'Klassen in ' + currentCat.name, initialOpen: false }, [
						el('div', {
							key: 'class-tabs',
							style: { display: 'flex', gap: '4px', marginBottom: '16px', flexWrap: 'wrap' }
						},
							currentCat.classes.map(function(cls, i) {
								return el(Button, {
									key: i,
									variant: i === activeClass ? 'primary' : 'secondary',
									isSmall: true,
									onClick: function() { setActiveClass(i); }
								}, cls.name || (i + 1));
							}).concat([
								el(Button, {
									key: 'add-class',
									variant: 'secondary',
									isSmall: true,
									onClick: function() { addClass(activeCategory); }
								}, '+')
							])
						),
						currentClass && el('div', { key: 'class-form' }, [
							el(TextControl, {
								key: 'class-name',
								label: 'Klassen Name',
								value: currentClass.name || '',
								onChange: function(v) { updateClass(activeCategory, activeClass, 'name', v); }
							}),
							el(TextControl, {
								key: 'class-price',
								label: 'Preis',
								value: currentClass.price || '',
								onChange: function(v) { updateClass(activeCategory, activeClass, 'price', v); }
							}),
							el(TextControl, {
								key: 'class-details',
								label: 'Details',
								value: currentClass.details || '',
								onChange: function(v) { updateClass(activeCategory, activeClass, 'details', v); }
							}),
							currentCat.classes.length > 1 && el(Button, {
								key: 'remove-class',
								variant: 'link',
								isDestructive: true,
								onClick: function() { removeClass(activeCategory, activeClass); }
							}, 'Klasse löschen')
						])
					])
				]),

				// Editor Preview
				el('div', blockProps, [
					el('h2', {
						key: 'preview-headline',
						style: { fontSize: '24px', fontWeight: '600', textAlign: 'center', marginBottom: '8px' }
					}, attributes.headline || 'Pricing Table'),
					attributes.subtext && el('p', {
						key: 'preview-subtext',
						style: { fontSize: '14px', color: '#86868b', textAlign: 'center', marginBottom: '24px' }
					}, attributes.subtext),
					el('div', { key: 'preview-grid', className: 'po-pricing-editor__preview' },
						attributes.categories.map(function(cat, i) {
							return el('div', {
								key: i,
								className: 'po-pricing-editor__card' + (cat.highlighted ? ' po-pricing-editor__card--highlighted' : '')
							}, [
								el('div', { key: 'title', className: 'po-pricing-editor__title' }, cat.name),
								el('div', { key: 'price', className: 'po-pricing-editor__price' },
									'ab ' + cat.fromPrice + attributes.currency
								),
								el('div', { key: 'period', className: 'po-pricing-editor__period' }, attributes.period)
							]);
						})
					)
				])
			]);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
