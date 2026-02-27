<?php
/**
 * Theme Images - Fallback-Bilder und Image Picker
 *
 * Stellt Funktionen bereit für:
 * - Zufällige Fallback-Bilder nach Altersgruppe und Orientierung
 * - Image Picker im Backend für Block-Editoren
 */

defined('ABSPATH') || exit;

/**
 * Mapping von Altersgruppen-Slugs zu Ordnernamen
 */
function parkourone_get_age_folder_map() {
	return [
		'minis'           => 'minis',
		'kids'            => 'kids',
		'juniors'         => 'juniors',
		'adults'          => 'adults',
		'juniors-adults'  => 'juniors',  // Kombi-Kategorie → Juniors
		'juniors & adults'=> 'juniors',  // Kombi-Kategorie → Juniors
		'seniors'         => 'adults',   // Fallback zu adults
		'masters'         => 'adults',   // Fallback zu adults
		'women'           => 'adults',   // Fallback zu adults
		'default'         => 'adults',   // Unbekannt → adults
	];
}

/**
 * Holt ein zufälliges Fallback-Bild
 *
 * @param string $age_category  Altersgruppe (minis, kids, juniors, adults, seniors)
 * @param string $orientation   'portrait' oder 'landscape'
 * @return string|null          URL zum Bild oder null
 */
function parkourone_get_fallback_image($age_category = 'adults', $orientation = 'landscape') {
	$folder_map = parkourone_get_age_folder_map();
	$folder = $folder_map[strtolower($age_category)] ?? 'adults';

	// Fallback-Kette: Kategorie → verwandte Kategorie → adults
	$related_map = [
		'minis' => 'kids',
		'kids' => 'minis',
		'seniors' => 'adults',
		'masters' => 'adults',
		'women' => 'adults',
	];

	$folders_to_try = [$folder];
	if (isset($related_map[$folder])) {
		$folders_to_try[] = $related_map[$folder];
	}
	if (!in_array('adults', $folders_to_try)) {
		$folders_to_try[] = 'adults';
	}

	$template_dir = get_template_directory();
	$template_uri = get_template_directory_uri();

	// Orientierungen: zuerst gewünschte, dann die andere als Fallback
	$other_orientation = ($orientation === 'landscape') ? 'portrait' : 'landscape';

	foreach ($folders_to_try as $try_folder) {
		// Zuerst gewünschte Orientierung, dann andere Orientierung derselben Kategorie
		foreach ([$orientation, $other_orientation] as $try_orientation) {
			$base_dir = $template_dir . '/assets/images/fallback/' . $try_orientation . '/' . $try_folder;

			if (!is_dir($base_dir)) continue;

			$images = glob($base_dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
			if (!empty($images)) {
				$random_image = $images[array_rand($images)];
				return $template_uri . '/assets/images/fallback/' . $try_orientation . '/' . $try_folder . '/' . basename($random_image);
			}
		}
	}

	return null;
}

/**
 * Holt alle verfügbaren Theme-Bilder für eine Kategorie
 *
 * @param string $age_category  Altersgruppe
 * @param string $orientation   'portrait', 'landscape' oder 'all'
 * @return array                Array mit Bild-URLs und Infos
 */
function parkourone_get_theme_images($age_category = 'adults', $orientation = 'all') {
	$folder_map = parkourone_get_age_folder_map();
	$folder = $folder_map[strtolower($age_category)] ?? 'adults';

	$images = [];
	$orientations = ($orientation === 'all') ? ['portrait', 'landscape'] : [$orientation];

	foreach ($orientations as $orient) {
		$base_dir = get_template_directory() . '/assets/images/fallback/' . $orient . '/' . $folder;
		$base_url = get_template_directory_uri() . '/assets/images/fallback/' . $orient . '/' . $folder;

		if (!is_dir($base_dir)) {
			continue;
		}

		$files = glob($base_dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);

		foreach ($files as $file) {
			$filename = basename($file);
			$images[] = [
				'url'         => $base_url . '/' . $filename,
				'filename'    => $filename,
				'orientation' => $orient,
				'category'    => $folder,
			];
		}
	}

	return $images;
}

/**
 * Holt alle Theme-Bilder gruppiert nach Kategorie
 * Für den Image Picker im Backend
 *
 * @return array Gruppierte Bilder
 */
function parkourone_get_all_theme_images() {
	$categories = ['minis', 'kids', 'juniors', 'adults', 'seniors'];
	$result = [];

	foreach ($categories as $category) {
		$result[$category] = [
			'portrait'  => parkourone_get_theme_images($category, 'portrait'),
			'landscape' => parkourone_get_theme_images($category, 'landscape'),
		];
	}

	return $result;
}

/**
 * AJAX Handler für Theme Images
 */
function parkourone_ajax_get_theme_images() {
	$category = sanitize_text_field($_POST['category'] ?? 'all');
	$orientation = sanitize_text_field($_POST['orientation'] ?? 'all');

	if ($category === 'all') {
		$images = parkourone_get_all_theme_images();
	} else {
		$images = parkourone_get_theme_images($category, $orientation);
	}

	wp_send_json_success($images);
}
add_action('wp_ajax_parkourone_get_theme_images', 'parkourone_ajax_get_theme_images');

/**
 * Registriert die Theme Image Picker Komponente für Gutenberg
 */
function parkourone_register_theme_image_picker() {
	wp_register_script(
		'parkourone-theme-image-picker',
		get_template_directory_uri() . '/assets/js/theme-image-picker.js',
		['wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch'],
		filemtime(get_template_directory() . '/assets/js/theme-image-picker.js'),
		true
	);

	wp_localize_script('parkourone-theme-image-picker', 'parkouroneThemeImages', [
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce'   => wp_create_nonce('parkourone_theme_images'),
		'images'  => parkourone_get_all_theme_images(),
	]);
}
add_action('enqueue_block_editor_assets', 'parkourone_register_theme_image_picker');

/**
 * REST API Endpoint für Theme Images
 */
function parkourone_register_theme_images_rest() {
	register_rest_route('parkourone/v1', '/theme-images', [
		'methods'  => 'GET',
		'callback' => function($request) {
			$category = $request->get_param('category') ?? 'all';
			$orientation = $request->get_param('orientation') ?? 'all';

			if ($category === 'all') {
				return parkourone_get_all_theme_images();
			}

			return parkourone_get_theme_images($category, $orientation);
		},
		'permission_callback' => function() {
			return current_user_can('edit_posts');
		},
	]);
}
add_action('rest_api_init', 'parkourone_register_theme_images_rest');
