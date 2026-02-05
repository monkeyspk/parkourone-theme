/**
 * Theme Image Picker Component for Gutenberg
 *
 * Allows selecting images from theme fallback folders
 * or uploading custom images via Media Library
 */

(function(wp) {
	const { createElement: el, useState, useEffect } = wp.element;
	const { Button, Modal, TabPanel, SelectControl, Spinner } = wp.components;
	const { MediaUpload, MediaUploadCheck } = wp.blockEditor || wp.editor;
	const { __ } = wp.i18n;

	// Category labels in German
	const categoryLabels = {
		minis: 'Minis (4-6 Jahre)',
		kids: 'Kids (7-11 Jahre)',
		juniors: 'Juniors (12-17 Jahre)',
		adults: 'Adults (18+)',
		seniors: 'Seniors / Masters'
	};

	// Orientation labels
	const orientationLabels = {
		portrait: 'Hochkant',
		landscape: 'Querformat'
	};

	/**
	 * Theme Image Picker Component
	 *
	 * @param {Object} props
	 * @param {string} props.value - Current image URL
	 * @param {Function} props.onChange - Callback when image changes
	 * @param {string} props.orientation - 'portrait', 'landscape', or 'all'
	 * @param {string} props.category - Age category filter
	 * @param {string} props.label - Button label
	 */
	function ThemeImagePicker(props) {
		const {
			value,
			onChange,
			orientation = 'all',
			category = 'all',
			label = __('Bild wählen', 'parkourone')
		} = props;

		const [isOpen, setIsOpen] = useState(false);
		const [selectedCategory, setSelectedCategory] = useState(category !== 'all' ? category : 'adults');
		const [selectedOrientation, setSelectedOrientation] = useState(orientation !== 'all' ? orientation : 'portrait');
		const [images, setImages] = useState(null);

		// Load images from localized data
		useEffect(function() {
			if (window.parkouroneThemeImages && window.parkouroneThemeImages.images) {
				setImages(window.parkouroneThemeImages.images);
			}
		}, []);

		// Get filtered images
		function getFilteredImages() {
			if (!images) return [];

			var categoryImages = images[selectedCategory];
			if (!categoryImages) return [];

			if (orientation !== 'all') {
				return categoryImages[orientation] || [];
			}

			return categoryImages[selectedOrientation] || [];
		}

		// Handle theme image selection
		function handleSelect(imageUrl) {
			onChange(imageUrl);
			setIsOpen(false);
		}

		// Handle media library selection
		function handleMediaSelect(media) {
			onChange(media.url);
			setIsOpen(false);
		}

		// Handle remove
		function handleRemove() {
			onChange('');
		}

		var filteredImages = getFilteredImages();

		return el('div', { className: 'po-theme-image-picker' },
			// Preview
			value && el('div', { className: 'po-theme-image-picker__preview' },
				el('img', {
					src: value,
					alt: '',
					style: {
						maxWidth: '200px',
						maxHeight: '150px',
						objectFit: 'cover',
						borderRadius: '8px',
						marginBottom: '12px'
					}
				}),
				el('div', { className: 'po-theme-image-picker__actions' },
					el(Button, {
						variant: 'secondary',
						isSmall: true,
						onClick: function() { setIsOpen(true); }
					}, __('Ändern', 'parkourone')),
					el(Button, {
						variant: 'link',
						isSmall: true,
						isDestructive: true,
						onClick: handleRemove,
						style: { marginLeft: '8px' }
					}, __('Entfernen', 'parkourone'))
				)
			),

			// Select button (when no image)
			!value && el(Button, {
				variant: 'secondary',
				onClick: function() { setIsOpen(true); }
			}, label),

			// Modal
			isOpen && el(Modal, {
				title: __('Bild auswählen', 'parkourone'),
				onRequestClose: function() { setIsOpen(false); },
				className: 'po-theme-image-picker-modal',
				style: { maxWidth: '800px', width: '90vw' }
			},
				el(TabPanel, {
					className: 'po-theme-image-picker__tabs',
					tabs: [
						{ name: 'theme', title: __('Theme Bilder', 'parkourone') },
						{ name: 'upload', title: __('Hochladen', 'parkourone') }
					]
				}, function(tab) {
					if (tab.name === 'theme') {
						return el('div', { className: 'po-theme-image-picker__theme-tab' },
							// Filters
							el('div', {
								className: 'po-theme-image-picker__filters',
								style: { display: 'flex', gap: '16px', marginBottom: '20px' }
							},
								el(SelectControl, {
									label: __('Kategorie', 'parkourone'),
									value: selectedCategory,
									options: Object.keys(categoryLabels).map(function(key) {
										return { label: categoryLabels[key], value: key };
									}),
									onChange: setSelectedCategory,
									__nextHasNoMarginBottom: true
								}),
								orientation === 'all' && el(SelectControl, {
									label: __('Format', 'parkourone'),
									value: selectedOrientation,
									options: Object.keys(orientationLabels).map(function(key) {
										return { label: orientationLabels[key], value: key };
									}),
									onChange: setSelectedOrientation,
									__nextHasNoMarginBottom: true
								})
							),

							// Image grid
							!images && el('div', { style: { textAlign: 'center', padding: '40px' } },
								el(Spinner)
							),

							images && filteredImages.length === 0 && el('p', {
								style: { textAlign: 'center', color: '#757575' }
							}, __('Keine Bilder in dieser Kategorie.', 'parkourone')),

							images && filteredImages.length > 0 && el('div', {
								className: 'po-theme-image-picker__grid',
								style: {
									display: 'grid',
									gridTemplateColumns: 'repeat(auto-fill, minmax(120px, 1fr))',
									gap: '12px',
									maxHeight: '400px',
									overflowY: 'auto',
									padding: '4px'
								}
							},
								filteredImages.map(function(img, index) {
									var isSelected = value === img.url;
									return el('button', {
										key: index,
										type: 'button',
										onClick: function() { handleSelect(img.url); },
										style: {
											padding: 0,
											border: isSelected ? '3px solid #2997ff' : '2px solid transparent',
											borderRadius: '8px',
											cursor: 'pointer',
											background: '#f0f0f0',
											overflow: 'hidden',
											aspectRatio: img.orientation === 'portrait' ? '5/7' : '3/2',
											transition: 'border-color 0.2s, transform 0.2s'
										},
										onMouseEnter: function(e) {
											if (!isSelected) e.target.style.borderColor = '#ccc';
										},
										onMouseLeave: function(e) {
											if (!isSelected) e.target.style.borderColor = 'transparent';
										}
									},
										el('img', {
											src: img.url,
											alt: img.filename,
											style: {
												width: '100%',
												height: '100%',
												objectFit: 'cover',
												display: 'block'
											}
										})
									);
								})
							)
						);
					}

					// Upload tab
					return el('div', {
						className: 'po-theme-image-picker__upload-tab',
						style: { textAlign: 'center', padding: '40px 20px' }
					},
						el(MediaUploadCheck, {},
							el(MediaUpload, {
								onSelect: handleMediaSelect,
								allowedTypes: ['image'],
								render: function(uploadProps) {
									return el(Button, {
										variant: 'primary',
										onClick: uploadProps.open,
										style: { padding: '12px 24px' }
									}, __('Aus Mediathek wählen', 'parkourone'));
								}
							})
						),
						el('p', {
							style: { marginTop: '16px', color: '#757575', fontSize: '13px' }
						}, __('Oder lade ein eigenes Bild hoch.', 'parkourone'))
					);
				})
			)
		);
	}

	// Export to window for use in blocks
	window.ParkouroneThemeImagePicker = ThemeImagePicker;

	// Also register as a format for potential SlotFill usage
	if (wp.plugins && wp.plugins.registerPlugin) {
		// Future: Could register as sidebar plugin
	}

})(window.wp);
