<?php
/**
 * Event-Bilder Verwaltung
 * Ermöglicht das Hinzufügen von Bildern zu Events (aus dem Academyboard-Plugin)
 * Die Bilder werden im Theme verwaltet, nicht im Plugin
 *
 * Inklusive Fallback-Bilder pro Zielgruppe (1-8 Bilder pro Kategorie)
 */

defined('ABSPATH') || exit;

// =====================================================
// Zentrale Funktion: Event-Bild abrufen
// =====================================================

/**
 * Holt das Bild für ein Event mit Fallback-System
 * Priorität: 1. Event-spezifisch, 2. Featured Image, 3. Kategorie-Fallback
 */
function parkourone_get_event_image($event_id, $age_slug = '') {
	// 1. Event-spezifisches Bild
	$image = get_post_meta($event_id, '_event_image', true);
	if (!empty($image)) return $image;

	// 2. WordPress Featured Image (volle Größe für beste Qualität)
	$image = get_the_post_thumbnail_url($event_id, 'full');
	if (!empty($image)) return $image;

	// 3. Kategorie ermitteln falls nicht übergeben
	if (empty($age_slug)) {
		$terms = wp_get_post_terms($event_id, 'event_category', ['fields' => 'all']);
		foreach ($terms as $term) {
			if ($term->parent) {
				$parent = get_term($term->parent, 'event_category');
				if ($parent && $parent->slug === 'alter') {
					$age_slug = $term->slug;
					break;
				}
			}
		}
	}

	// 4. Fallback-Bild aus Kategorie
	if (!empty($age_slug)) {
		$image = parkourone_get_category_fallback_image($age_slug, $event_id);
		if (!empty($image)) return $image;
	}

	return '';
}

/**
 * Mappt alle Zielgruppen auf die 2 Hauptkategorien für Fallback-Bilder
 * Kids = Kids + Minis
 * Adults = Juniors + Adults + Women + Original + Masters + Seniors (alle anderen)
 */
function parkourone_map_age_category($category_slug) {
	// Slug normalisieren (lowercase)
	$slug = strtolower(trim($category_slug));

	$mapping = [
		// Kids-Gruppe (nur Kinder bis ~11 Jahre)
		'minis' => 'kids',
		'kids' => 'kids',

		// Adults-Gruppe (alle ab ~12 Jahre)
		'juniors' => 'adults',
		'adults' => 'adults',
		'women' => 'adults',
		'original' => 'adults',
		'masters' => 'adults',
		'seniors' => 'adults',
		'seniors-masters' => 'adults',
		'juniors-adults' => 'adults',
	];

	return $mapping[$slug] ?? 'adults'; // Default: Adults
}

/**
 * Holt ein zufälliges Fallback-Bild für eine Kategorie
 * Verwendet Event-ID als Seed für konsistente Zuweisung
 * Priorität: 1. Theme-Ordner, 2. WordPress-Option (Admin-Upload)
 */
function parkourone_get_category_fallback_image($category_slug, $event_id = 0) {
	// Mapping auf Hauptkategorie (Kids, Juniors, Adults)
	$mapped_category = parkourone_map_age_category($category_slug);

	// 1. Zuerst Theme-Ordner prüfen
	$theme_images = parkourone_get_theme_fallback_images($mapped_category);

	if (!empty($theme_images)) {
		// Konsistente Zuweisung basierend auf Event-ID
		if ($event_id > 0) {
			$index = $event_id % count($theme_images);
		} else {
			$index = array_rand($theme_images);
		}
		return $theme_images[$index];
	}

	// 2. Fallback auf WordPress-Option (Admin-Uploads) - auch mit Mapping
	$fallback_images = get_option('parkourone_category_fallback_images', []);

	if (empty($fallback_images[$mapped_category])) {
		return '';
	}

	$images = array_filter($fallback_images[$mapped_category]);
	if (empty($images)) return '';

	if ($event_id > 0) {
		$index = $event_id % count($images);
	} else {
		$index = array_rand($images);
	}

	$keys = array_keys($images);
	return $images[$keys[$index]] ?? '';
}

/**
 * Holt alle Fallback-Bilder aus dem Theme-Ordner für eine Kategorie
 * Verwendet die neue portrait/landscape Struktur
 */
function parkourone_get_theme_fallback_images($category_slug, $orientation = 'portrait') {
	$images = [];

	// Mapping auf verfügbare Ordner
	$folder_map = [
		'kids'            => 'kids',
		'minis'           => 'minis',
		'juniors'         => 'juniors',
		'adults'          => 'adults',
		'juniors-adults'  => 'juniors',  // Kombi → Juniors
		'juniors & adults'=> 'juniors',  // Kombi → Juniors
		'seniors'         => 'adults',   // Fallback
		'masters'         => 'adults',   // Fallback
		'women'           => 'adults',   // Fallback
	];

	// Lowercase für Matching
	$slug_lower = strtolower($category_slug);
	$folder = $folder_map[$slug_lower] ?? 'adults';  // Unbekannt → adults
	$fallback_dir = get_template_directory() . '/assets/images/fallback/' . $orientation . '/' . $folder;
	$fallback_url = get_template_directory_uri() . '/assets/images/fallback/' . $orientation . '/' . $folder;

	if (!is_dir($fallback_dir)) {
		// Fallback zu adults
		$fallback_dir = get_template_directory() . '/assets/images/fallback/' . $orientation . '/adults';
		$fallback_url = get_template_directory_uri() . '/assets/images/fallback/' . $orientation . '/adults';
	}

	if (!is_dir($fallback_dir)) {
		return $images;
	}

	$files = scandir($fallback_dir);
	$allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

	foreach ($files as $file) {
		if ($file === '.' || $file === '..') continue;

		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (in_array($extension, $allowed_extensions)) {
			$images[] = $fallback_url . '/' . $file;
		}
	}

	// Sortieren für konsistente Reihenfolge
	sort($images);

	return $images;
}

/**
 * Gibt alle Fallback-Bilder für eine Kategorie zurück
 * Kombiniert Theme-Bilder und Admin-Uploads
 */
function parkourone_get_all_category_fallback_images($category_slug) {
	// Theme-Bilder zuerst
	$theme_images = parkourone_get_theme_fallback_images($category_slug);

	// Admin-Uploads
	$fallback_images = get_option('parkourone_category_fallback_images', []);
	$admin_images = array_filter($fallback_images[$category_slug] ?? []);

	// Kombinieren (Theme hat Priorität, Admin-Bilder ergänzen)
	return !empty($theme_images) ? $theme_images : array_values($admin_images);
}

// =====================================================
// Meta Box für Event-Bild hinzufügen
// =====================================================

function parkourone_add_event_image_metabox() {
	// Nur wenn Event CPT existiert (vom Plugin registriert)
	if (!post_type_exists('event')) return;

	add_meta_box(
		'parkourone_event_image',
		'Kurs-Bild (Theme)',
		'parkourone_event_image_metabox_html',
		'event',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'parkourone_add_event_image_metabox');

function parkourone_event_image_metabox_html($post) {
	wp_nonce_field('parkourone_event_image_save', 'parkourone_event_image_nonce');

	$image_url = get_post_meta($post->ID, '_event_image', true);
	?>
	<div class="parkourone-event-image-field">
		<div id="event-image-preview" style="margin-bottom: 10px;">
			<?php if ($image_url): ?>
				<img src="<?php echo esc_url($image_url); ?>" style="max-width: 100%; height: auto; border-radius: 8px;">
			<?php endif; ?>
		</div>

		<input type="hidden" id="event_image" name="event_image" value="<?php echo esc_attr($image_url); ?>">

		<p>
			<button type="button" class="button button-primary" id="upload-event-image">
				<?php echo $image_url ? 'Bild ändern' : 'Bild auswählen'; ?>
			</button>
			<?php if ($image_url): ?>
				<button type="button" class="button" id="remove-event-image">Entfernen</button>
			<?php endif; ?>
		</p>

		<p class="description" style="margin-top: 10px;">
			Dieses Bild wird im Klassen-Slider angezeigt.<br>
			Empfohlene Grösse: 800 x 1000 px (Hochformat)
		</p>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var mediaUploader;

		$('#upload-event-image').on('click', function(e) {
			e.preventDefault();

			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media({
				title: 'Kurs-Bild auswählen',
				button: { text: 'Bild verwenden' },
				multiple: false,
				library: { type: 'image' }
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				// Immer volle Größe verwenden für beste Qualität
				var imageUrl = attachment.url;

				$('#event_image').val(imageUrl);
				$('#event-image-preview').html('<img src="' + imageUrl + '" style="max-width: 100%; height: auto; border-radius: 8px;">');
				$('#upload-event-image').text('Bild ändern');

				if ($('#remove-event-image').length === 0) {
					$('#upload-event-image').after(' <button type="button" class="button" id="remove-event-image">Entfernen</button>');
					bindRemoveButton();
				}
			});

			mediaUploader.open();
		});

		function bindRemoveButton() {
			$('#remove-event-image').on('click', function(e) {
				e.preventDefault();
				$('#event_image').val('');
				$('#event-image-preview').html('');
				$('#upload-event-image').text('Bild auswählen');
				$(this).remove();
			});
		}

		bindRemoveButton();
	});
	</script>
	<?php
}

function parkourone_save_event_image($post_id) {
	if (!isset($_POST['parkourone_event_image_nonce'])) return;
	if (!wp_verify_nonce($_POST['parkourone_event_image_nonce'], 'parkourone_event_image_save')) return;
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	if (isset($_POST['event_image'])) {
		$image_url = esc_url_raw($_POST['event_image']);
		if ($image_url) {
			update_post_meta($post_id, '_event_image', $image_url);
		} else {
			delete_post_meta($post_id, '_event_image');
		}
	}
}
add_action('save_post_event', 'parkourone_save_event_image');

// =====================================================
// Admin-Seite für Bulk-Bildverwaltung
// Menü-Registrierung erfolgt in inc/admin-menu.php
// =====================================================

function parkourone_event_images_admin_page() {
	// Handle save
	if (isset($_POST['save_event_images']) && wp_verify_nonce($_POST['event_images_nonce'], 'save_event_images')) {
		$images = $_POST['event_images'] ?? [];
		foreach ($images as $event_id => $image_url) {
			$image_url = esc_url_raw($image_url);
			if ($image_url) {
				update_post_meta($event_id, '_event_image', $image_url);
			} else {
				delete_post_meta($event_id, '_event_image');
			}
		}
		echo '<div class="notice notice-success"><p>Bilder gespeichert!</p></div>';
	}

	// Get all events grouped by age category
	$events = get_posts([
		'post_type' => 'event',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC'
	]);

	// Group by age
	$grouped = [];
	foreach ($events as $event) {
		$terms = wp_get_post_terms($event->ID, 'event_category', ['fields' => 'all']);
		$age_name = 'Ohne Kategorie';

		foreach ($terms as $term) {
			if ($term->parent) {
				$parent = get_term($term->parent, 'event_category');
				if ($parent && $parent->slug === 'alter') {
					$age_name = $term->name;
					break;
				}
			}
		}

		if (!isset($grouped[$age_name])) {
			$grouped[$age_name] = [];
		}
		$grouped[$age_name][] = $event;
	}

	// Sort groups
	$order = ['Minis', 'Kids', 'Juniors', 'Adults', 'Women', 'Original'];
	uksort($grouped, function($a, $b) use ($order) {
		$pos_a = array_search($a, $order);
		$pos_b = array_search($b, $order);
		if ($pos_a === false) $pos_a = 999;
		if ($pos_b === false) $pos_b = 999;
		return $pos_a - $pos_b;
	});
	?>
	<div class="wrap">
		<h1>Kurs-Bilder verwalten</h1>
		<p>Hier kannst du allen Kursen ein Bild zuweisen. Die Bilder werden im Klassen-Slider angezeigt.</p>

		<form method="post">
			<?php wp_nonce_field('save_event_images', 'event_images_nonce'); ?>

			<?php foreach ($grouped as $category => $category_events): ?>
				<h2 style="margin-top: 2rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ccc;">
					<?php echo esc_html($category); ?>
					<span style="font-weight: normal; color: #666; font-size: 14px;">
						(<?php echo count($category_events); ?> Kurse)
					</span>
				</h2>

				<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem;">
					<?php foreach ($category_events as $event):
						$event_id = $event->ID;
						$image_url = get_post_meta($event_id, '_event_image', true);
						$headcoach = get_post_meta($event_id, '_event_headcoach', true);
						$weekday = '';
						$dates = get_post_meta($event_id, '_event_dates', true);
						if (is_array($dates) && !empty($dates[0]['date'])) {
							$timestamp = strtotime(str_replace('-', '.', $dates[0]['date']));
							if ($timestamp) {
								$days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
								$weekday = $days[date('w', $timestamp)];
							}
						}
					?>
					<div class="event-image-card" style="background: #fff; border-radius: 12px; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
						<div style="display: flex; gap: 1rem; align-items: flex-start;">
							<div class="event-image-preview" style="width: 80px; height: 100px; flex-shrink: 0; border-radius: 8px; overflow: hidden; background: #f0f0f0;">
								<?php if ($image_url): ?>
									<img src="<?php echo esc_url($image_url); ?>" style="width: 100%; height: 100%; object-fit: cover;">
								<?php else: ?>
									<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 24px;">📷</div>
								<?php endif; ?>
							</div>
							<div style="flex: 1; min-width: 0;">
								<strong style="display: block; margin-bottom: 0.25rem;"><?php echo esc_html($event->post_title); ?></strong>
								<span style="color: #666; font-size: 13px;">
									<?php if ($weekday): ?><?php echo $weekday; ?> · <?php endif; ?>
									<?php if ($headcoach): ?><?php echo esc_html($headcoach); ?><?php endif; ?>
								</span>
								<div style="margin-top: 0.5rem;">
									<input type="hidden" name="event_images[<?php echo $event_id; ?>]" value="<?php echo esc_attr($image_url); ?>" class="event-image-input">
									<button type="button" class="button button-small upload-image-btn">
										<?php echo $image_url ? 'Ändern' : 'Bild wählen'; ?>
									</button>
									<?php if ($image_url): ?>
										<button type="button" class="button button-small remove-image-btn" style="color: #a00;">×</button>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<p style="margin-top: 2rem;">
				<button type="submit" name="save_event_images" class="button button-primary button-hero">
					Alle Bilder speichern
				</button>
			</p>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var mediaUploader;

		$(document).on('click', '.upload-image-btn', function(e) {
			e.preventDefault();
			var $card = $(this).closest('.event-image-card');
			var $input = $card.find('.event-image-input');
			var $preview = $card.find('.event-image-preview');
			var $btn = $(this);

			mediaUploader = wp.media({
				title: 'Bild auswählen',
				button: { text: 'Verwenden' },
				multiple: false,
				library: { type: 'image' }
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				// Immer volle Größe verwenden für beste Qualität
				var imageUrl = attachment.url;

				$input.val(imageUrl);
				$preview.html('<img src="' + imageUrl + '" style="width: 100%; height: 100%; object-fit: cover;">');
				$btn.text('Ändern');

				if ($card.find('.remove-image-btn').length === 0) {
					$btn.after(' <button type="button" class="button button-small remove-image-btn" style="color: #a00;">×</button>');
				}
			});

			mediaUploader.open();
		});

		$(document).on('click', '.remove-image-btn', function(e) {
			e.preventDefault();
			var $card = $(this).closest('.event-image-card');
			$card.find('.event-image-input').val('');
			$card.find('.event-image-preview').html('<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 24px;">📷</div>');
			$card.find('.upload-image-btn').text('Bild wählen');
			$(this).remove();
		});
	});
	</script>
	<?php
}

// Media Uploader Scripts laden
function parkourone_event_images_admin_scripts($hook) {
	global $post_type;

	// Auf Event-Edit-Seite
	if ($post_type === 'event' && in_array($hook, ['post.php', 'post-new.php'])) {
		wp_enqueue_media();
	}

	// Auf Bulk-Admin-Seite
	if ($hook === 'event_page_parkourone-event-images') {
		wp_enqueue_media();
	}

	// Auf Fallback-Bilder-Seite
	if ($hook === 'appearance_page_parkourone-fallback-images') {
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'parkourone_event_images_admin_scripts');

// =====================================================
// Admin-Seite für Kategorie-Fallback-Bilder
// =====================================================

function parkourone_add_fallback_images_admin_page() {
	add_theme_page(
		'Fallback-Bilder',
		'Kurs-Fallback-Bilder',
		'edit_theme_options',
		'parkourone-fallback-images',
		'parkourone_fallback_images_admin_page'
	);
}
add_action('admin_menu', 'parkourone_add_fallback_images_admin_page');

function parkourone_fallback_images_admin_page() {
	// Speichern
	if (isset($_POST['save_fallback_images']) && wp_verify_nonce($_POST['fallback_images_nonce'], 'save_fallback_images')) {
		$images = $_POST['fallback_images'] ?? [];
		$sanitized = [];

		foreach ($images as $category => $category_images) {
			$sanitized[$category] = [];
			foreach ($category_images as $index => $url) {
				$sanitized[$category][$index] = esc_url_raw($url);
			}
		}

		update_option('parkourone_category_fallback_images', $sanitized);
		echo '<div class="notice notice-success"><p>Fallback-Bilder gespeichert!</p></div>';
	}

	// Aktuelle Bilder laden
	$fallback_images = get_option('parkourone_category_fallback_images', []);

	// Zielgruppen definieren
	$categories = [
		'minis' => [
			'name' => 'Minis',
			'description' => 'Kinder 4-6 Jahre',
			'color' => '#FF6B6B'
		],
		'kids' => [
			'name' => 'Kids',
			'description' => 'Kinder 7-11 Jahre',
			'color' => '#4ECDC4'
		],
		'juniors' => [
			'name' => 'Juniors',
			'description' => 'Jugendliche 12-15 Jahre',
			'color' => '#45B7D1'
		],
		'adults' => [
			'name' => 'Adults',
			'description' => 'Erwachsene 16+ Jahre',
			'color' => '#96CEB4'
		],
		'women' => [
			'name' => 'Women',
			'description' => 'Frauen ab 16 Jahren',
			'color' => '#DDA0DD'
		],
		'original' => [
			'name' => 'Original',
			'description' => 'Fortgeschrittene',
			'color' => '#FFD93D'
		]
	];
	?>
	<div class="wrap">
		<h1>Kurs-Fallback-Bilder</h1>
		<p style="font-size: 14px; color: #666; max-width: 800px;">
			Hier kannst du für jede Zielgruppe 1-8 Fallback-Bilder hinterlegen. Diese werden automatisch verwendet,
			wenn ein Kurs kein eigenes Bild hat. Die Bilder werden gleichmässig auf die Kurse verteilt.
		</p>

		<form method="post">
			<?php wp_nonce_field('save_fallback_images', 'fallback_images_nonce'); ?>

			<div style="display: grid; gap: 2rem; margin-top: 2rem;">
				<?php foreach ($categories as $slug => $category):
					$cat_images = $fallback_images[$slug] ?? [];
				?>
				<div class="fallback-category-card" style="background: #fff; border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
					<div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
						<div style="width: 48px; height: 48px; border-radius: 12px; background: <?php echo $category['color']; ?>; display: flex; align-items: center; justify-content: center;">
							<span style="font-size: 24px; filter: brightness(0) invert(1);">
								<?php
								$icons = [
									'minis' => '👶',
									'kids' => '🧒',
									'juniors' => '🧑',
									'adults' => '🧔',
									'women' => '👩',
									'original' => '🔥'
								];
								echo $icons[$slug] ?? '📷';
								?>
							</span>
						</div>
						<div>
							<h2 style="margin: 0; font-size: 1.25rem;"><?php echo esc_html($category['name']); ?></h2>
							<span style="color: #666; font-size: 13px;"><?php echo esc_html($category['description']); ?></span>
						</div>
						<div style="margin-left: auto; background: #f5f5f5; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 13px;">
							<span class="image-count" data-category="<?php echo $slug; ?>">
								<?php echo count(array_filter($cat_images)); ?>
							</span> / 8 Bilder
						</div>
					</div>

					<div class="fallback-images-grid" data-category="<?php echo $slug; ?>" style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.75rem;">
						<?php for ($i = 0; $i < 8; $i++):
							$image_url = $cat_images[$i] ?? '';
						?>
						<div class="fallback-image-slot" style="aspect-ratio: 4/5; border-radius: 12px; overflow: hidden; background: #f0f0f0; position: relative; cursor: pointer;">
							<?php if ($image_url): ?>
								<img src="<?php echo esc_url($image_url); ?>" style="width: 100%; height: 100%; object-fit: cover;">
								<button type="button" class="remove-fallback-btn" style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border-radius: 50%; background: rgba(0,0,0,0.6); color: #fff; border: none; cursor: pointer; font-size: 14px; line-height: 1;">&times;</button>
							<?php else: ?>
								<div class="empty-slot" style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #aaa;">
									<span style="font-size: 24px;">+</span>
									<span style="font-size: 10px;"><?php echo $i + 1; ?></span>
								</div>
							<?php endif; ?>
							<input type="hidden" name="fallback_images[<?php echo $slug; ?>][<?php echo $i; ?>]" value="<?php echo esc_attr($image_url); ?>" class="fallback-image-input">
						</div>
						<?php endfor; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<p style="margin-top: 2rem; position: sticky; bottom: 0; background: #f0f0f1; padding: 1rem; margin-left: -20px; margin-right: -20px; margin-bottom: -10px;">
				<button type="submit" name="save_fallback_images" class="button button-primary button-hero">
					Alle Fallback-Bilder speichern
				</button>
			</p>
		</form>
	</div>

	<style>
		.fallback-image-slot:hover {
			outline: 2px solid #2271b1;
			outline-offset: 2px;
		}
		.fallback-image-slot:hover .empty-slot {
			background: #e8f4fc;
		}
		.remove-fallback-btn:hover {
			background: rgba(200, 0, 0, 0.8) !important;
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		var mediaUploader;

		// Klick auf Slot
		$(document).on('click', '.fallback-image-slot', function(e) {
			if ($(e.target).hasClass('remove-fallback-btn')) return;

			var $slot = $(this);
			var $input = $slot.find('.fallback-image-input');
			var $grid = $slot.closest('.fallback-images-grid');
			var category = $grid.data('category');

			mediaUploader = wp.media({
				title: 'Fallback-Bild auswählen',
				button: { text: 'Bild verwenden' },
				multiple: false,
				library: { type: 'image' }
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				// Immer volle Größe verwenden für beste Qualität
				var imageUrl = attachment.url;

				$input.val(imageUrl);
				$slot.html('<img src="' + imageUrl + '" style="width: 100%; height: 100%; object-fit: cover;"><button type="button" class="remove-fallback-btn" style="position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border-radius: 50%; background: rgba(0,0,0,0.6); color: #fff; border: none; cursor: pointer; font-size: 14px; line-height: 1;">&times;</button>' + $input.prop('outerHTML'));

				updateCount(category);
			});

			mediaUploader.open();
		});

		// Entfernen
		$(document).on('click', '.remove-fallback-btn', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $slot = $(this).closest('.fallback-image-slot');
			var $input = $slot.find('.fallback-image-input');
			var $grid = $slot.closest('.fallback-images-grid');
			var category = $grid.data('category');
			var index = $slot.index() + 1;

			$input.val('');
			$slot.html('<div class="empty-slot" style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #aaa;"><span style="font-size: 24px;">+</span><span style="font-size: 10px;">' + index + '</span></div>' + $input.prop('outerHTML'));

			updateCount(category);
		});

		function updateCount(category) {
			var count = 0;
			$('.fallback-images-grid[data-category="' + category + '"] .fallback-image-input').each(function() {
				if ($(this).val()) count++;
			});
			$('.image-count[data-category="' + category + '"]').text(count);
		}
	});
	</script>
	<?php
}
