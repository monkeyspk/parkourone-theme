(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, RangeControl } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/404-hero', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-404-hero-editor' });

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					// Inhalte Panel
					el(PanelBody, { title: 'Inhalte', initialOpen: true },
						el(TextControl, {
							label: 'Eyebrow',
							value: attributes.eyebrow,
							onChange: function(v) { setAttributes({ eyebrow: v }); }
						}),
						el(TextControl, {
							label: 'Headline',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextControl, {
							label: 'Subtext',
							value: attributes.subtext,
							onChange: function(v) { setAttributes({ subtext: v }); }
						})
					),

					// Buttons Panel
					el(PanelBody, { title: 'Buttons', initialOpen: false },
						el('p', { style: { fontWeight: '600', marginBottom: '8px' } }, 'Primärer Button'),
						el(TextControl, {
							label: 'Text',
							value: attributes.primaryButtonText,
							onChange: function(v) { setAttributes({ primaryButtonText: v }); }
						}),
						el(TextControl, {
							label: 'URL',
							value: attributes.primaryButtonUrl,
							onChange: function(v) { setAttributes({ primaryButtonUrl: v }); },
							help: 'z.B. / für Startseite oder /kontakt/ für Seite'
						}),
						el('hr', { style: { margin: '20px 0' } }),
						el('p', { style: { fontWeight: '600', marginBottom: '8px' } }, 'Sekundärer Button'),
						el(TextControl, {
							label: 'Text',
							value: attributes.secondaryButtonText,
							onChange: function(v) { setAttributes({ secondaryButtonText: v }); }
						}),
						el(TextControl, {
							label: 'URL',
							value: attributes.secondaryButtonUrl,
							onChange: function(v) { setAttributes({ secondaryButtonUrl: v }); },
							help: 'z.B. #stundenplan für Scroll-Anker'
						})
					),

					// Design Panel
					el(PanelBody, { title: 'Design', initialOpen: false },
						el(RangeControl, {
							label: 'Overlay Deckkraft',
							value: attributes.overlayOpacity,
							onChange: function(v) { setAttributes({ overlayOpacity: v }); },
							min: 0,
							max: 90,
							step: 5
						})
					)
				),

				// Preview
				el('div', blockProps,
					el('div', {
						style: {
							background: '#1a1a2e',
							borderRadius: '8px',
							padding: '40px 24px',
							textAlign: 'center',
							color: '#ffffff',
							minHeight: '200px',
							display: 'flex',
							flexDirection: 'column',
							alignItems: 'center',
							justifyContent: 'center',
							gap: '12px'
						}
					},
						attributes.eyebrow && el('span', {
							style: {
								fontSize: '12px',
								textTransform: 'uppercase',
								letterSpacing: '2px',
								opacity: 0.7
							}
						}, attributes.eyebrow),
						el('h1', {
							style: {
								fontSize: '48px',
								fontWeight: '800',
								margin: '0',
								lineHeight: '1'
							}
						}, attributes.headline),
						attributes.subtext && el('p', {
							style: {
								fontSize: '14px',
								opacity: 0.8,
								margin: '0',
								maxWidth: '400px'
							}
						}, attributes.subtext),
						el('div', {
							style: {
								display: 'flex',
								gap: '12px',
								marginTop: '8px'
							}
						},
							attributes.primaryButtonText && el('span', {
								style: {
									background: '#e63946',
									padding: '8px 20px',
									borderRadius: '6px',
									fontSize: '13px',
									fontWeight: '600'
								}
							}, attributes.primaryButtonText),
							attributes.secondaryButtonText && el('span', {
								style: {
									border: '1px solid rgba(255,255,255,0.4)',
									padding: '8px 20px',
									borderRadius: '6px',
									fontSize: '13px',
									fontWeight: '600'
								}
							}, attributes.secondaryButtonText)
						)
					)
				)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
