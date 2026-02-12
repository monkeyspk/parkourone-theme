(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, RangeControl, Spinner } = wp.components;
	const { useState, useEffect } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType('parkourone/event-day-slider', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();
			const [loading, setLoading] = useState(true);
			const [eventCount, setEventCount] = useState(0);

			useEffect(function() {
				wp.apiFetch({ url: '/wp-json/events/v1/list?per_page=5' })
					.then(function(data) {
						setEventCount(data.total || (data.events || []).length);
						setLoading(false);
					})
					.catch(function() { setLoading(false); });
			}, []);

			return wp.element.createElement('div', blockProps,
				wp.element.createElement(InspectorControls, null,
					wp.element.createElement(PanelBody, { title: __('Einstellungen'), initialOpen: true },
						wp.element.createElement(TextControl, {
							label: __('\u00dcberschrift'),
							value: attributes.headline,
							onChange: function(val) { setAttributes({ headline: val }); }
						}),
						wp.element.createElement(TextControl, {
							label: __('Button-Text'),
							value: attributes.buttonText,
							onChange: function(val) { setAttributes({ buttonText: val }); }
						}),
						wp.element.createElement(RangeControl, {
							label: __('Anfangstage'),
							value: attributes.initialDays,
							onChange: function(val) { setAttributes({ initialDays: val }); },
							min: 3,
							max: 30,
							step: 1
						})
					)
				),
				wp.element.createElement('div', { className: 'po-event-day-slider-editor' },
					wp.element.createElement('h3', null, attributes.headline || 'N\u00e4chste Probetrainings'),
					// Mock Date Tabs
					wp.element.createElement('div', {
						style: {
							display: 'flex',
							gap: '6px',
							marginBottom: '12px',
							overflowX: 'auto'
						}
					},
						['Heute', 'Morgen', '\u00dcbermorgen', 'Do, 15.', 'Fr, 16.', 'Sa, 17.', 'So, 18.', 'Mo, 19.'].slice(0, attributes.initialDays).map(function(d, i) {
							return wp.element.createElement('span', {
								key: d,
								style: {
									padding: '6px 14px',
									background: i === 0 ? '#1d1d1f' : '#e5e5ea',
									color: i === 0 ? '#fff' : '#1d1d1f',
									borderRadius: '8px',
									fontSize: '12px',
									fontWeight: 600,
									whiteSpace: 'nowrap',
									flexShrink: 0
								}
							}, d);
						})
					),
					loading
						? wp.element.createElement(Spinner, null)
						: wp.element.createElement('p', {
							style: { color: '#86868b', fontSize: '13px' }
						}, eventCount + ' Events verf\u00fcgbar. Vorschau nur im Frontend sichtbar.')
				)
			);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
