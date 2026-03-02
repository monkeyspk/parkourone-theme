(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var el = wp.element.createElement;

	var defaults = {
		verletzungen: {
			headline: 'Rückerstattung bei Verletzung',
			description: 'Du hast dich verletzt und kannst mindestens 30 Tage nicht trainieren? Reiche hier deinen Antrag auf Rückerstattung ein.',
			buttonText: 'Antrag einreichen'
		},
		ahv: {
			headline: 'AHV-Nummer melden',
			description: 'Für das J+S Programm benötigen wir deine AHV-Nummer. Melde sie hier sicher und unkompliziert.',
			buttonText: 'AHV-Nummer senden'
		}
	};

	registerBlockType('parkourone/member-form', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var formType = attributes.formType || 'verletzungen';
			var d = defaults[formType] || defaults.verletzungen;

			var blockProps = useBlockProps({
				className: 'po-memberform-editor po-memberform--bg-' + (attributes.backgroundColor || 'white')
			});

			var fieldsInfo = formType === 'verletzungen'
				? 'Name, Vorname, PLZ, Ort, E-Mail, Klasse, Trainingsausfall (von/bis), IBAN, Kontoinhaber, Sportdispens (Upload), AGB, Captcha'
				: 'Name, Vorname, AHV-Nummer, Captcha';

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Formular-Einstellungen' }, [
						el(SelectControl, {
							key: 'formtype',
							label: 'Formular-Typ',
							value: formType,
							options: [
								{ label: 'Verletzungs-Rückerstattung', value: 'verletzungen' },
								{ label: 'AHV-Nummer', value: 'ahv' }
							],
							onChange: function(v) { setAttributes({ formType: v }); }
						}),
						el(TextControl, {
							key: 'headline',
							label: 'Überschrift (leer = Default)',
							value: attributes.headline,
							placeholder: d.headline,
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(TextareaControl, {
							key: 'desc',
							label: 'Beschreibung (leer = Default)',
							value: attributes.description,
							placeholder: d.description,
							onChange: function(v) { setAttributes({ description: v }); }
						}),
						el(TextControl, {
							key: 'btn',
							label: 'Button-Text (leer = Default)',
							value: attributes.buttonText,
							placeholder: d.buttonText,
							onChange: function(v) { setAttributes({ buttonText: v }); }
						}),
						el(TextControl, {
							key: 'email',
							label: 'Empfänger E-Mail (leer = Admin)',
							value: attributes.recipientEmail,
							onChange: function(v) { setAttributes({ recipientEmail: v }); }
						}),
						el(SelectControl, {
							key: 'bg',
							label: 'Hintergrund',
							value: attributes.backgroundColor,
							options: [
								{ label: 'Weiss', value: 'white' },
								{ label: 'Hell', value: 'light' },
								{ label: 'Dunkel', value: 'dark' }
							],
							onChange: function(v) { setAttributes({ backgroundColor: v }); }
						})
					])
				),
				el('div', blockProps, [
					el('div', { key: 'card', className: 'po-memberform-editor__card' }, [
						el('div', { key: 'icon', className: 'po-memberform-editor__icon' },
							formType === 'verletzungen' ? '\u{1FA79}' : '\u{1F4CB}'
						),
						el('h3', { key: 'title', className: 'po-memberform-editor__title' },
							attributes.headline || d.headline
						),
						el('p', { key: 'desc', className: 'po-memberform-editor__desc' },
							attributes.description || d.description
						),
						el('div', { key: 'btn', className: 'po-memberform-editor__btn' },
							attributes.buttonText || d.buttonText
						),
						el('p', { key: 'fields', className: 'po-memberform-editor__fields' },
							'Felder: ' + fieldsInfo
						)
					])
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
