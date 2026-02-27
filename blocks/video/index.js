(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;
	var Placeholder = wp.components.Placeholder;
	var el = wp.element.createElement;

	/**
	 * Extract YouTube/Vimeo ID from URL for editor preview.
	 */
	function extractVideoInfo(url) {
		if (!url) return null;
		var ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
		if (ytMatch) return { platform: 'youtube', id: ytMatch[1] };
		var vmMatch = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
		if (vmMatch) return { platform: 'vimeo', id: vmMatch[1] };
		return null;
	}

	/**
	 * Get YouTube thumbnail URL.
	 */
	function getYouTubeThumbnail(videoId) {
		return 'https://img.youtube.com/vi/' + videoId + '/maxresdefault.jpg';
	}

	registerBlockType('parkourone/video', {
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'po-video-editor' });

			var videoInfo = attributes.videoType === 'embed' ? extractVideoInfo(attributes.videoUrl) : null;
			var hasVideo = !!attributes.videoUrl;

			// ── Inspector Controls ──
			var inspector = el(InspectorControls, { key: 'inspector' }, [
				el(PanelBody, { key: 'video-source', title: 'Video-Quelle', initialOpen: true }, [
					el(SelectControl, {
						key: 'video-type',
						label: 'Typ',
						value: attributes.videoType,
						options: [
							{ label: 'Embed (YouTube / Vimeo)', value: 'embed' },
							{ label: 'Datei (MP4)', value: 'file' }
						],
						onChange: function(v) {
							setAttributes({ videoType: v, videoUrl: '' });
						}
					}),

					attributes.videoType === 'embed' && el(TextControl, {
						key: 'video-url',
						label: 'Video-URL',
						help: 'YouTube- oder Vimeo-URL eingeben.',
						placeholder: 'https://www.youtube.com/watch?v=...',
						value: attributes.videoUrl,
						onChange: function(v) { setAttributes({ videoUrl: v }); }
					}),

					attributes.videoType === 'file' && el('div', { key: 'file-upload', style: { marginBottom: '16px' } }, [
						el('label', {
							key: 'file-label',
							style: { display: 'block', marginBottom: '8px', fontWeight: '500', fontSize: '11px', textTransform: 'uppercase' }
						}, 'Videodatei'),
						attributes.videoUrl && el('div', {
							key: 'file-info',
							style: {
								background: 'rgba(0,0,0,0.05)',
								borderRadius: '6px',
								padding: '8px 12px',
								marginBottom: '8px',
								fontSize: '12px',
								wordBreak: 'break-all'
							}
						}, attributes.videoUrl.split('/').pop()),
						el(MediaUploadCheck, { key: 'upload-check' },
							el(MediaUpload, {
								key: 'upload',
								onSelect: function(media) { setAttributes({ videoUrl: media.url }); },
								allowedTypes: ['video'],
								render: function(obj) {
									return el('div', null, [
										el(Button, {
											key: 'upload-btn',
											variant: 'secondary',
											onClick: obj.open
										}, attributes.videoUrl ? 'Video wechseln' : 'Video hochladen'),
										attributes.videoUrl && el(Button, {
											key: 'remove-btn',
											variant: 'link',
											isDestructive: true,
											onClick: function() { setAttributes({ videoUrl: '' }); },
											style: { marginLeft: '8px' }
										}, 'Entfernen')
									]);
								}
							})
						)
					])
				]),

				el(PanelBody, { key: 'poster', title: 'Vorschaubild', initialOpen: false }, [
					el('div', { key: 'poster-upload' }, [
						attributes.posterImage && el('img', {
							key: 'poster-preview',
							src: attributes.posterImage,
							style: { maxWidth: '100%', borderRadius: '6px', marginBottom: '8px', display: 'block' }
						}),
						el(MediaUploadCheck, { key: 'poster-check' },
							el(MediaUpload, {
								key: 'poster-media',
								onSelect: function(media) { setAttributes({ posterImage: media.url }); },
								allowedTypes: ['image'],
								render: function(obj) {
									return el('div', null, [
										el(Button, {
											key: 'poster-btn',
											variant: 'secondary',
											onClick: obj.open
										}, attributes.posterImage ? 'Bild wechseln' : 'Vorschaubild hochladen'),
										attributes.posterImage && el(Button, {
											key: 'poster-remove',
											variant: 'link',
											isDestructive: true,
											onClick: function() { setAttributes({ posterImage: '' }); },
											style: { marginLeft: '8px' }
										}, 'Entfernen')
									]);
								}
							})
						)
					])
				]),

				el(PanelBody, { key: 'settings', title: 'Einstellungen', initialOpen: false }, [
					el(SelectControl, {
						key: 'aspect-ratio',
						label: 'Seitenverhaeltnis',
						value: attributes.aspectRatio,
						options: [
							{ label: '16:9 (Breitbild)', value: '16:9' },
							{ label: '4:3 (Klassisch)', value: '4:3' },
							{ label: '1:1 (Quadratisch)', value: '1:1' },
							{ label: '9:16 (Hochformat)', value: '9:16' }
						],
						onChange: function(v) { setAttributes({ aspectRatio: v }); }
					}),
					attributes.videoType === 'file' && el(ToggleControl, {
						key: 'autoplay',
						label: 'Autoplay',
						help: 'Video startet automatisch (stumm empfohlen).',
						checked: attributes.autoplay,
						onChange: function(v) { setAttributes({ autoplay: v }); }
					}),
					attributes.videoType === 'file' && el(ToggleControl, {
						key: 'loop',
						label: 'Loop',
						checked: attributes.loop,
						onChange: function(v) { setAttributes({ loop: v }); }
					}),
					attributes.videoType === 'file' && el(ToggleControl, {
						key: 'muted',
						label: 'Stumm',
						help: 'Fuer Autoplay erforderlich in den meisten Browsern.',
						checked: attributes.muted,
						onChange: function(v) { setAttributes({ muted: v }); }
					})
				]),

				el(PanelBody, { key: 'caption-panel', title: 'Bildunterschrift', initialOpen: false }, [
					el(TextControl, {
						key: 'caption',
						label: 'Bildunterschrift',
						placeholder: 'Optionale Beschreibung unter dem Video...',
						value: attributes.caption,
						onChange: function(v) { setAttributes({ caption: v }); }
					})
				])
			]);

			// ── Editor Preview ──
			var preview;

			if (!hasVideo) {
				// Empty state placeholder
				preview = el(Placeholder, {
					key: 'placeholder',
					icon: 'video-alt3',
					label: 'Video',
					instructions: attributes.videoType === 'embed'
						? 'Gib eine YouTube- oder Vimeo-URL in den Block-Einstellungen ein.'
						: 'Lade eine Videodatei in den Block-Einstellungen hoch.'
				}, [
					attributes.videoType === 'embed' && el(TextControl, {
						key: 'url-inline',
						placeholder: 'https://www.youtube.com/watch?v=...',
						value: attributes.videoUrl,
						onChange: function(v) { setAttributes({ videoUrl: v }); },
						style: { width: '100%' }
					}),
					attributes.videoType === 'file' && el(MediaUploadCheck, { key: 'upload-inline-check' },
						el(MediaUpload, {
							key: 'upload-inline',
							onSelect: function(media) { setAttributes({ videoUrl: media.url }); },
							allowedTypes: ['video'],
							render: function(obj) {
								return el(Button, {
									variant: 'primary',
									onClick: obj.open
								}, 'Video hochladen');
							}
						})
					)
				]);
			} else if (attributes.videoType === 'embed' && videoInfo) {
				// Embed preview with thumbnail
				var thumbUrl = attributes.posterImage
					|| (videoInfo.platform === 'youtube' ? getYouTubeThumbnail(videoInfo.id) : '');

				preview = el('div', {
					key: 'embed-preview',
					className: 'po-video-editor__preview po-video-editor__preview--' + attributes.aspectRatio.replace(':', 'x'),
					style: { position: 'relative', overflow: 'hidden', borderRadius: '12px', background: '#000' }
				}, [
					thumbUrl && el('img', {
						key: 'thumb',
						src: thumbUrl,
						style: {
							position: 'absolute',
							top: 0,
							left: 0,
							width: '100%',
							height: '100%',
							objectFit: 'cover',
							opacity: 0.7
						}
					}),
					el('div', {
						key: 'play-overlay',
						style: {
							position: 'absolute',
							top: '50%',
							left: '50%',
							transform: 'translate(-50%, -50%)',
							width: '64px',
							height: '64px',
							background: 'rgba(0,0,0,0.6)',
							borderRadius: '50%',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center'
						}
					}, el('svg', {
						key: 'play-svg',
						width: '24',
						height: '24',
						viewBox: '0 0 24 24',
						fill: 'white'
					}, el('polygon', { key: 'tri', points: '8,5 20,12 8,19' }))),
					el('div', {
						key: 'badge',
						style: {
							position: 'absolute',
							bottom: '12px',
							left: '12px',
							background: 'rgba(0,0,0,0.7)',
							color: '#fff',
							padding: '4px 10px',
							borderRadius: '6px',
							fontSize: '11px',
							fontWeight: '600',
							textTransform: 'uppercase',
							letterSpacing: '0.05em'
						}
					}, videoInfo.platform === 'youtube' ? 'YouTube' : 'Vimeo')
				]);
			} else if (attributes.videoType === 'file') {
				// File video preview
				preview = el('div', {
					key: 'file-preview',
					className: 'po-video-editor__preview po-video-editor__preview--' + attributes.aspectRatio.replace(':', 'x'),
					style: { position: 'relative', overflow: 'hidden', borderRadius: '12px', background: '#000' }
				}, el('video', {
					key: 'video-el',
					src: attributes.videoUrl,
					poster: attributes.posterImage || undefined,
					muted: true,
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						width: '100%',
						height: '100%',
						objectFit: 'cover'
					}
				}));
			} else {
				// Embed URL that could not be parsed
				preview = el('div', {
					key: 'unknown-preview',
					style: {
						background: '#1d1d1f',
						borderRadius: '12px',
						padding: '40px 24px',
						textAlign: 'center',
						color: 'rgba(255,255,255,0.6)',
						fontSize: '14px'
					}
				}, [
					el('span', { key: 'icon', className: 'dashicons dashicons-video-alt3', style: { fontSize: '32px', display: 'block', marginBottom: '12px', color: 'rgba(255,255,255,0.3)' } }),
					el('span', { key: 'text' }, 'Video-URL konnte nicht erkannt werden. Das Video wird als direkter Embed gerendert.')
				]);
			}

			// Caption preview
			var captionPreview = attributes.caption
				? el('p', {
					key: 'caption-preview',
					style: { textAlign: 'center', fontSize: '13px', color: '#86868b', marginTop: '12px' }
				}, attributes.caption)
				: null;

			return el('div', null, [
				inspector,
				el('figure', blockProps, [
					preview,
					captionPreview
				])
			]);
		},

		save: function() {
			return null;
		}
	});
})(window.wp);
