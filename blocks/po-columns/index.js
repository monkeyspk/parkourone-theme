(function(wp) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, SelectControl, RangeControl, ColorPalette } = wp.components;
	const { createElement: el, Fragment } = wp.element;

	const gapOptions = [
		{ label: 'Klein', value: 'small' },
		{ label: 'Normal', value: 'medium' },
		{ label: 'Gross', value: 'large' }
	];

	const verticalAlignOptions = [
		{ label: 'Oben', value: 'top' },
		{ label: 'Mitte', value: 'center' },
		{ label: 'Unten', value: 'bottom' }
	];

	const colors = [
		{ name: 'Transparent', color: '' },
		{ name: 'Weiss', color: '#ffffff' },
		{ name: 'Hellgrau', color: '#f5f5f7' },
		{ name: 'ParkourONE Lila (hell)', color: 'rgba(102, 126, 234, 0.1)' },
		{ name: 'ParkourONE Violett (hell)', color: 'rgba(118, 75, 162, 0.1)' }
	];

	registerBlockType('parkourone/po-columns', {
		edit: function(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps({
				className: 'po-columns-editor po-columns--' + attributes.columns + ' po-columns--gap-' + attributes.gap + ' po-columns--valign-' + attributes.verticalAlign,
				style: {
					backgroundColor: attributes.backgroundColor || undefined
				}
			});

			var updateColumnContent = function(index, field, value) {
				var newContent = attributes.columnContent.slice();
				newContent[index] = Object.assign({}, newContent[index], { [field]: value });
				setAttributes({ columnContent: newContent });
			};

			var columnElements = [];
			for (var i = 0; i < attributes.columns; i++) {
				(function(idx) {
					var colData = attributes.columnContent[idx] || { content: '', backgroundColor: '' };
					columnElements.push(
						el('div', {
							key: 'col-' + idx,
							className: 'po-columns-editor__column',
							style: { backgroundColor: colData.backgroundColor || undefined }
						}, [
							el(RichText, {
								key: 'content-' + idx,
								tagName: 'div',
								className: 'po-columns-editor__content',
								value: colData.content,
								onChange: function(v) { updateColumnContent(idx, 'content', v); },
								placeholder: 'Spalte ' + (idx + 1) + ' - Inhalt eingeben...',
								allowedFormats: ['core/bold', 'core/italic', 'core/link']
							})
						])
					);
				})(i);
			}

			// Spalten-Hintergrundfarben Controls
			var columnColorControls = [];
			for (var j = 0; j < attributes.columns; j++) {
				(function(idx) {
					var colData = attributes.columnContent[idx] || { content: '', backgroundColor: '' };
					columnColorControls.push(
						el(Fragment, { key: 'color-ctrl-' + idx }, [
							el('p', {
								key: 'label-' + idx,
								style: { marginBottom: '8px', fontWeight: 500, marginTop: idx > 0 ? '16px' : 0 }
							}, 'Spalte ' + (idx + 1) + ' Hintergrund'),
							el(ColorPalette, {
								key: 'palette-' + idx,
								colors: colors,
								value: colData.backgroundColor,
								onChange: function(v) { updateColumnContent(idx, 'backgroundColor', v); }
							})
						])
					);
				})(j);
			}

			return el('div', null, [
				el(InspectorControls, { key: 'controls' }, [
					el(PanelBody, { key: 'layout', title: 'Layout', initialOpen: true }, [
						el(RangeControl, {
							key: 'columns',
							label: 'Anzahl Spalten',
							value: attributes.columns,
							onChange: function(v) { setAttributes({ columns: v }); },
							min: 2,
							max: 4
						}),
						el(SelectControl, {
							key: 'gap',
							label: 'Abstand zwischen Spalten',
							value: attributes.gap,
							options: gapOptions,
							onChange: function(v) { setAttributes({ gap: v }); }
						}),
						el(SelectControl, {
							key: 'valign',
							label: 'Vertikale Ausrichtung',
							value: attributes.verticalAlign,
							options: verticalAlignOptions,
							onChange: function(v) { setAttributes({ verticalAlign: v }); }
						})
					]),
					el(PanelBody, { key: 'colors', title: 'Hintergrundfarben', initialOpen: false }, [
						el('p', { key: 'mainLabel', style: { marginBottom: '8px', fontWeight: 500 } }, 'Gesamter Block'),
						el(ColorPalette, {
							key: 'bgColor',
							colors: colors,
							value: attributes.backgroundColor,
							onChange: function(v) { setAttributes({ backgroundColor: v }); }
						})
					].concat(columnColorControls))
				]),
				el('div', blockProps, columnElements)
			]);
		},
		save: function() { return null; }
	});
})(window.wp);
