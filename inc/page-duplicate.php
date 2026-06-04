<?php
/**
 * Seiten (und andere Inhalte) im wp-admin duplizieren.
 *
 * Fügt in der Übersicht (Seiten, Beiträge, Custom Post Types) eine Aktion
 * „Duplizieren" hinzu, die den Inhalt inkl. Blöcke, Template, Meta und
 * Taxonomien als Entwurf kopiert und direkt im Editor öffnet.
 *
 * Bewusst NICHT auf bestimmte Post-Types beschränkt: greift überall, wo der/die
 * Nutzer:in den Inhaltstyp bearbeiten darf.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Baut den nonce-gesicherten Duplizieren-Link für eine Post-ID.
 */
function parkourone_duplicate_link($post_id) {
	$url = add_query_arg(
		[
			'action' => 'parkourone_duplicate_post',
			'post'   => $post_id,
		],
		admin_url('admin.php')
	);
	return wp_nonce_url($url, 'parkourone_duplicate_' . $post_id, 'po_dup_nonce');
}

/**
 * „Duplizieren"-Aktion in die Zeilen-Aktionen der Listentabelle einhängen.
 */
function parkourone_add_duplicate_row_action($actions, $post) {
	if (!is_object($post) || empty($post->ID)) {
		return $actions;
	}

	$pto = get_post_type_object($post->post_type);
	// Nur anbieten, wenn der Typ in der UI sichtbar ist und der/die Nutzer:in
	// neue Inhalte dieses Typs anlegen darf.
	if (!$pto || empty($pto->show_ui) || !current_user_can($pto->cap->create_posts ?? 'edit_posts')) {
		return $actions;
	}

	$actions['parkourone_duplicate'] = sprintf(
		'<a href="%s" title="%s">%s</a>',
		esc_url(parkourone_duplicate_link($post->ID)),
		esc_attr__('Diesen Inhalt als Entwurf duplizieren', 'parkourone'),
		esc_html__('Duplizieren', 'parkourone')
	);

	return $actions;
}
add_filter('page_row_actions', 'parkourone_add_duplicate_row_action', 10, 2);
add_filter('post_row_actions', 'parkourone_add_duplicate_row_action', 10, 2);

/**
 * Führt die Duplizierung aus und leitet in den Editor der Kopie weiter.
 */
function parkourone_handle_duplicate_post() {
	$post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

	if (!$post_id
		|| !isset($_GET['po_dup_nonce'])
		|| !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['po_dup_nonce'])), 'parkourone_duplicate_' . $post_id)) {
		wp_die(esc_html__('Ungültige Anfrage zum Duplizieren.', 'parkourone'));
	}

	$post = get_post($post_id);
	if (!$post) {
		wp_die(esc_html__('Der zu duplizierende Inhalt wurde nicht gefunden.', 'parkourone'));
	}

	$pto = get_post_type_object($post->post_type);
	if (!$pto || !current_user_can($pto->cap->create_posts ?? 'edit_posts')) {
		wp_die(esc_html__('Keine Berechtigung zum Duplizieren.', 'parkourone'));
	}

	// 1) Neuen Post als Entwurf anlegen (Inhalt/Blöcke 1:1 übernehmen).
	$new_id = wp_insert_post([
		'post_type'      => $post->post_type,
		'post_status'    => 'draft',
		'post_title'     => $post->post_title . ' (Kopie)',
		'post_content'   => $post->post_content,
		'post_excerpt'   => $post->post_excerpt,
		'post_parent'    => $post->post_parent,
		'menu_order'     => $post->menu_order,
		'comment_status' => $post->comment_status,
		'ping_status'    => $post->ping_status,
		'post_author'    => get_current_user_id(),
	], true);

	if (is_wp_error($new_id) || !$new_id) {
		wp_die(esc_html__('Der Inhalt konnte nicht dupliziert werden.', 'parkourone'));
	}

	// 2) Taxonomien übernehmen.
	$taxonomies = get_object_taxonomies($post->post_type);
	foreach ($taxonomies as $taxonomy) {
		$term_ids = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
		if (!is_wp_error($term_ids) && !empty($term_ids)) {
			wp_set_object_terms($new_id, $term_ids, $taxonomy);
		}
	}

	// 3) Post-Meta übernehmen – inkl. _wp_page_template (das Seiten-Template),
	//    damit die Kopie dieselbe Vorlage nutzt. Interne/Sperr-Meta auslassen.
	$skip_meta = ['_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date'];
	$meta = get_post_meta($post_id);
	foreach ($meta as $key => $values) {
		if (in_array($key, $skip_meta, true)) {
			continue;
		}
		foreach ($values as $value) {
			add_post_meta($new_id, $key, maybe_unserialize($value));
		}
	}

	// 4) In den Editor der Kopie weiterleiten.
	wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id));
	exit;
}
add_action('admin_action_parkourone_duplicate_post', 'parkourone_handle_duplicate_post');
