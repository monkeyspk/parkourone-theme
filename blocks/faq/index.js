(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, RangeControl, ToggleControl, Spinner } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;

	registerBlockType('parkourone/faq', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-faqone-editor' });

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

			// FAQs live laden
			const [faqs, setFaqs] = useState([]);
			const [loading, setLoading] = useState(false);

			useEffect(function() {
				setLoading(true);
				var params = '?category=' + encodeURIComponent(attributes.category || '');
				params += '&limit=' + (attributes.limit || 0);
				if (attributes.category) {
					params += '&include_general=' + (attributes.includeGeneral ? 'true' : 'false');
				}

				wp.apiFetch({ path: '/parkourone/v1/faqs' + params }).then(function(data) {
					setFaqs(data || []);
					setLoading(false);
				}).catch(function() {
					setFaqs([]);
					setLoading(false);
				});
			}, [attributes.category, attributes.limit, attributes.includeGeneral]);

			// Kategorie-Label finden
			var categoryLabel = 'Alle Kategorien';
			categoryOptions.forEach(function(opt) {
				if (opt.value === attributes.category) {
					categoryLabel = opt.label;
				}
			});

			const bgOptions = [
				{ value: 'white', label: 'Weiss' },
				{ value: 'light', label: 'Hellgrau' },
				{ value: 'dark', label: 'Dunkel' }
			];

			// Preview-Content zusammenbauen
			var previewContent;

			if (loading) {
				previewContent = el('div', { className: 'po-faqone-editor__loading' },
					el(Spinner),
					'FAQs werden geladen\u2026'
				);
			} else if (faqs.length === 0) {
				previewContent = el('div', { className: 'po-faqone-editor__empty' },
					'Keine FAQs gefunden.'
				);
			} else {
				previewContent = el('div', { className: 'po-faqone-editor__list' },
					faqs.map(function(faq, i) {
						return el('div', { key: i, className: 'po-faqone-editor__item' },
							el('span', { className: 'po-faqone-editor__question' }, faq.question),
							faq.is_general ? el('span', { className: 'po-faqone-editor__badge' }, 'Allgemein') : null
						);
					})
				);
			}

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
					el('h3', { key: 'title', className: 'po-faqone-editor__title' }, attributes.headline || 'FAQ Accordion'),
					el('p', { key: 'info', className: 'po-faqone-editor__info' },
						categoryLabel + (faqs.length > 0 ? ' \u00B7 ' + faqs.length + ' FAQ' + (faqs.length !== 1 ? 's' : '') : '')
					),
					previewContent,
					el('p', { key: 'hint', className: 'po-faqone-editor__hint' }, 'Verwalten unter: FAQs im Admin-Men\u00FC')
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
