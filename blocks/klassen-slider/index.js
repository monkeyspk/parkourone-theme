(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, ToggleControl, Spinner } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;

	registerBlockType('parkourone/klassen-slider', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-klassen-slider-editor' });
			const [klassen, setKlassen] = useState([]);
			const [loading, setLoading] = useState(true);
			const [filterOptions, setFilterOptions] = useState({
				age: [],
				location: [],
				offer: [],
				weekday: []
			});

			useEffect(function() {
				wp.apiFetch({ path: '/parkourone/v1/event-filters' })
					.then(function(response) {
						setFilterOptions(response);
					})
					.catch(function(err) {
						console.log('Filter laden fehlgeschlagen:', err);
					});
			}, []);

			useEffect(function() {
				setLoading(true);

				var params = [];
				if (attributes.filterAge) params.push('age=' + encodeURIComponent(attributes.filterAge));
				if (attributes.filterLocation) params.push('location=' + encodeURIComponent(attributes.filterLocation));
				if (attributes.filterOffer) params.push('offer=' + encodeURIComponent(attributes.filterOffer));
				if (attributes.filterWeekday) params.push('weekday=' + encodeURIComponent(attributes.filterWeekday));

				var path = '/parkourone/v1/klassen';
				if (params.length > 0) {
					path += '?' + params.join('&');
				}

				wp.apiFetch({ path: path })
					.then(function(response) {
						setKlassen(response || []);
						setLoading(false);
					})
					.catch(function(err) {
						console.log('Klassen laden fehlgeschlagen:', err);
						setLoading(false);
					});
			}, [attributes.filterAge, attributes.filterLocation, attributes.filterOffer, attributes.filterWeekday]);

			var toOptions = function(terms) {
				var opts = [{ label: '-- Alle --', value: '' }];
				if (terms && terms.length) {
					terms.forEach(function(t) {
						opts.push({ label: t.name, value: t.slug });
					});
				}
				return opts;
			};

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'settings', title: 'Einstellungen', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Ueberschrift',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(SelectControl, {
							key: 'filtermode',
							label: 'Filter-Modus',
							help: 'Welche Filter-Dropdowns sollen angezeigt werden?',
							value: attributes.filterMode || 'none',
							options: [
								{ label: 'Keine Filter', value: 'none' },
								{ label: 'Nach Altersgruppe', value: 'age' },
								{ label: 'Nach Standort', value: 'location' },
								{ label: 'Beide Filter', value: 'both' }
							],
							onChange: function(v) { setAttributes({ filterMode: v }); }
						}),
						el(TextControl, {
							key: 'bookingurl',
							label: 'Buchungsseite URL',
							value: attributes.bookingPageUrl,
							onChange: function(v) { setAttributes({ bookingPageUrl: v }); }
						}),
						el(TextControl, {
							key: 'btntext',
							label: 'Button Text',
							value: attributes.buttonText,
							onChange: function(v) { setAttributes({ buttonText: v }); }
						}),
						el(ToggleControl, {
							key: 'hideempty',
							label: 'Ausblenden wenn leer',
							help: 'Slider verstecken wenn keine Kurse gefunden werden',
							checked: attributes.hideIfEmpty,
							onChange: function(v) { setAttributes({ hideIfEmpty: v }); }
						})
					]),
					el(PanelBody, { key: 'filters', title: 'Filter', initialOpen: true }, [
						el(SelectControl, {
							key: 'age',
							label: 'Alter',
							value: attributes.filterAge,
							options: toOptions(filterOptions.age),
							onChange: function(v) { setAttributes({ filterAge: v }); }
						}),
						el(SelectControl, {
							key: 'location',
							label: 'Ortschaft',
							value: attributes.filterLocation,
							options: toOptions(filterOptions.location),
							onChange: function(v) { setAttributes({ filterLocation: v }); }
						}),
						el(SelectControl, {
							key: 'offer',
							label: 'Angebot',
							value: attributes.filterOffer,
							options: toOptions(filterOptions.offer),
							onChange: function(v) { setAttributes({ filterOffer: v }); }
						}),
						el(SelectControl, {
							key: 'weekday',
							label: 'Wochentag',
							value: attributes.filterWeekday,
							options: toOptions(filterOptions.weekday),
							onChange: function(v) { setAttributes({ filterWeekday: v }); }
						})
					]),
					el(PanelBody, { key: 'info', title: 'Bilder-Info', initialOpen: false }, [
						el('p', { key: 'desc', style: { fontSize: '13px', color: '#666' } },
							'Die Bilder werden automatisch aus folgenden Quellen geladen:'
						),
						el('ol', { key: 'list', style: { fontSize: '13px', margin: '10px 0', paddingLeft: '20px' } }, [
							el('li', { key: '1' }, 'Event-spezifisches Bild (Events > Bilder verwalten)'),
							el('li', { key: '2' }, 'WordPress Featured Image'),
							el('li', { key: '3' }, 'Kategorie-Fallback (Design > Kurs-Fallback-Bilder)')
						]),
						el('p', { key: 'hint', style: { fontSize: '12px', color: '#888', fontStyle: 'italic' } },
							'Die Fallback-Bilder stellen sicher, dass keine leeren Karten angezeigt werden.'
						)
					])
				]),
				el('div', blockProps, [
					attributes.headline
						? el('h2', { key: 'headline', className: 'po-klassen-slider__headline' }, attributes.headline)
						: null,
					el('div', { key: 'preview', className: 'po-klassen-slider__preview' },
						loading
							? el(Spinner, { key: 'spinner' })
							: (klassen.length === 0
								? el('p', { key: 'empty', className: 'po-klassen-slider__empty' },
									attributes.hideIfEmpty
										? 'Keine Klassen gefunden (Slider wird ausgeblendet).'
										: 'Keine Klassen gefunden. Wähle Filter oder lasse alle leer für alle Klassen.'
								)
								: el('div', { key: 'cards', className: 'po-klassen-slider__cards' },
									klassen.slice(0, 4).map(function(klasse, i) {
										return el('div', { key: klasse.permalink + i, className: 'po-klassen-card po-klassen-card--preview' }, [
											el('div', {
												key: 'img',
												className: 'po-klassen-card__image',
												style: klasse.image ? { backgroundImage: 'url(' + klasse.image + ')' } : {}
											}, [
												!klasse.image ? el('span', { key: 'noimg', className: 'po-klassen-card__noimg' }, 'Fallback wird geladen...') : null,
												klasse.headcoach_image ? el('div', { key: 'coach', className: 'po-klassen-card__coach' }, [
													el('img', { key: 'coachimg', src: klasse.headcoach_image, className: 'po-klassen-card__coach-img' }),
													el('span', { key: 'coachname', className: 'po-klassen-card__coach-name' }, klasse.headcoach)
												]) : null
											]),
											el('div', { key: 'content', className: 'po-klassen-card__content' }, [
												el('h3', { key: 'title', className: 'po-klassen-card__title' }, klasse.title),
												el('div', { key: 'meta', className: 'po-klassen-card__meta' }, [
													klasse.weekday ? el('span', { key: 'day', className: 'po-klassen-card__day' }, klasse.weekday) : null,
													klasse.start_time ? el('span', { key: 'time', className: 'po-klassen-card__time' }, klasse.start_time) : null
												])
											])
										]);
									})
								)
							)
					),
					klassen.length > 4
						? el('p', { key: 'more', style: { textAlign: 'center', color: '#666', fontSize: '0.875rem' } },
							'+ ' + (klassen.length - 4) + ' weitere Klassen')
						: null
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
