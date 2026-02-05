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
