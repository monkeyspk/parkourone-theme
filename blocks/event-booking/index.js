(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, Spinner } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;

	registerBlockType('parkourone/event-booking', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-event-booking-editor' });
			const [events, setEvents] = useState([]);
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

				var path = '/wp-json/events/v1/list';
				if (params.length > 0) {
					path += '?' + params.join('&');
				}

				wp.apiFetch({ url: path })
					.then(function(response) {
						setEvents(response.events || []);
						setLoading(false);
					})
					.catch(function(err) {
						console.log('Events laden fehlgeschlagen:', err);
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

			// Gruppiere Events nach Klasse (permalink)
			var grouped = {};
			events.forEach(function(ev) {
				var key = ev.permalink || ev.title;
				if (!grouped[key]) {
					grouped[key] = { title: ev.title, events: [] };
				}
				grouped[key].events.push(ev);
			});
			var klassen = Object.values(grouped);

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'settings', title: 'Einstellungen', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Ueberschrift',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextControl, {
							key: 'btntext',
							label: 'Button Text',
							value: attributes.buttonText,
							onChange: function(v) { setAttributes({ buttonText: v }); }
						})
					]),
					el(PanelBody, { key: 'filters', title: 'Vorfilter (Server-seitig)', initialOpen: true }, [
						el('p', { key: 'hint', style: { fontSize: '12px', color: '#888' } },
							'Diese Filter schraenken die angezeigten Events serverseitig ein. Besucher koennen zusaetzlich im Frontend filtern.'
						),
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
					])
				]),
				el('div', blockProps, [
					attributes.headline
						? el('h2', { key: 'headline', className: 'po-eb__headline' }, attributes.headline)
						: null,
					el('div', { key: 'preview', className: 'po-eb__preview' },
						loading
							? el(Spinner, { key: 'spinner' })
							: (klassen.length === 0
								? el('p', { key: 'empty', style: { color: '#666' } }, 'Keine Events gefunden.')
								: el('div', { key: 'cards', className: 'po-eb__cards-preview' },
									klassen.slice(0, 6).map(function(klasse, i) {
										return el('div', { key: i, className: 'po-eb__card-preview' }, [
											el('strong', { key: 'title' }, klasse.title),
											el('span', { key: 'count', style: { color: '#888', fontSize: '13px' } },
												' (' + klasse.events.length + ' Termine)')
										]);
									})
								)
							)
					),
					klassen.length > 6
						? el('p', { key: 'more', style: { textAlign: 'center', color: '#666', fontSize: '0.875rem' } },
							'+ ' + (klassen.length - 6) + ' weitere Klassen')
						: null
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
