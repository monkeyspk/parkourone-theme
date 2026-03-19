(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, SelectControl, Button } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/trust-education', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-trust-editor' });

			var goals = attributes.goals || [];
			var values = attributes.values || [];
			var methods = attributes.methods || [];

			var bgOptions = [
				{ value: 'white', label: 'Weiss' },
				{ value: 'light', label: 'Hellgrau' },
				{ value: 'dark', label: 'Dunkel' }
			];

			var iconOptions = [
				{ value: 'potential', label: 'Potential' },
				{ value: 'hand', label: 'Hand' },
				{ value: 'health', label: 'Gesundheit' }
			];

			// --- Goal helpers ---
			var updateGoal = function(index, key, value) {
				var newGoals = goals.map(function(g, i) {
					return i === index ? Object.assign({}, g, (function() { var o = {}; o[key] = value; return o; })()) : g;
				});
				setAttributes({ goals: newGoals });
			};

			var addGoal = function() {
				setAttributes({ goals: goals.concat([{ title: 'Neues Ziel', icon: 'potential', text: 'Beschreibung...', fullText: '' }]) });
			};

			var removeGoal = function(index) {
				setAttributes({ goals: goals.filter(function(_, i) { return i !== index; }) });
			};

			// --- Value helpers ---
			var updateValue = function(index, key, value) {
				var newValues = values.map(function(v, i) {
					return i === index ? Object.assign({}, v, (function() { var o = {}; o[key] = value; return o; })()) : v;
				});
				setAttributes({ values: newValues });
			};

			var addValue = function() {
				setAttributes({ values: values.concat([{ finger: 'Finger', value: 'Wert', desc: 'Beschreibung...' }]) });
			};

			var removeValue = function(index) {
				setAttributes({ values: values.filter(function(_, i) { return i !== index; }) });
			};

			// --- Method helpers ---
			var updateMethod = function(index, key, value) {
				var newMethods = methods.map(function(m, i) {
					return i === index ? Object.assign({}, m, (function() { var o = {}; o[key] = value; return o; })()) : m;
				});
				setAttributes({ methods: newMethods });
			};

			var addMethod = function() {
				setAttributes({ methods: methods.concat([{ title: 'Neue Methode', desc: 'Beschreibung...' }]) });
			};

			var removeMethod = function(index) {
				setAttributes({ methods: methods.filter(function(_, i) { return i !== index; }) });
			};

			// --- Build goal panels ---
			var goalPanelChildren = [];
			goals.forEach(function(goal, i) {
				goalPanelChildren.push(
					el('div', { key: 'goal-' + i, style: { marginBottom: '16px', paddingBottom: '16px', borderBottom: '1px solid #ddd' } }, [
						el('strong', { key: 'label-' + i, style: { display: 'block', marginBottom: '8px' } }, 'Ziel ' + (i + 1)),
						el(TextControl, {
							key: 'title-' + i,
							label: 'Titel',
							value: goal.title || '',
							onChange: function(v) { updateGoal(i, 'title', v); }
						}),
						el(TextareaControl, {
							key: 'text-' + i,
							label: 'Kurztext',
							value: goal.text || '',
							onChange: function(v) { updateGoal(i, 'text', v); }
						}),
						el(TextareaControl, {
							key: 'fullText-' + i,
							label: 'Detailtext (Modal)',
							value: goal.fullText || '',
							onChange: function(v) { updateGoal(i, 'fullText', v); }
						}),
						el(SelectControl, {
							key: 'icon-' + i,
							label: 'Icon',
							value: goal.icon || 'potential',
							options: iconOptions,
							onChange: function(v) { updateGoal(i, 'icon', v); }
						}),
						goals.length > 1 ? el(Button, {
							key: 'remove-' + i,
							isDestructive: true,
							variant: 'secondary',
							onClick: function() { removeGoal(i); }
						}, 'Entfernen') : null
					])
				);
			});
			goalPanelChildren.push(
				el(Button, {
					key: 'add-goal',
					variant: 'primary',
					onClick: addGoal,
					style: { marginTop: '8px' }
				}, '+ Bildungsziel hinzufügen')
			);

			// --- Build value panels ---
			var valuePanelChildren = [];
			values.forEach(function(val, i) {
				valuePanelChildren.push(
					el('div', { key: 'value-' + i, style: { marginBottom: '16px', paddingBottom: '16px', borderBottom: '1px solid #ddd' } }, [
						el('strong', { key: 'label-' + i, style: { display: 'block', marginBottom: '8px' } }, 'Wert ' + (i + 1)),
						el(TextControl, {
							key: 'finger-' + i,
							label: 'Finger',
							value: val.finger || '',
							onChange: function(v) { updateValue(i, 'finger', v); }
						}),
						el(TextControl, {
							key: 'value-' + i,
							label: 'Wert',
							value: val.value || '',
							onChange: function(v) { updateValue(i, 'value', v); }
						}),
						el(TextareaControl, {
							key: 'desc-' + i,
							label: 'Beschreibung',
							value: val.desc || '',
							onChange: function(v) { updateValue(i, 'desc', v); }
						}),
						values.length > 1 ? el(Button, {
							key: 'remove-' + i,
							isDestructive: true,
							variant: 'secondary',
							onClick: function() { removeValue(i); }
						}, 'Entfernen') : null
					])
				);
			});
			valuePanelChildren.push(
				el(Button, {
					key: 'add-value',
					variant: 'primary',
					onClick: addValue,
					style: { marginTop: '8px' }
				}, '+ Wert hinzufügen')
			);

			// --- Build method panels ---
			var methodPanelChildren = [];
			methods.forEach(function(method, i) {
				methodPanelChildren.push(
					el('div', { key: 'method-' + i, style: { marginBottom: '16px', paddingBottom: '16px', borderBottom: '1px solid #ddd' } }, [
						el('strong', { key: 'label-' + i, style: { display: 'block', marginBottom: '8px' } }, 'Methode ' + (i + 1)),
						el(TextControl, {
							key: 'title-' + i,
							label: 'Titel',
							value: method.title || '',
							onChange: function(v) { updateMethod(i, 'title', v); }
						}),
						el(TextareaControl, {
							key: 'desc-' + i,
							label: 'Beschreibung',
							value: method.desc || '',
							onChange: function(v) { updateMethod(i, 'desc', v); }
						}),
						methods.length > 1 ? el(Button, {
							key: 'remove-' + i,
							isDestructive: true,
							variant: 'secondary',
							onClick: function() { removeMethod(i); }
						}, 'Entfernen') : null
					])
				);
			});
			methodPanelChildren.push(
				el(Button, {
					key: 'add-method',
					variant: 'primary',
					onClick: addMethod,
					style: { marginTop: '8px' }
				}, '+ Methode hinzufügen')
			);

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					// Panel 1: Inhalt
					el(PanelBody, { key: 'content', title: 'Inhalt', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Überschrift',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextareaControl, {
							key: 'intro',
							label: 'Einführungstext',
							value: attributes.intro,
							onChange: function(v) { setAttributes({ intro: v }); }
						}),
						el(TextControl, {
							key: 'goalsHeadline',
							label: 'Bildungsziele Überschrift',
							value: attributes.goalsHeadline,
							onChange: function(v) { setAttributes({ goalsHeadline: v }); }
						}),
						el(TextControl, {
							key: 'ctaText',
							label: 'CTA Text',
							value: attributes.ctaText || '',
							onChange: function(v) { setAttributes({ ctaText: v }); }
						})
					]),

					// Panel 2: Bildungsziele
					el(PanelBody, { key: 'goals', title: 'Bildungsziele (' + goals.length + ')', initialOpen: false },
						goalPanelChildren
					),

					// Panel 3: Vision
					el(PanelBody, { key: 'vision', title: 'Vision', initialOpen: false }, [
						el(TextControl, {
							key: 'visionHeadline',
							label: 'Vision Überschrift',
							value: attributes.visionHeadline || '',
							onChange: function(v) { setAttributes({ visionHeadline: v }); }
						}),
						el(TextareaControl, {
							key: 'visionText',
							label: 'Vision Text',
							value: attributes.visionText || '',
							onChange: function(v) { setAttributes({ visionText: v }); }
						})
					]),

					// Panel 4: Wertehand
					el(PanelBody, { key: 'values', title: 'Wertehand (' + values.length + ')', initialOpen: false },
						valuePanelChildren
					),

					// Panel 5: Methodik
					el(PanelBody, { key: 'methods', title: 'Methodik (' + methods.length + ')', initialOpen: false },
						methodPanelChildren
					),

					// Panel 6: Design
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

				// Editor Preview
				el('div', blockProps, [
					el('div', { key: 'icon', className: 'po-trust-editor__icon' }, 'T'),
					el('h3', { key: 'title', className: 'po-trust-editor__title' }, attributes.headline),
					el('p', { key: 'intro', className: 'po-trust-editor__intro' },
						(attributes.intro || '').substring(0, 100) + '...'
					),
					el('div', { key: 'goals', className: 'po-trust-editor__goals' },
						goals.map(function(goal, i) {
							return el('span', { key: i, className: 'po-trust-editor__goal' }, goal.title);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
