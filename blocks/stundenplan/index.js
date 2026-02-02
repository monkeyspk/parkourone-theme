(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, Spinner } = wp.components;
	const { useState, useEffect } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType('parkourone/stundenplan', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();
			const [filters, setFilters] = useState({ age: [], location: [] });
			const [loading, setLoading] = useState(true);

			useEffect(function() {
				wp.apiFetch({ path: '/parkourone/v1/event-filters' })
					.then(function(data) {
						setFilters(data);
						setLoading(false);
					})
					.catch(function() {
						setLoading(false);
					});
			}, []);

			const ageOptions = [{ label: __('Alle Altersgruppen'), value: '' }];
			filters.age.forEach(function(term) {
				ageOptions.push({ label: term.name, value: term.slug });
			});

			const locationOptions = [{ label: __('Alle Standorte'), value: '' }];
			filters.location.forEach(function(term) {
				locationOptions.push({ label: term.name, value: term.slug });
			});

			return wp.element.createElement('div', blockProps,
				wp.element.createElement(InspectorControls, null,
					wp.element.createElement(PanelBody, { title: __('Einstellungen'), initialOpen: true },
						wp.element.createElement(TextControl, {
							label: __('Überschrift'),
							value: attributes.headline,
							onChange: function(val) { setAttributes({ headline: val }); }
						}),
						wp.element.createElement(SelectControl, {
							label: __('Altersgruppe'),
							value: attributes.filterAge,
							options: ageOptions,
							onChange: function(val) { setAttributes({ filterAge: val }); }
						}),
						wp.element.createElement(SelectControl, {
							label: __('Standort'),
							value: attributes.filterLocation,
							options: locationOptions,
							onChange: function(val) { setAttributes({ filterLocation: val }); }
						}),
						wp.element.createElement(TextControl, {
							label: __('Button-Text'),
							value: attributes.buttonText,
							onChange: function(val) { setAttributes({ buttonText: val }); }
						}),
						wp.element.createElement(SelectControl, {
							label: __('Filter-Darstellung'),
							value: attributes.filterLayout || 'fab',
							options: [
								{ label: __('Floating Button (FAB)'), value: 'fab' },
								{ label: __('Inline Chips (unter Titel)'), value: 'inline' }
							],
							onChange: function(val) { setAttributes({ filterLayout: val }); }
						})
					)
				),
				wp.element.createElement('div', { className: 'po-stundenplan-editor' },
					wp.element.createElement('h3', null, attributes.headline || 'Stundenplan'),
					wp.element.createElement('p', null, 'Stundenplan-Block'),
					loading
						? wp.element.createElement(Spinner, null)
						: wp.element.createElement('div', { className: 'po-stundenplan-preview' },
							wp.element.createElement('div', { className: 'po-stundenplan-preview__grid' },
								['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].map(function(day) {
									return wp.element.createElement('div', { key: day, className: 'po-stundenplan-preview__day' }, day);
								})
							),
							wp.element.createElement('p', { style: { marginTop: '12px', color: '#86868b', fontSize: '13px' } },
								'Filter: ' + (attributes.filterAge || 'Alle') + ' / ' + (attributes.filterLocation || 'Alle')
							)
						)
				)
			);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
