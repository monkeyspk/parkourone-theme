(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;

	registerBlockType('parkourone/stundenplan-detail', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el('div', blockProps,
				el(InspectorControls, {},
					el(PanelBody, { title: 'Einstellungen', initialOpen: true },
						el(TextControl, {
							label: 'Überschrift',
							value: attributes.headline,
							onChange: function(value) {
								setAttributes({ headline: value });
							}
						}),
						el(ToggleControl, {
							label: 'Filter anzeigen',
							checked: attributes.showFilters,
							onChange: function(value) {
								setAttributes({ showFilters: value });
							}
						}),
						el(ToggleControl, {
							label: 'Kompakte Ansicht',
							help: 'Zeigt weniger Details pro Kurs',
							checked: attributes.compactView,
							onChange: function(value) {
								setAttributes({ compactView: value });
							}
						})
					),
					el(PanelBody, { title: 'Vorfilter', initialOpen: false },
						el(TextControl, {
							label: 'Altersgruppe (Slug)',
							help: 'z.B. "kids" um nur Kids-Kurse zu zeigen',
							value: attributes.filterAge,
							onChange: function(value) {
								setAttributes({ filterAge: value });
							}
						}),
						el(TextControl, {
							label: 'Standort (Slug)',
							help: 'z.B. "berlin-mitte"',
							value: attributes.filterLocation,
							onChange: function(value) {
								setAttributes({ filterLocation: value });
							}
						})
					)
				),
				el('div', { className: 'po-stundenplan-detail-editor' },
					el('div', { className: 'po-stundenplan-detail-editor__icon' }, '📅'),
					el('h3', { className: 'po-stundenplan-detail-editor__title' },
						attributes.headline || 'Stundenplan Detail'
					),
					el('p', { className: 'po-stundenplan-detail-editor__desc' },
						'Wochenansicht mit ' +
						(attributes.showFilters ? 'Filtern' : 'ohne Filter') +
						(attributes.compactView ? ' (kompakt)' : '')
					)
				)
			);
		},
		save: function() {
			return null;
		}
	});
})(window.wp);
