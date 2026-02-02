import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

registerBlockType('parkourone/text-reveal', {
	edit: function Edit({ attributes, setAttributes }) {
		const { text, textSize, textAlign } = attributes;

		const blockProps = useBlockProps({
			className: `po-text-reveal po-text-reveal--${textSize} po-text-reveal--align-${textAlign}`
		});

		const sizeOptions = [
			{ label: 'Medium', value: 'medium' },
			{ label: 'Large', value: 'large' },
			{ label: 'Extra Large', value: 'xlarge' }
		];

		const alignOptions = [
			{ label: 'Links', value: 'left' },
			{ label: 'Zentriert', value: 'center' },
			{ label: 'Rechts', value: 'right' }
		];

		return (
			<>
				<InspectorControls>
					<PanelBody title="Text-Einstellungen">
						<SelectControl
							label="Textgröße"
							value={textSize}
							options={sizeOptions}
							onChange={(value) => setAttributes({ textSize: value })}
						/>
						<SelectControl
							label="Ausrichtung"
							value={textAlign}
							options={alignOptions}
							onChange={(value) => setAttributes({ textAlign: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<div {...blockProps}>
					<div className="po-text-reveal__container">
						<RichText
							tagName="p"
							className="po-text-reveal__text"
							value={text}
							onChange={(value) => setAttributes({ text: value })}
							placeholder="Text eingeben der beim Scrollen erscheint..."
							allowedFormats={['core/bold', 'core/italic']}
						/>
						<p className="po-text-reveal__hint">
							Beim Scrollen werden die Wörter progressiv sichtbar (Apple-Style)
						</p>
					</div>
				</div>
			</>
		);
	}
});
