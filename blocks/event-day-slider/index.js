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
					// Mock Liste
					wp.element.createElement('div', {
						style: {
							display: 'flex',
							flexDirection: 'column',
							gap: '6px',
							maxWidth: '500px',
							margin: '12px auto'
						}
					},
						[1, 2, 3].map(function(i) {
							return wp.element.createElement('div', {
								key: i,
								style: {
									display: 'flex',
									alignItems: 'center',
									gap: '12px',
									padding: '12px 16px',
									background: '#1d1d1f',
									borderRadius: '10px'
								}
							},
								wp.element.createElement('div', {
									style: {
										width: '36px',
										height: '36px',
										borderRadius: '50%',
										background: 'rgba(255,255,255,0.1)',
										flexShrink: 0
									}
								}),
								wp.element.createElement('div', {
									style: { flex: 1 }
								},
									wp.element.createElement('div', {
										style: { fontSize: '10px', color: 'rgba(255,255,255,0.4)', marginBottom: '2px' }
									}, 'Mo. 10. Feb.'),
									wp.element.createElement('div', {
										style: { fontSize: '13px', color: '#34c759', fontWeight: 600 }
									}, 'Training ' + i)
								),
								wp.element.createElement('span', {
									style: { fontSize: '14px', color: 'rgba(255,255,255,0.3)' }
								}, '\u203A')
							);
						})
					),
					loading
						? wp.element.createElement(Spinner, null)
						: wp.element.createElement('p', {
							style: { color: '#86868b', fontSize: '13px', textAlign: 'center', marginTop: '12px' }
						}, eventCount + ' Events verf\u00fcgbar. Vorschau nur im Frontend sichtbar.')
				)
			);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
