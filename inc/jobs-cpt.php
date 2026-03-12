<?php
/**
 * Jobs Custom Post Type
 * Ermöglicht das Verwalten von Stellenangeboten
 */

defined('ABSPATH') || exit;

// =====================================================
// Custom Post Type Registration
// =====================================================

function parkourone_register_job_cpt() {
	register_post_type('job', [
		'labels' => [
			'name' => 'Jobs',
			'singular_name' => 'Job',
			'add_new' => 'Neuer Job',
			'add_new_item' => 'Neuen Job hinzufügen',
			'edit_item' => 'Job bearbeiten',
			'view_item' => 'Job ansehen',
			'all_items' => 'Alle Jobs',
			'search_items' => 'Jobs suchen',
			'not_found' => 'Keine Jobs gefunden',
			'menu_name' => 'Jobs'
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 27,
		'menu_icon' => 'dashicons-businessperson',
		'supports' => ['title'],
		'has_archive' => false,
		'rewrite' => false,
		'show_in_rest' => true
	]);
}
add_action('init', 'parkourone_register_job_cpt');

// =====================================================
// Meta Box
// =====================================================

function parkourone_job_meta_box() {
	add_meta_box(
		'job_details',
		'Job Details',
		'parkourone_job_meta_box_html',
		'job',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'parkourone_job_meta_box');

function parkourone_job_meta_box_html($post) {
	wp_nonce_field('parkourone_job_meta', 'parkourone_job_nonce');

	$type = get_post_meta($post->ID, '_job_type', true);
	$short_desc = get_post_meta($post->ID, '_job_short_description', true);
	$full_desc = get_post_meta($post->ID, '_job_full_description', true);
	$requirements = get_post_meta($post->ID, '_job_requirements', true);
	$benefits = get_post_meta($post->ID, '_job_benefits', true);
	$how_to_apply = get_post_meta($post->ID, '_job_how_to_apply', true);
	$contact_email = get_post_meta($post->ID, '_job_contact_email', true);
	?>
	<style>
		.po-job-meta { display: grid; gap: 1.5rem; }
		.po-job-meta__field { display: flex; flex-direction: column; gap: 0.5rem; }
		.po-job-meta__field label { font-weight: 600; color: #1d2327; }
		.po-job-meta__field input[type="text"],
		.po-job-meta__field input[type="email"],
		.po-job-meta__field textarea { width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; }
		.po-job-meta__field textarea { min-height: 100px; font-family: inherit; }
		.po-job-meta__field .description { color: #646970; font-size: 13px; margin-top: 4px; }
		.po-job-meta__row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
		@media (max-width: 782px) { .po-job-meta__row { grid-template-columns: 1fr; } }
	</style>

	<div class="po-job-meta">
		<div class="po-job-meta__row">
			<div class="po-job-meta__field">
				<label for="job_type">Art der Stelle</label>
				<input type="text" id="job_type" name="job_type" value="<?php echo esc_attr($type); ?>" placeholder="z.B. Teilzeit / Vollzeit, 3-6 Monate, Freelance">
				<p class="description">Wird als Badge auf der Karte angezeigt</p>
			</div>

			<div class="po-job-meta__field">
				<label for="job_contact_email">Kontakt E-Mail</label>
				<input type="email" id="job_contact_email" name="job_contact_email" value="<?php echo esc_attr($contact_email); ?>" placeholder="jobs@parkourone.com">
				<p class="description">E-Mail für Bewerbungen</p>
			</div>
		</div>

		<div class="po-job-meta__field">
			<label for="job_short_description">Kurzbeschreibung</label>
			<textarea id="job_short_description" name="job_short_description" rows="2" placeholder="1-2 Sätze für die Karten-Vorschau"><?php echo esc_textarea($short_desc); ?></textarea>
			<p class="description">Wird auf der Job-Karte angezeigt (kurz halten)</p>
		</div>

		<div class="po-job-meta__field">
			<label for="job_full_description">Ausführliche Beschreibung</label>
			<textarea id="job_full_description" name="job_full_description" rows="5" placeholder="Detaillierte Beschreibung der Stelle..."><?php echo esc_textarea($full_desc); ?></textarea>
			<p class="description">Wird im Modal angezeigt wenn "Mehr erfahren" geklickt wird</p>
		</div>

		<div class="po-job-meta__row">
			<div class="po-job-meta__field">
				<label for="job_requirements">Anforderungen</label>
				<textarea id="job_requirements" name="job_requirements" rows="5" placeholder="Erfahrung im Parkour-Training&#10;Freude am Unterrichten&#10;Zuverlässigkeit"><?php echo esc_textarea($requirements); ?></textarea>
				<p class="description">Eine Anforderung pro Zeile (wird als Liste angezeigt)</p>
			</div>

			<div class="po-job-meta__field">
				<label for="job_benefits">Was wir bieten</label>
				<textarea id="job_benefits" name="job_benefits" rows="5" placeholder="Faire Vergütung&#10;Flexible Arbeitszeiten&#10;Kostenloses Training"><?php echo esc_textarea($benefits); ?></textarea>
				<p class="description">Ein Vorteil pro Zeile (wird als Liste angezeigt)</p>
			</div>
		</div>

		<div class="po-job-meta__field">
			<label for="job_how_to_apply">Bewerbungshinweise</label>
			<textarea id="job_how_to_apply" name="job_how_to_apply" rows="3" placeholder="Schick uns eine kurze Vorstellung von dir..."><?php echo esc_textarea($how_to_apply); ?></textarea>
			<p class="description">Anleitung wie man sich bewerben soll</p>
		</div>
	</div>
	<?php
}

// =====================================================
// Save Meta Data
// =====================================================

function parkourone_save_job_meta($post_id) {
	// Security checks
	if (!isset($_POST['parkourone_job_nonce'])) return;
	if (!wp_verify_nonce($_POST['parkourone_job_nonce'], 'parkourone_job_meta')) return;
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	// Save fields
	$fields = [
		'job_type' => '_job_type',
		'job_short_description' => '_job_short_description',
		'job_full_description' => '_job_full_description',
		'job_requirements' => '_job_requirements',
		'job_benefits' => '_job_benefits',
		'job_how_to_apply' => '_job_how_to_apply',
		'job_contact_email' => '_job_contact_email'
	];

	foreach ($fields as $post_key => $meta_key) {
		if (isset($_POST[$post_key])) {
			$value = $meta_key === '_job_contact_email'
				? sanitize_email($_POST[$post_key])
				: sanitize_textarea_field($_POST[$post_key]);
			update_post_meta($post_id, $meta_key, $value);
		}
	}
}
add_action('save_post_job', 'parkourone_save_job_meta');

// =====================================================
// Admin Columns
// =====================================================

function parkourone_job_admin_columns($columns) {
	$new_columns = [];
	foreach ($columns as $key => $value) {
		$new_columns[$key] = $value;
		if ($key === 'title') {
			$new_columns['job_type'] = 'Art';
			$new_columns['job_email'] = 'Kontakt';
		}
	}
	return $new_columns;
}
add_filter('manage_job_posts_columns', 'parkourone_job_admin_columns');

function parkourone_job_admin_column_content($column, $post_id) {
	switch ($column) {
		case 'job_type':
			$type = get_post_meta($post_id, '_job_type', true);
			echo $type ? esc_html($type) : '—';
			break;
		case 'job_email':
			$email = get_post_meta($post_id, '_job_contact_email', true);
			echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
			break;
	}
}
add_action('manage_job_posts_custom_column', 'parkourone_job_admin_column_content', 10, 2);

// =====================================================
// Helper Function: Get All Jobs
// =====================================================

function parkourone_get_jobs($args = []) {
	$defaults = [
		'post_type' => 'job',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'menu_order date',
		'order' => 'ASC'
	];

	$query_args = wp_parse_args($args, $defaults);
	$jobs_query = new WP_Query($query_args);
	$jobs = [];

	if ($jobs_query->have_posts()) {
		while ($jobs_query->have_posts()) {
			$jobs_query->the_post();
			$post_id = get_the_ID();

			$jobs[] = [
				'id' => $post_id,
				'title' => get_the_title(),
				'type' => get_post_meta($post_id, '_job_type', true),
				'desc' => get_post_meta($post_id, '_job_short_description', true),
				'fullDescription' => get_post_meta($post_id, '_job_full_description', true),
				'requirements' => get_post_meta($post_id, '_job_requirements', true),
				'benefits' => get_post_meta($post_id, '_job_benefits', true),
				'howToApply' => get_post_meta($post_id, '_job_how_to_apply', true),
				'contactEmail' => get_post_meta($post_id, '_job_contact_email', true)
			];
		}
		wp_reset_postdata();
	}

	return $jobs;
}

// =====================================================
// Check if Jobs Exist
// =====================================================

function parkourone_has_jobs() {
	$count = wp_count_posts('job');
	return isset($count->publish) && $count->publish > 0;
}

// =====================================================
// Setup Wizard: Preset Jobs
// =====================================================

function parkourone_get_preset_jobs() {
	$shared_requirements = "2+ Jahre Parkour-Erfahrung ODER pädagogischer/agogischer Hintergrund\nFreude an Menschen und Bewegungsförderung\nNothelferkurs\nAktueller Strafregisterauszug\nReferenzen und Ausbildungsnachweise";
	$shared_how_to_apply = "Motivationsschreiben, tabellarischer Lebenslauf, Abschlusszeugnisse, Nothelferkurs-Nachweis und aktueller Strafregisterauszug an schweiz@parkourone.com";
	$shared_email = 'schweiz@parkourone.com';

	$jobs = [
		// === Bern ===
		'kids_wankdorf' => [
			'title' => 'Klassenleitung Kids Wankdorf',
			'short_description' => 'Klassenleitung für Kids-Parkour-Klasse am Montag im Wankdorfstadion Bern.',
			'full_description' => 'Leitung einer Kids-Parkour-Klasse am Montag von 16:45–18:15 im Wankdorfstadion, Bern. Start: August 2026.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],
		'kids_laenggasse' => [
			'title' => 'Klassenleitung Kids Länggasse',
			'short_description' => 'Klassenleitung für Kids-Parkour-Klasse am Dienstag in der Länggasse, Bern.',
			'full_description' => 'Leitung einer Kids-Parkour-Klasse am Dienstag von 16:30–18:00 im Schulhaus Muesmatt, Bern. Start: August 2026.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],
		'kids_wabern' => [
			'title' => 'Klassenleitung Kids Wabern',
			'short_description' => 'Klassenleitung für Kids-Parkour-Klasse am Donnerstag in Wabern bei Bern.',
			'full_description' => 'Leitung einer Kids-Parkour-Klasse am Donnerstag von 16:30–18:00 im Morillon Schulhaus, Wabern. Start: August 2026.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],
		'ja_bern' => [
			'title' => 'Klassenleitung J&A Bern',
			'short_description' => 'Klassenleitung für Jugendliche & Erwachsene (J&A) Parkour-Klassen in Bern, zwei Abende pro Woche.',
			'full_description' => 'Leitung von Parkour-Klassen für Jugendliche und Erwachsene (J&A) in Bern. Gesucht wird eine Leitung für zwei Abende wöchentlich (Montag–Donnerstag). Start: August 2026 oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],
		'co_ja_bern_mo' => [
			'title' => 'Co-Leitung J&A Bern (Mo)',
			'short_description' => 'Co-Leitung der J&A-Parkour-Klasse am Montag in Bern-Wankdorf unter Frederic Gerber.',
			'full_description' => 'Co-Leitung der Parkour-Klasse für Jugendliche und Erwachsene am Montag von 18:30–20:30 in Bern, Wankdorf. Hauptleitung: Frederic Gerber. Start: August 2026.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],
		'co_ja_bern_mi' => [
			'title' => 'Co-Leitung J&A Bern (Mi)',
			'short_description' => 'Co-Leitung der J&A-Parkour-Klasse am Mittwoch im Gymnasium Kirchenfeld unter Frederic Gerber.',
			'full_description' => 'Co-Leitung der Parkour-Klasse für Jugendliche und Erwachsene am Mittwoch von 18:15–20:15 im Gymnasium Kirchenfeld, Bern. Hauptleitung: Frederic Gerber. Start: August 2026.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],
		'masters_bern' => [
			'title' => 'Klassenleitung Masters Bern',
			'short_description' => 'Klassenleitung der Masters-Parkour-Klasse am Montagabend in den Vidmarhallen, Bern.',
			'full_description' => 'Leitung der Masters-Parkour-Klasse am Montag von 18:30–20:00 in den Vidmarhallen, Bern. Start: Mai 2026 oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Bern',
		],

		// === Münsingen ===
		'co_kids_muensingen' => [
			'title' => 'Co-Leitung Kids Münsingen',
			'short_description' => 'Co-Leitung der Kids-Parkour-Klasse am Dienstag in Münsingen unter Vera Imboén.',
			'full_description' => 'Co-Leitung der Kids-Parkour-Klasse am Dienstag von 16:30–18:00 in der Mittelwegturnhalle, Münsingen. Hauptleitung: Vera Imboén. Start: per sofort oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Münsingen',
		],

		// === Zürich ===
		'klassenleitung_zuerich' => [
			'title' => 'Klassenleitung Zürich',
			'short_description' => 'Klassenleitung für Parkour-Klassen in Zürich. Flexible Tage Montag–Donnerstag.',
			'full_description' => 'Gesucht wird ein/e zuverlässige/r und motivierte/r Vollbluttraceur/in für die Leitung von Parkour-Klassen in Zürich. Verfügbare Tage: Montag–Donnerstag (flexible Planung). 2 Jahre Parkour-Erfahrung von Vorteil (aber nicht zwingend). Start: nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Zürich',
		],
		'co_kids_hardau' => [
			'title' => 'Co-Leitung Kids Hardau',
			'short_description' => 'Co-Leitung der Kids-Parkour-Klasse (6–12 Jahre) am Mittwoch im Hardaupark Zürich unter Luc Biege.',
			'full_description' => 'Co-Leitung der Kids-Parkour-Klasse für 6–12-Jährige am Mittwoch von 16:30–18:00 im Hardaupark, Zürich. Maximale Klassengrösse: 32. Hauptleitung: Luc Biege. Start: per sofort oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Zürich',
		],
		'co_kids_enge' => [
			'title' => 'Co-Leitung Kids Enge',
			'short_description' => 'Co-Leitung der Kids-Parkour-Klasse (6–12 Jahre) am Donnerstag in Zürich-Enge unter Luc Biege.',
			'full_description' => 'Co-Leitung der Kids-Parkour-Klasse für 6–12-Jährige am Donnerstag von 16:30–18:00 im Kantit Enge, Zürich. Maximale Klassengrösse: 32. Hauptleitung: Luc Biege. Start: nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Zürich',
		],

		// === Winterthur ===
		'klassenleitung_winterthur' => [
			'title' => 'Klassenleitung Winterthur',
			'short_description' => 'Klassenleitung für Parkour-Klassen in Winterthur. Grosse Nachfrage, Stundenplan verhandelbar.',
			'full_description' => 'Leitung von regulären Parkour-Klassen in Winterthur. Es besteht grosse Nachfrage nach einem regelmässigen Parkour-Angebot. Stundenplan ist verhandelbar. Start: per sofort oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Winterthur',
		],

		// === St. Gallen ===
		'klassenleitung_stgallen' => [
			'title' => 'Klassenleitung St. Gallen',
			'short_description' => 'Klassenleitung für Parkour-Klassen in St. Gallen. Hohe Nachfrage, Stundenplan verhandelbar.',
			'full_description' => 'Leitung von regulären Parkour-Klassen in St. Gallen. Hohe Nachfrage nach einem Parkour-Angebot. Stundenplan ist verhandelbar. Start: per sofort oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'St. Gallen',
		],

		// === Brig ===
		'stellvertretung_brig' => [
			'title' => 'Stellvertretung Brig',
			'short_description' => 'Stellvertretung für Parkour-Klassen in Brig.',
			'full_description' => 'Stellvertretung für Parkour-Klassen in Brig. Stundenplan ist verhandelbar. Start: per sofort oder nach Vereinbarung.',
			'type' => 'Teilzeit',
			'location' => 'Brig',
		],
	];

	// Gemeinsame Felder in jeden Preset einfügen
	foreach ($jobs as &$job) {
		$job['requirements'] = $shared_requirements;
		$job['how_to_apply'] = $shared_how_to_apply;
		$job['contact_email'] = $shared_email;
	}
	unset($job);

	return $jobs;
}

// =====================================================
// Setup Wizard: Admin Notice
// =====================================================

function parkourone_job_setup_notice() {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'job') {
		return;
	}

	if (get_option('parkourone_jobs_setup_done')) {
		return;
	}

	$count = wp_count_posts('job');
	if ($count->publish > 0 || $count->draft > 0) {
		update_option('parkourone_jobs_setup_done', true);
		return;
	}

	$presets = parkourone_get_preset_jobs();
	$current_location = '';
	?>
	<div class="notice notice-info" id="po-jobs-setup-notice">
		<h2>Willkommen bei den Jobs!</h2>
		<p>Wähle die Stellen aus, die du für deine Schule übernehmen möchtest. Du kannst sie danach beliebig anpassen.</p>

		<form id="po-jobs-setup-form" method="post">
			<?php wp_nonce_field('po_jobs_setup', 'po_jobs_nonce'); ?>
			<input type="hidden" name="action" value="po_jobs_setup">

			<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.5rem 0;">
				<?php foreach ($presets as $key => $job):
					if ($job['location'] !== $current_location):
						$current_location = $job['location'];
						?>
						<div style="grid-column: 1 / -1; margin-top: 0.5rem;">
							<strong style="font-size: 14px; color: #1d2327;"><?php echo esc_html($current_location); ?></strong>
						</div>
					<?php endif; ?>
				<label style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 1rem; background: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
					<input type="checkbox" name="jobs[]" value="<?php echo esc_attr($key); ?>" checked style="margin-top: 3px;">
					<div>
						<strong><?php echo esc_html($job['title']); ?></strong><br>
						<small style="color: #666;"><?php echo esc_html($job['short_description']); ?></small>
					</div>
				</label>
				<?php endforeach; ?>
			</div>

			<p>
				<button type="submit" class="button button-primary button-large">Ausgewählte Jobs anlegen</button>
				<button type="button" class="button button-secondary" id="po-skip-jobs-setup" style="margin-left: 0.5rem;">Überspringen</button>
			</p>
		</form>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-jobs-setup-form').on('submit', function(e) {
			e.preventDefault();
			var $form = $(this);
			var $btn = $form.find('button[type="submit"]');
			$btn.prop('disabled', true).text('Wird angelegt...');

			$.post(ajaxurl, $form.serialize(), function(response) {
				if (response.success) {
					$('#po-jobs-setup-notice').html(
						'<p><strong>✓ ' + response.data.count + ' Jobs wurden angelegt!</strong> Die Seite wird neu geladen...</p>'
					);
					setTimeout(function() { location.reload(); }, 1500);
				} else {
					alert('Fehler: ' + response.data);
					$btn.prop('disabled', false).text('Ausgewählte Jobs anlegen');
				}
			});
		});

		$('#po-skip-jobs-setup').on('click', function() {
			$.post(ajaxurl, { action: 'po_jobs_skip_setup' }, function() {
				$('#po-jobs-setup-notice').fadeOut();
			});
		});
	});
	</script>
	<?php
}
add_action('admin_notices', 'parkourone_job_setup_notice');

// =====================================================
// Setup Wizard: AJAX Handler
// =====================================================

function parkourone_job_do_setup() {
	check_ajax_referer('po_jobs_setup', 'po_jobs_nonce');

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Keine Berechtigung');
	}

	$selected = $_POST['jobs'] ?? [];
	if (empty($selected)) {
		update_option('parkourone_jobs_setup_done', true);
		wp_send_json_success(['count' => 0]);
	}

	$presets = parkourone_get_preset_jobs();
	$count = 0;

	foreach ($selected as $key) {
		if (!isset($presets[$key])) continue;

		$data = $presets[$key];

		$post_id = wp_insert_post([
			'post_type'   => 'job',
			'post_title'  => $data['title'],
			'post_status' => 'publish',
			'menu_order'  => $count,
		]);

		if (is_wp_error($post_id)) continue;

		$meta_map = [
			'type'              => '_job_type',
			'short_description' => '_job_short_description',
			'full_description'  => '_job_full_description',
			'requirements'      => '_job_requirements',
			'how_to_apply'      => '_job_how_to_apply',
			'contact_email'     => '_job_contact_email',
		];

		foreach ($meta_map as $data_key => $meta_key) {
			if (!empty($data[$data_key])) {
				update_post_meta($post_id, $meta_key, $data[$data_key]);
			}
		}

		$count++;
	}

	update_option('parkourone_jobs_setup_done', true);
	wp_send_json_success(['count' => $count]);
}
add_action('wp_ajax_po_jobs_setup', 'parkourone_job_do_setup');

// =====================================================
// Setup Wizard: Skip Handler
// =====================================================

function parkourone_job_skip_setup() {
	update_option('parkourone_jobs_setup_done', true);
	wp_send_json_success();
}
add_action('wp_ajax_po_jobs_skip_setup', 'parkourone_job_skip_setup');
