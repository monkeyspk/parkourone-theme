(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, Notice } = wp.components;
	const { createElement: el } = wp.element;

	registerBlockType('parkourone/team-grid', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-tg-editor' });

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { key: 'info', title: 'Team Grid', initialOpen: true },
						el(Notice, { 
							status: 'info', 
							isDismissible: false,
							style: { margin: '0' }
						}, [
							'Team-Mitglieder werden über den ',
							el('strong', { key: 'cpt' }, 'Coach'),
							' Inhaltstyp verwaltet. Gehe zu ',
							el('strong', { key: 'menu' }, 'Coaches'),
							' im Menü, um Profile zu bearbeiten.'
						])
					)
				),
				el('div', blockProps, [
					el(RichText, {
						key: 'headline',
						tagName: 'h2',
						className: 'po-tg-editor__headline',
						value: attributes.headline,
						onChange: function(v) { setAttributes({ headline: v }); },
						placeholder: 'Überschrift...'
					}),
					el(RichText, {
						key: 'intro',
						tagName: 'p',
						className: 'po-tg-editor__intro',
						value: attributes.intro,
						onChange: function(v) { setAttributes({ intro: v }); },
						placeholder: 'Einleitung...'
					}),
					el('div', { key: 'grid', className: 'po-tg-editor__grid' }, [
						el('div', { key: 'card1', className: 'po-tg-editor__card' }),
						el('div', { key: 'card2', className: 'po-tg-editor__card' }),
						el('div', { key: 'card3', className: 'po-tg-editor__card' })
					]),
					el('p', { key: 'note', style: { fontSize: '12px', color: '#888', textAlign: 'center', marginTop: '1rem' } }, 
						'Team-Mitglieder werden aus dem Coach-Inhaltstyp geladen'
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
