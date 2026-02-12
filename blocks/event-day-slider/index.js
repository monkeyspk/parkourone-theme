(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, Spinner } = wp.components;
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
						})
					)
				),
				wp.element.createElement('div', { className: 'po-event-day-slider-editor' },
					wp.element.createElement('h3', null, attributes.headline || 'N\u00e4chste Probetrainings'),
					// Mock Wochenansicht
					wp.element.createElement('div', {
						style: {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							gap: '12px',
							marginBottom: '12px'
						}
					},
						wp.element.createElement('span', {
							style: { fontSize: '16px', color: '#86868b' }
						}, '\u2190'),
						wp.element.createElement('span', {
							style: { fontSize: '13px', fontWeight: 600 }
						}, 'Wochenkalender (Mo \u2013 So)'),
						wp.element.createElement('span', {
							style: { fontSize: '16px', color: '#86868b' }
						}, '\u2192')
					),
					wp.element.createElement('div', {
						style: {
							display: 'grid',
							gridTemplateColumns: 'repeat(7, 1fr)',
							gap: '4px',
							marginBottom: '12px'
						}
					},
						['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].map(function(d, i) {
							return wp.element.createElement('div', {
								key: d,
								style: {
									padding: '8px 4px',
									background: '#1d1d1f',
									color: '#fff',
									borderRadius: '8px',
									fontSize: '11px',
									fontWeight: 600,
									textAlign: 'center'
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
