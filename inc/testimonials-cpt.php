<?php
/**
 * Testimonials Custom Post Type
 * Ermöglicht das Verwalten von Kundenbewertungen
 */

defined('ABSPATH') || exit;

// =====================================================
// Custom Post Type Registration
// =====================================================

function parkourone_register_testimonial_cpt() {
	register_post_type('testimonial', [
		'labels' => [
			'name' => 'Testimonials',
			'singular_name' => 'Testimonial',
			'add_new' => 'Neues Testimonial',
			'add_new_item' => 'Neues Testimonial hinzufügen',
			'edit_item' => 'Testimonial bearbeiten',
			'view_item' => 'Testimonial ansehen',
			'all_items' => 'Alle Testimonials',
			'search_items' => 'Testimonials suchen',
			'not_found' => 'Keine Testimonials gefunden',
			'menu_name' => 'Testimonials'
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 26,
		'menu_icon' => 'dashicons-format-quote',
		'supports' => ['title'],
		'has_archive' => false,
		'rewrite' => false,
		'show_in_rest' => true,
		'taxonomies' => ['testimonial_age_group']
	]);

	// Ticket #1: Altersgruppen-Taxonomie für Testimonials
	register_taxonomy('testimonial_age_group', 'testimonial', [
		'labels' => [
			'name' => 'Altersgruppen',
			'singular_name' => 'Altersgruppe',
			'search_items' => 'Altersgruppen suchen',
			'all_items' => 'Alle Altersgruppen',
			'edit_item' => 'Altersgruppe bearbeiten',
			'update_item' => 'Altersgruppe aktualisieren',
			'add_new_item' => 'Neue Altersgruppe hinzufügen',
			'new_item_name' => 'Neue Altersgruppe',
			'menu_name' => 'Altersgruppen'
		],
		'hierarchical' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => false
	]);

	// Standard-Altersgruppen erstellen falls nicht vorhanden
	parkourone_create_default_age_groups();
}
add_action('init', 'parkourone_register_testimonial_cpt');

/**
 * Erstellt die Standard-Altersgruppen für Testimonials
 * Nur 3 Hauptkategorien: Kids, Juniors, Adults
 */
function parkourone_create_default_age_groups() {
	$age_groups = [
		'kids' => 'Kids',
		'juniors' => 'Juniors',
		'adults' => 'Adults'
	];

	foreach ($age_groups as $slug => $name) {
		if (!term_exists($slug, 'testimonial_age_group')) {
			wp_insert_term($name, 'testimonial_age_group', ['slug' => $slug]);
		}
	}
}

// =====================================================
// Meta Box
// =====================================================

function parkourone_testimonial_meta_box() {
	add_meta_box(
		'testimonial_details',
		'Testimonial Details',
		'parkourone_testimonial_meta_box_html',
		'testimonial',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'parkourone_testimonial_meta_box');

// Media Uploader für Testimonial-Seite laden
function parkourone_testimonial_admin_scripts($hook) {
	global $post_type;
	if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'testimonial') {
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'parkourone_testimonial_admin_scripts');

function parkourone_testimonial_meta_box_html($post) {
	wp_nonce_field('testimonial_meta', 'testimonial_meta_nonce');

	$text = get_post_meta($post->ID, '_testimonial_text', true);
	$stars = get_post_meta($post->ID, '_testimonial_stars', true) ?: 5;
	$source = get_post_meta($post->ID, '_testimonial_source', true) ?: 'Google Review';
	$image_id = get_post_meta($post->ID, '_testimonial_image', true);
	$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
	?>
	<table class="form-table">
		<tr>
			<th><label>Profilbild</label></th>
			<td>
				<div id="testimonial-image-preview" style="margin-bottom: 10px;">
					<?php if ($image_url): ?>
						<img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto; border-radius: 50%;">
					<?php endif; ?>
				</div>
				<input type="hidden" id="_testimonial_image" name="_testimonial_image" value="<?php echo esc_attr($image_id); ?>">
				<button type="button" class="button" id="testimonial-image-upload">Bild auswählen</button>
				<button type="button" class="button" id="testimonial-image-remove" style="<?php echo $image_id ? '' : 'display:none;'; ?>">Entfernen</button>
				<p class="description">Optional: Profilbild der Person (wird rund angezeigt).</p>
			</td>
		</tr>
		<tr>
			<th><label for="_testimonial_text">Bewertungstext</label></th>
			<td>
				<textarea id="_testimonial_text" name="_testimonial_text" rows="4" class="large-text"><?php echo esc_textarea($text); ?></textarea>
				<p class="description">Der vollständige Text der Bewertung.</p>
			</td>
		</tr>
		<tr>
			<th><label for="_testimonial_stars">Sterne</label></th>
			<td>
				<select id="_testimonial_stars" name="_testimonial_stars">
					<?php for ($i = 5; $i >= 1; $i--): ?>
						<option value="<?php echo $i; ?>" <?php selected($stars, $i); ?>><?php echo $i; ?> Sterne</option>
					<?php endfor; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="_testimonial_source">Quelle</label></th>
			<td>
				<input type="text" id="_testimonial_source" name="_testimonial_source" value="<?php echo esc_attr($source); ?>" class="regular-text" placeholder="z.B. Google Review">
			</td>
		</tr>
	</table>

	<script>
	jQuery(document).ready(function($) {
		var frame;
		$('#testimonial-image-upload').on('click', function(e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({
				title: 'Profilbild auswählen',
				button: { text: 'Auswählen' },
				multiple: false
			});
			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#_testimonial_image').val(attachment.id);
				$('#testimonial-image-preview').html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 150px; height: auto; border-radius: 50%;">');
				$('#testimonial-image-remove').show();
			});
			frame.open();
		});
		$('#testimonial-image-remove').on('click', function(e) {
			e.preventDefault();
			$('#_testimonial_image').val('');
			$('#testimonial-image-preview').html('');
			$(this).hide();
		});
	});
	</script>
	<?php
}

function parkourone_save_testimonial_meta($post_id) {
	if (!isset($_POST['testimonial_meta_nonce']) || !wp_verify_nonce($_POST['testimonial_meta_nonce'], 'testimonial_meta')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$fields = ['_testimonial_text', '_testimonial_stars', '_testimonial_source'];

	foreach ($fields as $field) {
		if (isset($_POST[$field])) {
			update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
		}
	}

	// Profilbild speichern
	if (isset($_POST['_testimonial_image'])) {
		$image_id = absint($_POST['_testimonial_image']);
		if ($image_id > 0) {
			update_post_meta($post_id, '_testimonial_image', $image_id);
		} else {
			delete_post_meta($post_id, '_testimonial_image');
		}
	}
}
add_action('save_post_testimonial', 'parkourone_save_testimonial_meta');

// =====================================================
// Admin Columns
// =====================================================

function parkourone_testimonial_columns($columns) {
	return [
		'cb' => $columns['cb'],
		'title' => 'Name',
		'age_group' => 'Altersgruppe',
		'text_preview' => 'Text',
		'stars' => 'Sterne',
		'source' => 'Quelle',
		'date' => 'Datum'
	];
}
add_filter('manage_testimonial_posts_columns', 'parkourone_testimonial_columns');

function parkourone_testimonial_column_content($column, $post_id) {
	switch ($column) {
		case 'age_group':
			$terms = get_the_terms($post_id, 'testimonial_age_group');
			if ($terms && !is_wp_error($terms)) {
				$labels = array_map(function($term) {
					$colors = [
						'kids' => '#4CAF50',
						'juniors' => '#2196F3',
						'adults' => '#9C27B0'
					];
					$color = $colors[$term->slug] ?? '#666';
					return '<span style="background:' . $color . '; color:#fff; padding:2px 8px; border-radius:3px; font-size:11px;">' . esc_html($term->name) . '</span>';
				}, $terms);
				echo implode(' ', $labels);
			} else {
				echo '<span style="color:#999;">—</span>';
			}
			break;
		case 'text_preview':
			$text = get_post_meta($post_id, '_testimonial_text', true);
			echo esc_html(wp_trim_words($text, 15, '...'));
			break;
		case 'stars':
			$stars = get_post_meta($post_id, '_testimonial_stars', true) ?: 5;
			echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
			break;
		case 'source':
			echo esc_html(get_post_meta($post_id, '_testimonial_source', true));
			break;
	}
}
add_action('manage_testimonial_posts_custom_column', 'parkourone_testimonial_column_content', 10, 2);

// Altersgruppe als filterbare Spalte
function parkourone_testimonial_sortable_columns($columns) {
	$columns['age_group'] = 'age_group';
	return $columns;
}
add_filter('manage_edit-testimonial_sortable_columns', 'parkourone_testimonial_sortable_columns');

// =====================================================
// Preset Testimonials Import
// =====================================================

function parkourone_get_preset_testimonials() {
	return [
		// Adults - Erwachsene Teilnehmer
		['name' => 'Tadäus Steinemann', 'stars' => 5, 'text' => 'ParkourONE bedeutet: ALL for ONE --- ONE for ALL. Hier kanst du echtes Parkour bei super motivierten Coaches lernen. Regelmässige Trainings besuchen, Workshops und vieles mehr erfahren. Es ist eine tolle Community mit coolen Leuten.', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Silvio Stoll', 'stars' => 5, 'text' => 'Kompetent, zuverlässig, qualitativ hochstehendes Angebot, umfassende Vermittlung von Parkour in all seinen Facetten. ParkourONE bietet die optimalen Voraussetzungen und verfügt über einen jahrzehntelangen Erfahrungsschatz.', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'M. Lütolf', 'stars' => 5, 'text' => '"Hindernisse sind Möglichkeiten" Wie wahr. Mit Parkour habe ich erst vor Kurzem begonnen und jedes Training motiviert aufs Neue. Die Gemeinschaft zieht am gleichen Strick. ONE for all - all for ONE', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Rino Vanoni', 'stars' => 5, 'text' => 'Ich bin seit über zehn Jahren Schülerin in einem ihrer Kurse. ParkourONE steht für Qualität und Beständigkeit. Ihr Lehrkonzept ist hervorragend.', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Bogdan Cvetkovic', 'stars' => 5, 'text' => 'Die beste Community in der Parkour-Welt. Ich arbeite seit über 10 Jahren mit ihnen zusammen!', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Christoph Raaflaub', 'stars' => 5, 'text' => 'Ermöglicht den Beginn mit Parkour in jedem Alter. Gut und durchdachte Struktur der Trainings. So macht Bewegung richtig Spass!', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Zei Wagner', 'stars' => 5, 'text' => 'ParkourONE vermittelt nicht nur Parkour, sondern auch Werte. Seit mehr als 10 Jahren besuche ich das Training. SEHR empfehlenswert!', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Milena Losinger', 'stars' => 5, 'text' => 'Alles wunderbar. Team mit viel Herzblut dabei, Workshops super koordiniert.', 'source' => 'Google Review', 'age_group' => 'adults'],
		['name' => 'Brigitte Meier Müller', 'stars' => 5, 'text' => 'Cooles Training mit vielen Herausforderungen, abwechslungsreich, es macht Spass!', 'source' => 'Google Review', 'age_group' => 'adults'],

		// Juniors - Jugendliche / Teenager
		['name' => 'gugger f', 'stars' => 5, 'text' => 'Das Probetraining in Thun war der Hammer! Die Leiterin ist super und ist auf alle eingegangen. Hatte mehrere Erfolgserlebnisse und bin total geflasht!', 'source' => 'Google Review', 'age_group' => 'juniors'],

		// Kids - Eltern über ihre Kinder
		['name' => 'Luna Mel', 'stars' => 5, 'text' => 'Mein Sohn (7 Jahre) hat das Probetraining in der Länggasse absolviert. Er war hellauf begeistert. Toller Trainer, sehr professionell. Lukas freut sich schon bald starten zu können.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Lilian Carmine', 'stars' => 5, 'text' => 'Unsere Jungs lieben das Parkourtraining in der Kidsklasse. Es ist ein optimaler Ausgleich zum Schulalltag. Danke allen Trainern für die liebevolle und professionelle Betreuung!', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Katrin Lüthi-Affolter', 'stars' => 5, 'text' => 'Mein 7-jähriger Sohn besucht seit einem Jahr eine Klasse in Bern. Es ist so schön zu sehen, wie glücklich er nach dem Training immer ist. Die Trainer machen das super.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Nadine Rüegsegger', 'stars' => 5, 'text' => 'Unser Sohn liebt das Parkour-Training in der Kids-Klasse Köniz. Die Trainings sind abwechslungsreich und machen bei jedem Wetter mega SPASS. Die beiden Coaches machen einen tollen Job!', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Zita', 'stars' => 5, 'text' => 'Unser Sohn besucht seit zwei Jahren den Kurs Kids Hardau und kommt immer zufrieden und gut gelaunt vom Training heim. Die Trainer sind nett, die Atmosphäre locker.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Nora Luna', 'stars' => 5, 'text' => 'Mein Sohn besucht die Kidsklasse in Basel und ist von den Trainings begeistert, hat Spass und lernt jedesmal viel dazu.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Tatjana von Gunten', 'stars' => 5, 'text' => 'Unser Sohn ist seither nie von einem Training unzufrieden nach Hause gekommen. Die Trainer sind toll, schaffen es, den Kids die eigene Freude am Parkour zu vermitteln. Danke ParkourONE!', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Desirée Topple', 'stars' => 5, 'text' => 'Pierre ist ein toller Trainer, der die Kinder motiviert und ihnen die Möglichkeit bietet über sich hinauszuwachsen. Ich kann es nur empfehlen! Bravo!!!', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Evelin Stutzer', 'stars' => 5, 'text' => 'Endlich haben wir für unseren Sohn DEN Sport gefunden, der ihm auf allen Ebenen zusagt. Dass ganzheitliche Werte gleichzeitig vermittelt werden, macht auch uns Eltern glücklich.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Florian Brunner', 'stars' => 5, 'text' => 'Unser Sohn ist in der Trainingsklasse "Kids Zürich Dynamo 2". Er geht sehr gerne in den Parkour-Kurs. Es ist eine gute Gruppe mit einem lässigen Trainingsleiter.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Sven Deck', 'stars' => 5, 'text' => 'Unser Sohn lässt sich fast durch nichts vom Training abhalten. Es gibt ihm einen super Ausgleich zum Schulalltag - nicht nur körperlich sondern auch mental. Tolle Sache!', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Nathan Kaiser', 'stars' => 5, 'text' => 'Ein spannendes Programm, viel Abwechslung für die Kinder. Das Team hat auch Ideen - kein Training ist dasselbe!', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Annegret Reichwagen', 'stars' => 5, 'text' => 'Super Angebot und tolle Lehrerin die ein klasse Vorbild für unsere Kinder darstellt. Sie lehrt nicht nur Parkour, sie lebt es ihnen richtig vor.', 'source' => 'Google Review', 'age_group' => 'kids'],
		['name' => 'Nilgün Arikan', 'stars' => 5, 'text' => 'Für mich sehr beeindruckend zu beobachten, wie schnell die Kleinen die Techniken für Springen, Klettern und Hüpfen verinnerlichen. Sandro geht toll auf die Kinder ein!!!', 'source' => 'Google Review', 'age_group' => 'kids'],
	];
}

// Admin Notice + Import Button
function parkourone_testimonials_admin_notice() {
	$screen = get_current_screen();
	if ($screen->post_type !== 'testimonial') return;

	$count = wp_count_posts('testimonial');
	$total = $count->publish + $count->draft;

	// Fall 1: Keine Testimonials vorhanden - Import anbieten (immer zeigen wenn leer)
	if ($total === 0):
	?>
	<div class="notice notice-info is-dismissible" id="po-testimonials-setup">
		<p><strong>Testimonials einrichten</strong></p>
		<p>Möchtest du die 24 voreingestellten Google Reviews importieren? Sie werden automatisch den passenden Altersgruppen zugeordnet (9× Adults, 1× Juniors, 14× Kids).</p>
		<p>
			<button type="button" class="button button-primary" id="po-import-testimonials">Preset Testimonials importieren</button>
			<button type="button" class="button" id="po-skip-testimonials">Nein, ich erstelle eigene</button>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-import-testimonials').on('click', function() {
			$(this).prop('disabled', true).text('Importiere...');
			$.post(ajaxurl, {
				action: 'po_import_testimonials',
				nonce: '<?php echo wp_create_nonce('po_import_testimonials'); ?>'
			}, function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Fehler: ' + response.data.message);
				}
			});
		});
		$('#po-skip-testimonials').on('click', function() {
			$.post(ajaxurl, {
				action: 'po_dismiss_testimonials_notice',
				nonce: '<?php echo wp_create_nonce('po_dismiss_testimonials'); ?>'
			});
			$('#po-testimonials-setup').fadeOut();
		});
	});
	</script>
	<?php
	endif;

	// Fall 2: Testimonials ohne Altersgruppe vorhanden - Zuordnung anbieten
	if ($total > 0 && !get_option('parkourone_testimonials_categories_assigned')):
		// Prüfen ob es Testimonials ohne Kategorie gibt
		$uncategorized = get_posts([
			'post_type' => 'testimonial',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'tax_query' => [
				[
					'taxonomy' => 'testimonial_age_group',
					'operator' => 'NOT EXISTS'
				]
			]
		]);
		if (!empty($uncategorized)):
	?>
	<div class="notice notice-warning is-dismissible" id="po-testimonials-categorize">
		<p><strong>Altersgruppen zuordnen</strong></p>
		<p>Es gibt Testimonials ohne Altersgruppen-Zuordnung. Sollen die Preset-Testimonials automatisch kategorisiert werden?</p>
		<p>
			<button type="button" class="button button-primary" id="po-categorize-testimonials">Automatisch zuordnen</button>
			<button type="button" class="button" id="po-skip-categorize">Manuell zuordnen</button>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-categorize-testimonials').on('click', function() {
			$(this).prop('disabled', true).text('Ordne zu...');
			$.post(ajaxurl, {
				action: 'po_categorize_testimonials',
				nonce: '<?php echo wp_create_nonce('po_categorize_testimonials'); ?>'
			}, function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Fehler: ' + response.data.message);
				}
			});
		});
		$('#po-skip-categorize').on('click', function() {
			$.post(ajaxurl, {
				action: 'po_dismiss_categorize_notice',
				nonce: '<?php echo wp_create_nonce('po_dismiss_categorize'); ?>'
			});
			$('#po-testimonials-categorize').fadeOut();
		});
	});
	</script>
	<?php
		endif;
	endif;
}
add_action('admin_notices', 'parkourone_testimonials_admin_notice');

// AJAX: Kategorisiere bestehende Testimonials
function parkourone_ajax_categorize_testimonials() {
	check_ajax_referer('po_categorize_testimonials', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error(['message' => 'Keine Berechtigung']);
	}

	$presets = parkourone_get_preset_testimonials();
	$categorized = 0;

	// Mapping: Name → Altersgruppe aus Presets erstellen
	$name_to_age = [];
	foreach ($presets as $preset) {
		if (!empty($preset['age_group'])) {
			$name_to_age[$preset['name']] = $preset['age_group'];
		}
	}

	// Alle Testimonials ohne Kategorie holen
	$testimonials = get_posts([
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'tax_query' => [
			[
				'taxonomy' => 'testimonial_age_group',
				'operator' => 'NOT EXISTS'
			]
		]
	]);

	foreach ($testimonials as $testimonial) {
		if (isset($name_to_age[$testimonial->post_title])) {
			wp_set_object_terms($testimonial->ID, $name_to_age[$testimonial->post_title], 'testimonial_age_group');
			$categorized++;
		}
	}

	update_option('parkourone_testimonials_categories_assigned', true);
	wp_send_json_success(['categorized' => $categorized]);
}
add_action('wp_ajax_po_categorize_testimonials', 'parkourone_ajax_categorize_testimonials');

// AJAX: Dismiss Kategorisierung Notice
function parkourone_ajax_dismiss_categorize_notice() {
	check_ajax_referer('po_dismiss_categorize', 'nonce');
	update_option('parkourone_testimonials_categories_assigned', true);
	wp_send_json_success();
}
add_action('wp_ajax_po_dismiss_categorize_notice', 'parkourone_ajax_dismiss_categorize_notice');

// AJAX: Import Testimonials
function parkourone_ajax_import_testimonials() {
	check_ajax_referer('po_import_testimonials', 'nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error(['message' => 'Keine Berechtigung']);
	}

	$presets = parkourone_get_preset_testimonials();
	$imported = 0;

	foreach ($presets as $preset) {
		$post_id = wp_insert_post([
			'post_type' => 'testimonial',
			'post_title' => $preset['name'],
			'post_status' => 'publish'
		]);

		if ($post_id && !is_wp_error($post_id)) {
			update_post_meta($post_id, '_testimonial_text', $preset['text']);
			update_post_meta($post_id, '_testimonial_stars', $preset['stars']);
			update_post_meta($post_id, '_testimonial_source', $preset['source']);

			// Altersgruppe zuweisen falls vorhanden
			if (!empty($preset['age_group'])) {
				wp_set_object_terms($post_id, $preset['age_group'], 'testimonial_age_group');
			}

			$imported++;
		}
	}

	update_option('parkourone_testimonials_notice_dismissed', true);
	wp_send_json_success(['imported' => $imported]);
}
add_action('wp_ajax_po_import_testimonials', 'parkourone_ajax_import_testimonials');

// AJAX: Dismiss Notice
function parkourone_ajax_dismiss_testimonials_notice() {
	check_ajax_referer('po_dismiss_testimonials', 'nonce');
	update_option('parkourone_testimonials_notice_dismissed', true);
	wp_send_json_success();
}
add_action('wp_ajax_po_dismiss_testimonials_notice', 'parkourone_ajax_dismiss_testimonials_notice');

// =====================================================
// Intelligente Testimonials (wie FAQs)
// =====================================================

/**
 * Holt Testimonials für einen Seitentyp
 * Ähnlich wie parkourone_get_page_faqs()
 *
 * @param string $page_type Seitentyp: 'kids', 'juniors', 'adults', 'standort', 'startseite', 'workshops'
 * @param int $limit Max. Anzahl (Standard: 4)
 * @param bool $random Zufällige Auswahl (Standard: true)
 * @return array Testimonials
 */
function parkourone_get_page_testimonials($page_type = '', $limit = 4, $random = true) {
	$testimonials = [];

	// Mapping: Seitentyp → Testimonial Altersgruppe
	$age_map = [
		'kids' => 'kids',
		'minis' => 'kids',
		'juniors' => 'juniors',
		'adults' => 'adults',
		'women' => 'adults',
		'original' => 'adults',
		'35plus' => 'adults',
	];

	// Bei Altersgruppen-Seiten: passende Testimonials laden
	if (!empty($page_type) && isset($age_map[strtolower($page_type)])) {
		$age_group = $age_map[strtolower($page_type)];
		$testimonials = parkourone_get_testimonials_by_age($age_group, $limit, $random);
	}
	// Bei Standorten, Startseite, Workshops: gemischt
	else {
		$testimonials = parkourone_get_testimonials_mixed($limit, $random);
	}

	return $testimonials;
}

/**
 * Holt Testimonials einer bestimmten Altersgruppe
 */
function parkourone_get_testimonials_by_age($age_group, $limit = 4, $random = true) {
	$args = [
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => $limit > 0 ? $limit : -1,
		'tax_query' => [
			[
				'taxonomy' => 'testimonial_age_group',
				'field' => 'slug',
				'terms' => $age_group
			]
		]
	];

	if ($random) {
		$args['orderby'] = 'rand';
	} else {
		$args['orderby'] = 'date';
		$args['order'] = 'DESC';
	}

	$posts = get_posts($args);
	return parkourone_format_testimonials($posts);
}

/**
 * Holt gemischte Testimonials aus allen Altersgruppen
 */
function parkourone_get_testimonials_mixed($limit = 4, $random = true) {
	$args = [
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => $limit > 0 ? $limit : -1
	];

	if ($random) {
		$args['orderby'] = 'rand';
	} else {
		$args['orderby'] = 'date';
		$args['order'] = 'DESC';
	}

	$posts = get_posts($args);
	return parkourone_format_testimonials($posts);
}

/**
 * Formatiert Testimonial-Posts zu Array
 */
function parkourone_format_testimonials($posts) {
	$testimonials = [];

	foreach ($posts as $post) {
		$image_id = get_post_meta($post->ID, '_testimonial_image', true);
		$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';

		$testimonials[] = [
			'id' => $post->ID,
			'name' => $post->post_title,
			'text' => get_post_meta($post->ID, '_testimonial_text', true),
			'stars' => get_post_meta($post->ID, '_testimonial_stars', true) ?: 5,
			'source' => get_post_meta($post->ID, '_testimonial_source', true),
			'image' => $image_url,
			'age_group' => wp_get_post_terms($post->ID, 'testimonial_age_group', ['fields' => 'slugs'])
		];
	}

	return $testimonials;
}

// =====================================================
// Google Reviews Link Einstellung
// =====================================================

/**
 * Registriert Google Reviews URL Einstellung im Customizer
 */
function parkourone_customize_google_reviews($wp_customize) {
	// Section
	$wp_customize->add_section('parkourone_reviews', [
		'title' => 'Google Reviews',
		'description' => 'Einstellungen für Google Reviews Link',
		'priority' => 130
	]);

	// Setting
	$wp_customize->add_setting('parkourone_google_reviews_url', [
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh'
	]);

	// Control
	$wp_customize->add_control('parkourone_google_reviews_url', [
		'label' => 'Google Reviews URL',
		'description' => 'Link zu euren Google Reviews (z.B. https://g.page/r/...)',
		'section' => 'parkourone_reviews',
		'type' => 'url'
	]);
}
add_action('customize_register', 'parkourone_customize_google_reviews');

/**
 * Holt die Google Reviews URL aus dem Customizer
 * Fallback: Generischer Google-Suche Link
 */
function parkourone_get_google_reviews_url() {
	$url = get_theme_mod('parkourone_google_reviews_url', '');

	if (empty($url)) {
		// Fallback: Google Suche nach ParkourONE Bewertungen
		$site_location = function_exists('parkourone_get_site_location')
			? parkourone_get_site_location()
			: ['name' => 'Schweiz'];
		$url = 'https://www.google.com/search?q=parkourone+' . urlencode($site_location['name']) . '+bewertungen';
	}

	return $url;
}
