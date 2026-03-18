(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, RangeControl, CheckboxControl, Spinner } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;
	const { apiFetch } = wp;

	registerBlockType('parkourone/angebote-grid', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-angebote-grid-editor' });
			const [angebote, setAngebote] = useState([]);
			const [loading, setLoading] = useState(true);
			const [kategorien, setKategorien] = useState([]);

			useEffect(function() {
				apiFetch({ path: '/parkourone/v1/angebote' })
					.then(function(data) {
						setAngebote(data);
						setLoading(false);
					})
					.catch(function() {
						setLoading(false);
					});
				apiFetch({ path: '/wp/v2/angebot_kategorie?per_page=100' })
					.then(function(terms) {
						setKategorien(terms);
					})
					.catch(function() {});
			}, []);

			var filterCats = attributes.filterCategories || [];

			function toggleCategory(slug) {
				var idx = filterCats.indexOf(slug);
				if (idx === -1) {
					setAttributes({ filterCategories: filterCats.concat([slug]) });
				} else {
					setAttributes({ filterCategories: filterCats.filter(function(s) { return s !== slug; }) });
				}
			}

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Einstellungen' }, [
						el(ToggleControl, {
							key: 'filter',
							label: 'Filter anzeigen',
							checked: attributes.showFilter,
							onChange: function(val) { setAttributes({ showFilter: val }); }
						}),
						el(RangeControl, {
							key: 'cols',
							label: 'Spalten (Desktop)',
							value: attributes.columns,
							onChange: function(val) { setAttributes({ columns: val }); },
							min: 2,
							max: 4
						}),
						el('div', { key: 'cat-filter', style: { borderTop: '1px solid #e0e0e0', paddingTop: '1rem', marginTop: '0.5rem' } },
							el('p', { style: { fontWeight: '600', fontSize: '13px', margin: '0 0 0.25rem 0' } }, 'Kategorien filtern'),
							el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 0.75rem 0' } },
								filterCats.length === 0 ? 'Alle Kategorien werden angezeigt' : filterCats.length + ' Kategorie(n) ausgewählt'
							),
							kategorien.length === 0
								? el('p', { style: { color: '#999', fontSize: '13px' } }, 'Lade Kategorien...')
								: el('div', null, kategorien.map(function(kat) {
									return el(CheckboxControl, {
										key: kat.slug,
										label: kat.name,
										checked: filterCats.indexOf(kat.slug) !== -1,
										onChange: function() { toggleCategory(kat.slug); }
									});
								}))
						)
					])
				),
				el('div', blockProps, [
					el('h3', { key: 'title', style: { margin: '0 0 0.5rem 0' } }, 'Angebote Grid'),
					el('p', { key: 'desc', style: { color: '#666', margin: '0 0 1rem 0', fontSize: '14px' } },
						'Zeigt ' + angebote.length + ' Angebote aus dem Backend. Filter: ' + (attributes.showFilter ? 'Ja' : 'Nein') + ', Spalten: ' + attributes.columns
					),
					loading
						? el(Spinner, { key: 'spinner' })
						: el('div', {
							key: 'preview',
							className: 'po-angebote-grid-editor__preview',
							style: { gridTemplateColumns: 'repeat(' + Math.min(attributes.columns, angebote.length || 1) + ', 1fr)' }
						},
							angebote.slice(0, 6).map(function(a, i) {
								return el('div', { key: i, className: 'po-angebote-grid-editor__card' }, [
									el('div', { key: 'title', className: 'po-angebote-grid-editor__card-title' }, a.titel),
									el('div', { key: 'cat', className: 'po-angebote-grid-editor__card-cat' }, a.kategorie?.name || '-')
								]);
							}),
							angebote.length > 6 && el('p', { key: 'more', style: { gridColumn: '1 / -1', textAlign: 'center', color: '#666', fontSize: '14px' } },
								'+ ' + (angebote.length - 6) + ' weitere Angebote'
							)
						),
					angebote.length === 0 && !loading && el('p', { key: 'empty', style: { color: '#666' } },
						'Keine Angebote vorhanden. Erstelle Angebote unter "Angebote" im WordPress Admin.'
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
