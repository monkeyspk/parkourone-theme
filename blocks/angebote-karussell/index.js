(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, ToggleControl, TextControl, Spinner } = wp.components;
	const { createElement: el, useState, useEffect } = wp.element;
	const { apiFetch } = wp;

	registerBlockType('parkourone/angebote-karussell', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-angebote-karussell-editor' });
			const [angebote, setAngebote] = useState([]);
			const [loading, setLoading] = useState(true);

			useEffect(function() {
				apiFetch({ path: '/parkourone/v1/angebote?featured=1' })
					.then(function(data) {
						setAngebote(data);
						setLoading(false);
					})
					.catch(function() {
						setLoading(false);
					});
			}, []);

			return el('div', null, [
				el(InspectorControls, { key: 'controls' },
					el(PanelBody, { title: 'Einstellungen' }, [
						el(ToggleControl, {
							key: 'showlink',
							label: '"Alle Angebote" Link anzeigen',
							checked: attributes.showAllLink,
							onChange: function(val) { setAttributes({ showAllLink: val }); }
						}),
						attributes.showAllLink && el(TextControl, {
							key: 'linkurl',
							label: 'Link URL',
							value: attributes.allLinkUrl,
							onChange: function(val) { setAttributes({ allLinkUrl: val }); }
						}),
						attributes.showAllLink && el(TextControl, {
							key: 'linktext',
							label: 'Link Text',
							value: attributes.allLinkText,
							onChange: function(val) { setAttributes({ allLinkText: val }); }
						})
					])
				),
				el('div', blockProps, [
					el('div', { key: 'header', style: { marginBottom: '1rem' } }, [
						el(RichText, {
							key: 'headline',
							tagName: 'h2',
							value: attributes.headline,
							onChange: function(val) { setAttributes({ headline: val }); },
							placeholder: 'Headline...',
							style: { margin: '0 0 0.25rem 0', fontSize: '24px', fontWeight: '700' }
						}),
						el(RichText, {
							key: 'subtext',
							tagName: 'p',
							value: attributes.subtext,
							onChange: function(val) { setAttributes({ subtext: val }); },
							placeholder: 'Subtext...',
							style: { margin: '0', color: '#666' }
						})
					]),
					el('p', { key: 'info', style: { fontSize: '14px', color: '#666', margin: '0 0 1rem 0' } },
						'Zeigt ' + angebote.length + ' featured Angebote als Karussell. Angebote als "featured" markieren unter Angebote > Bearbeiten.'
					),
					loading
						? el(Spinner, { key: 'spinner' })
						: el('div', { key: 'preview', className: 'po-angebote-karussell-editor__preview' },
							angebote.slice(0, 4).map(function(a, i) {
								return el('div', { key: i, className: 'po-angebote-karussell-editor__card' }, [
									el('strong', { key: 'title' }, a.titel),
									el('div', { key: 'cat', style: { fontSize: '12px', color: '#666' } }, a.kategorie?.name || '-')
								]);
							}),
							angebote.length === 0 && el('p', { style: { color: '#999' } }, 'Keine featured Angebote')
						)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
