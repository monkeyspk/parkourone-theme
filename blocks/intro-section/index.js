(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var Button = wp.components.Button;

	registerBlockType('parkourone/intro-section', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var updateBenefit = function(index, value) {
				var newBenefits = attributes.benefits.slice();
				newBenefits[index] = value;
				setAttributes({ benefits: newBenefits });
			};

			var addBenefit = function() {
				var newBenefits = attributes.benefits.slice();
				newBenefits.push('Neuer Vorteil');
				setAttributes({ benefits: newBenefits });
			};

			var removeBenefit = function(index) {
				var newBenefits = attributes.benefits.slice();
				newBenefits.splice(index, 1);
				setAttributes({ benefits: newBenefits });
			};

			return el(Fragment, {},
				el(InspectorControls, {},
					el(PanelBody, { title: 'Inhalt', initialOpen: true },
						el(TextControl, {
							label: 'Headline',
							value: attributes.headline,
							onChange: function(value) {
								setAttributes({ headline: value });
							}
						}),
						el(TextareaControl, {
							label: 'Text',
							value: attributes.text,
							rows: 3,
							onChange: function(value) {
								setAttributes({ text: value });
							}
						})
					),
					el(PanelBody, { title: 'Benefits', initialOpen: true },
						el(ToggleControl, {
							label: 'Benefits anzeigen',
							checked: attributes.showBenefits,
							onChange: function(value) {
								setAttributes({ showBenefits: value });
							}
						}),
						attributes.showBenefits && el(TextControl, {
							label: 'Benefits Überschrift',
							value: attributes.benefitsHeadline,
							onChange: function(value) {
								setAttributes({ benefitsHeadline: value });
							}
						}),
						attributes.showBenefits && el('div', { style: { marginTop: '16px' } },
							el('label', {
								style: {
									display: 'block',
									marginBottom: '8px',
									fontWeight: '500'
								}
							}, 'Benefits'),
							attributes.benefits.map(function(benefit, index) {
								return el('div', {
									key: index,
									style: {
										display: 'flex',
										gap: '8px',
										marginBottom: '8px',
										alignItems: 'center'
									}
								},
									el(TextControl, {
										value: benefit,
										onChange: function(value) {
											updateBenefit(index, value);
										},
										style: { flex: 1, marginBottom: 0 }
									}),
									el(Button, {
										isDestructive: true,
										isSmall: true,
										icon: 'no-alt',
										onClick: function() {
											removeBenefit(index);
										}
									})
								);
							}),
							el(Button, {
								isSecondary: true,
								isSmall: true,
								icon: 'plus-alt2',
								onClick: addBenefit,
								style: { marginTop: '8px' }
							}, 'Benefit hinzufügen')
						)
					),
					el(PanelBody, { title: 'Layout & Design', initialOpen: false },
						el(SelectControl, {
							label: 'Layout',
							value: attributes.layout,
							options: [
								{ label: 'Standard (links)', value: 'default' },
								{ label: 'Zentriert', value: 'centered' },
								{ label: 'Zweispaltig', value: 'split' }
							],
							onChange: function(value) {
								setAttributes({ layout: value });
							}
						}),
						el(SelectControl, {
							label: 'Hintergrund',
							value: attributes.backgroundColor,
							options: [
								{ label: 'Weiss', value: 'white' },
								{ label: 'Hellgrau', value: 'light' },
								{ label: 'Gradient', value: 'gradient' }
							],
							onChange: function(value) {
								setAttributes({ backgroundColor: value });
							}
						})
					)
				),
				el('div', blockProps,
					el('div', { className: 'po-intro-section-editor' },
						el('h2', { className: 'po-intro-section-editor__headline' },
							attributes.headline || 'Headline hier...'
						),
						el('p', { className: 'po-intro-section-editor__text' },
							attributes.text || 'Beschreibungstext hier...'
						),
						attributes.showBenefits && attributes.benefits.length > 0 && el('div', { className: 'po-intro-section-editor__benefits' },
							el('strong', { style: { marginBottom: '8px', display: 'block' } }, attributes.benefitsHeadline),
							attributes.benefits.map(function(benefit, index) {
								return el('div', {
									key: index,
									className: 'po-intro-section-editor__benefit'
								},
									el('span', { className: 'po-intro-section-editor__benefit-icon' }, '✓'),
									el('span', {}, benefit)
								);
							})
						),
						el('div', { className: 'po-intro-section-editor__meta' },
							'Layout: ' +
							(attributes.layout === 'default' ? 'Standard' :
							 attributes.layout === 'centered' ? 'Zentriert' : 'Zweispaltig') +
							' • Hintergrund: ' +
							(attributes.backgroundColor === 'white' ? 'Weiss' :
							 attributes.backgroundColor === 'light' ? 'Hellgrau' : 'Gradient')
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
