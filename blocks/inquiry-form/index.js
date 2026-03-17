(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var Button = wp.components.Button;
	var el = wp.element.createElement;

	function parseGroups(str) {
		try { return JSON.parse(str || '[]'); } catch(e) { return []; }
	}

	registerBlockType('parkourone/inquiry-form', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var checkboxGroups = parseGroups(attributes.checkboxGroups);
			var blockProps = useBlockProps({
				className: 'po-inquiry po-inquiry--' + attributes.backgroundColor
			});

			function updateGroups(groups) {
				setAttributes({ checkboxGroups: JSON.stringify(groups) });
			}

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Formular-Einstellungen' }, [
						el(TextControl, {
							key: 'headline',
							label: 'Überschrift',
							value: attributes.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextareaControl, {
							key: 'subtext',
							label: 'Beschreibungstext',
							value: attributes.subtext,
							onChange: function(v) { setAttributes({ subtext: v }); }
						}),
						el(SelectControl, {
							key: 'formtype',
							label: 'Formular-Typ',
							value: attributes.formType,
							options: [
								{ label: 'Workshop', value: 'workshop' },
								{ label: 'Schulen', value: 'schulen' },
								{ label: 'Teamevent', value: 'teamevent' }
							],
							onChange: function(v) { setAttributes({ formType: v }); }
						}),
						el(TextControl, {
							key: 'email',
							label: 'Empfänger E-Mail (leer = Admin)',
							value: attributes.recipientEmail,
							onChange: function(v) { setAttributes({ recipientEmail: v }); }
						}),
						el(TextControl, {
							key: 'submit',
							label: 'Button-Text',
							value: attributes.submitText,
							onChange: function(v) { setAttributes({ submitText: v }); }
						}),
						el(SelectControl, {
							key: 'bg',
							label: 'Hintergrund',
							value: attributes.backgroundColor,
							options: [
								{ label: 'Dunkel', value: 'dark' },
								{ label: 'Hell', value: 'light' },
								{ label: 'Weiss', value: 'white' }
							],
							onChange: function(v) { setAttributes({ backgroundColor: v }); }
						})
					]),
					el(PanelBody, { title: 'Optionale Felder', initialOpen: true }, [
						el(ToggleControl, {
							key: 'showloc',
							label: 'Gewünschter Ort',
							checked: attributes.showLocation,
							onChange: function(v) { setAttributes({ showLocation: v }); }
						}),
						el(ToggleControl, {
							key: 'showpart',
							label: 'Anzahl Personen',
							checked: attributes.showParticipants,
							onChange: function(v) { setAttributes({ showParticipants: v }); }
						}),
						el(ToggleControl, {
							key: 'showdates',
							label: 'Gewünschte Daten',
							checked: attributes.showDates,
							onChange: function(v) { setAttributes({ showDates: v }); }
						}),
						el(ToggleControl, {
							key: 'showproj',
							label: 'Länge des Projektes',
							checked: attributes.showProjectLength,
							onChange: function(v) { setAttributes({ showProjectLength: v }); }
						}),
						el(ToggleControl, {
							key: 'showclass',
							label: 'Anzahl Klassen',
							checked: attributes.showClassCount,
							onChange: function(v) { setAttributes({ showClassCount: v }); }
						})
					]),
				el(PanelBody, { title: 'Checkbox-Gruppen', initialOpen: false },
					checkboxGroups.map(function(group, gi) {
						return el('div', { key: 'grp-' + gi, style: { marginBottom: '1.5rem', paddingBottom: '1rem', borderBottom: '1px solid #ddd' } }, [
							el(TextControl, {
								key: 'title-' + gi,
								label: 'Gruppentitel',
								value: group.title,
								onChange: function(v) {
									var g = parseGroups(attributes.checkboxGroups);
									g[gi].title = v;
									updateGroups(g);
								}
							}),
							el(TextareaControl, {
								key: 'opts-' + gi,
								label: 'Optionen (eine pro Zeile)',
								value: (group.options || []).join('\n'),
								onChange: function(v) {
									var g = parseGroups(attributes.checkboxGroups);
									g[gi].options = v.split('\n').filter(function(o) { return o.trim() !== ''; });
									updateGroups(g);
								}
							}),
							el(Button, {
								key: 'rm-' + gi,
								variant: 'secondary',
								isDestructive: true,
								size: 'small',
								onClick: function() {
									var g = parseGroups(attributes.checkboxGroups);
									g.splice(gi, 1);
									updateGroups(g);
								}
							}, 'Gruppe entfernen')
						]);
					}).concat([
						el(Button, {
							key: 'add-group',
							variant: 'primary',
							onClick: function() {
								var g = parseGroups(attributes.checkboxGroups);
								g.push({ title: '', options: [] });
								updateGroups(g);
							}
						}, 'Gruppe hinzufügen')
					])
				)
				),
				el('div', blockProps, [
					el('div', { key: 'inner', className: 'po-inquiry__inner' }, [
						el('h2', { key: 'h', className: 'po-inquiry__headline' }, attributes.headline),
						attributes.subtext && el('p', { key: 'sub', className: 'po-inquiry__subtext' }, attributes.subtext),
						el('div', { key: 'preview', style: { padding: '2rem', textAlign: 'center', opacity: 0.6 } }, [
							el('p', { key: 'info' }, 'Formular-Vorschau (' + attributes.formType + ')'),
							el('p', { key: 'fields', style: { fontSize: '0.875rem' } },
								'Felder: Name, Vorname, Adresse, PLZ/Ort, Telefon, E-Mail' +
								(attributes.showLocation ? ', Ort' : '') +
								(attributes.showParticipants ? ', Teilnehmer' : '') +
								(attributes.showDates ? ', Datum' : '') +
								(attributes.showProjectLength ? ', Projektlänge' : '') +
								(attributes.showClassCount ? ', Klassen' : '') +
								', Nachricht, AGB'
							)
						]),
						checkboxGroups.length > 0 && el('div', { key: 'cb-preview', style: { marginTop: '1rem', textAlign: 'left' } },
							checkboxGroups.map(function(group, gi) {
								return el('div', { key: 'cbg-' + gi, style: { marginBottom: '0.75rem' } }, [
									el('p', { key: 'cbt-' + gi, style: { fontWeight: 'bold', margin: '0 0 0.25rem 0', fontSize: '0.875rem' } }, group.title),
									el('p', { key: 'cbo-' + gi, style: { margin: 0, fontSize: '0.8125rem', opacity: 0.7 } },
										(group.options || []).map(function(o) { return '☐ ' + o; }).join('  ')
									)
								]);
							})
						)
					])
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
