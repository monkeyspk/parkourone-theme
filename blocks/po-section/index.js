(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var InnerBlocks = wp.blockEditor.InnerBlocks;
    var RichText = wp.blockEditor.RichText;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var ColorPalette = wp.components.ColorPalette;
    var BaseControl = wp.components.BaseControl;

    var colors = [
        { name: 'Transparent', color: '' },
        { name: 'Weiß', color: '#ffffff' },
        { name: 'Hellgrau', color: '#f5f5f7' },
        { name: 'Dunkelgrau', color: '#1d1d1f' },
        { name: 'Schwarz', color: '#000000' },
        { name: 'Blau', color: '#0066cc' },
        { name: 'Blau hell', color: '#e8f4fd' },
        { name: 'Rot', color: '#ff3b30' },
        { name: 'Grün', color: '#34c759' },
        { name: 'Lila', color: 'rgba(102, 126, 234, 0.1)' }
    ];

    registerBlockType('parkourone/po-section', {
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var editorClasses = 'po-section-editor';
            if (attributes.backgroundColor) {
                editorClasses += ' po-section-editor--has-bg';
            }

            var editorStyle = {};
            if (attributes.backgroundColor) {
                editorStyle.backgroundColor = attributes.backgroundColor;
            }

            var darkBgs = ['#1d1d1f', '#000000', '#0066cc', '#ff3b30'];
            var isDark = darkBgs.indexOf(attributes.backgroundColor) !== -1;
            if (isDark) {
                editorStyle.color = '#fff';
            }

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Einstellungen', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Überschrift anzeigen',
                            checked: attributes.showHeadline,
                            onChange: function(val) { setAttributes({ showHeadline: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Innenabstand',
                            value: attributes.paddingSize,
                            options: [
                                { label: 'Kein', value: 'none' },
                                { label: 'Klein', value: 'small' },
                                { label: 'Normal', value: 'medium' },
                                { label: 'Groß', value: 'large' }
                            ],
                            onChange: function(val) { setAttributes({ paddingSize: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Maximale Breite',
                            value: attributes.maxWidth,
                            options: [
                                { label: 'Schmal (800px)', value: 'narrow' },
                                { label: 'Standard (1200px)', value: 'default' },
                                { label: 'Breit (1400px)', value: 'wide' },
                                { label: 'Volle Breite', value: 'full' }
                            ],
                            onChange: function(val) { setAttributes({ maxWidth: val }); }
                        }),
                        el(BaseControl, { label: 'Hintergrundfarbe' },
                            el(ColorPalette, {
                                colors: colors,
                                value: attributes.backgroundColor || undefined,
                                onChange: function(val) { setAttributes({ backgroundColor: val || '' }); },
                                clearable: true
                            })
                        )
                    )
                ),
                el('div', { className: editorClasses, style: editorStyle },
                    attributes.showHeadline ? el(RichText, {
                        tagName: 'h2',
                        className: 'po-section-editor__headline',
                        value: attributes.headline,
                        onChange: function(val) { setAttributes({ headline: val }); },
                        placeholder: 'Überschrift eingeben...',
                        style: isDark ? { color: '#fff' } : {}
                    }) : null,
                    el('div', { className: 'po-section-editor__inner-blocks' },
                        el(InnerBlocks, {
                            allowedBlocks: null,
                            template: [
                                ['core/paragraph', { placeholder: 'Inhalte hier einfügen...' }]
                            ],
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender
                        })
                    )
                )
            );
        },

        save: function() {
            return el(InnerBlocks.Content);
        }
    });
})(window.wp);
