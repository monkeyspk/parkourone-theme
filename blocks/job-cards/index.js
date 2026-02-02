(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, Button, ColorPalette } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/job-cards', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const jobs = attributes.jobs || [];
			const blockProps = useBlockProps({ 
				className: 'po-jobs',
				style: { backgroundColor: attributes.backgroundColor }
			});

			const colors = [
				{ name: 'Weiß', color: '#ffffff' },
				{ name: 'Hellgrau', color: '#f5f5f7' }
			];

			const updateJob = function(i, key, val) {
				const arr = [...jobs];
				arr[i] = { ...arr[i], [key]: val };
				setAttributes({ jobs: arr });
			};
			
			const addJob = function() {
				setAttributes({ jobs: [...jobs, {title:'Neue Stelle', type:'Full-time', desc:'Beschreibung...', ctaText:'Jetzt bewerben', ctaUrl:'#'}] });
			};
			
			const removeJob = function(i) {
				setAttributes({ jobs: jobs.filter(function(_, idx) { return idx !== i; }) });
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Einstellungen' }, [
						el('p', { key: 'colorlabel', style: { marginBottom: '8px' } }, 'Hintergrundfarbe'),
						el(ColorPalette, {
							key: 'bgcolor',
							colors: colors,
							value: attributes.backgroundColor,
							onChange: function(val) { setAttributes({ backgroundColor: val || '#ffffff' }); }
						})
					]),
					el(PanelBody, { title: 'Stellen', initialOpen: true }, [
						jobs.map(function(j, i) {
							return el('div', { key: i, style: { marginBottom: '1.5rem', paddingBottom: '1.5rem', borderBottom: '1px solid #ddd' } }, [
								el('strong', { key: 'label' }, 'Stelle ' + (i + 1)),
								el(TextControl, {
									key: 'title' + i,
									label: 'Titel',
									value: j.title,
									onChange: function(v) { updateJob(i, 'title', v); }
								}),
								el(TextControl, {
									key: 'type' + i,
									label: 'Art (z.B. Full-time)',
									value: j.type,
									onChange: function(v) { updateJob(i, 'type', v); }
								}),
								el(TextareaControl, {
									key: 'desc' + i,
									label: 'Beschreibung',
									value: j.desc,
									onChange: function(v) { updateJob(i, 'desc', v); }
								}),
								el(TextControl, {
									key: 'ctatext' + i,
									label: 'Button Text',
									value: j.ctaText,
									onChange: function(v) { updateJob(i, 'ctaText', v); }
								}),
								el(TextControl, {
									key: 'ctaurl' + i,
									label: 'Button URL',
									value: j.ctaUrl,
									onChange: function(v) { updateJob(i, 'ctaUrl', v); }
								}),
								el(Button, {
									key: 'remove' + i,
									isDestructive: true,
									variant: 'link',
									onClick: function() { removeJob(i); }
								}, 'Entfernen')
							]);
						}),
						el(Button, {
							key: 'add',
							variant: 'secondary',
							onClick: addJob
						}, '+ Stelle hinzufügen')
					])
				),
				el('div', blockProps, [
					el(RichText, {
						key: 'headline',
						tagName: 'h2',
						className: 'po-jobs__headline',
						value: attributes.headline,
						onChange: function(v) { setAttributes({ headline: v }); },
						placeholder: 'Überschrift...'
					}),
					el(RichText, {
						key: 'intro',
						tagName: 'p',
						className: 'po-jobs__intro',
						value: attributes.intro,
						onChange: function(v) { setAttributes({ intro: v }); },
						placeholder: 'Einleitung...'
					}),
					el('div', { key: 'grid', className: 'po-jobs__grid' },
						jobs.map(function(j, i) {
							return el('div', { key: i, className: 'po-job-card' }, [
								el('h3', { key: 'title', className: 'po-job-card__title' }, j.title),
								el('span', { key: 'type', className: 'po-job-card__type' }, j.type),
								el('p', { key: 'desc', className: 'po-job-card__desc' }, j.desc),
								el('span', { key: 'cta', className: 'po-job-card__cta' }, j.ctaText)
							]);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
