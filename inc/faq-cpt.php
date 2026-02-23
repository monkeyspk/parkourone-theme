<?php
/**
 * FAQ Custom Post Type
 * Ermöglicht das Verwalten von häufig gestellten Fragen
 */

defined('ABSPATH') || exit;

// =====================================================
// Custom Post Type Registration
// =====================================================

function parkourone_register_faq_cpt() {
	register_post_type('faq', [
		'labels' => [
			'name' => 'FAQs',
			'singular_name' => 'FAQ',
			'add_new' => 'Neue FAQ',
			'add_new_item' => 'Neue FAQ hinzufügen',
			'edit_item' => 'FAQ bearbeiten',
			'view_item' => 'FAQ ansehen',
			'all_items' => 'Alle FAQs',
			'search_items' => 'FAQs suchen',
			'not_found' => 'Keine FAQs gefunden',
			'menu_name' => 'FAQs'
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 27,
		'menu_icon' => 'dashicons-editor-help',
		'supports' => ['title'],
		'has_archive' => false,
		'rewrite' => false,
		'show_in_rest' => true
	]);
}
add_action('init', 'parkourone_register_faq_cpt');

// =====================================================
// Meta Box
// =====================================================

function parkourone_faq_meta_box() {
	add_meta_box(
		'faq_details',
		'FAQ Details',
		'parkourone_faq_meta_box_html',
		'faq',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'parkourone_faq_meta_box');

/**
 * Liefert alle FAQ-Kategorien: Standard-Kategorien + custom aus der DB
 */
function parkourone_get_all_faq_categories() {
	// Standard-Kategorien (für Auto-Pages relevant)
	$default_categories = [
		'allgemein' => 'Allgemein',
		'probetraining' => 'Probetraining',
		'mitgliedschaft' => 'Mitgliedschaft',
		'kids' => 'Kids & Minis',
		'juniors' => 'Juniors',
		'adults' => 'Adults',
		'workshops' => 'Workshops & Kurse',
		'standort' => 'Standort',
		'gutschein' => 'Gutschein',
	];

	// Alle verwendeten Kategorien aus der DB lesen
	global $wpdb;
	$db_categories = $wpdb->get_col(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
		 WHERE meta_key = '_faq_category' AND meta_value != ''
		 ORDER BY meta_value ASC"
	);

	// Custom-Kategorien hinzufügen (die nicht in den Standards sind)
	foreach ($db_categories as $cat) {
		if (!isset($default_categories[$cat])) {
			$default_categories[$cat] = ucfirst($cat);
		}
	}

	return $default_categories;
}

function parkourone_faq_meta_box_html($post) {
	wp_nonce_field('faq_meta', 'faq_meta_nonce');

	$answer = get_post_meta($post->ID, '_faq_answer', true);
	$category = get_post_meta($post->ID, '_faq_category', true);
	$additional_categories = get_post_meta($post->ID, '_faq_additional_categories', true) ?: [];
	$order = get_post_meta($post->ID, '_faq_order', true) ?: 0;

	// Alle Kategorien (Standard + Custom aus DB)
	$all_categories = parkourone_get_all_faq_categories();

	// Zusätzliche Seiten (optional)
	$extra_pages = [
		'startseite' => 'Startseite',
		'kids' => 'Kids-Seite',
		'juniors' => 'Juniors-Seite',
		'adults' => 'Adults-Seite',
		'workshops' => 'Kurse & Workshops',
		'standort' => 'Standort-Seiten',
	];
	?>
	<table class="form-table">
		<tr>
			<th><label for="_faq_answer">Antwort</label></th>
			<td>
				<?php
				wp_editor($answer, '_faq_answer', [
					'textarea_rows' => 8,
					'media_buttons' => false,
					'teeny' => true,
					'quicktags' => true
				]);
				?>
				<p class="description">Die ausführliche Antwort auf die Frage (im Titel).</p>
			</td>
		</tr>
		<tr>
			<th><label for="_faq_category">Haupt-Kategorie</label></th>
			<td>
				<input type="text" id="_faq_category" name="_faq_category" value="<?php echo esc_attr($category); ?>" class="regular-text" list="faq-categories-list" placeholder="Kategorie wählen oder neue eingeben...">
				<datalist id="faq-categories-list">
					<?php foreach ($all_categories as $value => $label): ?>
						<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</datalist>
				<p class="description">Bestehende Kategorie wählen oder neuen Slug eingeben (z.B. "klassenwechsel").</p>
			</td>
		</tr>
		<tr>
			<th>Zusätzlich anzeigen auf</th>
			<td>
				<fieldset>
					<?php foreach ($extra_pages as $value => $label): ?>
						<label style="display: block; margin-bottom: 5px;">
							<input type="checkbox" name="_faq_additional_categories[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, (array)$additional_categories)); ?>>
							<?php echo esc_html($label); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">Optional: FAQ auch auf diesen Seiten anzeigen (unabhängig von Haupt-Kategorie).</p>
			</td>
		</tr>
		<tr>
			<th><label for="_faq_order">Reihenfolge</label></th>
			<td>
				<input type="number" id="_faq_order" name="_faq_order" value="<?php echo esc_attr($order); ?>" class="small-text" min="0" step="1">
				<p class="description">Niedrigere Zahlen werden zuerst angezeigt.</p>
			</td>
		</tr>
	</table>
	<?php
}

function parkourone_save_faq_meta($post_id) {
	if (!isset($_POST['faq_meta_nonce']) || !wp_verify_nonce($_POST['faq_meta_nonce'], 'faq_meta')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['_faq_answer'])) {
		update_post_meta($post_id, '_faq_answer', wp_kses_post($_POST['_faq_answer']));
	}

	if (isset($_POST['_faq_category'])) {
		update_post_meta($post_id, '_faq_category', sanitize_text_field($_POST['_faq_category']));
	}

	if (isset($_POST['_faq_order'])) {
		update_post_meta($post_id, '_faq_order', absint($_POST['_faq_order']));
	}

	// Zusätzliche Kategorien speichern (Checkboxen)
	if (isset($_POST['_faq_additional_categories'])) {
		$additional = array_map('sanitize_text_field', (array) $_POST['_faq_additional_categories']);
		update_post_meta($post_id, '_faq_additional_categories', $additional);
	} else {
		// Keine Checkboxen ausgewählt = leeres Array
		update_post_meta($post_id, '_faq_additional_categories', []);
	}
}
add_action('save_post_faq', 'parkourone_save_faq_meta');

// =====================================================
// Admin Columns
// =====================================================

function parkourone_faq_columns($columns) {
	return [
		'cb' => $columns['cb'],
		'title' => 'Frage',
		'answer_preview' => 'Antwort',
		'category' => 'Kategorie',
		'appears_on' => 'Erscheint auf',
		'order' => 'Reihenfolge',
		'date' => 'Datum'
	];
}
add_filter('manage_faq_posts_columns', 'parkourone_faq_columns');

/**
 * Mapping: Kategorie → Seiten wo sie erscheint
 */
function parkourone_get_category_pages_map() {
	return [
		'allgemein' => 'Startseite + alle',
		'probetraining' => 'Altersgruppen + Standorte',
		'mitgliedschaft' => 'Mitgliedschaft',
		'kids' => 'Kids-Seite',
		'juniors' => 'Juniors-Seite',
		'adults' => 'Adults-Seite',
		'workshops' => 'Kurse & Workshops',
		'standort' => 'Standort-Seiten',
		'startseite' => 'Startseite',
		'gutschein' => 'Gutschein-Seite',
	];
}

/**
 * Berechnet auf welchen Seiten eine FAQ erscheint (Haupt + zusätzliche Kategorien)
 */
function parkourone_get_faq_appearances($post_id) {
	$appearances = [];
	$category_map = parkourone_get_category_pages_map();

	// Haupt-Kategorie
	$main_category = get_post_meta($post_id, '_faq_category', true);
	if (!empty($main_category)) {
		$appearances['main'] = $category_map[$main_category] ?? ucfirst($main_category);
	}

	// Zusätzliche Kategorien
	$additional = get_post_meta($post_id, '_faq_additional_categories', true) ?: [];
	$additional_pages = [];
	foreach ((array) $additional as $cat) {
		if (isset($category_map[$cat])) {
			$additional_pages[] = $category_map[$cat];
		}
	}
	if (!empty($additional_pages)) {
		$appearances['additional'] = $additional_pages;
	}

	return $appearances;
}

function parkourone_faq_column_content($column, $post_id) {
	switch ($column) {
		case 'answer_preview':
			$answer = get_post_meta($post_id, '_faq_answer', true);
			echo esc_html(wp_trim_words(wp_strip_all_tags($answer), 15, '...'));
			break;

		case 'category':
			$cat = get_post_meta($post_id, '_faq_category', true);
			$all_categories = parkourone_get_all_faq_categories();
			$label = $all_categories[$cat] ?? ($cat ? ucfirst($cat) : '—');

			// Farbcodierung nach Kategorie
			$colors = [
				'allgemein' => '#2271b1',
				'probetraining' => '#00a32a',
				'mitgliedschaft' => '#dba617',
				'kids' => '#d63638',
				'juniors' => '#9b59b6',
				'adults' => '#3498db',
				'workshops' => '#e67e22',
				'standort' => '#1abc9c',
				'gutschein' => '#8e44ad',
			];
			$color = $colors[$cat] ?? '#666';

			echo '<span style="background:' . esc_attr($color) . '; color:#fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html($label) . '</span>';
			break;

		case 'appears_on':
			$appearances = parkourone_get_faq_appearances($post_id);

			if (!empty($appearances['main']) || !empty($appearances['additional'])) {
				echo '<span style="color: #00a32a; font-size: 14px;">✓</span> ';

				// Haupt-Kategorie (fett)
				if (!empty($appearances['main'])) {
					echo '<strong style="color: #1d2327; font-size: 12px;">' . esc_html($appearances['main']) . '</strong>';
				}

				// Zusätzliche Kategorien (mit + Zeichen)
				if (!empty($appearances['additional'])) {
					foreach ($appearances['additional'] as $page) {
						echo '<span style="color: #2271b1; font-size: 11px; margin-left: 5px;">+ ' . esc_html($page) . '</span>';
					}
				}
			} else {
				echo '<span style="color: #d63638;">✗ Nicht zugewiesen</span>';
			}
			break;

		case 'order':
			echo esc_html(get_post_meta($post_id, '_faq_order', true) ?: 0);
			break;
	}
}
add_action('manage_faq_posts_custom_column', 'parkourone_faq_column_content', 10, 2);

// Sortierbar machen
function parkourone_faq_sortable_columns($columns) {
	$columns['order'] = 'order';
	$columns['category'] = 'category';
	return $columns;
}
add_filter('manage_edit-faq_sortable_columns', 'parkourone_faq_sortable_columns');

function parkourone_faq_orderby($query) {
	if (!is_admin() || !$query->is_main_query()) return;
	if ($query->get('post_type') !== 'faq') return;

	$orderby = $query->get('orderby');

	if ($orderby === 'order') {
		$query->set('meta_key', '_faq_order');
		$query->set('orderby', 'meta_value_num');
	}

	if ($orderby === 'category') {
		$query->set('meta_key', '_faq_category');
		$query->set('orderby', 'meta_value');
	}
}
add_action('pre_get_posts', 'parkourone_faq_orderby');

// =====================================================
// Helper Function: FAQs abrufen
// =====================================================

/**
 * Holt FAQs, optional gefiltert nach Kategorie
 * Berücksichtigt sowohl Haupt-Kategorie als auch zusätzliche Kategorien
 *
 * @param string $category Optional: FAQ-Kategorie (z.B. 'probetraining')
 * @param int $limit Optional: Max. Anzahl (0 = alle)
 * @return array FAQs mit 'question' und 'answer'
 */
function parkourone_get_faqs($category = '', $limit = 0) {
	$args = [
		'post_type' => 'faq',
		'post_status' => 'publish',
		'posts_per_page' => $limit > 0 ? $limit : -1,
		'orderby' => 'meta_value_num',
		'meta_key' => '_faq_order',
		'order' => 'ASC'
	];

	if (!empty($category)) {
		// Suche in Haupt-Kategorie ODER zusätzlichen Kategorien
		$args['meta_query'] = [
			'relation' => 'OR',
			[
				'key' => '_faq_category',
				'value' => $category,
				'compare' => '='
			],
			[
				'key' => '_faq_additional_categories',
				'value' => '"' . $category . '"',
				'compare' => 'LIKE'
			]
		];
	}

	$posts = get_posts($args);
	$faqs = [];

	foreach ($posts as $post) {
		$faqs[] = [
			'question' => $post->post_title,
			'answer' => get_post_meta($post->ID, '_faq_answer', true),
			'category' => get_post_meta($post->ID, '_faq_category', true)
		];
	}

	return $faqs;
}

/**
 * Holt FAQs für einen Seitentyp - kombiniert spezifische + allgemeine FAQs
 *
 * Reihenfolge der FAQs:
 * 1. Spezifische Kategorie (z.B. kids, standort)
 * 2. Probetraining (immer relevant für Altersgruppen/Standorte)
 * 3. Allgemein (Was ist Parkour?, etc.)
 *
 * @param string|array $page_types Seitentyp(en) z.B. 'kids', 'standort', ['kids', 'workshops']
 * @param int $limit Max. Anzahl FAQs (0 = unbegrenzt)
 * @return array FAQs mit question, answer, category
 */
function parkourone_get_page_faqs($page_types = '', $limit = 6) {
	$categories = [];

	// Normalize to array
	if (!empty($page_types)) {
		$page_types = is_array($page_types) ? $page_types : [$page_types];

		// Map page types to FAQ categories
		$category_map = [
			'kids' => 'kids',
			'minis' => 'kids',
			'juniors' => 'juniors',
			'adults' => 'adults',
			'women' => 'adults',
			'original' => 'adults',
			'workshops' => 'workshops',
			'kurse' => 'workshops',
			'standort' => 'standort',
			'probetraining' => 'probetraining',
		];

		foreach ($page_types as $type) {
			$type_lower = strtolower($type);
			if (isset($category_map[$type_lower])) {
				$categories[] = $category_map[$type_lower];
			}
		}
	}

	// Für Altersgruppen und Standorte: Probetraining-FAQs hinzufügen
	$age_categories = ['kids', 'juniors', 'adults'];
	$has_age_or_location = !empty(array_intersect($categories, array_merge($age_categories, ['standort'])));

	if ($has_age_or_location && !in_array('probetraining', $categories)) {
		$categories[] = 'probetraining';
	}

	// Allgemein immer am Ende
	$categories[] = 'allgemein';
	$categories = array_unique($categories);

	// FAQs für alle Kategorien holen (in Reihenfolge)
	$all_faqs = [];
	$seen_questions = []; // Duplikate vermeiden

	foreach ($categories as $cat) {
		$cat_faqs = parkourone_get_faqs($cat, 0);
		foreach ($cat_faqs as $faq) {
			// Duplikate überspringen
			if (in_array($faq['question'], $seen_questions)) {
				continue;
			}
			$seen_questions[] = $faq['question'];
			$all_faqs[] = $faq;
		}
	}

	// Limit anwenden
	if ($limit > 0 && count($all_faqs) > $limit) {
		$all_faqs = array_slice($all_faqs, 0, $limit);
	}

	return $all_faqs;
}

/**
 * Generiert Schema.org FAQPage JSON-LD Markup
 *
 * @param array $faqs Array von FAQs mit 'question' und 'answer'
 * @return string JSON-LD Script Tag
 */
function parkourone_generate_faq_schema($faqs) {
	if (empty($faqs)) {
		return '';
	}

	$faq_items = [];
	foreach ($faqs as $faq) {
		$faq_items[] = [
			'@type' => 'Question',
			'name' => wp_strip_all_tags($faq['question']),
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text' => wp_strip_all_tags($faq['answer'])
			]
		];
	}

	$schema = [
		'@context' => 'https://schema.org',
		'@type' => 'FAQPage',
		'mainEntity' => $faq_items
	];

	return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

// =====================================================
// Standard-FAQs zum Importieren
// =====================================================

/**
 * Liefert die Standard-FAQs für ParkourONE
 * Optimiert für Google & LLM-Suche (ChatGPT, Perplexity, etc.)
 *
 * OFFIZIELLE TEXTE - von ParkourONE freigegeben
 */
function parkourone_get_default_faqs() {
	return [
		// =====================================================
		// ALLGEMEIN - Für alle Seiten
		// =====================================================
		[
			'question' => 'Was ist Parkour?',
			'answer' => '<p>Parkour ist die Kunst, sich effizient und kreativ durch den Raum zu bewegen. Du lernst, Hindernisse wie Mauern, Geländer und Treppen sicher zu überwinden – mit Techniken wie Rollen, Springen, Klettern und Balancieren.</p><p>Aber Parkour ist mehr als Sport. Bei ParkourONE arbeiten wir mit TRUST Education – unserer eigenen pädagogischen Methode, die wir über Jahre entwickelt haben. TRUST steht für die Werte, die wir in jeder Trainingseinheit leben: Vertrauen in dich selbst aufbauen, Respekt im Umgang mit anderen und der Umgebung, und die Fähigkeit, Herausforderungen Schritt für Schritt zu meistern.</p><p>Parkour trainiert deinen Körper und deinen Geist gleichermassen. Du lernst, Ängste zu überwinden, Probleme kreativ zu lösen und deine eigenen Grenzen neu zu definieren.</p>',
			'category' => 'allgemein',
			'order' => 1
		],
		[
			'question' => 'Brauche ich Vorkenntnisse?',
			'answer' => '<p>Nein, Anfänger sind herzlich willkommen. Unsere Klassen sind so aufgebaut, dass du ohne Vorkenntnisse starten kannst. Die Coaches passen das Training an dein Level an und begleiten dich Schritt für Schritt.</p>',
			'category' => 'allgemein',
			'order' => 2
		],
		[
			'question' => 'Ist Parkour gefährlich?',
			'answer' => '<p>Bei richtiger Anleitung ist Parkour ein sehr sicherer Sport. Du trainierst unter professioneller Aufsicht mit qualifizierten Coaches. Wir bauen Techniken schrittweise auf – vom Einfachen zum Komplexen. Sicherheit steht bei uns an erster Stelle.</p>',
			'category' => 'allgemein',
			'order' => 3
		],
		[
			'question' => 'Was muss ich zum Training mitbringen?',
			'answer' => '<p>Das Schöne an Parkour: Du brauchst keine spezielle Ausrüstung. Nur dich selbst. Wir empfehlen:</p><ul><li>Bequeme Sportkleidung</li><li>Sportschuhe mit guter Sohle (keine Sandalen)</li><li>Wasser zum Trinken</li></ul><p>Wir trainieren bei jedem Wetter – auch draussen. Im Winter empfehlen wir mehrere Schichten und einen Rucksack mit trockenen Ersatzsachen für nach dem Training.</p>',
			'category' => 'allgemein',
			'order' => 4
		],

		// =====================================================
		// PROBETRAINING
		// =====================================================
		[
			'question' => 'Wie funktioniert das Probetraining?',
			'answer' => '<p>Das Probetraining ist dein erster Schritt. Wähle einen Standort und eine Altersgruppe, buche online und komm vorbei. Du lernst die Basics, triffst die Gruppe und bekommst ein Gefühl dafür, ob ParkourONE zu dir passt. Danach entscheidest du, ob du dabei bleiben möchtest.</p>',
			'category' => 'probetraining',
			'order' => 1
		],
		[
			'question' => 'Was kostet das Probetraining?',
			'answer' => '<p>Das Probetraining kostet 15 CHF bzw. 15 €. Dieser Betrag wird einmalig beim Buchen fällig.</p>',
			'category' => 'probetraining',
			'order' => 2
		],
		[
			'question' => 'Kann ich das Probetraining jederzeit buchen?',
			'answer' => '<p>Ja, du kannst online buchen und an einem regulären Klassentermin teilnehmen. Wähle einfach einen passenden Termin in deiner Nähe.</p>',
			'category' => 'probetraining',
			'order' => 3
		],

		// =====================================================
		// MITGLIEDSCHAFT
		// =====================================================
		[
			'question' => 'Was kostet eine Mitgliedschaft?',
			'answer' => '<p>Die Preise variieren je nach Standort und Altersgruppe. Kontaktiere uns für ein Angebot oder komm einfach für ein unverbindliches Probetraining vorbei.</p>',
			'category' => 'mitgliedschaft',
			'order' => 1
		],
		[
			'question' => 'Kann ich die Mitgliedschaft pausieren oder kündigen?',
			'answer' => '<p>Du kannst jederzeit kündigen. Pausieren ist leider nicht möglich – wir möchten allen Interessierten die Chance geben, einen Platz in unseren Klassen zu bekommen.</p><p>Die Kündigungsfrist beträgt in der Regel einen Monat zum Monatsende. Bei Kids-Klassen in der Schweiz gilt eine Frist von drei Monaten zum Quartalsende. Details findest du in deinem Vertrag.</p>',
			'category' => 'mitgliedschaft',
			'order' => 2
		],

		// =====================================================
		// KIDS - Für Kinder-Seiten
		// =====================================================
		[
			'question' => 'Ab welchem Alter können Kinder starten?',
			'answer' => '<p>Wir bieten Klassen ab 4 Jahren an:</p><ul><li><strong>Kids (4–12 Jahre):</strong> Spielerischer Einstieg in grundlegende Parkour-Techniken. Je jünger die Kinder, desto mehr steht Spass und Entdecken im Vordergrund.</li><li><strong>Juniors (12–18 Jahre):</strong> Fortgeschrittene Techniken und mehr Eigenverantwortung. Hier geht es auch um mentale Stärke und Community.</li><li><strong>Adults (18+):</strong> Training für Erwachsene jeden Alters und Fitnesslevels. Du arbeitest an deinen persönlichen Zielen.</li></ul>',
			'category' => 'kids',
			'order' => 1
		],
		[
			'question' => 'Müssen Eltern beim Kindertraining dabei sein?',
			'answer' => '<p>Nein. Die Kinder sollen sich frei und unabhängig entwickeln können. Unsere qualifizierten Coaches betreuen sie während der gesamten Trainingszeit. Beim ersten Probetraining darf ein Elternteil dabei sein, um sich ein Bild zu machen.</p>',
			'category' => 'kids',
			'order' => 2
		],
		[
			'question' => 'Ist Parkour für mein Kind geeignet?',
			'answer' => '<p>Parkour ist für alle Kinder geeignet. Die Sportart fördert Koordination, Gleichgewicht, Kraft und Selbstvertrauen. Kinder lernen, ihren Körper besser wahrzunehmen und Herausforderungen zu meistern. Das Training wird an das individuelle Niveau jedes Kindes angepasst.</p>',
			'category' => 'kids',
			'order' => 3
		],

		// =====================================================
		// JUNIORS - Für Jugendliche-Seiten
		// =====================================================
		[
			'question' => 'Was lernen Jugendliche bei Parkour Juniors?',
			'answer' => '<p>In den Juniors-Klassen lernst du fortgeschrittene Techniken wie Präzisionssprünge, Wall-Runs, Vaults und Flips. Neben dem körperlichen Training fördern wir mentale Stärke, Selbstständigkeit und den respektvollen Umgang in der Community. Du wirst Teil einer Gruppe, die sich gegenseitig unterstützt und weiterbringt.</p>',
			'category' => 'juniors',
			'order' => 1
		],

		// =====================================================
		// ADULTS - Für Erwachsenen-Seiten
		// =====================================================
		[
			'question' => 'Bin ich zu alt für Parkour?',
			'answer' => '<p>Nein. Bei uns trainieren Erwachsene von Anfang 20 bis über 60. Parkour lässt sich an jedes Fitnesslevel anpassen. Du trainierst in deinem eigenen Tempo und wächst mit jeder Einheit.</p>',
			'category' => 'adults',
			'order' => 1
		],
		[
			'question' => 'Muss ich fit sein, um zu starten?',
			'answer' => '<p>Nein. Die Fitness kommt mit dem Training. Unsere Klassen sind so gestaltet, dass jeder auf seinem Level mitmachen kann. Schon nach wenigen Wochen wirst du Verbesserungen bei Kraft, Ausdauer und Beweglichkeit merken.</p>',
			'category' => 'adults',
			'order' => 2
		],

		// =====================================================
		// WORKSHOPS - Für Kurse & Workshops Seite
		// =====================================================
		[
			'question' => 'Was ist der Unterschied zwischen Klassen und Workshops?',
			'answer' => '<p><strong>Klassen</strong> sind wöchentliche Trainings mit fortlaufender Mitgliedschaft – ideal für kontinuierlichen Fortschritt.</p><p><strong>Workshops und Ferienkurse</strong> sind Einzeltermine oder mehrtägige Intensivkurse zu speziellen Themen – perfekt zum Reinschnuppern oder zur Vertiefung bestimmter Techniken.</p>',
			'category' => 'workshops',
			'order' => 1
		],
		[
			'question' => 'Bietet ihr Ferienkurse an?',
			'answer' => '<p>Ja. In den Schulferien bieten wir spezielle Kurse für Kinder und Jugendliche an. Diese mehrtägigen Intensivkurse sind ideal, um Parkour auszuprobieren oder bestehende Skills zu verbessern. Die aktuellen Termine findest du in unserem Kursangebot.</p>',
			'category' => 'workshops',
			'order' => 2
		],

		// =====================================================
		// STANDORT - Für Ortschafts-Seiten
		// =====================================================
		[
			'question' => 'Wo findet das Training statt?',
			'answer' => '<p>Das Training findet je nach Wetter und Standort indoor oder outdoor statt. Wir nutzen öffentliche Plätze, Parks und Parkour-Spots. Der genaue Treffpunkt wird dir für jede Klasse angezeigt.</p>',
			'category' => 'standort',
			'order' => 1
		],
		[
			'question' => 'Trainiert ihr auch bei schlechtem Wetter?',
			'answer' => '<p>Ja. Parkour ist eine Outdoor-Sportart. Wir lernen, mit verschiedenen Bedingungen umzugehen und uns anzupassen. Bei extremem Wetter wie Gewitter oder Eisregen informieren wir dich rechtzeitig über Änderungen.</p>',
			'category' => 'standort',
			'order' => 2
		],

		// =====================================================
		// ZUSÄTZLICHE ALLGEMEINE FAQs
		// =====================================================
		[
			'question' => 'Wie gross sind die Trainingsgruppen?',
			'answer' => '<p>Pro Coach trainieren maximal 16 Personen. Je nach Coach und Klasse kann die Gruppengrösse leicht variieren – zwischen 14 und 18 Teilnehmenden. So stellen wir sicher, dass jeder individuell betreut wird.</p>',
			'category' => 'allgemein',
			'order' => 5
		],
		[
			'question' => 'Wie lange dauert eine Trainingseinheit?',
			'answer' => '<p>Das variiert je nach Klasse und Standort. Die genaue Dauer findest du in der Übersicht der jeweiligen Klasse.</p>',
			'category' => 'allgemein',
			'order' => 6
		],
		[
			'question' => 'Gibt es verschiedene Levels innerhalb der Klassen?',
			'answer' => '<p>Nein, wir arbeiten nicht mit festen Levels. Unsere Coaches gestalten jede Übung so, dass du – egal wo du stehst – an deine persönlichen Grenzen kommst. Das Training passt sich dir an, nicht umgekehrt.</p>',
			'category' => 'allgemein',
			'order' => 7
		],
		[
			'question' => 'Wer sind die Coaches und welche Ausbildung haben sie?',
			'answer' => '<p>Alle unsere Coaches sind TRUST-zertifiziert und haben die mehrstufige Ausbildung der ParkourONE Academy absolviert. Sie trainieren selbst seit Jahren Parkour und geben ihre Erfahrung weiter. Unser Team findest du auf der Über-uns-Seite.</p>',
			'category' => 'allgemein',
			'order' => 8
		],
		[
			'question' => 'Ist TRUST Education eine anerkannte Ausbildung?',
			'answer' => '<p>TRUST Education ist keine staatlich anerkannte Ausbildung – aber eine der wenigen systematisch ausgearbeiteten Parkour-Ausbildungen weltweit.</p><p>Roger Widmer, Gründer von ParkourONE und ausgebildeter Erwachsenenbildner (SVEB II) mit Diplomstudiengang in Art Education an der Zürcher Hochschule der Künste, hat TRUST gemeinsam mit Fachpersonen über viele Jahre entwickelt. Das System verbindet praktische Parkour-Erfahrung mit pädagogischen Erkenntnissen und einem klaren Wertegerüst.</p><p>TRUST wird heute in allen ParkourONE-Schulen in der Schweiz und Deutschland angewendet.</p>',
			'category' => 'allgemein',
			'order' => 9
		],
		[
			'question' => 'Was passiert bei Verletzungen während des Trainings?',
			'answer' => '<p>Du bist während des Trainings selbst verantwortlich für dich und deine Entscheidungen – das gehört zur Parkour-Philosophie. Falls du dich verletzt und länger pausieren musst, kannst du mit einem Arztzeugnis deine Mitgliedschaft unterbrechen.</p>',
			'category' => 'allgemein',
			'order' => 10
		],
		[
			'question' => 'Was ist der Unterschied zwischen Parkour und Freerunning?',
			'answer' => '<p>Die Unterscheidung ist vor allem historisch. Parkour fokussiert auf die effiziente Fortbewegung – den direkten Weg von A nach B. Freerunning integriert zusätzlich akrobatische Elemente wie Saltos und Drehungen.</p><p>Heute sind die Grenzen fliessend. ParkourONE kommt aus dem traditionellen Parkour mit Fokus auf Effizienz, Technik und die dahinterliegende Philosophie.</p>',
			'category' => 'allgemein',
			'order' => 11
		],
		[
			'question' => 'Brauche ich spezielle Parkour-Schuhe?',
			'answer' => '<p>Nein. Du brauchst keine speziellen Schuhe – aber gute. Wichtig ist eine Sohle aus durchgehendem Gummi, die dir guten Grip gibt.</p><p>Aber wie mein Freund Bogdan Cvetković von Parkour Serbien sagt: «It\'s not about the grip, it\'s about the technique.»</p>',
			'category' => 'allgemein',
			'order' => 12
		],
		[
			'question' => 'Kann ich auch ausserhalb des Trainings üben?',
			'answer' => '<p>Auf jeden Fall – und wir empfehlen es sogar. Parkour ist überall. Je mehr du zwischen den Trainings übst, desto schneller kommst du voran und kannst beim nächsten Mal an neuen Themen arbeiten.</p>',
			'category' => 'allgemein',
			'order' => 13
		],
		[
			'question' => 'Gibt es Geschenkgutscheine?',
			'answer' => '<p>Ja, Gutscheine kannst du in unserem Shop kaufen.</p>',
			'category' => 'allgemein',
			'order' => 14
		],

		// =====================================================
		// ZUSÄTZLICHE PROBETRAINING FAQs
		// =====================================================
		[
			'question' => 'Kann ich mehrere Probetrainings machen?',
			'answer' => '<p>Ja. Wenn du verschiedene Standorte oder Zeiten ausprobieren möchtest, buche einfach ein Probetraining in der jeweiligen Klasse.</p>',
			'category' => 'probetraining',
			'order' => 4
		],

		// =====================================================
		// ZUSÄTZLICHE MITGLIEDSCHAFT FAQs
		// =====================================================
		[
			'question' => 'Was passiert, wenn ich ein Training verpasse?',
			'answer' => '<p>Unsere Klassen sind fortlaufend und du bezahlst monatlich. Wenn du ein Training verpasst, gibt es leider keine Möglichkeit, es nachzuholen. Aber keine Sorge – beim nächsten Mal steigst du einfach wieder ein.</p>',
			'category' => 'mitgliedschaft',
			'order' => 3
		],
		[
			'question' => 'Wie melde ich mich für eine Mitgliedschaft an?',
			'answer' => '<p>Nach deinem Probetraining erhältst du einen Vertrag mit allen Infos. Du kannst dann in Ruhe entscheiden, ob du einsteigen möchtest.</p>',
			'category' => 'mitgliedschaft',
			'order' => 4
		],
		[
			'question' => 'Kann ich den Standort oder die Klasse wechseln?',
			'answer' => '<p>Ja, das ist möglich. Wir empfehlen dir, zuerst ein Probetraining in der neuen Klasse zu besuchen. Danach kannst du nahtlos wechseln.</p>',
			'category' => 'mitgliedschaft',
			'order' => 5
		],

		// =====================================================
		// ZUSÄTZLICHE KIDS FAQs
		// =====================================================
		[
			'question' => 'Kann ich einen Kindergeburtstag bei euch feiern?',
			'answer' => '<p>Ja, das geht. Wir organisieren Parkour-Geburtstage für Kinder – ein Erlebnis, das garantiert in Erinnerung bleibt. Melde dich bei uns für Details und Verfügbarkeit.</p>',
			'category' => 'kids',
			'order' => 4
		],

		// =====================================================
		// ZUSÄTZLICHE WORKSHOPS FAQs (inkl. Business)
		// =====================================================
		[
			'question' => 'Bietet ihr Privattraining an?',
			'answer' => '<p>Ja, wir bieten Personal Coachings an. Du kannst diese direkt über unsere Website buchen.</p>',
			'category' => 'workshops',
			'order' => 3
		],
		[
			'question' => 'Bietet ihr Teambuilding-Events oder Firmentrainings an?',
			'answer' => '<p>Ja. Parkour eignet sich hervorragend für Teams – es fördert Vertrauen, Kommunikation und das gemeinsame Lösen von Herausforderungen. Wir gestalten massgeschneiderte Events für Firmen, Vereine und Gruppen. Kontaktiere uns für ein individuelles Angebot.</p>',
			'category' => 'workshops',
			'order' => 4
		],
		[
			'question' => 'Kann ich einen Kindergeburtstag buchen?',
			'answer' => '<p>Ja, das geht. Wir organisieren Parkour-Geburtstage für Kinder – ein Erlebnis, das garantiert in Erinnerung bleibt. Melde dich bei uns für Details und Verfügbarkeit.</p>',
			'category' => 'workshops',
			'order' => 5
		],
		[
			'question' => 'Bietet ihr Schulprojekte oder Kooperationen mit Schulen an?',
			'answer' => '<p>Ja. Wir arbeiten regelmässig mit Schulen zusammen – sei es für Projektwochen, Sportunterricht oder spezielle Workshops. Parkour fördert Bewegung, Selbstvertrauen und soziale Kompetenzen auf eine Art, die Kinder und Jugendliche begeistert. Kontaktiere uns, um ein Projekt für deine Schule zu besprechen.</p>',
			'category' => 'workshops',
			'order' => 6
		],

		// =====================================================
		// GUTSCHEIN - Für Gutschein-Seite
		// =====================================================
		[
			'question' => 'Wie löse ich einen Gutschein ein?',
			'answer' => '<p>Im Checkout (Kasse) gibt es ein Gutschein-Feld. Gib dort deinen Gutschein-Code ein und der Betrag wird automatisch von deiner Bestellung abgezogen. Du kannst den Gutschein für alle Angebote einsetzen – egal ob Probetraining, Mitgliedschaft, Workshop oder Ferienkurs.</p>',
			'category' => 'gutschein',
			'order' => 1
		],
		[
			'question' => 'Wie lange ist der Gutschein gültig?',
			'answer' => '<p>Gutscheine sind ab Kaufdatum ein Jahr lang gültig. Das genaue Ablaufdatum steht in der Gutschein-E-Mail, die du beim Kauf erhältst.</p>',
			'category' => 'gutschein',
			'order' => 2
		],
		[
			'question' => 'Kann ich den Gutschein-Betrag aufteilen?',
			'answer' => '<p>Ja. Wenn deine Bestellung weniger kostet als der Gutschein-Wert, bleibt der Restbetrag auf dem Gutschein erhalten. Du kannst ihn bei einer späteren Bestellung einsetzen.</p>',
			'category' => 'gutschein',
			'order' => 3
		],
		[
			'question' => 'Bekommt der Beschenkte eine E-Mail?',
			'answer' => '<p>Ja. Wenn du beim Kauf eine Empfänger-E-Mail angibst, erhält der Beschenkte den Gutschein-Code zusammen mit deiner persönlichen Nachricht per E-Mail. Ohne Empfänger-E-Mail bekommst du den Code selbst und kannst ihn persönlich weitergeben.</p>',
			'category' => 'gutschein',
			'order' => 4
		],
		[
			'question' => 'Kann ich den Gutschein zurückgeben?',
			'answer' => '<p>Nicht eingelöste Gutscheine können erstattet werden. Kontaktiere uns dafür einfach per E-Mail oder über das Kontaktformular.</p>',
			'category' => 'gutschein',
			'order' => 5
		],
		[
			'question' => 'Für welchen Standort gilt der Gutschein?',
			'answer' => '<p>Der Gutschein gilt für den Standort, an dem er gekauft wurde. Wenn du den Gutschein auf berlin.parkourone.com kaufst, kann er auch nur in Berlin eingelöst werden.</p>',
			'category' => 'gutschein',
			'order' => 6
		],
	];
}

/**
 * Importiert Standard-FAQs in die Datenbank
 * Kann über Admin aufgerufen werden
 *
 * @param bool $overwrite Bestehende FAQs mit gleicher Frage überschreiben
 * @return array Anzahl importierter/übersprungener FAQs
 */
function parkourone_import_default_faqs($overwrite = false) {
	$faqs = parkourone_get_default_faqs();
	$imported = 0;
	$skipped = 0;

	foreach ($faqs as $faq) {
		// Prüfen ob FAQ bereits existiert
		$existing = get_posts([
			'post_type' => 'faq',
			'post_status' => 'any',
			'title' => $faq['question'],
			'posts_per_page' => 1
		]);

		if (!empty($existing) && !$overwrite) {
			$skipped++;
			continue;
		}

		// Bestehende FAQ aktualisieren oder neue erstellen
		$post_id = !empty($existing) ? $existing[0]->ID : 0;

		$post_data = [
			'ID' => $post_id,
			'post_type' => 'faq',
			'post_title' => $faq['question'],
			'post_status' => 'publish',
		];

		if ($post_id) {
			wp_update_post($post_data);
		} else {
			$post_id = wp_insert_post($post_data);
		}

		if ($post_id && !is_wp_error($post_id)) {
			update_post_meta($post_id, '_faq_answer', $faq['answer']);
			update_post_meta($post_id, '_faq_category', $faq['category']);
			update_post_meta($post_id, '_faq_order', $faq['order']);
			$imported++;
		}
	}

	return [
		'imported' => $imported,
		'skipped' => $skipped
	];
}

// =====================================================
// Auto-Import: FAQs automatisch beim Theme-Setup laden
// =====================================================

/**
 * Importiert Standard-FAQs automatisch wenn keine existieren
 * Läuft einmalig nach Theme-Aktivierung oder bei leerem FAQ-Bestand
 */
function parkourone_auto_import_faqs() {
	// Nur im Admin und nur wenn eingeloggt
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}

	// Prüfen ob bereits importiert wurde
	$already_imported = get_option('parkourone_faqs_auto_imported', false);
	if ($already_imported) {
		return;
	}

	// Prüfen ob FAQs existieren
	$existing_count = wp_count_posts('faq');
	$total = ($existing_count->publish ?? 0) + ($existing_count->draft ?? 0);

	if ($total == 0) {
		// FAQs importieren
		$result = parkourone_import_default_faqs(false);

		// Markieren dass Import durchgeführt wurde
		update_option('parkourone_faqs_auto_imported', true);

		// Admin-Benachrichtigung für nächsten Seitenaufruf
		set_transient('parkourone_faqs_imported_notice', $result['imported'], 60);
	}
}
add_action('admin_init', 'parkourone_auto_import_faqs', 5);

// Bei Theme-Aktivierung: Flag zurücksetzen damit Import läuft
function parkourone_reset_faq_import_on_theme_switch() {
	delete_option('parkourone_faqs_auto_imported');
}
add_action('after_switch_theme', 'parkourone_reset_faq_import_on_theme_switch');

// =====================================================
// Admin-Benachrichtigungen
// =====================================================

/**
 * Zeigt Erfolgs-Nachricht nach Auto-Import
 */
function parkourone_faq_auto_import_notice() {
	$imported = get_transient('parkourone_faqs_imported_notice');
	if ($imported) {
		delete_transient('parkourone_faqs_imported_notice');
		?>
		<div class="notice notice-success is-dismissible">
			<p><strong>✓ <?php echo intval($imported); ?> Standard-FAQs wurden automatisch importiert!</strong> <a href="<?php echo admin_url('edit.php?post_type=faq'); ?>">FAQs ansehen →</a></p>
		</div>
		<?php
	}
}
add_action('admin_notices', 'parkourone_faq_auto_import_notice');

/**
 * Manueller Import-Button falls FAQs gelöscht wurden
 * + Hinweis auf fehlende Kategorien mit Einzelimport
 */
function parkourone_faq_import_admin_notice() {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'faq' || $screen->base !== 'edit') return;

	// Prüfen ob bereits FAQs existieren
	$existing_count = wp_count_posts('faq')->publish;

	if ($existing_count == 0) {
		?>
		<div class="notice notice-info">
			<p><strong>Keine FAQs vorhanden.</strong> Möchtest du die Standard-FAQs für ParkourONE importieren?</p>
			<p>
				<a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=faq&action=import_default_faqs'), 'import_faqs'); ?>" class="button button-primary">
					Standard-FAQs importieren
				</a>
			</p>
		</div>
		<?php
		return;
	}

	// Fehlende Kategorien prüfen: Welche Standard-Kategorien haben keine FAQs?
	$default_faqs = parkourone_get_default_faqs();
	$categories_with_defaults = [];
	foreach ($default_faqs as $faq) {
		$categories_with_defaults[$faq['category']] = true;
	}

	// Vorhandene Kategorien aus der DB
	global $wpdb;
	$existing_categories = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_faq_category' AND pm.meta_value != ''
		 AND p.post_status = 'publish' AND p.post_type = 'faq'"
	);

	$missing_categories = array_diff(array_keys($categories_with_defaults), $existing_categories);

	if (!empty($missing_categories)) {
		$all_categories = parkourone_get_all_faq_categories();
		?>
		<div class="notice notice-warning" style="padding-bottom: 12px;">
			<p><strong>Fehlende FAQ-Kategorien:</strong> Für folgende Kategorien sind Standard-FAQs verfügbar, aber noch nicht importiert:</p>
			<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
				<?php foreach ($missing_categories as $cat_slug):
					$label = $all_categories[$cat_slug] ?? ucfirst($cat_slug);
					$count = 0;
					foreach ($default_faqs as $faq) {
						if ($faq['category'] === $cat_slug) $count++;
					}
				?>
					<a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=faq&action=import_category_faqs&category=' . urlencode($cat_slug)), 'import_category_faqs'); ?>"
					   class="button" style="display: inline-flex; align-items: center; gap: 6px;">
						<span><?php echo esc_html($label); ?></span>
						<span style="background: #2271b1; color: #fff; font-size: 11px; padding: 1px 6px; border-radius: 10px;"><?php echo $count; ?></span>
					</a>
				<?php endforeach; ?>
			</div>
			<p style="margin-top: 10px;">
				<a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=faq&action=import_default_faqs'), 'import_faqs'); ?>" class="button button-link" style="text-decoration: none;">
					Alle fehlenden importieren →
				</a>
			</p>
		</div>
		<?php
	}
}
add_action('admin_notices', 'parkourone_faq_import_admin_notice');

/**
 * Handler für FAQ Import (alle oder einzelne Kategorie)
 */
function parkourone_handle_faq_import() {
	if (!isset($_GET['action'])) return;
	if (!current_user_can('manage_options')) return;

	// Alle Standard-FAQs importieren
	if ($_GET['action'] === 'import_default_faqs') {
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'import_faqs')) return;

		$result = parkourone_import_default_faqs(false);
		wp_redirect(admin_url('edit.php?post_type=faq&imported=' . $result['imported']));
		exit;
	}

	// Einzelne Kategorie importieren
	if ($_GET['action'] === 'import_category_faqs') {
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'import_category_faqs')) return;

		$category = sanitize_text_field($_GET['category'] ?? '');
		if (empty($category)) return;

		$all_faqs = parkourone_get_default_faqs();
		$imported = 0;

		foreach ($all_faqs as $faq) {
			if ($faq['category'] !== $category) continue;

			// Prüfen ob FAQ bereits existiert
			$existing = get_posts([
				'post_type' => 'faq',
				'post_status' => 'any',
				'title' => $faq['question'],
				'posts_per_page' => 1
			]);

			if (!empty($existing)) continue;

			$post_id = wp_insert_post([
				'post_type' => 'faq',
				'post_title' => $faq['question'],
				'post_status' => 'publish',
			]);

			if ($post_id && !is_wp_error($post_id)) {
				update_post_meta($post_id, '_faq_answer', $faq['answer']);
				update_post_meta($post_id, '_faq_category', $faq['category']);
				update_post_meta($post_id, '_faq_order', $faq['order']);
				$imported++;
			}
		}

		wp_redirect(admin_url('edit.php?post_type=faq&imported=' . $imported));
		exit;
	}
}
add_action('admin_init', 'parkourone_handle_faq_import');

/**
 * Erfolgsmeldung nach Import anzeigen
 */
function parkourone_faq_import_success_notice() {
	if (!isset($_GET['imported'])) return;

	$screen = get_current_screen();
	if ($screen->post_type !== 'faq') return;

	$imported = intval($_GET['imported']);
	?>
	<div class="notice notice-success is-dismissible">
		<p><strong><?php echo $imported; ?> FAQs wurden erfolgreich importiert!</strong></p>
	</div>
	<?php
}
add_action('admin_notices', 'parkourone_faq_import_success_notice');

// =====================================================
// REST API: FAQ-Kategorien dynamisch liefern
// =====================================================

function parkourone_register_faq_categories_endpoint() {
	register_rest_route('parkourone/v1', '/faq-categories', [
		'methods' => 'GET',
		'callback' => function() {
			$categories = parkourone_get_all_faq_categories();
			$result = [];
			foreach ($categories as $slug => $label) {
				$result[] = ['value' => $slug, 'label' => $label];
			}
			return $result;
		},
		'permission_callback' => function() {
			return current_user_can('edit_posts');
		}
	]);
}
add_action('rest_api_init', 'parkourone_register_faq_categories_endpoint');

// =====================================================
// REST API: FAQs für Editor-Live-Preview liefern
// =====================================================

function parkourone_register_faqs_endpoint() {
	register_rest_route('parkourone/v1', '/faqs', [
		'methods' => 'GET',
		'callback' => function($request) {
			$category = sanitize_text_field($request->get_param('category') ?? '');
			$limit = intval($request->get_param('limit') ?? 6);
			$include_general = $request->get_param('include_general') !== 'false';

			if (!empty($category)) {
				$faqs = parkourone_get_faqs($category, 0);

				if ($include_general && $category !== 'allgemein') {
					$general_faqs = parkourone_get_faqs('allgemein', 0);
					$seen = array_map(function($f) { return $f['question']; }, $faqs);
					foreach ($general_faqs as $gf) {
						if (!in_array($gf['question'], $seen)) {
							$gf['is_general'] = true;
							$faqs[] = $gf;
						}
					}
				}

				if ($limit > 0 && count($faqs) > $limit) {
					$faqs = array_slice($faqs, 0, $limit);
				}
			} else {
				$faqs = parkourone_get_faqs('', $limit);
			}

			return rest_ensure_response($faqs);
		},
		'permission_callback' => function() {
			return current_user_can('edit_posts');
		}
	]);
}
add_action('rest_api_init', 'parkourone_register_faqs_endpoint');
