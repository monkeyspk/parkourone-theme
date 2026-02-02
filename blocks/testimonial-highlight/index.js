(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, SelectControl, Button } = wp.components;
	const { createElement: el, useState } = wp.element;

	registerBlockType('parkourone/testimonial-highlight', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-quote-highlight-editor' });
			const [activeQuote, setActiveQuote] = useState(0);

			function updateQuote(index, key, value) {
				const newQuotes = [...attributes.quotes];
				newQuotes[index] = { ...newQuotes[index], [key]: value };
				setAttributes({ quotes: newQuotes });
			}

			function addQuote() {
				if (attributes.quotes.length >= 2) return;
				const newQuotes = [...attributes.quotes, {
					text: 'Neues Zitat hier eingeben...',
					author: 'Name',
					role: 'Rolle',
					imageUrl: ''
				}];
				setAttributes({ quotes: newQuotes });
				setActiveQuote(newQuotes.length - 1);
			}

			function removeQuote(index) {
				if (attributes.quotes.length <= 1) return;
				const newQuotes = attributes.quotes.filter(function(_, i) { return i !== index; });
				setAttributes({ quotes: newQuotes });
				if (activeQuote >= newQuotes.length) {
					setActiveQuote(newQuotes.length - 1);
				}
			}

			var quote = attributes.quotes[activeQuote];

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'settings', title: 'Einstellungen', initialOpen: true }, [
						el(SelectControl, {
							key: 'layout',
							label: 'Layout',
							value: attributes.layout,
							options: [
								{ label: 'Ein Zitat (gross)', value: 'single' },
								{ label: 'Zwei Zitate (nebeneinander)', value: 'double' }
							],
							onChange: function(v) { setAttributes({ layout: v }); }
						}),
						el(SelectControl, {
							key: 'style',
							label: 'Stil',
							value: attributes.style,
							options: [
								{ label: 'Hell', value: 'light' },
								{ label: 'Dunkel', value: 'dark' }
							],
							onChange: function(v) { setAttributes({ style: v }); }
						})
					]),
					el(PanelBody, { key: 'quotes', title: 'Zitate bearbeiten', initialOpen: true }, [
						el('div', { key: 'tabs', style: { display: 'flex', gap: '4px', marginBottom: '16px' } },
							attributes.quotes.map(function(q, i) {
								return el(Button, {
									key: i,
									variant: i === activeQuote ? 'primary' : 'secondary',
									isSmall: true,
									onClick: function() { setActiveQuote(i); }
								}, 'Zitat ' + (i + 1));
							}).concat(
								attributes.quotes.length < 2 ? [
									el(Button, {
										key: 'add',
										variant: 'secondary',
										isSmall: true,
										onClick: addQuote,
										style: { marginLeft: '8px' }
									}, '+')
								] : []
							)
						),
						quote && el('div', { key: 'form' }, [
							el(TextareaControl, {
								key: 'text',
								label: 'Zitat',
								value: quote.text,
								onChange: function(v) { updateQuote(activeQuote, 'text', v); },
								rows: 4
							}),
							el(TextControl, {
								key: 'author',
								label: 'Name',
								value: quote.author,
								onChange: function(v) { updateQuote(activeQuote, 'author', v); }
							}),
							el(TextControl, {
								key: 'role',
								label: 'Rolle / Info',
								value: quote.role,
								onChange: function(v) { updateQuote(activeQuote, 'role', v); }
							}),
							el('div', { key: 'image', style: { marginTop: '16px' } }, [
								el('label', { key: 'label', style: { display: 'block', marginBottom: '8px', fontWeight: '500' } }, 'Bild'),
								el(MediaUploadCheck, { key: 'check' },
									el(MediaUpload, {
										onSelect: function(media) { updateQuote(activeQuote, 'imageUrl', media.url); },
										allowedTypes: ['image'],
										render: function(obj) {
											return el('div', null,
												quote.imageUrl
													? el('div', null, [
														el('img', { key: 'img', src: quote.imageUrl, style: { width: '60px', height: '60px', borderRadius: '50%', objectFit: 'cover', marginBottom: '8px' } }),
														el('br', { key: 'br' }),
														el(Button, { key: 'change', onClick: obj.open, variant: 'secondary', isSmall: true }, 'Aendern'),
														el(Button, { key: 'remove', onClick: function() { updateQuote(activeQuote, 'imageUrl', ''); }, variant: 'link', isDestructive: true, isSmall: true, style: { marginLeft: '8px' } }, 'Entfernen')
													])
													: el(Button, { onClick: obj.open, variant: 'secondary' }, 'Bild waehlen')
											);
										}
									})
								)
							]),
							attributes.quotes.length > 1 && el(Button, {
								key: 'remove',
								variant: 'link',
								isDestructive: true,
								onClick: function() { removeQuote(activeQuote); },
								style: { marginTop: '16px' }
							}, 'Zitat entfernen')
						])
					])
				]),
				el('div', blockProps, [
					el('p', { key: 'desc', style: { color: '#86868b', marginBottom: '16px' } }, 'Testimonial Highlight - Apple Style'),
					attributes.quotes.map(function(q, i) {
						return el('div', { key: i, style: { marginBottom: i < attributes.quotes.length - 1 ? '24px' : '0' } }, [
							el('p', { key: 'text', className: 'po-quote-highlight-editor__quote' }, '"' + q.text + '"'),
							el('p', { key: 'author', className: 'po-quote-highlight-editor__author' }, '— ' + q.author + (q.role ? ', ' + q.role : ''))
						]);
					})
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
