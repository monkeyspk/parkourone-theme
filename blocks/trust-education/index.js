(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, SelectControl } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/trust-education', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-trust-editor' });

			const bgOptions = [
				{ value: 'white', label: 'Weiss' },
				{ value: 'light', label: 'Hellgrau' },
				{ value: 'dark', label: 'Dunkel' }
			];

			const updateGoal = function(index, key, value) {
				const newGoals = [...attributes.goals];
				newGoals[index] = { ...newGoals[index], [key]: value };
				setAttributes({ goals: newGoals });
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
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
						})
					]),
					el(PanelBody, { key: 'goal1', title: 'Ziel 1: Potentialentfaltung', initialOpen: false }, [
						el(TextControl, {
							key: 'title1',
							label: 'Titel',
							value: attributes.goals[0]?.title || '',
							onChange: function(v) { updateGoal(0, 'title', v); }
						}),
						el(TextareaControl, {
							key: 'text1',
							label: 'Text',
							value: attributes.goals[0]?.text || '',
							onChange: function(v) { updateGoal(0, 'text', v); }
						})
					]),
					el(PanelBody, { key: 'goal2', title: 'Ziel 2: Werthaltung', initialOpen: false }, [
						el(TextControl, {
							key: 'title2',
							label: 'Titel',
							value: attributes.goals[1]?.title || '',
							onChange: function(v) { updateGoal(1, 'title', v); }
						}),
						el(TextareaControl, {
							key: 'text2',
							label: 'Text',
							value: attributes.goals[1]?.text || '',
							onChange: function(v) { updateGoal(1, 'text', v); }
						})
					]),
					el(PanelBody, { key: 'goal3', title: 'Ziel 3: Gesundheitsförderung', initialOpen: false }, [
						el(TextControl, {
							key: 'title3',
							label: 'Titel',
							value: attributes.goals[2]?.title || '',
							onChange: function(v) { updateGoal(2, 'title', v); }
						}),
						el(TextareaControl, {
							key: 'text3',
							label: 'Text',
							value: attributes.goals[2]?.text || '',
							onChange: function(v) { updateGoal(2, 'text', v); }
						})
					]),
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
				el('div', blockProps, [
					el('div', { key: 'icon', className: 'po-trust-editor__icon' }, 'T'),
					el('h3', { key: 'title', className: 'po-trust-editor__title' }, attributes.headline),
					el('p', { key: 'intro', className: 'po-trust-editor__intro' }, attributes.intro.substring(0, 100) + '...'),
					el('div', { key: 'goals', className: 'po-trust-editor__goals' },
						attributes.goals.map(function(goal, i) {
							return el('span', { key: i, className: 'po-trust-editor__goal' }, goal.title);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
