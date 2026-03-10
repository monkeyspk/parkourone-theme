<?php
/**
 * Coach Avatar Cache
 * Externe AcademyBoard-Bilder lokal cachen + WebP konvertieren
 * - Download + Resize auf 300x300, 80x80, max 600px
 * - WebP-Konvertierung
 * - Lokale Auslieferung aus /wp-content/uploads/coach-avatars/
 * - Auto-Refresh bei URL-Änderung
 */
defined('ABSPATH') || exit;

define('PARKOURONE_AVATAR_DIR', 'coach-avatars');

// =====================================================
// Kern: Avatar herunterladen + cachen
// =====================================================

function parkourone_cache_coach_avatar($url, $coach_id) {
	if (empty($url) || empty($coach_id)) return false;

	$upload_dir = wp_get_upload_dir();
	$avatar_dir = $upload_dir['basedir'] . '/' . PARKOURONE_AVATAR_DIR;
	$avatar_url_base = $upload_dir['baseurl'] . '/' . PARKOURONE_AVATAR_DIR;

	// Verzeichnis erstellen
	if (!is_dir($avatar_dir)) {
		wp_mkdir_p($avatar_dir);
	}

	// Hash der URL für Dateinamen (ändert sich wenn Bild sich ändert)
	$url_hash = substr(md5($url), 0, 8);
	$base_name = 'coach-' . $coach_id . '-' . $url_hash;

	// Transient-Lock: Parallele Downloads verhindern
	$lock_key = 'po_avatar_lock_' . $coach_id;
	if (get_transient($lock_key)) {
		// Bereits in Bearbeitung — vorhandenen Cache oder externe URL nutzen
		$existing = get_post_meta($coach_id, '_coach_avatar_cache', true);
		if (!empty($existing['sizes'])) {
			return $existing;
		}
		return false;
	}
	set_transient($lock_key, true, 60); // 60s Lock

	// Bild herunterladen
	$response = wp_remote_get($url, [
		'timeout' => 15,
		'sslverify' => false
	]);

	if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
		delete_transient($lock_key);
		return false;
	}

	$image_data = wp_remote_retrieve_body($response);
	if (empty($image_data)) {
		delete_transient($lock_key);
		return false;
	}

	// Temporäre Datei schreiben
	$tmp_file = $avatar_dir . '/' . $base_name . '-tmp';
	if (file_put_contents($tmp_file, $image_data) === false) {
		delete_transient($lock_key);
		return false;
	}

	// Größen erzeugen
	$sizes = [
		'300x300' => 300,
		'80x80' => 80,
		'full' => 600
	];

	$cached_sizes = [];

	foreach ($sizes as $label => $max_dim) {
		$result = parkourone_resize_and_convert_avatar($tmp_file, $avatar_dir, $base_name, $label, $max_dim);
		if ($result) {
			$cached_sizes[$label] = $avatar_url_base . '/' . $result;
		}
	}

	// Temp-Datei aufräumen
	@unlink($tmp_file);

	if (empty($cached_sizes)) {
		delete_transient($lock_key);
		return false;
	}

	// Alte Cache-Dateien aufräumen (anderer Hash)
	parkourone_cleanup_old_avatar_files($avatar_dir, $coach_id, $url_hash);

	// Metadata speichern
	$cache_data = [
		'source_url' => $url,
		'cached_at' => time(),
		'sizes' => $cached_sizes
	];
	update_post_meta($coach_id, '_coach_avatar_cache', $cache_data);

	delete_transient($lock_key);
	return $cache_data;
}

// =====================================================
// Resize + WebP-Konvertierung
// =====================================================

function parkourone_resize_and_convert_avatar($source, $dir, $base_name, $label, $max_dim) {
	$editor = wp_get_image_editor($source);
	if (is_wp_error($editor)) {
		// Fallback: Datei as-is kopieren (immerhin lokal)
		$fallback_name = $base_name . '-' . $label . '.jpg';
		if (@copy($source, $dir . '/' . $fallback_name)) {
			return $fallback_name;
		}
		return false;
	}

	$size = $editor->get_size();
	$w = $size['width'];
	$h = $size['height'];

	if ($label === 'full') {
		// Full: nur verkleinern wenn größer als max_dim
		if ($w > $max_dim || $h > $max_dim) {
			$editor->resize($max_dim, $max_dim, false);
		}
	} else {
		// Quadratisch: Crop auf Mitte
		$min_side = min($w, $h);
		$crop_x = ($w - $min_side) / 2;
		$crop_y = ($h - $min_side) / 2;
		$editor->crop($crop_x, $crop_y, $min_side, $min_side, $max_dim, $max_dim);
	}

	// Zuerst als JPG speichern
	$jpg_name = $base_name . '-' . $label . '.jpg';
	$jpg_path = $dir . '/' . $jpg_name;
	$editor->set_quality(85);
	$saved = $editor->save($jpg_path, 'image/jpeg');

	if (is_wp_error($saved)) {
		return false;
	}

	// WebP konvertieren (wenn WebP-Converter verfügbar)
	if (function_exists('parkourone_webp_convert')) {
		$webp_result = parkourone_webp_convert($jpg_path);
		if ($webp_result) {
			// WebP existiert — verwende WebP-URL
			$webp_name = $base_name . '-' . $label . '.webp';
			// JPG als Fallback behalten
			return $webp_name;
		}
	}

	return $jpg_name;
}

// =====================================================
// Alte Cache-Dateien aufräumen
// =====================================================

function parkourone_cleanup_old_avatar_files($dir, $coach_id, $current_hash) {
	$pattern = $dir . '/coach-' . $coach_id . '-*';
	$files = glob($pattern);
	if (!$files) return;

	foreach ($files as $file) {
		$basename = basename($file);
		// Nur löschen wenn anderer Hash (nicht aktueller, nicht tmp)
		if (strpos($basename, 'coach-' . $coach_id . '-' . $current_hash) === false
			&& strpos($basename, '-tmp') === false) {
			@unlink($file);
		}
	}
}

// =====================================================
// Accessor: Coach-Avatar-URL holen
// =====================================================

function parkourone_get_coach_avatar_url($coach_id, $size = '300x300') {
	if (empty($coach_id)) return false;

	$cache = get_post_meta($coach_id, '_coach_avatar_cache', true);
	$api_image = get_post_meta($coach_id, '_coach_api_image', true);

	// Cache vorhanden und gültig?
	if (!empty($cache) && is_array($cache)) {
		// Prüfe ob Quell-URL sich geändert hat
		$cache_valid = true;
		if (!empty($api_image) && ($cache['source_url'] ?? '') !== $api_image) {
			$cache_valid = false;
		}

		// Prüfe ob Datei existiert
		if ($cache_valid && !empty($cache['sizes'][$size])) {
			$upload_dir = wp_get_upload_dir();
			$url = $cache['sizes'][$size];
			$relative = str_replace($upload_dir['baseurl'], '', $url);
			$file_path = $upload_dir['basedir'] . $relative;

			if (file_exists($file_path)) {
				return $url;
			}
			$cache_valid = false;
		}
	}

	// Cache-Miss: Im Frontend NICHT downloaden (würde Seite blockieren)
	// Stattdessen externe URL als Fallback zurückgeben
	if (!empty($api_image)) {
		// Nur im Admin/Cron cachen, nie im Frontend
		if (is_admin() || wp_doing_cron()) {
			$result = parkourone_cache_coach_avatar($api_image, $coach_id);
			if ($result && !empty($result['sizes'][$size])) {
				return $result['sizes'][$size];
			}
		}
		return $api_image;
	}

	return false;
}

// =====================================================
// High-Level Accessor: Einheitlich für alle Blocks
// =====================================================

function parkourone_get_coach_display_image($coach_id, $size = '300x300') {
	if (empty($coach_id)) return '';

	// 1. Manuelles Profilbild hat Priorität
	$profile_image = get_post_meta($coach_id, '_coach_profile_image', true);
	if (!empty($profile_image)) {
		return $profile_image;
	}

	// 2. Cached API-Bild
	$cached = parkourone_get_coach_avatar_url($coach_id, $size);
	if ($cached) {
		return $cached;
	}

	// 3. Fallback: Raw API-URL
	$api_image = get_post_meta($coach_id, '_coach_api_image', true);
	return $api_image ?: '';
}

/**
 * Coach-Display-Image über Namen (für Event-basierte Blocks)
 * Lookup: Coach-Name → Coach-ID → Cache
 */
function parkourone_get_coach_display_image_by_name($coach_name, $size = '300x300', $event_image_url = '') {
	if (empty($coach_name)) return $event_image_url;

	// Statischer Cache: Vermeidet wiederholte DB-Queries pro Request
	static $cache = [];
	$cache_key = $coach_name . '|' . $size;
	if (isset($cache[$cache_key])) {
		return $cache[$cache_key] ?: $event_image_url;
	}

	// Coach-Post finden
	if (function_exists('parkourone_get_coach_by_name')) {
		$coach_data = parkourone_get_coach_by_name($coach_name);
		if ($coach_data && !empty($coach_data['id'])) {
			$image = parkourone_get_coach_display_image($coach_data['id'], $size);
			if (!empty($image)) {
				$cache[$cache_key] = $image;
				return $image;
			}
		}
	}

	$cache[$cache_key] = false;
	return $event_image_url;
}

// =====================================================
// Hooks: Bei Meta-Änderung neu cachen
// =====================================================

function parkourone_on_coach_image_updated($meta_id, $object_id, $meta_key, $meta_value) {
	if ($meta_key !== '_coach_api_image') return;
	if (empty($meta_value) || get_post_type($object_id) !== 'coach') return;

	// Asynchron neu cachen
	parkourone_cache_coach_avatar($meta_value, $object_id);
}
add_action('updated_post_meta', 'parkourone_on_coach_image_updated', 10, 4);
add_action('added_post_meta', 'parkourone_on_coach_image_updated', 10, 4);

// =====================================================
// Hook: Nach Coach-Sync alle stale Caches refreshen
// =====================================================

function parkourone_refresh_stale_avatars() {
	$coaches = get_posts([
		'post_type' => 'coach',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'fields' => 'ids'
	]);

	foreach ($coaches as $coach_id) {
		$api_image = get_post_meta($coach_id, '_coach_api_image', true);
		if (empty($api_image)) continue;

		$cache = get_post_meta($coach_id, '_coach_avatar_cache', true);

		// Nur cachen wenn nötig (kein Cache oder URL geändert)
		if (empty($cache) || ($cache['source_url'] ?? '') !== $api_image) {
			parkourone_cache_coach_avatar($api_image, $coach_id);
		}
	}
}
add_action('parkourone_coaches_synced', 'parkourone_refresh_stale_avatars');

// =====================================================
// Cron: Wöchentlich alle Avatare auffrischen
// =====================================================

function parkourone_schedule_avatar_cron() {
	if (!wp_next_scheduled('parkourone_refresh_coach_avatars')) {
		wp_schedule_event(time(), 'weekly', 'parkourone_refresh_coach_avatars');
	}
}
add_action('init', 'parkourone_schedule_avatar_cron');

function parkourone_cron_refresh_avatars() {
	parkourone_refresh_stale_avatars();
}
add_action('parkourone_refresh_coach_avatars', 'parkourone_cron_refresh_avatars');

// =====================================================
// Cleanup: Bei Coach-Löschung Cache aufräumen
// =====================================================

function parkourone_cleanup_coach_avatar_cache($post_id) {
	if (get_post_type($post_id) !== 'coach') return;

	$upload_dir = wp_get_upload_dir();
	$avatar_dir = $upload_dir['basedir'] . '/' . PARKOURONE_AVATAR_DIR;

	$pattern = $avatar_dir . '/coach-' . $post_id . '-*';
	$files = glob($pattern);
	if ($files) {
		foreach ($files as $file) {
			@unlink($file);
		}
	}

	delete_post_meta($post_id, '_coach_avatar_cache');
}
add_action('before_delete_post', 'parkourone_cleanup_coach_avatar_cache');

// =====================================================
// Bulk-Cache: Admin-Aktion
// =====================================================

function parkourone_bulk_cache_all_coach_avatars() {
	check_ajax_referer('parkourone_avatar_bulk', 'nonce');
	if (!current_user_can('manage_options')) wp_die('Keine Berechtigung');

	$offset = (int) ($_POST['offset'] ?? 0);
	$batch_size = 5; // Kleiner weil Downloads + Resize

	$coaches = get_posts([
		'post_type' => 'coach',
		'posts_per_page' => $batch_size,
		'offset' => $offset,
		'post_status' => 'publish',
		'meta_query' => [
			[
				'key' => '_coach_api_image',
				'value' => '',
				'compare' => '!='
			]
		]
	]);

	$cached = 0;
	$skipped = 0;

	foreach ($coaches as $coach) {
		$api_image = get_post_meta($coach->ID, '_coach_api_image', true);
		if (empty($api_image)) {
			$skipped++;
			continue;
		}

		$result = parkourone_cache_coach_avatar($api_image, $coach->ID);
		if ($result) {
			$cached++;
		} else {
			$skipped++;
		}
	}

	// Total mit API-Bild zählen
	$total = (int) (new WP_Query([
		'post_type' => 'coach',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_query' => [['key' => '_coach_api_image', 'value' => '', 'compare' => '!=']],
		'fields' => 'ids'
	]))->found_posts;

	$new_offset = $offset + $batch_size;
	$done = $new_offset >= $total || empty($coaches);

	wp_send_json_success([
		'cached' => $cached,
		'skipped' => $skipped,
		'offset' => $new_offset,
		'total' => $total,
		'done' => $done,
		'percent' => $total > 0 ? min(100, round($new_offset / $total * 100)) : 100
	]);
}
add_action('wp_ajax_parkourone_bulk_cache_avatars', 'parkourone_bulk_cache_all_coach_avatars');
