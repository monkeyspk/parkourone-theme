(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, RangeControl, ToggleControl } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;

	registerBlockType('parkourone/faq', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-faq-editor' });

			// Kategorien dynamisch aus REST API laden
			const [categoryOptions, setCategoryOptions] = useState([
				{ value: '', label: 'Alle Kategorien' }
			]);

			useEffect(function() {
				wp.apiFetch({ path: '/parkourone/v1/faq-categories' }).then(function(categories) {
					var options = [{ value: '', label: 'Alle Kategorien' }];
					categories.forEach(function(cat) {
						options.push({ value: cat.value, label: cat.label });
					});
					setCategoryOptions(options);
				});
			}, []);

			const bgOptions = [
				{ value: 'white', label: 'Weiss' },
				{ value: 'light', label: 'Hellgrau' },
				{ value: 'dark', label: 'Dunkel' }
			];

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'content', title: 'Inhalt', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Überschrift',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(SelectControl, {
							key: 'category',
							label: 'Kategorie filtern',
							value: attributes.category,
							options: categoryOptions,
							onChange: function(v) { setAttributes({ category: v }); }
						}),
						el(RangeControl, {
							key: 'limit',
							label: 'Max. Anzahl FAQs',
							value: attributes.limit,
							min: 0,
							max: 20,
							help: '0 = alle anzeigen',
							onChange: function(v) { setAttributes({ limit: v }); }
						}),
						attributes.category ? el(ToggleControl, {
							key: 'includeGeneral',
							label: 'Allgemeine FAQs einblenden',
							help: 'Zeigt zusätzlich allgemeine FAQs nach den Kategorie-FAQs an.',
							checked: attributes.includeGeneral,
							onChange: function(v) { setAttributes({ includeGeneral: v }); }
						}) : null
					]),
					el(PanelBody, { key: 'design', title: 'Design', initialOpen: false }, [
						el(SelectControl, {
							key: 'bg',
							label: 'Hintergrund',
							value: attributes.backgroundColor,
							options: bgOptions,
							onChange: function(v) { setAttributes({ backgroundColor: v }); }
						})
					])
				]),
				el('div', blockProps, [
					el('div', { key: 'icon', className: 'po-faq-editor__icon' }, '?'),
					el('h3', { key: 'title', className: 'po-faq-editor__title' }, attributes.headline || 'FAQ Accordion'),
					el('p', { key: 'desc', className: 'po-faq-editor__desc' },
						attributes.category
							? 'Zeigt FAQs der Kategorie: ' + attributes.category
							: 'Zeigt alle FAQs als Accordion'
					),
					el('p', { key: 'hint', style: { fontSize: '0.75rem', color: '#999', marginTop: '0.5rem' } }, 'Verwalten unter: FAQs im Admin-Menü')
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
