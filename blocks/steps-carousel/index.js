(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl, TextareaControl, Button } = wp.components;
	const { createElement: el, Fragment } = wp.element;

	registerBlockType('parkourone/steps-carousel', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const { headline, subheadline, steps, backgroundColor, ageCategory } = attributes;

			const blockProps = useBlockProps({
				className: 'po-steps-timeline po-steps-timeline--' + backgroundColor
			});

			const bgOptions = [
				{ label: 'Hell (Grau)', value: 'light' },
				{ label: 'Dunkel', value: 'dark' },
				{ label: 'Weiss', value: 'white' }
			];

			const categoryOptions = [
				{ label: 'Standard (gemischt)', value: 'default' },
				{ label: 'Kids', value: 'kids' },
				{ label: 'Juniors', value: 'juniors' },
				{ label: 'Adults', value: 'adults' }
			];

			function updateStep(index, field, value) {
				const newSteps = steps.slice();
				newSteps[index] = Object.assign({}, newSteps[index], { [field]: value });
				setAttributes({ steps: newSteps });
			}

			function addStep() {
				setAttributes({
					steps: steps.concat([{ title: 'Neuer Schritt', description: 'Beschreibung...', icon: 'check' }])
				});
			}

			function removeStep(index) {
				const newSteps = steps.filter(function(_, i) { return i !== index; });
				setAttributes({ steps: newSteps });
			}

			// Build step editors for sidebar
			var stepEditors = steps.map(function(step, index) {
				return el('div', {
					key: index,
					style: { marginBottom: '20px', padding: '10px', background: '#f0f0f0', borderRadius: '4px' }
				}, [
					el('p', { key: 'label' }, el('strong', null, 'Schritt ' + (index + 1))),
					el(TextControl, {
						key: 'title',
						label: 'Titel',
						value: step.title,
						onChange: function(value) { updateStep(index, 'title', value); }
					}),
					el(TextareaControl, {
						key: 'desc',
						label: 'Beschreibung',
						value: step.description,
						onChange: function(value) { updateStep(index, 'description', value); }
					}),
					el(Button, {
						key: 'remove',
						isDestructive: true,
						isSmall: true,
						onClick: function() { removeStep(index); }
					}, 'Entfernen')
				]);
			});

			// Build step previews
			var stepPreviews = steps.map(function(step, index) {
				return el('div', {
					key: index,
					className: 'po-steps-timeline__step'
				}, [
					el('div', { key: 'num-wrap', className: 'po-steps-timeline__number-wrap' },
						el('span', { className: 'po-steps-timeline__number' }, index + 1)
					),
					el('div', {
						key: 'img',
						className: 'po-steps-timeline__image-wrap',
						style: { background: '#e0e0e0', display: 'flex', alignItems: 'center', justifyContent: 'center' }
					}, el('span', { style: { color: '#999', fontSize: '12px' } }, 'Bild ' + (index + 1))),
					el('h3', { key: 'title', className: 'po-steps-timeline__title' }, step.title),
					el('p', { key: 'desc', className: 'po-steps-timeline__desc' }, step.description)
				]);
			});

			return el(Fragment, null, [
				el(InspectorControls, { key: 'inspector' }, [
					el(PanelBody, { key: 'settings', title: 'Einstellungen' }, [
						el(SelectControl, {
							key: 'bg',
							label: 'Hintergrund',
							value: backgroundColor,
							options: bgOptions,
							onChange: function(value) { setAttributes({ backgroundColor: value }); }
						}),
						el(SelectControl, {
							key: 'cat',
							label: 'Altersgruppe (fuer Bilder)',
							value: ageCategory || 'default',
							options: categoryOptions,
							onChange: function(value) { setAttributes({ ageCategory: value }); },
							help: 'Bestimmt welche Bilder fuer die Schritte angezeigt werden'
						})
					]),
					el(PanelBody, { key: 'steps', title: 'Schritte', initialOpen: true }, [
						stepEditors,
						el(Button, {
							key: 'add',
							isPrimary: true,
							onClick: addStep
						}, 'Schritt hinzufuegen')
					])
				]),
				el('div', blockProps,
					el('div', { className: 'po-steps-timeline__container' }, [
						el('div', { key: 'header', className: 'po-steps-timeline__header' }, [
							el(RichText, {
								key: 'headline',
								tagName: 'h2',
								className: 'po-steps-timeline__headline',
								value: headline,
								onChange: function(value) { setAttributes({ headline: value }); },
								placeholder: 'Ueberschrift...'
							}),
							el(RichText, {
								key: 'sub',
								tagName: 'p',
								className: 'po-steps-timeline__subheadline',
								value: subheadline,
								onChange: function(value) { setAttributes({ subheadline: value }); },
								placeholder: 'Unterzeile (optional)...'
							})
						]),
						el('div', { key: 'track', className: 'po-steps-timeline__track' }, stepPreviews)
					])
				)
			]);
		}
	});
})(window.wp);
