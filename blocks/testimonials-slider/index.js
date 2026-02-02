(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl, SelectControl, RangeControl } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;

	const pageTypes = [
		{ label: 'Automatisch (gemischt)', value: '' },
		{ label: 'Kids-Seite', value: 'kids' },
		{ label: 'Juniors-Seite', value: 'juniors' },
		{ label: 'Adults-Seite', value: 'adults' },
		{ label: 'Standort-Seite', value: 'standort' },
		{ label: 'Startseite', value: 'startseite' },
		{ label: 'Workshops/Kurse', value: 'workshops' }
	];

	const ageGroups = [
		{ label: 'Automatisch', value: '' },
		{ label: 'Kids', value: 'kids' },
		{ label: 'Juniors', value: 'juniors' },
		{ label: 'Adults', value: 'adults' }
	];

	registerBlockType('parkourone/testimonials-slider', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-testimonials-editor' });

			// Beschreibung basierend auf Einstellungen
			var description = 'Zeigt ';
			if (attributes.pageType) {
				description += attributes.limit + ' Testimonials passend zur ' + attributes.pageType + '-Seite';
			} else if (attributes.filterAgeGroup) {
				description += attributes.limit + ' Testimonials für ' + attributes.filterAgeGroup;
			} else {
				description += attributes.limit + ' zufällig gemischte Testimonials';
			}

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'content', title: 'Inhalt', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Überschrift',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(SelectControl, {
							key: 'pageType',
							label: 'Seitentyp (intelligent)',
							value: attributes.pageType,
							options: pageTypes,
							onChange: function(v) { setAttributes({ pageType: v, filterAgeGroup: '' }); },
							help: 'Lädt passende Testimonials wie bei FAQs'
						}),
						el(SelectControl, {
							key: 'ageGroup',
							label: 'Oder: Altersgruppe manuell',
							value: attributes.filterAgeGroup,
							options: ageGroups,
							onChange: function(v) { setAttributes({ filterAgeGroup: v, pageType: '' }); },
							help: 'Überschreibt den Seitentyp'
						}),
						el(RangeControl, {
							key: 'limit',
							label: 'Anzahl Testimonials',
							value: attributes.limit,
							onChange: function(v) { setAttributes({ limit: v }); },
							min: 2,
							max: 6,
							help: 'Empfohlen: 3 (+ Google Card = 4 total)'
						})
					]),
					el(PanelBody, { key: 'display', title: 'Anzeige', initialOpen: false }, [
						el(ToggleControl, {
							key: 'stars',
							label: 'Sterne anzeigen',
							checked: attributes.showStars,
							onChange: function(v) { setAttributes({ showStars: v }); }
						}),
						el(ToggleControl, {
							key: 'googleCard',
							label: 'Google Reviews Card anzeigen',
							checked: attributes.showGoogleCard,
							onChange: function(v) { setAttributes({ showGoogleCard: v }); },
							help: 'Card mit Link zu Google Reviews am Ende'
						})
					]),
					el(PanelBody, { key: 'google', title: 'Google Reviews Link', initialOpen: false }, [
						el(TextControl, {
							key: 'googleUrl',
							label: 'Google Reviews URL (optional)',
							value: attributes.googleReviewsUrl,
							onChange: function(v) { setAttributes({ googleReviewsUrl: v }); },
							help: 'Leer lassen = verwendet Einstellung aus dem Customizer'
						}),
						el('p', {
							key: 'customizer-hint',
							style: { fontSize: '12px', color: '#666', marginTop: '8px' }
						}, 'Globaler Link: Design → Customizer → Google Reviews')
					])
				]),
				el('div', blockProps, [
					el('div', { key: 'icon', className: 'po-testimonials-editor__icon' }, '💬'),
					el('h3', { key: 'title', className: 'po-testimonials-editor__title' }, attributes.headline || 'Testimonials Slider'),
					el('p', { key: 'desc', className: 'po-testimonials-editor__desc' }, description),
					attributes.showGoogleCard && el('p', {
						key: 'google-hint',
						style: { fontSize: '0.75rem', color: '#34A853', marginTop: '0.5rem' }
					}, '+ Google Reviews Card am Ende'),
					el('p', { key: 'hint', style: { fontSize: '0.75rem', color: '#999', marginTop: '0.5rem' } },
						'Testimonials verwalten: Admin → Testimonials'
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
