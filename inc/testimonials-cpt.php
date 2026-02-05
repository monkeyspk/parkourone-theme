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
		'taxonomies' => ['testimonial_age_group', 'testimonial_school']
	]);

	// Altersgruppen-Taxonomie
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

	// Schul-/Standort-Taxonomie
	register_taxonomy('testimonial_school', 'testimonial', [
		'labels' => [
			'name' => 'Schulen / Standorte',
			'singular_name' => 'Schule',
			'search_items' => 'Schulen suchen',
			'all_items' => 'Alle Schulen',
			'edit_item' => 'Schule bearbeiten',
			'update_item' => 'Schule aktualisieren',
			'add_new_item' => 'Neue Schule hinzufügen',
			'new_item_name' => 'Neue Schule',
			'menu_name' => 'Schulen'
		],
		'hierarchical' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => false
	]);

	// Standard-Taxonomien erstellen
	parkourone_create_default_age_groups();
	parkourone_create_default_schools();
}
add_action('init', 'parkourone_register_testimonial_cpt');

/**
 * Erstellt die Standard-Schulen/Standorte
 */
function parkourone_create_default_schools() {
	$schools = [
		'schweiz' => 'Schweiz',
		'berlin' => 'Berlin',
		'dresden' => 'Dresden',
		'hannover' => 'Hannover',
		'muenster' => 'Münster',
		'augsburg' => 'Augsburg',
		'rheinruhr' => 'Rheinruhr',
	];

	foreach ($schools as $slug => $name) {
		if (!term_exists($slug, 'testimonial_school')) {
			wp_insert_term($name, 'testimonial_school', ['slug' => $slug]);
		}
	}
}

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
		'school' => 'Schule',
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
		case 'school':
			$terms = get_the_terms($post_id, 'testimonial_school');
			if ($terms && !is_wp_error($terms)) {
				$labels = array_map(function($term) {
					return '<span style="background:#2997ff; color:#fff; padding:2px 8px; border-radius:3px; font-size:11px;">' . esc_html($term->name) . '</span>';
				}, $terms);
				echo implode(' ', $labels);
			} else {
				echo '<span style="color:#999;">—</span>';
			}
			break;
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

function parkourone_get_preset_testimonials($school = 'schweiz') {
	$testimonials = [
		// =====================================================
		// SCHWEIZ - Google Reviews
		// =====================================================
		'schweiz' => [
			// Adults
			['name' => 'Tadäus Steinemann', 'stars' => 5, 'text' => 'ParkourONE bedeutet: ALL for ONE --- ONE for ALL. Hier kanst du echtes Parkour bei super motivierten Coaches lernen. Regelmässige Trainings besuchen, Workshops und vieles mehr erfahren. Es ist eine tolle Community mit coolen Leuten.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Silvio Stoll', 'stars' => 5, 'text' => 'Kompetent, zuverlässig, qualitativ hochstehendes Angebot, umfassende Vermittlung von Parkour in all seinen Facetten. ParkourONE bietet die optimalen Voraussetzungen und verfügt über einen jahrzehntelangen Erfahrungsschatz.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'M. Lütolf', 'stars' => 5, 'text' => '"Hindernisse sind Möglichkeiten" Wie wahr. Mit Parkour habe ich erst vor Kurzem begonnen und jedes Training motiviert aufs Neue. Die Gemeinschaft zieht am gleichen Strick. ONE for all - all for ONE', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Rino Vanoni', 'stars' => 5, 'text' => 'Ich bin seit über zehn Jahren Schülerin in einem ihrer Kurse. ParkourONE steht für Qualität und Beständigkeit. Ihr Lehrkonzept ist hervorragend.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Bogdan Cvetkovic', 'stars' => 5, 'text' => 'Die beste Community in der Parkour-Welt. Ich arbeite seit über 10 Jahren mit ihnen zusammen!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Christoph Raaflaub', 'stars' => 5, 'text' => 'Ermöglicht den Beginn mit Parkour in jedem Alter. Gut und durchdachte Struktur der Trainings. So macht Bewegung richtig Spass!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Zei Wagner', 'stars' => 5, 'text' => 'ParkourONE vermittelt nicht nur Parkour, sondern auch Werte. Seit mehr als 10 Jahren besuche ich das Training. SEHR empfehlenswert!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Milena Losinger', 'stars' => 5, 'text' => 'Alles wunderbar. Team mit viel Herzblut dabei, Workshops super koordiniert.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Brigitte Meier Müller', 'stars' => 5, 'text' => 'Cooles Training mit vielen Herausforderungen, abwechslungsreich, es macht Spass!', 'source' => 'Google Review', 'age_group' => 'adults'],
			// Juniors
			['name' => 'gugger f', 'stars' => 5, 'text' => 'Das Probetraining in Thun war der Hammer! Die Leiterin ist super und ist auf alle eingegangen. Hatte mehrere Erfolgserlebnisse und bin total geflasht!', 'source' => 'Google Review', 'age_group' => 'juniors'],
			// Kids
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
		],

		// =====================================================
		// BERLIN - Google Reviews
		// =====================================================
		'berlin' => [
			// Minis
			['name' => 'Barbara Gerster', 'stars' => 5, 'text' => 'Wir gingen zu einer Schnupperstunde und mein Sohn (6 Jahre alt) genoss es wirklich. Der Trainer war sehr freundlich, was ihm half, als alles Neue für ihn war. Ich mag die Bedeutung von persönlichem Kontakt und individuellen Grüßen.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Peter Hein', 'stars' => 5, 'text' => 'Die Kleinen hatten Spaß und schliefen gut an diesem Abend.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Oksana Dmitrieva-Vartmann', 'stars' => 5, 'text' => 'Ihr seid großartig!', 'source' => 'Google Review', 'age_group' => 'kids'],
			// Kids
			['name' => 'Antje B', 'stars' => 5, 'text' => 'M. (12 Jahre alt) hatte eine fantastische Woche während der Herbstferien. Er hatte viel Spaß und hat viel gelernt. Die Atmosphäre mit allen drei Trainern war fantastisch – er fühlte sich sehr wohl.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Marin Turina', 'stars' => 5, 'text' => 'Unser Sohn (13 Jahre alt) ging zu einer Trainingseinheit mit einem Freund und war sehr begeistert. Der Trainer war freundlich und die Hindernisse waren Spaß. Wir haben ihn jetzt offiziell angemeldet.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Matea Jancic', 'stars' => 5, 'text' => 'Mein Sohn hatte letzte Woche eine Schnupperstunde und LIEBTE jeden Teil davon! Ich war erstaunt, wie freundlich und professionell der Trainer war - super süß mit den Kindern und mit einem sehr durchdachten Unterricht.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Anna Salzsieder', 'stars' => 5, 'text' => 'Wir besuchten die letzte Trainingseinheit am Freitag und meine Tochter hatte einen Riesenspaß. Carina war super süß, und wir freuen uns schon auf die nächsten Trainingstage voller Sprünge, Ausgleich, Schwungen und Spaß!', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'johanne bro', 'stars' => 5, 'text' => 'Mein Sohn hat die Schnupperstunde wirklich genossen und freut sich schon auf die nächste.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Amelia nin soust', 'stars' => 5, 'text' => 'Mein Sohn war sehr glücklich mit dem Erlebnis und möchte definitiv weitermachen.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Ira Voß', 'stars' => 5, 'text' => 'Mein Sohn kam von der Trainingseinheit erschöpft aber glücklich zurück. Was könnte man sich noch mehr wünschen?! Er ist jetzt für einen Kurs angemeldet.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Genia Principalli', 'stars' => 5, 'text' => 'Unser Kind besuchte eine Schnupperstunde und fühlte sich bei den Trainern sehr wohl und hatte viel Spaß.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Jana Sophia Sotela Prendergast', 'stars' => 5, 'text' => 'Großartiges, kinderfreundliches Training mit einem super freundlichen Trainer. Mein Kind hat es absolut geliebt. Der Inhalt und die Einhaltung der Regeln sind perfekt auf die Kinder abgestimmt.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Satu Hofsommer', 'stars' => 5, 'text' => 'Mein Sohn war absolut begeistert vom Einführungskurs für Kinder. Marty war ein super entspannter Trainer, der die Technik den Kindern gut erklärte. Die Teamübung war besonders beliebt.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Ja Ne', 'stars' => 5, 'text' => 'Carina ist eine sehr sympathische Trainerin, die die Mini-Gruppe mit perfekter Didaktik anleitet und Parkour auf kinderfreundliche und druckfreie Weise vermittelt.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Frances Berger', 'stars' => 5, 'text' => 'Unser Sohn besuchte den Anfängerkurs heute und war sehr enthusiastisch! Es war eine großartige Möglichkeit zu sehen, ob er regelmäßig Hindernisseübungen machen möchte.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Tabea Jänicke', 'stars' => 5, 'text' => 'Wir lieben es. Meine Geschwister kommen immer glücklich vom Training nach Hause.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Nicholas von Alphen', 'stars' => 5, 'text' => 'Super freundlich und aufmerksam! Großartiges Training und ein großartiges Team. Mein Sohn war begeistert, spricht von nichts anderem und möchte sofort weitermachen!', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Cordelia Krause', 'stars' => 5, 'text' => 'Mein Kind (10 Jahre alt) nahm an einem zweistündigen Einführungsworkshop teil und wir beide waren begeistert! Freundliche Trainer, klare Kommunikation, und eine großartige körperliche Herausforderung.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Tobias Rossa', 'stars' => 5, 'text' => 'Meine Tochter (9) hatte viel Spaß beim Klettern und Springen. Die Atmosphäre war auch sehr angenehm für die Kinder.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'V. Butscher', 'stars' => 5, 'text' => 'Die Kinder bekommen es sofort richtig vom Einführungsworkshop an und können viel ausprobieren und anwenden - meine Tochter hat es wirklich genossen. Die Trainer waren sehr nett.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Stephan Lem.', 'stars' => 5, 'text' => 'Wir gingen zu einer Schnupperstunde. Sehr nettes, kinderfreundliches Training.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Gerbil 1283', 'stars' => 5, 'text' => 'Meine Tochter besuchte den Einführungskurs und war absolut begeistert. Die Trainer waren sehr geduldig, freundlich und aufmerksam. Sie wird sicherlich weitermachen.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Nancy Birkhoelzer', 'stars' => 5, 'text' => 'Fantastisch! Großartige Trainer, die nicht nur Bewegung fördern und die Kinder motivieren, neue Dinge auszuprobieren, sondern auch Werte vermitteln und Teamgeist stärken!', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Tamara Shamseva', 'stars' => 5, 'text' => 'Mein Sohn nahm am Einführungsworkshop teil. Es war großartig! Er bemerkte nicht mal, dass die zwei Stunden vorbei waren. Die Trainer waren super freundlich.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Pamela Anglo', 'stars' => 5, 'text' => 'Mein Sohn nahm im September am Schnupperkurs teil und war sehr beeindruckt und sichtbar begeistert. Nach kurzer Wartezeit kann er jetzt sogar mit seinem Freund in die Kids-Klasse gehen.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Antje Finzel', 'stars' => 5, 'text' => 'Wir besuchten den Einführungsworkshop für Kinder. Die Trainer waren fantastisch. Sie konnten die Kinder begeistern. Mein Sohn hatte viel Spaß.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Pia Sökeland', 'stars' => 5, 'text' => 'Unser Sohn besuchte diesen Monat den Einführungsworkshop und hatte eine großartige Zeit! Glücklicherweise wird er bald an dem Kurs teilnehmen und ist wahnsinnig aufgeregt.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Wiebke Andresen', 'stars' => 5, 'text' => 'Mein Sohn (11 Jahre alt) war begeistert vom Einführungsworkshop mit Tobi am Potsdamer Platz. Er hatte Spaß, lernte neue Tricks und hofft, einen Platz in den wöchentlichen Trainingseinheiten zu bekommen.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Barbara Duering', 'stars' => 5, 'text' => 'Es macht Spaß! Unser Sohn ist begeistert und freut sich auf mehr!', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Krischan Meder', 'stars' => 5, 'text' => 'Mein Sohn genoss den Schnupperkurs wirklich und freut sich schon auf die regelmäßige Klasse.', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Evgenii Sivkov', 'stars' => 5, 'text' => 'Meine Tochter nahm am letzten Wochenende am Workshop teil, sie hatte viel Spaß und war sehr glücklich, wie es gelaufen ist!', 'source' => 'Google Review', 'age_group' => 'kids'],
			['name' => 'Ute Sauerbrey', 'stars' => 5, 'text' => 'Mein Sohn war total aufgeregt und möchte sicherlich weitermachen.', 'source' => 'Google Review', 'age_group' => 'kids'],
			// Juniors
			['name' => 'Rene Sasse', 'stars' => 5, 'text' => 'Unser Sohn, 9 Jahre alt, wollte schon lange Parkour machen. Wir bekamen die Chance durch eine Schnupperstunde und er war absolut begeistert. Wir als Eltern sind auch sehr begeistert von der Empathie und dem Verständnis der Trainer gegenüber den Kindern.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			['name' => 'Philipp Lange', 'stars' => 5, 'text' => 'Ich besuchte den Einführungsworkshop am 7. Oktober mit zwei meiner Kinder und war begeistert. Meine Kinder waren auch, nachher.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			['name' => 'FF', 'stars' => 5, 'text' => 'Ausgezeichnetes Einführungstraining mit freundlichen Trainern, die die Grundlagen in entspannter und gründlicher Weise erklärten. Meine Tochter tritt jetzt dem regulären Trainingsprogramm bei.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			['name' => 'Ulrike von Heinemann', 'stars' => 5, 'text' => 'Der Einführungsworkshop bei ParkourONE hat viel Spaß gemacht! Wir lernten schnell viele verschiedene Techniken zum Überwinden einer hohen Mauer und einer Stange. Ich war sofort begeistert.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			['name' => 'Lara Maier', 'stars' => 5, 'text' => 'Mein Kind hatte letzte Woche eine zweistündige Schnupperstunde und war sofort begeistert! Die Atmosphäre war entspannt, und die Trainer und anderen Teilnehmer waren sehr unterstützend.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			['name' => 'Apollo 118', 'stars' => 5, 'text' => 'Mein Sohn nahm an einem Einführungsworkshop im letzten Jahr teil. Alles lief reibungslos, die Trainer waren freundlich und immer hilfsbereit. Er genoss es so sehr, dass er jetzt vollständig mit ParkourONE involviert sein möchte.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			['name' => 'Tatyana Budkevich', 'stars' => 5, 'text' => 'Schnuppertraining mit der Juniors-Gruppe! Das Wetter war eine totale Katastrophe, mit 100% Regen während der zweistündigen Trainingseinheit. Ich war überrascht, dass ich nach 20 Minuten nicht mehr bemerkte, dass ich komplett nass und dreckig war.', 'source' => 'Google Review', 'age_group' => 'juniors'],
			// Adults
			['name' => 'Katrin Megelin', 'stars' => 5, 'text' => 'Gestern war mein erstes Parkour Schnuppertraining. Da ich nicht viel Kraft habe, bin ich eher steif als flexibel. Das Training war sehr unterhaltsam.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Antonia Mogk', 'stars' => 5, 'text' => 'Großartiges Programm und sehr nette, engagierte und zugängliche Trainer – sehr empfohlen!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Raquel Pernías', 'stars' => 5, 'text' => 'Mein Sohn machte eine Trainingseinheit und sagte: „Ich hatte eine wirklich gute Erfahrung bei ParkourONE in Berlin. Luca und Fabian sind sehr nett und helfen gerne, wenn man etwas nicht versteht."', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Peggy Sadtler', 'stars' => 5, 'text' => 'Unser Sohn ging zu Luca im Brosepark für eine Schnupperstunde und liebte es absolut. Ich habe selten solch empathische Trainer im Freizeitsport getroffen. Hier werden die Kinder gesehen und motiviert.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Johannes Schulte', 'stars' => 5, 'text' => 'Echter Parkour im Freien, mit gut ausgebildeten Trainern. 10/10!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Marichen Marichen', 'stars' => 5, 'text' => 'Cooles Training, wirklich lustig. Beim ersten Mal war ich total erschöpft, aber glücklich. Ich werde definitiv weitermachen.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'ronald schulz', 'stars' => 5, 'text' => 'Es war ein sehr gutes Training. Mein Sohn liebte es. Er konnte sich testen und musste sich wirklich selbst antreiben. Weil es sehr gut strukturiert und verspielt war, hatte er keine Probleme.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Steffen Kauffmann', 'stars' => 5, 'text' => 'Fantastisch und lebendig! Ein großartiger Ort für ein cooles Team, das durch aktive Gruppenarbeit motiviert, herausfordert und vereint. Das ist genau das, was die Stadt braucht!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Clemens Bulau', 'stars' => 5, 'text' => 'Ein wirklich wundervolles Erlebnis! Als Senior (65) habe ich eine persönliche Parkour-Trainingseinheit gebucht und habe in nur einer Stunde die gewünschte Trainingsform erreicht.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Timo Lubitz', 'stars' => 5, 'text' => 'Ein fantastisches Team, sehr kinderorientiert und freundlich, mit ausgezeichneter Kommunikation eines exemplarischen Wertesystems basierend auf Respekt, Achtsamkeit und Nachhaltigkeit.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Josephine Belke', 'stars' => 5, 'text' => 'Ein sehr gut strukturiertes Training mit Stärkungselementen, viel Spaß und Experimentation und eine großartige Atmosphäre. Meine Tochter hat es wirklich genossen!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Robert Straß', 'stars' => 5, 'text' => 'Großartig und lebendig! Ein großartiger Ort für ein cooles Team.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Levi Besch', 'stars' => 5, 'text' => 'Super freundlich und aufmerksam! Großartiges Training und ein großartiges Team.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Jonathan O.', 'stars' => 5, 'text' => 'Super freundliche Trainer mit viel Erfahrung und Expertise.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Nina Reithmeier', 'stars' => 5, 'text' => 'Super freundliche und kompetente Trainer, die ihr Herz und ihre Seele in ihre Arbeit legen. Sehr empfohlen.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Corn.A.', 'stars' => 5, 'text' => 'Sehr gutes Parkour Training. Ich empfehle es sehr.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Miriam E.', 'stars' => 5, 'text' => 'Ich nahm am Anfängerkurs an diesem Samstag teil, es war großartig lustig und die perfekte Menge an Herausforderung.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Jonas Jung', 'stars' => 5, 'text' => 'Super freundliche Trainer mit Tonnen von Erfahrung und Expertise, die sie immer gerne teilen. Der Ort für Parkour in Berlin!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Malte Fiedler', 'stars' => 5, 'text' => 'Hier macht es Spaß, sich selbst an deine physischen Grenzen zu treiben.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Lena Henselin', 'stars' => 5, 'text' => 'Ich nahm an einem Einführungskurs teil und es hat mir wirklich gefallen. Das Ganze war sehr anfängerfreundlich und unvoreingenommen und der Trainer machte dich wohlfühlen.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Anna Boddin', 'stars' => 5, 'text' => 'Ein großartiges Anfängerseminar mit zwei extrem freundlichen, motivierten und motivierenden Trainern.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Jochen Hein', 'stars' => 5, 'text' => 'Parkour: Ein großartiger Sport für Stadtkinder, geführt von dem freundlichen ParkourONE Team, an coolen Orten in Berlin. Awesome!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Tanja Gabriel', 'stars' => 5, 'text' => 'Ich nahm am 25. September 2022 an einem Einführungsworkshop teil und bin extra aus Hamburg nach Berlin gereist dafür. Ich bin extrem beeindruckt von der Philosophie und den Werten von ParkourONE.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Slavko Lozic', 'stars' => 5, 'text' => 'Ich besuchte den Einführungsworkshop ohne vorherige Erfahrung und genoss es sehr, während ich die Grundlagen lernte (Seitensprung, Ledge Walk/Balancing, Wall Run).', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Christoph Hobo', 'stars' => 5, 'text' => 'Großartiger Einführungsworkshop mit Luca, der großartige Arbeit bei der Erklärung geleistet hat.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'DIOgenes', 'stars' => 5, 'text' => 'Super freundliche und engagierte Trainer.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Iaione Perez', 'stars' => 5, 'text' => 'Du wirst high vom Leben. Wirklich lustig, anspruchsvoll aber in deinem eigenen Tempo.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Kristin Dill', 'stars' => 5, 'text' => 'Für jetzt können wir nur über den Online-Einführungskurs berichten. Es ist in viele kurze Videos unterteilt und die Erklärungen sind wirklich ausgezeichnet.', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'Sandra Stöckli', 'stars' => 5, 'text' => 'Der Ort für Parkour in Berlin!', 'source' => 'Google Review', 'age_group' => 'adults'],
			['name' => 'ETREFORT Clothing', 'stars' => 5, 'text' => 'ParkourONE Berlin bietet ein sehr breites Angebot an Aktivitäten. Sehr empfehlenswert! Schau vorbei und lass dich von der Vielfalt des Parkour inspirieren.', 'source' => 'Google Review', 'age_group' => 'adults'],
		],

		// =====================================================
		// DRESDEN - Google Reviews
		// =====================================================
		'dresden' => [
			// Hier Dresden Reviews einfügen
		],

		// =====================================================
		// HANNOVER - Google Reviews
		// =====================================================
		'hannover' => [
			// Hier Hannover Reviews einfügen
		],

		// =====================================================
		// MÜNSTER - Google Reviews
		// =====================================================
		'muenster' => [
			// Hier Münster Reviews einfügen
		],

		// =====================================================
		// AUGSBURG - Google Reviews
		// =====================================================
		'augsburg' => [
			// Hier Augsburg Reviews einfügen
		],

		// =====================================================
		// RHEINRUHR - Google Reviews
		// =====================================================
		'rheinruhr' => [
			// Hier Rheinruhr Reviews einfügen
		],
	];

	// Rückwärtskompatibilität: Wenn 'all' übergeben wird, alle zusammenführen
	if ($school === 'all') {
		$all = [];
		foreach ($testimonials as $school_testimonials) {
			$all = array_merge($all, $school_testimonials);
		}
		return $all;
	}

	return $testimonials[$school] ?? [];
}

/**
 * Holt alle Schulen mit verfügbaren Preset-Testimonials
 */
function parkourone_get_schools_with_presets() {
	$school_names = [
		'schweiz' => 'Schweiz',
		'berlin' => 'Berlin',
		'dresden' => 'Dresden',
		'hannover' => 'Hannover',
		'muenster' => 'Münster',
		'augsburg' => 'Augsburg',
		'rheinruhr' => 'Rheinruhr',
	];

	$result = [];
	foreach ($school_names as $slug => $name) {
		$presets = parkourone_get_preset_testimonials($slug);
		if (!empty($presets)) {
			$result[$slug] = [
				'name' => $name,
				'count' => count($presets)
			];
		}
	}

	return $result;
}

/**
 * Holt Schulen deren Testimonials noch nicht importiert wurden
 */
function parkourone_get_unimported_schools() {
	$schools_with_presets = parkourone_get_schools_with_presets();
	$result = [];

	foreach ($schools_with_presets as $slug => $info) {
		// Prüfen ob Testimonials für diese Schule existieren
		$existing = get_posts([
			'post_type' => 'testimonial',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'tax_query' => [
				[
					'taxonomy' => 'testimonial_school',
					'field' => 'slug',
					'terms' => $slug
				]
			]
		]);

		if (empty($existing)) {
			$result[$slug] = $info;
		}
	}

	return $result;
}

// Admin Notice + Import Button
function parkourone_testimonials_admin_notice() {
	$screen = get_current_screen();
	if ($screen->post_type !== 'testimonial') return;

	$count = wp_count_posts('testimonial');
	$total = $count->publish + $count->draft;

	// Verfügbare Schulen mit Testimonial-Anzahl holen
	$schools_with_presets = parkourone_get_schools_with_presets();

	// Fall 1: Keine Testimonials vorhanden - Import anbieten (immer zeigen wenn leer)
	if ($total === 0):
	?>
	<div class="notice notice-info is-dismissible" id="po-testimonials-setup">
		<p><strong>Testimonials einrichten</strong></p>
		<p>Importiere voreingestellte Google Reviews für eine Schule. Sie werden automatisch der Schule und den passenden Altersgruppen zugeordnet.</p>
		<p>
			<select id="po-school-select" style="margin-right: 10px;">
				<?php foreach ($schools_with_presets as $slug => $info): ?>
					<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($info['name']); ?> (<?php echo $info['count']; ?> Reviews)</option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button button-primary" id="po-import-testimonials">Testimonials importieren</button>
			<button type="button" class="button" id="po-skip-testimonials">Nein, ich erstelle eigene</button>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-import-testimonials').on('click', function() {
			var school = $('#po-school-select').val();
			$(this).prop('disabled', true).text('Importiere...');
			$.post(ajaxurl, {
				action: 'po_import_testimonials',
				nonce: '<?php echo wp_create_nonce('po_import_testimonials'); ?>',
				school: school
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
	// Fall 1b: Testimonials vorhanden, aber weitere Schulen können importiert werden
	elseif (!empty($schools_with_presets)):
		// Prüfen welche Schulen noch nicht importiert wurden
		$unimported_schools = parkourone_get_unimported_schools();
		if (!empty($unimported_schools)):
	?>
	<div class="notice notice-info is-dismissible" id="po-testimonials-import-more">
		<p><strong>Weitere Testimonials importieren</strong></p>
		<p>Es gibt noch weitere Schulen mit voreingestellten Reviews:</p>
		<p>
			<select id="po-school-select-more" style="margin-right: 10px;">
				<?php foreach ($unimported_schools as $slug => $info): ?>
					<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($info['name']); ?> (<?php echo $info['count']; ?> Reviews)</option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button button-primary" id="po-import-more-testimonials">Importieren</button>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#po-import-more-testimonials').on('click', function() {
			var school = $('#po-school-select-more').val();
			$(this).prop('disabled', true).text('Importiere...');
			$.post(ajaxurl, {
				action: 'po_import_testimonials',
				nonce: '<?php echo wp_create_nonce('po_import_testimonials'); ?>',
				school: school
			}, function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Fehler: ' + response.data.message);
				}
			});
		});
	});
	</script>
	<?php
		endif;
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

	$categorized = 0;
	$schools_assigned = 0;

	// Alle Schulen durchgehen und Mappings erstellen
	$name_to_data = [];
	$schools = ['schweiz', 'berlin', 'dresden', 'hannover', 'muenster', 'augsburg', 'rheinruhr'];
	foreach ($schools as $school) {
		$presets = parkourone_get_preset_testimonials($school);
		foreach ($presets as $preset) {
			$name_to_data[$preset['name']] = [
				'age_group' => $preset['age_group'] ?? '',
				'school' => $school
			];
		}
	}

	// Alle Testimonials ohne Kategorie holen
	$testimonials = get_posts([
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => -1
	]);

	foreach ($testimonials as $testimonial) {
		if (isset($name_to_data[$testimonial->post_title])) {
			$data = $name_to_data[$testimonial->post_title];

			// Altersgruppe zuweisen falls noch nicht vorhanden
			$existing_age = wp_get_post_terms($testimonial->ID, 'testimonial_age_group', ['fields' => 'slugs']);
			if (empty($existing_age) && !empty($data['age_group'])) {
				wp_set_object_terms($testimonial->ID, $data['age_group'], 'testimonial_age_group');
				$categorized++;
			}

			// Schule zuweisen falls noch nicht vorhanden
			$existing_school = wp_get_post_terms($testimonial->ID, 'testimonial_school', ['fields' => 'slugs']);
			if (empty($existing_school) && !empty($data['school'])) {
				wp_set_object_terms($testimonial->ID, $data['school'], 'testimonial_school');
				$schools_assigned++;
			}
		}
	}

	update_option('parkourone_testimonials_categories_assigned', true);
	wp_send_json_success(['categorized' => $categorized, 'schools_assigned' => $schools_assigned]);
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

	// Schule aus Request holen (Standard: schweiz)
	$school = sanitize_text_field($_POST['school'] ?? 'schweiz');
	$presets = parkourone_get_preset_testimonials($school);

	if (empty($presets)) {
		wp_send_json_error(['message' => 'Keine Testimonials für diese Schule gefunden']);
	}

	$imported = 0;

	foreach ($presets as $preset) {
		// Prüfen ob bereits existiert (nach Name + Schule)
		$existing = get_posts([
			'post_type' => 'testimonial',
			'post_status' => 'any',
			'title' => $preset['name'],
			'posts_per_page' => 1,
			'tax_query' => [
				[
					'taxonomy' => 'testimonial_school',
					'field' => 'slug',
					'terms' => $school
				]
			]
		]);

		if (!empty($existing)) {
			continue; // Überspringen wenn bereits vorhanden
		}

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

			// Schule zuweisen
			wp_set_object_terms($post_id, $school, 'testimonial_school');

			$imported++;
		}
	}

	update_option('parkourone_testimonials_notice_dismissed', true);
	wp_send_json_success(['imported' => $imported, 'school' => $school]);
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
 * @param string $school Schule/Standort (optional, Standard: aktuelle Site)
 * @return array Testimonials
 */
function parkourone_get_page_testimonials($page_type = '', $limit = 4, $random = true, $school = '') {
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
		$testimonials = parkourone_get_testimonials_by_age($age_group, $limit, $random, $school);
	}
	// Bei Standorten, Startseite, Workshops: gemischt
	else {
		$testimonials = parkourone_get_testimonials_mixed($limit, $random, $school);
	}

	return $testimonials;
}

/**
 * Holt Testimonials einer bestimmten Altersgruppe
 *
 * @param string $age_group Altersgruppe
 * @param int $limit Max. Anzahl
 * @param bool $random Zufällige Auswahl
 * @param string $school Schule/Standort (optional)
 */
function parkourone_get_testimonials_by_age($age_group, $limit = 4, $random = true, $school = '') {
	$args = [
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => $limit > 0 ? $limit : -1,
		'tax_query' => [
			'relation' => 'AND',
			[
				'taxonomy' => 'testimonial_age_group',
				'field' => 'slug',
				'terms' => $age_group
			]
		]
	];

	// Schul-Filter hinzufügen wenn angegeben
	if (!empty($school)) {
		$args['tax_query'][] = [
			'taxonomy' => 'testimonial_school',
			'field' => 'slug',
			'terms' => $school
		];
	}

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
 *
 * @param int $limit Max. Anzahl
 * @param bool $random Zufällige Auswahl
 * @param string $school Schule/Standort (optional)
 */
function parkourone_get_testimonials_mixed($limit = 4, $random = true, $school = '') {
	$args = [
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => $limit > 0 ? $limit : -1
	];

	// Schul-Filter hinzufügen wenn angegeben
	if (!empty($school)) {
		$args['tax_query'] = [
			[
				'taxonomy' => 'testimonial_school',
				'field' => 'slug',
				'terms' => $school
			]
		];
	}

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
			'age_group' => wp_get_post_terms($post->ID, 'testimonial_age_group', ['fields' => 'slugs']),
			'school' => wp_get_post_terms($post->ID, 'testimonial_school', ['fields' => 'slugs'])
		];
	}

	return $testimonials;
}

/**
 * Holt Testimonials für eine bestimmte Schule
 *
 * @param string $school Schule/Standort
 * @param int $limit Max. Anzahl
 * @param bool $random Zufällige Auswahl
 * @return array Testimonials
 */
function parkourone_get_testimonials_by_school($school, $limit = 4, $random = true) {
	$args = [
		'post_type' => 'testimonial',
		'post_status' => 'publish',
		'posts_per_page' => $limit > 0 ? $limit : -1,
		'tax_query' => [
			[
				'taxonomy' => 'testimonial_school',
				'field' => 'slug',
				'terms' => $school
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
