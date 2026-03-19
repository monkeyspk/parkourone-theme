(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText, MediaUpload, MediaUploadCheck } = wp.blockEditor;
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
				var newSteps = steps.slice();
				newSteps[index] = Object.assign({}, newSteps[index], { [field]: value });
				setAttributes({ steps: newSteps });
			}

			function addStep() {
				setAttributes({
					steps: steps.concat([{ title: 'Neuer Schritt', description: 'Beschreibung...', icon: 'check', imageUrl: '' }])
				});
			}

			function removeStep(index) {
				var newSteps = steps.filter(function(_, i) { return i !== index; });
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
					el('div', { key: 'image', style: { marginTop: '8px' } }, [
						el('label', { key: 'lbl', style: { display: 'block', marginBottom: '4px', fontWeight: '500', fontSize: '11px', textTransform: 'uppercase' } }, 'Custom Bild (optional)'),
						el(MediaUploadCheck, { key: 'check' },
							el(MediaUpload, {
								onSelect: function(media) { updateStep(index, 'imageUrl', media.url); },
								allowedTypes: ['image'],
								render: function(obj) {
									return el('div', null,
										step.imageUrl
											? el('div', null, [
												el('img', { key: 'img', src: step.imageUrl, style: { maxWidth: '100%', borderRadius: '8px', marginBottom: '4px' } }),
												el(Button, { key: 'change', onClick: obj.open, variant: 'secondary', isSmall: true, style: { marginRight: '8px' } }, 'Ändern'),
												el(Button, { key: 'remove', onClick: function() { updateStep(index, 'imageUrl', ''); }, variant: 'link', isDestructive: true, isSmall: true }, 'Entfernen')
											])
											: el(Button, { onClick: obj.open, variant: 'secondary', isSmall: true }, 'Bild wählen')
									);
								}
							})
						),
						!step.imageUrl && el('p', { key: 'help', style: { fontSize: '11px', color: '#757575', margin: '4px 0 0' } }, 'Ohne Custom Bild wird ein Fallback-Bild der Altersgruppe verwendet.')
					]),
					el(Button, {
						key: 'remove',
						isDestructive: true,
						isSmall: true,
						onClick: function() { removeStep(index); },
						style: { marginTop: '8px' }
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
						style: step.imageUrl
							? {}
							: { background: '#e0e0e0', display: 'flex', alignItems: 'center', justifyContent: 'center' }
					}, step.imageUrl
						? el('img', { src: step.imageUrl, className: 'po-steps-timeline__image' })
						: el('span', { style: { color: '#999', fontSize: '12px' } }, 'Fallback')
					),
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
							label: 'Altersgruppe (Fallback-Bilder)',
							value: ageCategory || 'default',
							options: categoryOptions,
							onChange: function(value) { setAttributes({ ageCategory: value }); },
							help: 'Wird nur für Schritte ohne Custom Bild verwendet'
						})
					]),
					el(PanelBody, { key: 'steps', title: 'Schritte (' + steps.length + ')', initialOpen: true }, [
						stepEditors,
						el(Button, {
							key: 'add',
							isPrimary: true,
							onClick: addStep
						}, 'Schritt hinzufügen')
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
								placeholder: 'Überschrift...'
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
