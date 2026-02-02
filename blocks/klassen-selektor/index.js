(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;

	registerBlockType('parkourone/klassen-selektor', {
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
						el(SelectControl, {
							label: 'Gruppieren nach',
							value: attributes.groupBy,
							options: [
								{ label: 'Wochentag', value: 'weekday' },
								{ label: 'Altersgruppe', value: 'age' },
								{ label: 'Standort', value: 'location' }
							],
							onChange: function(value) {
								setAttributes({ groupBy: value });
							}
						}),
						el(ToggleControl, {
							label: 'Buchungspanel anzeigen',
							checked: attributes.showBookingPanel,
							onChange: function(value) {
								setAttributes({ showBookingPanel: value });
							}
						})
					),
					el(PanelBody, { title: 'Filter', initialOpen: false },
						el(TextControl, {
							label: 'Altersgruppe (Slug)',
							help: 'z.B. "kids" oder "adults,juniors"',
							value: attributes.filterAge,
							onChange: function(value) {
								setAttributes({ filterAge: value });
							}
						}),
						el(TextControl, {
							label: 'Standort (Slug)',
							help: 'z.B. "berlin-mitte" oder "bern,basel"',
							value: attributes.filterLocation,
							onChange: function(value) {
								setAttributes({ filterLocation: value });
							}
						})
					)
				),
				el('div', { className: 'po-klassen-selektor-editor' },
					el('div', { className: 'po-klassen-selektor-editor__icon' }, '📋'),
					el('h3', { className: 'po-klassen-selektor-editor__title' },
						attributes.headline || 'Klassen Selektor'
					),
					el('p', { className: 'po-klassen-selektor-editor__desc' },
						'Accordion-Auswahl mit ' +
						(attributes.groupBy === 'weekday' ? 'Wochentagen' :
						 attributes.groupBy === 'age' ? 'Altersgruppen' : 'Standorten') +
						(attributes.showBookingPanel ? ' und Buchungspanel' : '')
					)
				)
			);
		},
		save: function() {
			return null;
		}
	});
})(window.wp);
