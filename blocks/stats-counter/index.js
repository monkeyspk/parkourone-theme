(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, Button } = wp.components;
	const { createElement: el, useState } = wp.element;

	registerBlockType('parkourone/stats-counter', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({ className: 'po-stats-editor' });
			const [activeStat, setActiveStat] = useState(0);

			function updateStat(index, key, value) {
				const newStats = [...attributes.stats];
				newStats[index] = { ...newStats[index], [key]: value };
				setAttributes({ stats: newStats });
			}

			function addStat() {
				const newStats = [...attributes.stats, {
					number: '100',
					suffix: '',
					label: 'Neuer Wert',
					subtext: ''
				}];
				setAttributes({ stats: newStats });
				setActiveStat(newStats.length - 1);
			}

			function removeStat(index) {
				if (attributes.stats.length <= 1) return;
				const newStats = attributes.stats.filter(function(_, i) { return i !== index; });
				setAttributes({ stats: newStats });
				if (activeStat >= newStats.length) {
					setActiveStat(newStats.length - 1);
				}
			}

			var stat = attributes.stats[activeStat];

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'settings', title: 'Einstellungen', initialOpen: true }, [
						el(TextControl, {
							key: 'headline',
							label: 'Ueberschrift (optional)',
							value: attributes.headline || '',
							onChange: function(v) { setAttributes({ headline: v }); }
						}),
						el(SelectControl, {
							key: 'style',
							label: 'Stil',
							value: attributes.style,
							options: [
								{ label: 'Hell', value: 'light' },
								{ label: 'Dunkel', value: 'dark' },
								{ label: 'Gradient', value: 'gradient' }
							],
							onChange: function(v) { setAttributes({ style: v }); }
						})
					]),
					el(PanelBody, { key: 'stats', title: 'Statistiken bearbeiten', initialOpen: true }, [
						el('div', { key: 'tabs', style: { display: 'flex', gap: '4px', marginBottom: '16px', flexWrap: 'wrap' } },
							attributes.stats.map(function(s, i) {
								return el(Button, {
									key: i,
									variant: i === activeStat ? 'primary' : 'secondary',
									isSmall: true,
									onClick: function() { setActiveStat(i); }
								}, (i + 1));
							}).concat([
								el(Button, {
									key: 'add',
									variant: 'secondary',
									isSmall: true,
									onClick: addStat,
									style: { marginLeft: '8px' }
								}, '+')
							])
						),
						stat && el('div', { key: 'form' }, [
							el(TextControl, {
								key: 'number',
								label: 'Zahl',
								value: stat.number,
								onChange: function(v) { updateStat(activeStat, 'number', v); }
							}),
							el(TextControl, {
								key: 'suffix',
								label: 'Suffix (z.B. +, %, etc.)',
								value: stat.suffix,
								onChange: function(v) { updateStat(activeStat, 'suffix', v); }
							}),
							el(TextControl, {
								key: 'label',
								label: 'Label',
								value: stat.label,
								onChange: function(v) { updateStat(activeStat, 'label', v); }
							}),
							el(TextControl, {
								key: 'subtext',
								label: 'Subtext (emotional)',
								value: stat.subtext,
								onChange: function(v) { updateStat(activeStat, 'subtext', v); }
							}),
							attributes.stats.length > 1 && el(Button, {
								key: 'remove',
								variant: 'link',
								isDestructive: true,
								onClick: function() { removeStat(activeStat); },
								style: { marginTop: '12px' }
							}, 'Statistik entfernen')
						])
					])
				]),
				el('div', blockProps, [
					attributes.headline
						? el('h2', { key: 'headline', style: { fontSize: '20px', fontWeight: '600', textAlign: 'center', marginBottom: '24px' } }, attributes.headline)
						: null,
					el('p', { key: 'desc', style: { color: '#86868b', marginBottom: '24px', textAlign: 'center' } }, 'Stats Counter - Apple Style'),
					el('div', { key: 'preview', className: 'po-stats-editor__preview' },
						attributes.stats.map(function(s, i) {
							return el('div', { key: i, className: 'po-stats-editor__item' }, [
								el('span', { key: 'num', className: 'po-stats-editor__number' }, s.number + (s.suffix || '')),
								el('span', { key: 'label', className: 'po-stats-editor__label' }, s.label),
								s.subtext ? el('span', { key: 'sub', className: 'po-stats-editor__subtext' }, s.subtext) : null
							]);
						})
					)
				])
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
