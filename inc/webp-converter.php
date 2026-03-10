<?php
/**
 * WebP Converter
 * Automatische WebP-Konvertierung für Theme-Bilder
 * - Bei Upload: .webp neben Original erzeugen
 * - Bei Ausgabe: <img> in <picture> wrappen
 * - GD als Engine, Imagick als Fallback
 */
defined('ABSPATH') || exit;

// =====================================================
// Feature-Check: WebP-Support vorhanden?
// =====================================================

function parkourone_webp_supported() {
	static $supported = null;
	if ($supported !== null) return $supported;

	if (function_exists('imagewebp') && function_exists('imagecreatefromjpeg')) {
		$supported = true;
	} elseif (class_exists('Imagick') && in_array('WEBP', \Imagick::queryFormats())) {
		$supported = true;
	} else {
		$supported = false;
	}
	return $supported;
}

function parkourone_webp_engine() {
	if (function_exists('imagewebp') && function_exists('imagecreatefromjpeg')) {
		return 'gd';
	}
	if (class_exists('Imagick') && in_array('WEBP', \Imagick::queryFormats())) {
		return 'imagick';
	}
	return false;
}

// Komplett deaktivieren wenn kein WebP-Support
if (!parkourone_webp_supported()) {
	return;
}

// =====================================================
// Kern: JPG/PNG → WebP konvertieren
// =====================================================

function parkourone_webp_convert($source_path, $quality = null) {
	if ($quality === null) {
		$quality = (int) get_option('parkourone_webp_quality', 80);
	}

	if (!file_exists($source_path)) return false;

	$ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
	if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return false;

	$webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source_path);

	// Skip wenn WebP neuer als Quelle
	if (file_exists($webp_path) && filemtime($webp_path) >= filemtime($source_path)) {
		return $webp_path;
	}

	$engine = parkourone_webp_engine();

	if ($engine === 'gd') {
		return parkourone_webp_convert_gd($source_path, $webp_path, $ext, $quality);
	} elseif ($engine === 'imagick') {
		return parkourone_webp_convert_imagick($source_path, $webp_path, $quality);
	}

	return false;
}

function parkourone_webp_convert_gd($source_path, $webp_path, $ext, $quality) {
	if ($ext === 'png') {
		$image = @imagecreatefrompng($source_path);
		if (!$image) return false;
		// PNG-Transparenz beibehalten
		imagepalettetotruecolor($image);
		imagealphablending($image, true);
		imagesavealpha($image, true);
	} else {
		$image = @imagecreatefromjpeg($source_path);
		if (!$image) return false;
	}

	$result = @imagewebp($image, $webp_path, $quality);
	imagedestroy($image);

	if ($result && file_exists($webp_path)) {
		// Sanity-Check: WebP sollte nicht größer als Original sein
		if (filesize($webp_path) >= filesize($source_path)) {
			@unlink($webp_path);
			return false;
		}
		return $webp_path;
	}

	return false;
}

function parkourone_webp_convert_imagick($source_path, $webp_path, $quality) {
	try {
		$imagick = new \Imagick($source_path);
		$imagick->setImageFormat('webp');
		$imagick->setImageCompressionQuality($quality);
		$imagick->setOption('webp:method', '4');
		$result = $imagick->writeImage($webp_path);
		$imagick->clear();
		$imagick->destroy();

		if ($result && file_exists($webp_path)) {
			if (filesize($webp_path) >= filesize($source_path)) {
				@unlink($webp_path);
				return false;
			}
			return $webp_path;
		}
	} catch (\Exception $e) {
		// Fehler still ignorieren
	}

	return false;
}

// =====================================================
// URL-Resolver: Gibt WebP-URL zurück oder false
// =====================================================

function parkourone_webp_get_url($image_url) {
	if (empty($image_url)) return false;

	// Uploads-Verzeichnis prüfen
	$upload_dir = wp_get_upload_dir();
	$base_url = $upload_dir['baseurl'];
	$base_dir = $upload_dir['basedir'];

	if (strpos($image_url, $base_url) !== false) {
		$relative = str_replace($base_url, '', $image_url);
		$source_path = $base_dir . $relative;
		$webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source_path);

		if (file_exists($webp_path)) {
			return preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_url);
		}
		return false;
	}

	// Theme-Assets prüfen (Fallback-Bilder, Hero-Defaults etc.)
	$theme_url = get_template_directory_uri();
	$theme_dir = get_template_directory();

	if (strpos($image_url, $theme_url) !== false) {
		$relative = str_replace($theme_url, '', $image_url);
		$source_path = $theme_dir . $relative;
		$webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source_path);

		if (file_exists($webp_path)) {
			return preg_replace('/\.(jpe?g|png)$/i', '.webp', $image_url);
		}
		return false;
	}

	return false;
}

// =====================================================
// Upload-Hook: WebP bei Upload erzeugen
// =====================================================

function parkourone_webp_on_upload($metadata, $attachment_id) {
	$file = get_attached_file($attachment_id);
	if (!$file) return $metadata;

	$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return $metadata;

	// Original konvertieren
	parkourone_webp_convert($file);

	// Alle Größen konvertieren
	if (!empty($metadata['sizes'])) {
		$dir = dirname($file);
		foreach ($metadata['sizes'] as $size) {
			$size_file = $dir . '/' . $size['file'];
			if (file_exists($size_file)) {
				parkourone_webp_convert($size_file);
			}
		}
	}

	return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'parkourone_webp_on_upload', 10, 2);

// =====================================================
// Delete-Hook: WebP-Dateien aufräumen
// =====================================================

function parkourone_webp_on_delete($attachment_id) {
	$file = get_attached_file($attachment_id);
	if (!$file) return;

	$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return;

	// Original-WebP löschen
	$webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
	if (file_exists($webp_path)) {
		@unlink($webp_path);
	}

	// Alle Größen-WebPs löschen
	$metadata = wp_get_attachment_metadata($attachment_id);
	if (!empty($metadata['sizes'])) {
		$dir = dirname($file);
		foreach ($metadata['sizes'] as $size) {
			$size_webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $dir . '/' . $size['file']);
			if (file_exists($size_webp)) {
				@unlink($size_webp);
			}
		}
	}
}
add_action('delete_attachment', 'parkourone_webp_on_delete');

// =====================================================
// Output-Filter: <img> in <picture> wrappen
// =====================================================

function parkourone_webp_wrap_img_tag($content) {
	if (empty($content)) return $content;

	// Keine <img>-Tags? Abbruch.
	if (strpos($content, '<img') === false) return $content;

	// Bestehende <picture>-Blöcke: WebP-Sources ergänzen, dann schützen
	$pictures = [];
	$content = preg_replace_callback('/<picture[^>]*>.*?<\/picture>/is', function($m) use (&$pictures) {
		$picture_html = $m[0];
		// Für jede <source> ohne type="image/webp": WebP-Version davor einfügen
		$picture_html = preg_replace_callback(
			'/<source\s([^>]*srcset=["\'])([^"\']+\.(jpe?g|png))(["\'][^>]*)>/i',
			function($sm) {
				$webp = parkourone_webp_get_url($sm[2]);
				if (!$webp) return $sm[0];
				// WebP-Source vor der Original-Source einfügen
				$media_attr = '';
				if (preg_match('/media=["\']([^"\']+)["\']/i', $sm[0], $media)) {
					$media_attr = ' media="' . $media[1] . '"';
				}
				return '<source type="image/webp"' . $media_attr . ' srcset="' . esc_url($webp) . '">' . $sm[0];
			},
			$picture_html
		);
		// Auch das <img> im <picture> berücksichtigen
		$picture_html = preg_replace_callback(
			'/<img\s([^>]*src=["\'])([^"\']+\.(jpe?g|png))(["\'][^>]*)>/i',
			function($im) use ($picture_html) {
				// Nur wenn noch keine WebP-Source im Picture vorhanden
				if (strpos($picture_html, 'image/webp') !== false) return $im[0];
				$webp = parkourone_webp_get_url($im[2]);
				if (!$webp) return $im[0];
				return '<source type="image/webp" srcset="' . esc_url($webp) . '">' . $im[0];
			},
			$picture_html
		);
		$key = '<!--PO_PIC_' . count($pictures) . '-->';
		$pictures[$key] = $picture_html;
		return $key;
	}, $content);

	// Alle verbleibenden <img> mit JPG/PNG wrappen
	$content = preg_replace_callback(
		'/<img\s[^>]*src=["\']([^"\']+\.(jpe?g|png))["\'][^>]*>/i',
		function ($match) {
			$full_tag = $match[0];
			$src = $match[1];

			$webp_url = parkourone_webp_get_url($src);
			if (!$webp_url) return $full_tag;

			// srcset extrahieren falls vorhanden
			$srcset_webp = '';
			if (preg_match('/srcset=["\']([^"\']+)["\']/i', $full_tag, $srcset_match)) {
				$srcset = $srcset_match[1];
				// Jede URL im srcset in WebP umwandeln
				$srcset_webp = preg_replace_callback(
					'/(\S+\.(jpe?g|png))/i',
					function ($m) {
						$webp = parkourone_webp_get_url($m[1]);
						return $webp ?: $m[1];
					},
					$srcset
				);
			}

			$source_attrs = 'type="image/webp"';
			if (!empty($srcset_webp)) {
				$source_attrs .= ' srcset="' . esc_attr($srcset_webp) . '"';
			} else {
				$source_attrs .= ' srcset="' . esc_url($webp_url) . '"';
			}

			// sizes-Attribut übernehmen
			if (preg_match('/sizes=["\']([^"\']+)["\']/i', $full_tag, $sizes_match)) {
				$source_attrs .= ' sizes="' . esc_attr($sizes_match[1]) . '"';
			}

			return '<picture><source ' . $source_attrs . '>' . $full_tag . '</picture>';
		},
		$content
	);

	// Geschützte <picture>-Blöcke wiederherstellen
	if (!empty($pictures)) {
		$content = str_replace(array_keys($pictures), array_values($pictures), $content);
	}

	return $content;
}
// Output-Buffer: Gesamte Seite filtern (nicht nur the_content)
// Damit werden auch Block-Renders, Template-Parts, Fallback-Bilder etc. erfasst
function parkourone_webp_start_ob() {
	if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
	ob_start('parkourone_webp_wrap_img_tag');
}
function parkourone_webp_end_ob() {
	if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
	if (ob_get_level() > 0) {
		ob_end_flush();
	}
}
add_action('template_redirect', 'parkourone_webp_start_ob', 1);
add_action('shutdown', 'parkourone_webp_end_ob', 999);

// =====================================================
// Bulk-Konvertierung: AJAX-Handler
// =====================================================

function parkourone_webp_bulk_convert() {
	check_ajax_referer('parkourone_webp_bulk', 'nonce');
	if (!current_user_can('manage_options')) wp_die('Keine Berechtigung');

	@set_time_limit(120); // Mehr Zeit für Konvertierung

	$offset = (int) ($_POST['offset'] ?? 0);
	$batch_size = 5; // Klein halten — jedes Bild hat viele Größen

	$attachments = get_posts([
		'post_type' => 'attachment',
		'post_mime_type' => ['image/jpeg', 'image/png'],
		'posts_per_page' => $batch_size,
		'offset' => $offset,
		'post_status' => 'inherit',
		'orderby' => 'ID',
		'order' => 'ASC'
	]);

	$converted = 0;
	$skipped = 0;
	$errors = 0;

	foreach ($attachments as $attachment) {
		$file = get_attached_file($attachment->ID);
		if (!$file || !file_exists($file)) {
			$skipped++;
			continue;
		}

		$result = parkourone_webp_convert($file);
		if ($result) {
			$converted++;
		} else {
			$skipped++;
		}

		// Auch alle Größen konvertieren
		$metadata = wp_get_attachment_metadata($attachment->ID);
		if (!empty($metadata['sizes'])) {
			$dir = dirname($file);
			foreach ($metadata['sizes'] as $size) {
				$size_file = $dir . '/' . $size['file'];
				if (file_exists($size_file)) {
					parkourone_webp_convert($size_file);
				}
			}
		}
	}

	// Total zählen
	$total = (int) wp_count_attachments()->{'image/jpeg'} + (int) wp_count_attachments()->{'image/png'};
	$new_offset = $offset + $batch_size;
	$done = $new_offset >= $total;

	wp_send_json_success([
		'converted' => $converted,
		'skipped' => $skipped,
		'offset' => $new_offset,
		'total' => $total,
		'done' => $done,
		'percent' => $total > 0 ? min(100, round($new_offset / $total * 100)) : 100
	]);
}
add_action('wp_ajax_parkourone_webp_bulk_convert', 'parkourone_webp_bulk_convert');

// =====================================================
// Stats: Anzahl konvertierter Bilder
// =====================================================

function parkourone_webp_get_stats() {
	$upload_dir = wp_get_upload_dir();
	$base_dir = $upload_dir['basedir'];

	$total_images = (int) wp_count_attachments()->{'image/jpeg'} + (int) wp_count_attachments()->{'image/png'};

	// Zähle vorhandene WebP-Dateien (nur Originale, nicht Größen)
	$webp_count = 0;
	$attachments = get_posts([
		'post_type' => 'attachment',
		'post_mime_type' => ['image/jpeg', 'image/png'],
		'posts_per_page' => -1,
		'post_status' => 'inherit',
		'fields' => 'ids'
	]);

	foreach ($attachments as $id) {
		$file = get_attached_file($id);
		if (!$file) continue;
		$webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
		if (file_exists($webp)) {
			$webp_count++;
		}
	}

	return [
		'total' => $total_images,
		'converted' => $webp_count,
		'percent' => $total_images > 0 ? round($webp_count / $total_images * 100) : 0
	];
}

// =====================================================
// Theme-Assets konvertieren (Fallback-Bilder etc.)
// =====================================================

function parkourone_webp_convert_theme_assets() {
	$theme_dir = get_template_directory();
	$dirs = [
		$theme_dir . '/assets/images/fallback',
		$theme_dir . '/assets/images/hero',
		$theme_dir . '/assets/images/angebote-placeholder',
	];

	$converted = 0;
	foreach ($dirs as $dir) {
		if (!is_dir($dir)) continue;
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
		foreach ($files as $file) {
			if ($file->isDir()) continue;
			$ext = strtolower($file->getExtension());
			if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;
			if (parkourone_webp_convert($file->getPathname())) {
				$converted++;
			}
		}
	}
	return $converted;
}

// Bei Theme-Aktivierung oder Update: Theme-Assets konvertieren
function parkourone_webp_on_theme_switch() {
	if (!parkourone_webp_supported()) return;
	parkourone_webp_convert_theme_assets();
}
add_action('after_switch_theme', 'parkourone_webp_on_theme_switch');

// Einmalig bei erstem Laden konvertieren (wenn noch nicht geschehen)
function parkourone_webp_maybe_convert_assets() {
	if (get_option('parkourone_webp_assets_converted')) return;

	parkourone_webp_convert_theme_assets();
	update_option('parkourone_webp_assets_converted', true);
}
add_action('admin_init', 'parkourone_webp_maybe_convert_assets');

// AJAX: Theme-Assets konvertieren
function parkourone_webp_convert_assets_ajax() {
	check_ajax_referer('parkourone_webp_bulk', 'nonce');
	if (!current_user_can('manage_options')) wp_die('Keine Berechtigung');

	$converted = parkourone_webp_convert_theme_assets();
	update_option('parkourone_webp_assets_converted', true);

	wp_send_json_success(['converted' => $converted]);
}
add_action('wp_ajax_parkourone_webp_convert_assets', 'parkourone_webp_convert_assets_ajax');

// =====================================================
// Admin-UI: WebP-Tab auf System-Seite
// =====================================================

function parkourone_webp_admin_page($embedded = false) {
	if (!current_user_can('manage_options')) return;

	// Quality-Einstellung speichern
	if (isset($_POST['parkourone_webp_quality']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'parkourone_webp_settings')) {
		$quality = max(60, min(95, (int) $_POST['parkourone_webp_quality']));
		update_option('parkourone_webp_quality', $quality);
		echo '<div class="notice notice-success"><p>Qualitätseinstellung gespeichert.</p></div>';
	}

	$engine = parkourone_webp_engine();
	$quality = (int) get_option('parkourone_webp_quality', 80);
	$stats = parkourone_webp_get_stats();

	if (!$embedded): ?>
	<div class="wrap">
		<h1>WebP Konvertierung</h1>
	<?php endif; ?>

	<div style="background: #fff; padding: 24px; border-radius: 8px; max-width: 700px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">

		<!-- Engine Status -->
		<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
			<div style="width: 64px; height: 64px; border-radius: 50%; background: #d4edda; display: flex; align-items: center; justify-content: center; font-size: 28px;">
				<?php echo $engine === 'gd' ? 'GD' : 'IM'; ?>
			</div>
			<div>
				<h2 style="margin: 0; font-size: 20px;">WebP aktiv (<?php echo $engine === 'gd' ? 'PHP GD' : 'Imagick'; ?>)</h2>
				<p style="margin: 4px 0 0; color: #666;">
					Bilder werden automatisch als WebP konvertiert und ausgeliefert.
				</p>
			</div>
		</div>

		<!-- Stats -->
		<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
			<div style="text-align: center; padding: 16px; background: #f8f9fa; border-radius: 8px;">
				<div style="font-size: 28px; font-weight: 700; color: #1d2327;"><?php echo $stats['total']; ?></div>
				<div style="font-size: 13px; color: #666;">JPG/PNG Bilder</div>
			</div>
			<div style="text-align: center; padding: 16px; background: #f8f9fa; border-radius: 8px;">
				<div style="font-size: 28px; font-weight: 700; color: #00a32a;"><?php echo $stats['converted']; ?></div>
				<div style="font-size: 13px; color: #666;">WebP vorhanden</div>
			</div>
			<div style="text-align: center; padding: 16px; background: #f8f9fa; border-radius: 8px;">
				<div style="font-size: 28px; font-weight: 700; color: <?php echo $stats['percent'] >= 90 ? '#00a32a' : '#dba617'; ?>;"><?php echo $stats['percent']; ?>%</div>
				<div style="font-size: 13px; color: #666;">Konvertiert</div>
			</div>
		</div>

		<!-- Quality Setting -->
		<form method="post" style="margin-bottom: 24px;">
			<?php wp_nonce_field('parkourone_webp_settings'); ?>
			<label for="parkourone_webp_quality" style="display: block; font-weight: 600; margin-bottom: 8px;">
				Qualität: <span id="quality-value"><?php echo $quality; ?></span>%
			</label>
			<div style="display: flex; align-items: center; gap: 12px;">
				<span style="font-size: 13px; color: #666;">60</span>
				<input type="range" id="parkourone_webp_quality" name="parkourone_webp_quality"
					min="60" max="95" value="<?php echo $quality; ?>"
					style="flex: 1;"
					oninput="document.getElementById('quality-value').textContent = this.value">
				<span style="font-size: 13px; color: #666;">95</span>
				<button type="submit" class="button">Speichern</button>
			</div>
			<p style="font-size: 12px; color: #999; margin-top: 4px;">
				80 = guter Kompromiss aus Qualität und Dateigröße. Niedriger = kleinere Dateien.
			</p>
		</form>

		<!-- Bulk Convert -->
		<div style="border-top: 1px solid #eee; padding-top: 20px;">
			<h3 style="margin-top: 0;">Bestehende Bilder konvertieren</h3>
			<?php if ($stats['percent'] >= 100): ?>
				<p style="color: #00a32a; font-weight: 600;">Alle Bilder sind bereits konvertiert.</p>
			<?php else: ?>
				<p style="color: #666; font-size: 14px; margin-bottom: 12px;">
					<?php echo $stats['total'] - $stats['converted']; ?> Bilder noch nicht konvertiert.
					Die Konvertierung läuft im Hintergrund — du kannst die Seite offen lassen.
				</p>
				<button type="button" id="po-webp-bulk-btn" class="button button-primary" onclick="parkouroneWebpBulk()">
					Alle konvertieren
				</button>
				<div id="po-webp-progress" style="display: none; margin-top: 16px;">
					<div style="background: #f0f0f1; border-radius: 4px; overflow: hidden; height: 24px; position: relative;">
						<div id="po-webp-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
						<span id="po-webp-percent" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-size: 13px; font-weight: 600;">0%</span>
					</div>
					<p id="po-webp-status" style="font-size: 13px; color: #666; margin-top: 8px;"></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script>
	function parkouroneWebpBulk() {
		const btn = document.getElementById('po-webp-bulk-btn');
		const progress = document.getElementById('po-webp-progress');
		const bar = document.getElementById('po-webp-bar');
		const percent = document.getElementById('po-webp-percent');
		const status = document.getElementById('po-webp-status');

		btn.disabled = true;
		btn.textContent = 'Konvertiere...';
		progress.style.display = 'block';

		let retries = 0;
		const maxRetries = 3;

		function runBatch(offset) {
			const formData = new FormData();
			formData.append('action', 'parkourone_webp_bulk_convert');
			formData.append('nonce', '<?php echo wp_create_nonce('parkourone_webp_bulk'); ?>');
			formData.append('offset', offset);

			fetch(ajaxurl, { method: 'POST', body: formData })
				.then(r => r.json())
				.then(data => {
					retries = 0;
					if (data.success) {
						const d = data.data;
						bar.style.width = d.percent + '%';
						percent.textContent = d.percent + '%';
						status.textContent = d.offset + ' von ' + d.total + ' verarbeitet...';

						if (!d.done) {
							runBatch(d.offset);
						} else {
							btn.textContent = 'Fertig!';
							btn.style.background = '#00a32a';
							btn.style.borderColor = '#00a32a';
							status.textContent = 'Alle Bilder konvertiert!';
							bar.style.width = '100%';
							percent.textContent = '100%';
						}
					} else {
						status.textContent = 'Fehler bei der Konvertierung.';
						btn.disabled = false;
						btn.textContent = 'Erneut versuchen';
					}
				})
				.catch(() => {
					retries++;
					if (retries <= maxRetries) {
						status.textContent = 'Verbindungsfehler — Versuch ' + retries + '/' + maxRetries + '...';
						setTimeout(() => runBatch(offset), 2000);
					} else {
						status.textContent = 'Mehrfach fehlgeschlagen bei Bild ' + offset + '. Bitte erneut versuchen.';
						btn.disabled = false;
						btn.textContent = 'Erneut versuchen';
						btn.onclick = function() { retries = 0; parkouroneWebpBulkAt(offset); };
					}
				});
		}

		window.parkouroneWebpBulkAt = function(o) {
			btn.disabled = true;
			btn.textContent = 'Konvertiere...';
			progress.style.display = 'block';
			runBatch(o);
		};

		runBatch(0);
	}

	function parkouroneWebpAssets() {
		const btn = document.getElementById('po-webp-assets-btn');
		btn.disabled = true;
		btn.textContent = 'Konvertiere...';

		const formData = new FormData();
		formData.append('action', 'parkourone_webp_convert_assets');
		formData.append('nonce', '<?php echo wp_create_nonce('parkourone_webp_bulk'); ?>');

		fetch(ajaxurl, { method: 'POST', body: formData })
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					btn.textContent = data.data.converted + ' Theme-Bilder konvertiert!';
					btn.style.background = '#00a32a';
					btn.style.borderColor = '#00a32a';
				} else {
					btn.textContent = 'Fehler';
					btn.disabled = false;
				}
			})
			.catch(() => { btn.textContent = 'Fehler'; btn.disabled = false; });
	}
	</script>

	<!-- Theme-Assets -->
	<div style="background: #fff; padding: 24px; border-radius: 8px; max-width: 700px; margin-top: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
		<h3 style="margin-top: 0;">Theme-Bilder (Fallbacks, Hero-Defaults)</h3>
		<p style="color: #666; font-size: 14px; margin-bottom: 12px;">
			<?php if (get_option('parkourone_webp_assets_converted')): ?>
				Theme-Assets wurden bereits konvertiert.
			<?php else: ?>
				137 statische Bilder (Fallback-Bilder, Hero-Defaults) noch nicht als WebP vorhanden.
			<?php endif; ?>
		</p>
		<button type="button" id="po-webp-assets-btn" class="button" onclick="parkouroneWebpAssets()">
			Theme-Bilder konvertieren
		</button>
	</div>

	<?php if (!$embedded): ?>
	</div>
	<?php endif;
}
